<?php
// manage_justifications.php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'prof') {
    header("Location: login.php"); exit;
}

$pdo = getDatabaseConnection();
$msg = "";

// --- HANDLE ACTIONS ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $just_id = $_POST['just_id'];
    $action = $_POST['action']; // 'accept' or 'reject'
    
    // Get details to link back to attendance record
    $stmt = $pdo->prepare("SELECT * FROM justifications WHERE id = ?");
    $stmt->execute([$just_id]);
    $just = $stmt->fetch();

    if ($just) {
        if ($action == 'accept') {
            $pdo->beginTransaction();
            // 1. Update Justification Status
            $pdo->prepare("UPDATE justifications SET status = 'accepted' WHERE id = ?")->execute([$just_id]);
            
            // 2. Fix Attendance (Mark as Present so stats improve)
            $pdo->prepare("UPDATE attendance_records SET status = 'present' WHERE session_id = ? AND student_id = ?")
                ->execute([$just['session_id'], $just['student_id']]);
            
            $pdo->commit();
            $msg = "Justification Accepted! Attendance updated to Present.";
        } elseif ($action == 'reject') {
            $pdo->prepare("UPDATE justifications SET status = 'rejected' WHERE id = ?")->execute([$just_id]);
            $msg = "Justification Rejected.";
        }
    }
}

// --- FETCH PENDING ---
// We join 3 tables: Justifications, Students, and Sessions
$sql = "SELECT j.id, j.file_path, j.status, s.fullname, s.matricule, sess.course_id, sess.date
        FROM justifications j
        JOIN students s ON j.student_id = s.id
        JOIN attendance_sessions sess ON j.session_id = sess.id
        WHERE j.status = 'pending'
        ORDER BY j.id DESC";
$pending = $pdo->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Review Justifications</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f4f7f6; padding: 30px; }
        .container { max-width: 1000px; margin: auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        h1 { margin-top: 0; color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
        .btn-back { text-decoration: none; color: #555; font-weight: bold; display: inline-block; margin-bottom: 20px; }
        
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 15px; border-bottom: 1px solid #eee; text-align: left; vertical-align: middle; }
        th { background: #f8f9fa; color: #555; }
        
        .btn { border: none; padding: 8px 12px; color: white; cursor: pointer; border-radius: 4px; font-weight: bold; }
        .btn-view { background: #17a2b8; text-decoration: none; display: inline-block; font-size: 14px; }
        .btn-accept { background: #28a745; }
        .btn-reject { background: #dc3545; }
        
        .msg { background: #d4edda; color: #155724; padding: 15px; margin-bottom: 20px; border-radius: 5px; }
    </style>
</head>
<body>

<div class="container">
    <a href="prof_home.php" class="btn-back">← Back to Dashboard</a>
    <h1>📄 Review Justifications</h1>

    <?php if($msg) echo "<div class='msg'>$msg</div>"; ?>

    <?php if($pending->rowCount() == 0): ?>
        <p style="text-align:center; color:#888; padding: 20px;">No pending justifications.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Course / Date</th>
                    <th>Document</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $pending->fetch(PDO::FETCH_ASSOC)): ?>
                <tr>
                    <td>
                        <strong><?php echo htmlspecialchars($row['fullname']); ?></strong><br>
                        <small><?php echo htmlspecialchars($row['matricule']); ?></small>
                    </td>
                    <td>
                        <?php echo htmlspecialchars($row['course_id']); ?><br>
                        <small><?php echo date('M d, Y', strtotime($row['date'])); ?></small>
                    </td>
                    <td>
                        <a href="<?php echo $row['file_path']; ?>" target="_blank" class="btn btn-view">👁️ View File</a>
                    </td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="just_id" value="<?php echo $row['id']; ?>">
                            <button type="submit" name="action" value="accept" class="btn btn-accept">✓ Accept</button>
                            <button type="submit" name="action" value="reject" class="btn btn-reject">✕ Reject</button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

</body>
</html>