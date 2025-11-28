<?php
// signup.php - Student Self-Registration
require_once 'db_connect.php';

$message = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $matricule = $_POST['matricule']; // Will be Username
    $fullname = $_POST['fullname'];
    $group = $_POST['group'];
    $password = $_POST['password'];

    if (empty($matricule) || empty($fullname) || empty($password)) {
        $error = "All fields are required.";
    } else {
        $pdo = getDatabaseConnection();
        
        try {
            // Start Transaction: We must insert into TWO tables.
            // If one fails, we cancel both.
            $pdo->beginTransaction();

            // 1. Create Student Data Record
            $stmt = $pdo->prepare("INSERT INTO students (matricule, fullname, group_id) VALUES (?, ?, ?)");
            $stmt->execute([$matricule, $fullname, $group]);
            $student_db_id = $pdo->lastInsertId();

            // 2. Create Login User
            $hashed_pass = password_hash($password, PASSWORD_DEFAULT);
            $stmt_user = $pdo->prepare("INSERT INTO users (username, password, role, related_id) VALUES (?, ?, 'student', ?)");
            $stmt_user->execute([$matricule, $hashed_pass, $student_db_id]);

            // Commit Transaction
            $pdo->commit();
            $message = "Account created successfully! You can now login.";

        } catch (PDOException $e) {
            $pdo->rollBack();
            if ($e->getCode() == 23000) {
                $error = "Error: Matricule/Username already exists.";
            } else {
                $error = "System Error: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Sign Up</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f0f2f5; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .card { background: white; padding: 40px; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
        h2 { text-align: center; color: #333; }
        input { width: 100%; padding: 12px; margin: 10px 0; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; }
        button { width: 100%; padding: 12px; background: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; margin-top: 10px; }
        button:hover { background: #218838; }
        .error { color: red; text-align: center; margin-bottom: 10px; }
        .success { color: green; text-align: center; margin-bottom: 10px; font-weight: bold; }
        .login-link { text-align: center; margin-top: 20px; display: block; text-decoration: none; color: #007bff; }
    </style>
</head>
<body>

    <div class="card">
        <h2>🎓 Student Sign Up</h2>
        
        <?php if($error) echo "<div class='error'>$error</div>"; ?>
        <?php if($message) echo "<div class='success'>$message</div>"; ?>

        <?php if(!$message): // Hide form if successful ?>
        <form method="POST">
            <label>Matricule (Username):</label>
            <input type="text" name="matricule" required placeholder="e.g. 202331">

            <label>Full Name:</label>
            <input type="text" name="fullname" required placeholder="e.g. REDA Mahrez">

            <label>Group Number:</label>
            <input type="number" name="group" required placeholder="e.g. 20">

            <label>Password:</label>
            <input type="password" name="password" required placeholder="Create a password">

            <button type="submit">Create Account</button>
        </form>
        <?php endif; ?>

        <a href="login.php" class="login-link">Already have an account? Login</a>
    </div>

</body>
</html>