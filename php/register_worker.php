<?php
// -----------------------------------------------------
// DEBUGGING MODE (TEMPORARY)
// -----------------------------------------------------
ini_set('display_errors', 1);
error_reporting(E_ALL);

// -----------------------------------------------------
// JSON response header
// -----------------------------------------------------

 header('Content-Type: application/json');

// -----------------------------------------------------
// REQUIRED FILES
// -----------------------------------------------------
require_once 'db.php';

// PHPMailer
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


// -----------------------------------------------------
// ONLY ALLOW POST REQUESTS
// -----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    
     http_response_code(405);
     echo json_encode(['status'=>'error','message'=>'Method not allowed']);

//    echo "INVALID_METHOD";
    exit;
}


// -----------------------------------------------------
// SANITIZATION HELPER
// -----------------------------------------------------
function clean_input($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}


// -----------------------------------------------------
// COLLECT INPUT
// -----------------------------------------------------
$full_name        = clean_input($_POST['full_name'] ?? '');
$phone            = clean_input($_POST['phone'] ?? '');
$email            = clean_input($_POST['email'] ?? '');
$gender           = clean_input($_POST['gender'] ?? '');
$address          = clean_input($_POST['address'] ?? '');
$trade            = clean_input($_POST['trade'] ?? '');
$experience       = intval($_POST['experience'] ?? 0);
$password         = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';


// -----------------------------------------------------
// VALIDATION
// -----------------------------------------------------
if (!$full_name || !$phone || !$email || !$trade || !$password) {

    
     http_response_code(400);
     echo json_encode(['status'=>'error','message'=>'Required fields missing']);

//    echo "MISSING_FIELDS";
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

    
     http_response_code(400);
     echo json_encode(['status'=>'error','message'=>'Invalid email']);

//    echo "INVALID_EMAIL";
    exit;
}


// -----------------------------------------------------
// HASH PASSWORD
// -----------------------------------------------------
$password_hash = password_hash($password, PASSWORD_DEFAULT);


// -----------------------------------------------------
// FILE UPLOAD HANDLER
// -----------------------------------------------------
$uploadDir = __DIR__ . '/../uploads/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

function handleUpload($field, $uploadDir) {
    if (!isset($_FILES[$field]) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) return null;

    $tmp  = $_FILES[$field]['tmp_name'];
    $orig = basename($_FILES[$field]['name']);
    $ext  = strtolower(pathinfo($orig, PATHINFO_EXTENSION));

    $allowed = ['jpg','jpeg','png','pdf'];
    if (!in_array($ext, $allowed)) return null;

    $new = uniqid() . '.' . $ext;
    $dest = $uploadDir . $new;

    if (move_uploaded_file($tmp, $dest)) return $new;
    return null;
}

$id_doc      = handleUpload('id_doc', $uploadDir);
$certificate = handleUpload('certificate', $uploadDir);
$photo       = handleUpload('photo', $uploadDir);


try {

    // -----------------------------------------------------
    // CHECK IF EMAIL ALREADY EXISTS
    // -----------------------------------------------------
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);

    if ($stmt->fetch()) {

        
         http_response_code(409);
         echo json_encode(['status'=>'error','message'=>'Email already registered']);

       // echo "EMAIL_EXISTS";
        exit;
    }


    // -----------------------------------------------------
    // GENERATE VERIFICATION TOKEN
    // -----------------------------------------------------
    $verify_token = bin2hex(random_bytes(16));
    $token_expiry = date('Y-m-d H:i:s', strtotime('+1 day'));


    // -----------------------------------------------------
    // DATABASE TRANSACTION
    // -----------------------------------------------------
    $pdo->beginTransaction();


    // Insert into users table
    $stmt = $pdo->prepare("
        INSERT INTO users 
        (full_name, email, phone, password_hash, role, created_at, is_verified, verify_token, token_expiry) 
        VALUES (?, ?, ?, ?, 'worker', NOW(), 0, ?, ?)
    ");

    $stmt->execute([
        $full_name, 
        $email, 
        $phone, 
        $password_hash, 
        $verify_token, 
        $token_expiry
    ]);

    $user_id = $pdo->lastInsertId();


    // Insert worker details
    $stmt = $pdo->prepare("
        INSERT INTO workers 
        (user_id, gender, address, trade, experience_years, id_doc, certificate, photo, verification_status, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
    ");

    $stmt->execute([
        $user_id, $gender, $address, $trade, $experience,
        $id_doc, $certificate, $photo
    ]);


    $pdo->commit();


    // -----------------------------------------------------
    // BUILD VERIFICATION LINK
    // -----------------------------------------------------
    $verifyLink = "https://naijaworkmen.com/php/verify_email.php?token=$verify_token&email=" . urlencode($email);


    // -----------------------------------------------------
    // SEND EMAIL
    // -----------------------------------------------------
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
    $mail->Subject = 'Verify your email';

    // FIXED BUG: $firstname DOES NOT EXIST
    // CHANGED to $full_name
    $mail->Body = "
        <div style='font-family: Arial, sans-serif; color: #333;'>
            <h2>Welcome to NaijaWorkmen!</h2>

            <p>Dear {$full_name},</p>

            <p>Thank you for signing up. To complete your registration, please verify your email below:</p>

            <p style='margin:20px 0;'>
                <a href='$verifyLink'
                   style='background:#007bff; color:#fff; padding:12px 18px; text-decoration:none; border-radius:5px;'>
                   Verify My Email
                </a>
            </p>

            <p>If the button does not work, copy and paste this link:</p>
            <p><small>$verifyLink</small></p>

            <p>This link is valid for <strong>24 hours</strong>.</p>

            <p>Warm regards,<br><strong>NaijaWorkmen Support Team</strong></p>
        </div>
    ";

    $mail->send();


    // -----------------------------------------------------
    // SUCCESS RESPONSE FOR SPA
    // -----------------------------------------------------

    
     echo json_encode(['status'=>'success','message'=>'Registration successful']);

//    echo "SUCCESS";
  //  exit;


} catch (Exception $e) {

    $pdo->rollBack();
    error_log($e->getMessage());

    // REMOVED HTTP + JSON
     http_response_code(500);
     echo json_encode(['status'=>'error','message'=>'An error occurred']);

//    echo "ERROR_DB";
  //  exit;
}

?>
