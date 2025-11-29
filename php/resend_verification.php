<?php
// Enable debugging temporarily (remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

require_once 'db.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Allow only POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

// Read form data from POST
$email = $_POST['email'] ?? '';

// Validate email
if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'A valid email is required']);
    exit;
}

try {
    // Check if user exists
    $stmt = $pdo->prepare("SELECT id, full_name, is_verified FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        echo json_encode(['status' => 'error', 'message' => 'No account found with this email']);
        exit;
    }

    // Already verified?
    if ($user['is_verified'] == 1) {
        echo json_encode(['status' => 'error', 'message' => 'Your email is already verified']);
        exit;
    }

    // Generate new verification token
    $verify_token = bin2hex(random_bytes(16));
    $expiry = date('Y-m-d H:i:s', strtotime('+24 hours'));

    // Update token in DB
    $stmt = $pdo->prepare("UPDATE users SET verify_token = ?, token_expiry = ? WHERE id = ?");
    $stmt->execute([$verify_token, $expiry, $user['id']]);

    // Prepare PHPMailer
    $mail = new PHPMailer(true);

    $mail->isSMTP();
    $mail->Host       = 'naijaworkmen.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'noreply@naijaworkmen.com';
    $mail->Password   = 'naijaworkmen123@';
    $mail->SMTPSecure = 'ssl';
    $mail->Port       = 465;

    $mail->setFrom('noreply@naijaworkmen.com', 'NaijaWorkmen Verification');
    $mail->addAddress($email, $user['full_name']);

    $verificationLink = "https://naijaworkmen.com/php/verify_email.php?token=$verify_token&email=$email";

    $mail->isHTML(true);
    $mail->Subject = 'Resend Email Verification – NaijaWorkmen';

    $mail->Body = "
        <div style='font-family:Arial; font-size:15px; color:#333;'>
            <p>Dear <strong>{$user['full_name']}</strong>,</p>

            <p>You requested a new verification link for your NaijaWorkmen account.</p>

            <p>Please click the button below to verify your email address:</p>

            <p style='margin:20px 0;'>
                <a href='$verificationLink' 
                   style='background:#007bff; color:white; padding:12px 20px; 
                          text-decoration:none; border-radius:5px;'>
                    Verify Email
                </a>
            </p>

            <p>If the button doesn’t work, copy and paste this link in your browser:</p>

            <p style='word-wrap:break-word;'>$verificationLink</p>

            <p>This link will expire in <strong>24 hours</strong>.</p>

            <p>Warm regards,<br>NaijaWorkmen Team</p>
        </div>
    ";

    error_log("Sending verification email to: " . $email);

    $mail->send();

    echo json_encode(['status' => 'success', 'message' => 'A new verification email has been sent!']);

} catch (Exception $e) {
    error_log("Resend Verification Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Unable to resend email at this time. Please try again later.']);
}
