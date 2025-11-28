<?php
// take_attendance.php
require_once 'db_connect.php';

if (!isset($_GET['session_id'])) die("Error: No Session ID.");
$session_id = $_GET['session_id'];
$pdo = getDatabaseConnection();
$message = "";

// 1. Get Session Details (Includes Level)
$stmt = $pdo->prepare("SELECT * FROM attendance_sessions WHERE id = ?");
$stmt->execute([$session_id]);
$session = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$session) die("Session not found.");

$current_group_id = $session['group_id'];
$current_level = isset($session['level']) ? $session['level'] : 'L3'; // Default to L3 if missing

// 2. Handle Save
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $present_ids = isset($_POST['present']) ? $_POST['present'] : [];
    $active_ids = isset($_POST['active']) ? $_POST['active'] : [];
    
    try {
        $pdo->beginTransaction();
        
        // Delete old records for this session
        $pdo->prepare("DELETE FROM attendance_records WHERE session_id = ?")->execute([$session_id]);

        // Get correct students (Filter by Level AND Group)
        $stmt_students = $pdo->prepare("SELECT id FROM students WHERE group_id = ? AND level = ?");
        $stmt_students->execute([$current_group_id, $current_level]);
        $all_students = $stmt_students->fetchAll(PDO::FETCH_COLUMN);

        $insert = $pdo->prepare("INSERT INTO attendance_records (session_id, student_id, status, participated) VALUES (?, ?, ?, ?)");

        foreach ($all_students as $sid) {
            $is_present = in_array($sid, $present_ids);
            $is_active = in_array($sid, $active_ids);
            $status = $is_present ? 'present' : 'absent';
            $participated = ($is_present && $is_active) ? 1 : 0;
            
            $insert->execute([$session_id, $sid, $status, $participated]);
        }
        
        $pdo->prepare("UPDATE attendance_sessions SET status = 'closed' WHERE id = ?")->execute([$session_id]);
        $pdo->commit();
        $message = "Attendance Saved!";
    } catch (Exception $e) {
        $pdo->rollBack();
        die("Error: " . $e->getMessage());
    }
}

// 3. Fetch Students for Display (Filter by Level AND Group)
$sql = "SELECT s.*, ar.status, ar.participated 
        FROM students s 
        LEFT JOIN attendance_records ar ON s.id = ar.student_id AND ar.session_id = ? 
        WHERE s.group_id = ? AND s.level = ?"; // STRICT FILTER

$stmt = $pdo->prepare($sql);
$stmt->execute([$session_id, $current_group_id, $current_level]);
$students_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Take Attendance</title>
    <style>
        body { font-family: sans-serif; padding: 20px; background: #f9f9f9; }
        .container { max-width: 800px; margin: auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background: #007bff; color: white; }
        .btn-save { padding: 10px 20px; background: #28a745; color: white; border: none; font-size: 16px; cursor: pointer; float: right; margin-top: 15px; }
    </style>
</head>
<body>
<div class="container">
    <a href="prof_home.php">← Back to Dashboard</a>
    
    <h2><?php echo htmlspecialchars($session['course_id']); ?> (<?php echo $current_level; ?> - G<?php echo $current_group_id; ?>)</h2>

    <?php if ($message) echo "<p style='color:green; font-weight:bold;'>$message</p>"; ?>

    <?php if (count($students_list) > 0): ?>
        <form method="POST">
            <table>
                <thead>
                    <tr>
                        <th style="text-align:center; width: 80px;">Present</th>
                        <th style="text-align:center; width: 80px;">Active</th>
                        <th>Matricule</th>
                        <th>Name</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students_list as $stu): ?>
                    <?php 
                        $chk_p = ($stu['status'] == 'present') ? 'checked' : '';
                        $chk_a = ($stu['participated'] == 1) ? 'checked' : '';
                    ?>
                    <tr>
                        <td style="text-align:center;"><input type="checkbox" name="present[]" value="<?php echo $stu['id']; ?>" style="transform: scale(1.5);" <?php echo $chk_p; ?>></td>
                        <td style="text-align:center;"><input type="checkbox" name="active[]" value="<?php echo $stu['id']; ?>" style="transform: scale(1.5);" <?php echo $chk_a; ?>></td>
                        <td><?php echo htmlspecialchars($stu['matricule']); ?></td>
                        <td><?php echo htmlspecialchars($stu['fullname']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <button type="submit" class="btn-save">Update Records</button>
        </form>
    <?php else: ?>
        <div style="padding:20px; background:#fff3cd; border:1px solid #ffeeba; color:#856404;">
            <strong>No Students Found!</strong><br>
            The system looked for students in <strong>Level: <?php echo $current_level; ?></strong> and <strong>Group: <?php echo $current_group_id; ?></strong> but found none.<br>
            Please check if you selected the correct Level/Group when creating the session.
        </div>
    <?php endif; ?>
</div>
</body>
</html>