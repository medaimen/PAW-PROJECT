<?php
// upgrade_db.php
require_once 'db_connect.php';
$pdo = getDatabaseConnection();

try {
    // 1. Add 'level' to students
    $pdo->exec("ALTER TABLE students ADD COLUMN level VARCHAR(10) NOT NULL DEFAULT 'L3' AFTER matricule");
    
    // 2. Add 'level' to sessions
    $pdo->exec("ALTER TABLE attendance_sessions ADD COLUMN level VARCHAR(10) NOT NULL DEFAULT 'L3' AFTER course_id");

    echo "<h1>✅ Database Upgraded!</h1>";
    echo "<p>Added 'level' column to Students and Sessions.</p>";
    echo "<a href='login.php'>Go to Login</a>";

} catch (PDOException $e) {
    echo "Database is likely already upgraded. Error: " . $e->getMessage();
}
?>