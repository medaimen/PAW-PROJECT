<?php
// reset_system.php - Wipes all data for a fresh start
require_once 'db_connect.php';
$pdo = getDatabaseConnection();

echo "<h1>🧹 System Cleaning...</h1>";

try {
    // 1. Disable Foreign Key Checks (To allow deleting parent/child rows freely)
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

    // 2. Empty the Tables (TRUNCATE wipes them clean)
    $tables = ['attendance_records', 'attendance_sessions', 'justifications', 'students', 'users'];
    
    foreach ($tables as $table) {
        $pdo->exec("TRUNCATE TABLE $table");
        echo "✔ Table <strong>$table</strong> emptied.<br>";
    }

    // 3. Re-Enable Foreign Keys
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    // 4. Restore Default Admin Account
    // Username: admin | Password: 123456
    $pass = password_hash("123456", PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES ('admin', ?, 'admin')");
    $stmt->execute([$pass]);
    
    echo "<hr>";
    echo "<h2 style='color:green'>✅ Reset Complete!</h2>";
    echo "<p>All students, professors, and sessions are gone.</p>";
    echo "<p>Only the <strong>Admin</strong> account remains.</p>";
    echo "<a href='login.php' style='font-size:18px; font-weight:bold;'>Go to Login</a>";

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>