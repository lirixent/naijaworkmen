<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Return only JSON responses
header("Content-Type: application/json; charset=UTF-8");

// Database connection
require_once __DIR__ . "/db.php";

// PHPMailer includes
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Default response
$response = [
    "status" => "error",
    "message" => "Something went wrong."
];

// ---------------------------------------------------------
// REQUIRED FIELDS
// This is your list, nothing removed.
// ---------------------------------------------------------
$required = [
    "full_name",
    "email",
    "password",
    "qualification",
    "course",
    "institution",
    "year_graduated"  // Will be resolved via alternate key
];

// Helper to fetch POST value with multiple possible names
function post_get($keys) {
    foreach ((array)$keys as $k) {
        if (isset($_POST[$k]) && trim($_POST[$k]) !== '') {
            return trim($_POST[$k]);
        }
    }
    return null;
}

// Validate each required field
foreach ($required as $field) {

    // Special rule for year
    if ($field === "year_graduated") {
        $val = post_get(['year_graduated', 'graduation_year']);
    } else {
        $val = post_get([$field]);
    }

    if ($val === null) {
        echo json_encode([
            "status" => "error",
            "message" => "Missing field: $field"
        ]);
        exit;
    }
}

// Collect POST values using your system
$full_name       = post_get(['full_name']);
$email           = post_get(['email']);
$password_hash   = password_hash($_POST['password'], PASSWORD_BCRYPT);
$qualification   = post_get(['qualification']);
$course          = post_get(['course']);
$institution     = post_get(['institution']);
$year_graduated  = post_get(['year_graduated', 'graduation_year']);

$role = "graduate";

// Email validation
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid email address"
    ]);
    exit;
}

// ---------------------------------------------------------
// CHECK IF EMAIL ALREADY BELONGS TO A GRADUATE
// You said users can register for multiple roles.
// ---------------------------------------------------------
$stmt = $pdo->prepare("SELECT id, role FROM users WHERE email = ? LIMIT 1");
$stmt->execute([$email]);
$existing = $stmt->fetch(PDO::FETCH_ASSOC);

// If email exists AND is graduate, reject
if ($existing && $existing['role'] === 'graduate') {
    echo json_encode([
        "status" => "error",
        "message" => "This email has already been used to register as a graduate. Please use a different email to continue your registration."
    ]);
    exit;
}

// ---------------------------------------------------------
// CV UPLOAD BLOCK (preserved as-is, just validated)
// ---------------------------------------------------------
$cvPath = null;

if (!empty($_FILES["cv"]["name"])) {

    $uploadDir = __DIR__ . "/../uploads/graduates/cv/";

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $cvName = uniqid() . "_" . basename($_FILES["cv"]["name"]);
    $cvPath = "uploads/graduates/cv/" . $cvName;

    $dst = __DIR__ . "/../" . $cvPath;

    if (!move_uploaded_file($_FILES["cv"]["tmp_name"], $dst)) {
        echo json_encode([
            "status" => "error",
            "message" => "Failed to upload CV"
        ]);
        exit;
    }
}

// ---------------------------------------------------------
// EMAIL VERIFICATION TOKEN
// ---------------------------------------------------------
$token = bin2hex(random_bytes(32));
$tokenExpiry = date("Y-m-d H:i:s", time() + 86400); // 24 hours

try {
    // Begin DB transaction
    $pdo->beginTransaction();

    // ---------------------------------------------------------
    // INSERT INTO USERS TABLE (unchanged from your structure)
    // ---------------------------------------------------------
    $userStmt = $pdo->prepare("
        INSERT INTO users 
        (full_name, email, phone, password_hash, role, is_verified, verify_token, token_expiry, created_at) 
        VALUES (?, ?, '', ?, ?, 0, ?, ?, NOW())
    ");
    $userStmt->execute([
        $full_name,
        $email,
        $password_hash,
        $role,
        $token,
        $tokenExpiry
    ]);

    $user_id = $pdo->lastInsertId();

    // ---------------------------------------------------------
    // INSERT INTO GRADUATES TABLE
    // ---------------------------------------------------------
    $gradStmt = $pdo->prepare("
        INSERT INTO graduates 
        (user_id, highest_qualification, course, institution, year_graduated, cv, verification_status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())
    ");
    $gradStmt->execute([
        $user_id,
        $qualification,
        $course,
        $institution,
        $year_graduated,
        $cvPath
    ]);

    $pdo->commit();

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode([
        "status" => "error",
        "message" => "Database error: " . $e->getMessage()
    ]);
    exit;
}

// ---------------------------------------------------------
// SEND EMAIL USING PHPMailer (your exact SMTP setup)
// ---------------------------------------------------------
$verifyLink = "https://naijaworkmen.com/php/verify_email.php?email=" . urlencode($email) . "&token=" . $token;

try {
    $mail = new PHPMailer(true);

    $mail->isSMTP();
    $mail->Host       = 'naijaworkmen.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'noreply@naijaworkmen.com';
    $mail->Password   = 'naijaworkmen123@';
    $mail->SMTPSecure = 'ssl';
    $mail->Port       = 465;

    $mail->setFrom('noreply@naijaworkmen.com', 'NaijaWorkmen');
    $mail->addAddress($email, $full_name);

    $mail->isHTML(true);
    $mail->Subject = "Verify Your NaijaWorkMen Account";

    $mail->Body = "
        <div style='font-family: Arial, sans-serif; color:#333'>
            <h2>Welcome to NaijaWorkMen!</h2>
            <p>Dear {$full_name},</p>
            <p>Please verify your email by clicking the link below:</p>

            <p style='margin:20px 0'>
                <a href='{$verifyLink}' 
                   style='background:#007bff; color:#fff; padding:12px 18px; border-radius:5px; text-decoration:none;'>
                    Verify My Email
                </a>
            </p>

            <p>If the button does not work, copy and paste this link:</p>
            <p><small>{$verifyLink}</small></p>
            <p>This link expires in 24 hours.</p>
        </div>
    ";

    $mail->send();

} catch (Exception $e) {
    // DO NOT FAIL REGISTRATION IF EMAIL FAILS
    error_log("Email error: " . $e->getMessage());
}

// ---------------------------------------------------------
// SUCCESS RESPONSE — SPA FORMAT
// ---------------------------------------------------------
echo json_encode([
    "status" => "success",
    "message" => "Registration successful! Please verify your email."
]);
exit;

?>
