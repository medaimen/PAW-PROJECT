<?php
// add_student.php - With Level Selection
require_once 'db_connect.php';
session_start();
// Security Check (Admin Only)
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') { header("Location: login.php"); exit; }

$message = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $matricule = $_POST['matricule'];
    $fullname = $_POST['name'];
    $level = $_POST['level']; // NEW
    $group = $_POST['group'];

    $pdo = getDatabaseConnection();
    try {
        $pdo->beginTransaction();

        // 1. Insert Student (With Level)
        $stmt = $pdo->prepare("INSERT INTO students (matricule, fullname, level, group_id) VALUES (?, ?, ?, ?)");
        $stmt->execute([$matricule, $fullname, $level, $group]);
        $sid = $pdo->lastInsertId();

        // 2. Create Login
        $hash = password_hash($matricule, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, password, role, related_id) VALUES (?, ?, 'student', ?)");
        $stmt->execute([$matricule, $hash, $sid]);

        $pdo->commit();
        $message = "Student added to $level - Group $group!";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Student</title>
    <style>body{font-family:sans-serif; padding:20px; background:#f4f4f4;} .card{background:white; padding:30px; border-radius:10px; max-width:400px; margin:auto; box-shadow:0 4px 10px rgba(0,0,0,0.1);}</style>
</head>
<body>
    <div class="card">
        <h2>Add New Student</h2>
        <?php if ($message) echo "<p style='color:green'>$message</p>"; ?>
        <?php if ($error) echo "<p style='color:red'>$error</p>"; ?>

        <form method="POST">
            <label>Matricule:</label><br>
            <input type="text" name="matricule" required style="width:100%; padding:8px; margin-bottom:10px;">
            
            <label>Full Name:</label><br>
            <input type="text" name="name" required style="width:100%; padding:8px; margin-bottom:10px;">
            
            <label>Academic Year (Level):</label><br>
            <select name="level" style="width:100%; padding:8px; margin-bottom:10px;">
                <option value="L1">Licence 1 (L1)</option>
                <option value="L2">Licence 2 (L2)</option>
                <option value="L3">Licence 3 (L3)</option>
                <option value="M1">Master 1 (M1)</option>
                <option value="M2">Master 2 (M2)</option>
            </select>

            <label>Group Number:</label><br>
            <input type="number" name="group" required style="width:100%; padding:8px; margin-bottom:10px;">

            <button type="submit" style="padding:10px; width:100%; background:#007bff; color:white; border:none; cursor:pointer;">Create Student</button>
        </form>
        <br><a href="admin_home.php">Back to Dashboard</a>
    </div>
</body>
</html>