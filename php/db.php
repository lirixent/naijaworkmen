<?php
// db.php - PDO connection (use environment vars in production)
$host = "localhost";
$db   = "naijrloq_naijaworkmen";
$user = "naijrloq_lirixent";
$pass = "letJESUSin123*";
$charset = 'utf8mb4';
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];
try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    http_response_code(500);
    echo 'Database connection failed';
    exit;
}
