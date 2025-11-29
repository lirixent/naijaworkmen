<?php
// -----------------------------------------------------
// DEBUGGING MODE
// -----------------------------------------------------
ini_set('display_errors', 1);
error_reporting(E_ALL);

// -----------------------------------------------------
// JSON response header
// -----------------------------------------------------
header("Content-Type: application/json; charset=UTF-8");

// -----------------------------------------------------
// REQUIRED FILES
// -----------------------------------------------------
require_once 'db.php';

// -----------------------------------------------------
// ONLY ALLOW POST REQUESTS
// -----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
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
$email = clean_input($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$role = clean_input($_POST['login_role'] ?? '');

// -----------------------------------------------------
// VALIDATION
// -----------------------------------------------------
if (!$email || !$password || !$role) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Email, password and role are required']);
    exit;
}

// -----------------------------------------------------
// HARD-CODED ADMIN CHECK
// -----------------------------------------------------
if ($email === 'naijaworkmen@gmail.com' && $password === 'admin123') {
    echo json_encode([
        'status' => 'success',
        'role' => 'admin',
        'message' => 'Admin login successful!',
        'full_name' => 'Admin',
        'email' => $email
    ]);
    exit;
}

// -----------------------------------------------------
// FETCH USER FROM DB
// -----------------------------------------------------
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = ? LIMIT 1");
    $stmt->execute([$email, $role]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(['status' => 'error', 'message' => 'Email not registered']);
        exit;
    }

    // -----------------------------------------------------
    // VERIFY PASSWORD
    // -----------------------------------------------------
    if (!password_verify($password, $user['password_hash'])) {
        echo json_encode(['status' => 'error', 'message' => 'Incorrect password']);
        exit;
    }

    // -----------------------------------------------------
    // EMAIL VERIFICATION
    // -----------------------------------------------------
    if ($user['is_verified'] == 0) {
        echo json_encode([
            'status' => 'error',
            'code'   => 'NOT_VERIFIED',
            'message'=> 'Your email is not verified. Please verify to continue.',
            'email' => $email
        ]);
        exit;
    }

    // -----------------------------------------------------
    // BUILD RESPONSE BASED ON ROLE
    // -----------------------------------------------------
    $response = [
        'status' => 'success',
        'role' => $user['role'],
        'message' => 'Login successful!',
        'full_name' => $user['full_name'],
        'email' => $user['email'],
        'phone' => $user['phone']
    ];

    // --------------------- WORKER ---------------------
    if ($user['role'] === 'worker') {
        $stmt = $pdo->prepare("SELECT * FROM workers WHERE user_id = ? LIMIT 1");
        $stmt->execute([$user['id']]);
        $worker = $stmt->fetch(PDO::FETCH_ASSOC);

        $response['photo'] = isset($worker['photo']) && $worker['photo'] !== ""
            ? "uploads/" . $worker['photo']
            : "";

        $response['gender'] = $worker['gender'] ?? '';
        $response['address'] = $worker['address'] ?? '';
        $response['trade'] = $worker['trade'] ?? '';
        $response['experience'] = $worker['experience_years'] ?? 0;
    }

    // --------------------- GRADUATE ---------------------
    if ($user['role'] === 'graduate') {
        $stmt = $pdo->prepare("SELECT * FROM graduates WHERE user_id = ? LIMIT 1");
        $stmt->execute([$user['id']]);
        $grad = $stmt->fetch(PDO::FETCH_ASSOC);

        $response['highest_qualification'] = $grad['highest_qualification'] ?? '';
        $response['course'] = $grad['course'] ?? '';
        $response['institution'] = $grad['institution'] ?? '';
        $response['year_graduated'] = $grad['year_graduated'] ?? '';
        $response['cv_link'] = $grad['cv'] ?? '';
        $response['verification_status'] = $grad['verification_status'] ?? 'pending';
    }

    echo json_encode($response);
    exit;

} catch (Exception $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'An error occurred']);
    exit;
}
?>
