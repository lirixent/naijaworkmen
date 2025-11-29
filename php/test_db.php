<?php
// test_db.php
require 'db.php'; // include the PDO connection

try {
    // Try a simple query
    $stmt = $pdo->query("SELECT NOW() AS `current_time`");
    $row = $stmt->fetch();
    echo "Database connection successful! Current server time: " . $row['current_time'];
} catch (\PDOException $e) {
    echo "Database connection failed: " . $e->getMessage();
}
?>
