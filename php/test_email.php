<?php

// Enable PHP error reporting temporarily
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

$mail = new PHPMailer(true);

try {
    // Server settings
    $mail->isSMTP();
    $mail->Host       = 'naijaworkmen.com';  // SMTP server from cPanel
    $mail->SMTPAuth   = true;
    $mail->Username   = 'noreply@naijaworkmen.com'; // Full email
    $mail->Password   = 'naijaworkmen123@';       // Email password
    $mail->SMTPSecure = 'ssl';                     // Use SSL because port 465
    $mail->Port       = 465;                       // SMTP port

    // Recipients
    $mail->setFrom('noreply@naijaworkmen.com', 'NaijaWorkmen');
    $mail->addAddress('naijaworkmen@gmail.com', 'Test Recipient'); // Your test email

    // Content
    $mail->isHTML(true);
    $mail->Subject = 'Test Email from NaijaWorkmen';
    $mail->Body    = 'This is a test email sent via PHPMailer using cPanel SMTP.';

    $mail->send();
    echo 'Test email sent successfully!';
} catch (Exception $e) {
    echo "Mailer Error: {$mail->ErrorInfo}";
}
?>
