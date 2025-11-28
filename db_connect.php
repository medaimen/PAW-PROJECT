<?php
require_once 'config.php';

function getDatabaseConnection() {
    try {
       $dsn = "mysql:host=localhost;dbname=student_db;charset=utf8";
        $pdo = new PDO($dsn, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        file_put_contents('db_errors.log', date('Y-m-d H:i:s') . " - Connection failed: " . $e->getMessage() . PHP_EOL, FILE_APPEND);
        die("Connection failed. Please try again later.");
    }
}
?>
