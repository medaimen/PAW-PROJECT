<?php
// student_home.php - View Attendance, Upload & Delete Justification
session_start();
require_once 'db_connect.php';

// Security Check
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'student') {
    header("Location: login.php"); exit;
}

$pdo = getDatabaseConnection();
$student_id = $_SESSION['related_id'];
$msg = "";
$msg_type = ""; // green or red

// --- HANDLE FILE DELETION ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_justification_id'])) {
    $just_id = $_POST['delete_justification_id'];
    
    try {
        // 1. Get file path to delete from server
        $stmt = $pdo->prepare("SELECT file_path FROM justifications WHERE id = ? AND student_id = ?");
        $stmt->execute([$just_id, $student_id]);
        $file_info = $stmt->fetch();

        if ($file_info) {
            // 2. Delete physical file
            if (file_exists($file_info['file_path'])) {
                unlink($file_info['file_path']);
            }
            // 3. Delete record from DB
            $pdo->prepare("DELETE FROM justifications WHERE id = ?")->execute([$just_id]);
            $msg = "Justification file deleted successfully.";
            $msg_type = "green";
        } else {
             $msg = "Error: Could not find justification to delete.";
             $msg_type = "red";
        }
    } catch (Exception $e) {
        $msg = "Error deleting: " . $e->getMessage();
        $msg_type = "red";
    }
}

// --- HANDLE FILE UPLOAD ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['proof'])) {
    $session_id = $_POST['session_id'];
    
    if (!is_dir('uploads')) mkdir('uploads'); // Create folder if missing

    $allowed = ['jpg', 'jpeg', 'png', 'pdf'];
    $filename = $_FILES['proof']['name'];
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    if (in_array($ext, $allowed)) {
        $new_filename = "uploads/" . time() . "_" . $student_id . "." . $ext;
        if (move_uploaded_file($_FILES['proof']['tmp_name'], $new_filename)) {
            $sql = "INSERT INTO justifications (student_id, session_id, file_path, status) VALUES (?, ?, ?, 'pending')";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$student_id, $session_id, $new_filename]);
            $msg = "Justification uploaded successfully! Pending review.";
            $msg_type = "green";
        } else {
            $msg = "Error saving file.";
            $msg_type = "red";
        }
    } else {
        $msg = "Invalid file type. Only JPG, PNG, and PDF allowed.";
        $msg_type = "red";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>My Student Portal</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f0f2f5; padding: 40px; }
        .container { max-width: 1000px; margin: auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        h1 { margin-top: 0; color: #333; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 15px; border-bottom: 1px solid #eee; text-align: left; }
        th { background: #007bff; color: white; }
        .status-present { color: green; font-weight: bold; background: #d4edda; padding: 5px 10px; border-radius: 4px; }
        .status-absent { color: red; font-weight: bold; background: #f8d7da; padding: 5px 10px; border-radius: 4px; }
        .btn-upload { background: #007bff; color: white; border: none; padding: 8px 12px; cursor: pointer; border-radius: 4px; font-size: 14px;}
        .btn-upload:hover { background: #0056b3; }
        .btn-delete { background: none; border: none; cursor: pointer; color: #dc3545; font-weight: bold; }
        .btn-delete:hover { color: red; }
        .msg { padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .green { background: #d4edda; color: #155724; }
        .red { background: #f8d7da; color: #721c24; }

        /* Mobile Responsiveness */
        @media (max-width: 768px) {
            body { padding: 20px; }
            .container { padding: 20px; max-width: 100%; }
            h1 { font-size: 1.5em; }
            table { font-size: 14px; overflow-x: auto; display: block; white-space: nowrap; }
            th, td { padding: 10px 8px; min-width: 100px; }
            .btn-upload { padding: 6px 10px; font-size: 12px; }
            .btn-delete { font-size: 12px; }
            input[type="file"] { width: 150px; font-size: 12px; }
        }
    </style>
</head>
<body>

<div class="container">
    <div style="display:flex; justify-content:space-between; align-items:center; border-bottom: 2px solid #f0f2f5; padding-bottom: 20px; margin-bottom: 20px;">
        <h1>🎓 My Student Portal</h1>
        <a href="logout.php" style="color:#dc3545; text-decoration:none; font-weight:bold; padding: 10px 20px; border: 2px solid #dc3545; border-radius: 5px;">Logout</a>
    </div>

    <p>Welcome! Here is your attendance record.</p>

    <?php if($msg) echo "<div class='msg $msg_type'>$msg</div>"; ?>

    <table>
        <thead>
            <tr>
                <th>Course</th>
                <th>Date</th>
                <th>Status</th>
                <th>Participation</th>
                <th>Justification</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Fetch attendance history
            $sql = "SELECT s.id as sess_id, s.course_id, s.date, r.status, r.participated, j.id as just_id, j.status as just_status
                    FROM attendance_records r
                    JOIN attendance_sessions s ON r.session_id = s.id
                    LEFT JOIN justifications j ON r.session_id = j.session_id AND r.student_id = j.student_id
                    WHERE r.student_id = ? ORDER BY s.date DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$student_id]);

            if($stmt->rowCount() == 0): ?>
                <tr><td colspan="5" style="text-align:center; padding: 30px; color: #777;">No attendance records found.</td></tr>
            <?php else:
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)):
                    $statusText = strtoupper($row['status']);
                    $statusClass = ($row['status'] == 'present') ? 'status-present' : 'status-absent';
            ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($row['course_id']); ?></strong></td>
                    <td><?php echo date('M d, Y', strtotime($row['date'])); ?> <small style="color:#888;"><?php echo date('H:i', strtotime($row['date'])); ?></small></td>
                    <td><span class="<?php echo $statusClass; ?>"><?php echo $statusText; ?></span></td>
                    <td style="text-align:center;"><?php echo ($row['participated']) ? '⭐ Active' : '<span style="color:#ccc;">-</span>'; ?></td>
                    
                    <td>
                        <?php if ($row['status'] == 'absent'): ?>
                            <?php if ($row['just_id']): ?>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <span style="font-weight:bold; color:<?php echo ($row['just_status']=='accepted'?'green':($row['just_status']=='rejected'?'red':'orange')); ?>">
                                        <?php echo ucfirst($row['just_status']); ?>
                                    </span>
                                    <form method="POST" onsubmit="return confirm('Are you sure you want to delete this file?');">
                                        <input type="hidden" name="delete_justification_id" value="<?php echo $row['just_id']; ?>">
                                        <button type="submit" class="btn-delete" title="Delete Justification">❌</button>
                                    </form>
                                </div>
                            <?php else: ?>
                                <form method="POST" enctype="multipart/form-data" style="display:flex; gap:10px; align-items:center;">
                                    <input type="hidden" name="session_id" value="<?php echo $row['sess_id']; ?>">
                                    <input type="file" name="proof" required style="font-size:12px; width: 200px;">
                                    <button type="submit" class="btn-upload">Upload</button>
                                </form>
                            <?php endif; ?>
                        <?php else: ?>
                            <span style="color:#ccc;">N/A</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>