<?php
// login.php - Split Screen Design
session_start();
require_once 'db_connect.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $pdo = getDatabaseConnection();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :u");
    $stmt->execute([':u' => $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['related_id'] = $user['related_id'];

        if ($user['role'] == 'admin') header("Location: admin_home.php");
        elseif ($user['role'] == 'prof') header("Location: prof_home.php");
        elseif ($user['role'] == 'student') header("Location: student_home.php");
        exit;
    } else {
        $error = "Invalid credentials.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Algiers University</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; margin: 0; height: 100vh; display: flex; }
        
        /* LEFT SIDE: IMAGE */
        .image-section {
            flex: 1.5; /* Takes up 60% of screen */
            background: url('images/login_bg.webp') no-repeat center center;
            background-size: cover; /* Covers the area perfectly */
            position: relative;
        }
        
        /* Overlay to make text pop if you want text on image */
        .image-overlay {
            position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0, 51, 102, 0.3); /* Blue tint */
        }

        /* RIGHT SIDE: FORM */
        .form-section {
            flex: 1; /* Takes up 40% of screen */
            display: flex;
            justify-content: center;
            align-items: center;
            background: white;
            padding: 40px;
        }

        .login-card { width: 100%; max-width: 350px; text-align: center; }

        .uni-logo {
            width: 120px; /* Adjust size here */
            margin-bottom: 15px;
            display: block;
            margin-left: auto;
            margin-right: auto;
        }

        h2 { color: #333; margin-bottom: 10px; font-weight: 700; }
        p.subtitle { color: #666; margin-bottom: 30px; }
        
        input { 
            width: 100%; padding: 15px; margin-bottom: 15px; 
            border: 1px solid #ddd; border-radius: 8px; 
            box-sizing: border-box; background: #f9f9f9;
        }
        input:focus { border-color: #007bff; background: white; outline: none; }
        
        button { 
            width: 100%; padding: 15px; background: #007bff; 
            color: white; border: none; border-radius: 8px; 
            cursor: pointer; font-size: 16px; font-weight: bold; 
            transition: 0.3s;
        }
        button:hover { background: #0056b3; }
        
        .error { color: #dc3545; background: #ffe6e6; padding: 10px; border-radius: 5px; margin-bottom: 20px; font-size: 14px; }
        .links { margin-top: 20px; font-size: 14px; }
        .links a { color: #007bff; text-decoration: none; font-weight: bold; }
        
        /* Mobile Responsive */
        @media (max-width: 768px) {
            .image-section { display: none; } /* Hide image on mobile */
            .form-section { flex: 1; }
        }
    </style>
</head>
<body>

    <div class="image-section">
        <div class="image-overlay"></div>
    </div>

    <div class="form-section">
        <div class="login-card">
            <img src="images/Logo_univ.png" alt="Algiers University Logo" class="uni-logo">
            <h2>Algiers University</h2>
            <p class="subtitle">Welcome back! Please login to your account.</p>

            <?php if($error): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST">
                <input type="text" name="username" placeholder="Username or Matricule" required>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit">Login</button>
            </form>

            <div class="links">
                <p>New Student? <a href="signup.php">Create Account</a></p>
            </div>
        </div>
    </div>

</body>
</html>