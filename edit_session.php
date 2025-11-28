<?php
// edit_session.php - Modernized UI
require_once 'db_connect.php';

if (!isset($_GET['id'])) header("Location: create_session.php");
$id = $_GET['id'];
$pdo = getDatabaseConnection();
$message = "";
$error = "";

// Handle Update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $course = $_POST['course'];
    $group = $_POST['group'];
    $prof = $_POST['prof'];

    if(empty($course) || empty($group) || empty($prof)) {
        $error = "All fields are required.";
    } else {
        try {
            $sql = "UPDATE attendance_sessions SET course_id=?, group_id=?, opened_by=? WHERE id=?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$course, $group, $prof, $id]);
            $message = "Session info updated successfully!";
        } catch (PDOException $e) {
            $error = "Error updating session: " . $e->getMessage();
        }
    }
}

// Fetch Current Data
try {
    $stmt = $pdo->prepare("SELECT * FROM attendance_sessions WHERE id=?");
    $stmt->execute([$id]);
    $sess = $stmt->fetch(PDO::FETCH_ASSOC);
    if(!$sess) die("Session not found.");
} catch (PDOException $e) {
    die("Error fetching data: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Session Info</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f7f6; padding: 20px; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .card { 
            background: white; 
            padding: 40px; 
            border-radius: 12px; 
            box-shadow: 0 10px 25px rgba(0,0,0,0.05); 
            width: 100%;
            max-width: 450px;
        }
        h2 { margin-top: 0; color: #333; text-align: center; margin-bottom: 25px; }
        
        /* Form Elements */
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: 600; color: #555; }
        input[type="text"], input[type="number"] { 
            width: 100%; 
            padding: 12px; 
            border: 1px solid #ddd; 
            border-radius: 8px; 
            box-sizing: border-box; /* Important for padding to not affect width */
            transition: border-color 0.3s;
            font-size: 16px;
        }
        input:focus { border-color: #007bff; outline: none; }
        
        /* Buttons */
        .btn-update { 
            width: 100%; 
            padding: 12px; 
            background-color: #ffc107; /* Orange/Yellow for Edit action */
            color: #212529; 
            border: none; 
            border-radius: 8px; 
            font-size: 18px; 
            font-weight: bold;
            cursor: pointer; 
            transition: background-color 0.2s;
        }
        .btn-update:hover { background-color: #e0a800; }
        
        /* Messages */
        .msg { padding: 15px; margin-bottom: 20px; border-radius: 8px; text-align: center; }
        .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        
        /* Links */
        .back-link { display: block; text-align: center; margin-top: 25px; text-decoration: none; color: #6c757d; font-weight: 600; }
        .back-link:hover { color: #343a40; text-decoration: underline; }
    </style>
</head>
<body>

    <div class="card">
        <h2>Edit Session #<?php echo $id; ?></h2>

        <?php if ($message): ?> <div class="msg success"><?php echo $message; ?></div> <?php endif; ?>
        <?php if ($error): ?> <div class="msg error"><?php echo $error; ?></div> <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="course">Course Name:</label>
                <input type="text" id="course" name="course" value="<?php echo htmlspecialchars($sess['course_id']); ?>" required>
            </div>

            <div class="form-group">
                <label for="group">Group ID:</label>
                <input type="number" id="group" name="group" value="<?php echo htmlspecialchars($sess['group_id']); ?>" required>
            </div>

            <div class="form-group">
                <label for="prof">Professor ID:</label>
                <input type="number" id="prof" name="prof" value="<?php echo htmlspecialchars($sess['opened_by']); ?>" required>
            </div>

            <button type="submit" class="btn-update">Update Session Info</button>
        </form>

        <a href="create_session.php" class="back-link">← Back to Session Manager</a>
    </div>

</body>
</html>