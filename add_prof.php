<?php
// add_prof.php
require_once 'db_connect.php';

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $pdo = getDatabaseConnection();
    // Default password hashing
    $hashed = password_hash($password, PASSWORD_DEFAULT);

    try {
        $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'prof')");
        $stmt->execute([$username, $hashed]);
        $message = "Professor created! ID: " . $pdo->lastInsertId();
    } catch (PDOException $e) {
        $message = "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Professor</title>
    <style>body{font-family:sans-serif; padding:20px;}</style>
</head>
<body>
    <h2>Create Professor Account</h2>
    <?php if ($message) echo "<p style='color:green'>$message</p>"; ?>
    <form method="POST">
        Username: <input type="text" name="username" required><br><br>
        Password: <input type="text" name="password" required><br><br>
        <button type="submit">Create Prof</button>
    </form>
    <a href="admin_home.php">Back</a>
</body>
</html>