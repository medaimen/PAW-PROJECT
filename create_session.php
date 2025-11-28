<?php
// create_session.php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'prof') {
    header("Location: login.php"); exit;
}

$current_prof_id = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $course_id = $_POST['course_id'];
    $level = $_POST['level']; // NEW: Get Level
    $group_id = $_POST['group_id'];

    try {
        $pdo = getDatabaseConnection();
        // INSERT LEVEL
        $sql = "INSERT INTO attendance_sessions (course_id, level, group_id, opened_by) VALUES (:c, :l, :g, :p)";
        $stmt = $pdo->prepare($sql);
        
        $stmt->execute([
            ':c' => $course_id,
            ':l' => $level,
            ':g' => $group_id,
            ':p' => $current_prof_id
        ]);

        // Redirect back to dashboard
        header("Location: prof_home.php");
        exit;

    } catch (PDOException $e) {
        die("Error: " . $e->getMessage());
    }
} else {
    header("Location: prof_home.php");
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Sessions</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; padding: 20px; background-color: #f4f7f6; }
        .container { max-width: 700px; margin: auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        h2, h3 { color: #333; margin-top: 0; }
        
        label { display: block; margin-top: 15px; font-weight: bold; color: #555; }
        input { width: 100%; padding: 12px; margin-top: 5px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; }
        button { margin-top: 20px; padding: 12px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; width: 100%; font-size: 16px; font-weight: bold; }
        button:hover { background: #0056b3; }
        
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #c3e6cb; }
        .btn-back { display: inline-block; padding: 10px 15px; background: #6c757d; color: white; text-decoration: none; border-radius: 5px; margin-bottom: 20px; }
        
        ul { padding-left: 0; list-style: none; }
        li { margin-bottom: 10px; border-bottom: 1px solid #eee; padding: 10px; background: #fafafa; border-radius: 4px; }
        a { text-decoration: none; font-weight: 600; font-size: 0.9em; margin-left: 8px; }
    </style>
</head>
<body>

    <div style="max-width: 700px; margin: auto;">
        <a href="prof_home.php" class="btn-back">← Back to Dashboard</a>
    </div>

    <div class="container">
        <h2>Create New Session</h2>

        <?php if ($message): ?>
            <div class="success">
                <?php echo $message; ?><br>
                <strong>Session ID: <?php echo $new_session_id; ?></strong>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <label>Course Name (e.g., JAVA-101):</label>
            <input type="text" name="course_id" required placeholder="Enter Course Code">

            <label>Group ID (e.g., 20):</label>
            <input type="number" name="group_id" required placeholder="Enter Group Number">

            <button type="submit">Start Session</button>
        </form>

        <hr style="margin: 30px 0;">

        <h3>Active Sessions</h3>
        <ul>
        <?php
        $pdo = getDatabaseConnection();
        // Show recent sessions
        // Filter: Only show sessions created by THIS professor
$prof_id = $_SESSION['user_id'];
$stm = $pdo->prepare("SELECT * FROM attendance_sessions WHERE opened_by = ? ORDER BY id DESC LIMIT 10");
$stm->execute([$prof_id]);
        
        while($row = $stm->fetch(PDO::FETCH_ASSOC)) {
            $statusColor = ($row['status'] == 'open') ? 'green' : 'gray';
            echo "<li>";
            echo "<strong>ID {$row['id']}</strong> - {$row['course_id']} (Grp {$row['group_id']}) ";
            echo "<span style='color:$statusColor'>[{$row['status']}]</span>";
            
            echo " <a href='edit_session.php?id={$row['id']}' style='color:orange;'>[Edit Info]</a>";
            echo " <a href='delete_session.php?id={$row['id']}' style='color:red;' onclick='return confirm(\"Delete?\")'>[Delete]</a>";
            
            if($row['status'] == 'open') {
                echo " | <a href='take_attendance.php?session_id={$row['id']}' style='color:#28a745;'>Take Attendance</a>";
                echo " | <a href='close_session.php?id={$row['id']}' style='color:#dc3545;'>Close</a>";
            } else {
                echo " | <a href='take_attendance.php?session_id={$row['id']}' style='color:purple;'>[Review Attendance]</a>";
            }
            echo "</li>";
        }
        ?>
        </ul>
    </div>

</body>
</html>