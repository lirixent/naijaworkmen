<?php
// ---------------------------------------------------------------
// VERIFY EMAIL SCRIPT (PDO Version - Matches Register Script)
// ---------------------------------------------------------------

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/db.php'; // Your PDO connection file

// Validate token & email
if (!isset($_GET['token']) || !isset($_GET['email'])) {
    exit("Invalid verification link.");
}

$token = $_GET['token'];
$email = $_GET['email'];

// Fetch user by token & email
$stmt = $pdo->prepare("SELECT id, is_verified, token_expiry 
                       FROM users 
                       WHERE email = ? AND verify_token = ? 
                       LIMIT 1");
$stmt->execute([$email, $token]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    exit("Invalid or expired verification token.");
}

// Check if token expired
if (strtotime($user['token_expiry']) < time()) {
    exit("This verification link has expired. Please request a new one.");
}

// Already verified?
if ($user['is_verified'] == 1) {
    echo "
        <h2>Email Already Verified ✔</h2>
        <p>Your email has already been confirmed.</p>
        <a href='../login.html'>Proceed to Login</a>
    ";
    exit;
}

// Mark user as verified
$update = $pdo->prepare("UPDATE users 
                         SET is_verified = 1, verify_token = NULL, token_expiry = NULL 
                         WHERE id = ?");
$update->execute([$user['id']]);

echo "
    <h2>Email Verified Successfully 🎉</h2>
    <p>Your email has been confirmed. You can now log in to your account.</p>
    <a href='../login.html'>Proceed to Login</a>
";
?>
