<?php
// install_db.php - UPDATED FOR FINAL ASSIGNMENT
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "student_db";

try {
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname`");
    $pdo->exec("USE `$dbname`");

    // 1. STUDENTS TABLE (Updated with email for linking)
    $pdo->exec("CREATE TABLE IF NOT EXISTS students (
        id INT AUTO_INCREMENT PRIMARY KEY,
        fullname VARCHAR(100) NOT NULL,
        matricule VARCHAR(50) NOT NULL UNIQUE,
        email VARCHAR(100) UNIQUE, 
        group_id INT NOT NULL
    )");

    // 2. SESSIONS TABLE
    $pdo->exec("CREATE TABLE IF NOT EXISTS attendance_sessions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        course_id VARCHAR(50) NOT NULL,
        group_id INT NOT NULL,
        date DATETIME DEFAULT CURRENT_TIMESTAMP,
        opened_by INT NOT NULL,
        status VARCHAR(20) DEFAULT 'open'
    )");

    // 3. RECORDS TABLE (With Participation)
    $pdo->exec("CREATE TABLE IF NOT EXISTS attendance_records (
        id INT AUTO_INCREMENT PRIMARY KEY,
        session_id INT NOT NULL,
        student_id INT NOT NULL,
        status VARCHAR(10) DEFAULT 'present',
        participated TINYINT(1) DEFAULT 0,
        FOREIGN KEY (session_id) REFERENCES attendance_sessions(id) ON DELETE CASCADE,
        FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
    )");

    // 4. USERS TABLE (New! For Login)
    // Roles: 'admin', 'prof', 'student'
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role VARCHAR(20) NOT NULL,
        related_id INT DEFAULT NULL 
    )");
    // related_id will link to the 'students' table ID if the user is a student

    // 5. JUSTIFICATIONS TABLE (New! For Student Uploads)
    $pdo->exec("CREATE TABLE IF NOT EXISTS justifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        session_id INT NOT NULL,
        file_path VARCHAR(255) NOT NULL,
        status VARCHAR(20) DEFAULT 'pending', -- pending, accepted, rejected
        comment TEXT,
        FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
        FOREIGN KEY (session_id) REFERENCES attendance_sessions(id) ON DELETE CASCADE
    )");

    // --- SEED DEFAULT USERS (Password is '123456') ---
    $password = password_hash("123456", PASSWORD_DEFAULT);
    
    // Admin
    $pdo->exec("INSERT IGNORE INTO users (username, password, role) VALUES ('admin', '$password', 'admin')");
    
    // Prof
    $pdo->exec("INSERT IGNORE INTO users (username, password, role) VALUES ('prof', '$password', 'prof')");

    echo "<h2>✔ Database Updated Successfully!</h2>";
    echo "<h3>Default Logins (Password: 123456)</h3>";
    echo "<ul>";
    echo "<li>Admin: <strong>admin</strong></li>";
    echo "<li>Professor: <strong>prof</strong></li>";
    echo "<li>Student: (You must link a student first)</li>";
    echo "</ul>";
    echo "<a href='login.php'>Go to Login</a>";

} catch (PDOException $e) {
    die("DB ERROR: " . $e->getMessage());
}
?>