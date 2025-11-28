<?php
// group_details.php - Manage Specific Group (Level + ID)
session_start();
require_once 'db_connect.php';

// Security & Validation
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin' || !isset($_GET['group_id']) || !isset($_GET['level'])) {
    header("Location: manage_groups.php"); exit;
}

$group_id = $_GET['group_id'];
$level = $_GET['level']; // Get Level
$pdo = getDatabaseConnection();
$msg = "";

// --- 1. ADD STUDENT ---
if (isset($_POST['add_student'])) {
    $matricule = $_POST['matricule'];
    $fullname = $_POST['fullname'];
    
    try {
        $pdo->beginTransaction();
        // Insert Student
        $stmt = $pdo->prepare("INSERT INTO students (matricule, fullname, level, group_id) VALUES (?, ?, ?, ?)");
        $stmt->execute([$matricule, $fullname, $level, $group_id]);
        $sid = $pdo->lastInsertId();

        // Create Login
        $hash = password_hash($matricule, PASSWORD_DEFAULT);
        $stmt2 = $pdo->prepare("INSERT INTO users (username, password, role, related_id) VALUES (?, ?, 'student', ?)");
        $stmt2->execute([$matricule, $hash, $sid]);

        $pdo->commit();
        $msg = "Student added to $level - Group $group_id!";
    } catch (Exception $e) {
        $pdo->rollBack();
        $msg = "Error: " . $e->getMessage();
    }
}

// --- 2. RENAME GROUP ID ---
if (isset($_POST['update_group_id'])) {
    $new_id = $_POST['new_id'];
    if($new_id != $group_id) {
        $pdo->prepare("UPDATE students SET group_id = ? WHERE group_id = ?")->execute([$new_id, $group_id]);
        $pdo->prepare("UPDATE attendance_sessions SET group_id = ? WHERE group_id = ?")->execute([$new_id, $group_id]);
        header("Location: group_details.php?group_id=$new_id&level=$level&renamed=1"); 
        exit;
    }
}

// --- FETCH STUDENTS ---
$stmt = $pdo->prepare("SELECT * FROM students WHERE group_id = ? AND level = ? ORDER BY fullname ASC");
$stmt->execute([$group_id, $level]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Manage <?php echo "$level - G$group_id"; ?></title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f4f7f6; padding: 20px; }
        .container { max-width: 1200px; margin: auto; display: grid; grid-template-columns: 1fr 2fr; gap: 20px; }
        
        .card { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h2 { margin-top: 0; color: #333; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        
        input { width: 100%; padding: 10px; margin: 5px 0 15px 0; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; }
        label { font-weight: bold; font-size: 0.9em; color: #555; }
        
        button { width: 100%; padding: 10px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; color: white; }
        .btn-add { background: #28a745; }
        .btn-update { background: #ffc107; color: #333; }
        
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; border-bottom: 1px solid #eee; text-align: left; }
        th { background: #f8f9fa; }
        
        .back-link { grid-column: 1 / -1; margin-bottom: 10px; }
    </style>
</head>
<body>

<div class="back-link">
    <a href="manage_groups.php" style="text-decoration:none; font-weight:bold; color:#555;">← Back to Group Hub</a>
</div>

<?php if(isset($_GET['renamed'])) echo "<div style='background:#d4edda; padding:10px; margin-bottom:20px; text-align:center; color:green; font-weight:bold;'>Group Renamed Successfully!</div>"; ?>
<?php if($msg) echo "<div style='background:#d4edda; padding:10px; margin-bottom:20px; text-align:center; color:green; font-weight:bold;'>$msg</div>"; ?>

<div class="container">

    <div class="left-col">
        
        <div class="card" style="border-top: 5px solid #28a745;">
            <h2>➕ Add Student to <?php echo "$level - G$group_id"; ?></h2>
            <form method="POST">
                <label>Matricule (ID):</label>
                <input type="text" name="matricule" required placeholder="e.g. 2024001">
                
                <label>Full Name:</label>
                <input type="text" name="fullname" required placeholder="e.g. Jane Doe">
                
                <button type="submit" name="add_student" class="btn-add">Add Student</button>
            </form>
        </div>

        <div class="card" style="margin-top: 20px; border-top: 5px solid #ffc107;">
            <h2>✏️ Edit Group ID</h2>
            <form method="POST">
                <label>Current ID: <strong><?php echo $group_id; ?></strong></label>
                <input type="number" name="new_id" value="<?php echo $group_id; ?>" required>
                <button type="submit" name="update_group_id" class="btn-update">Update ID</button>
            </form>
        </div>
    </div>

    <div class="right-col">
        <div class="card">
            <h2>🎓 Students in <?php echo "$level - Group $group_id"; ?></h2>
            
            <?php if($stmt->rowCount() == 0): ?>
                <p style="text-align:center; padding:20px; color:#888;">This group is empty. Add students on the left.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Matricule</th>
                            <th>Name</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($s = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($s['matricule']); ?></td>
                            <td><?php echo htmlspecialchars($s['fullname']); ?></td>
                            <td>
                                <a href="update_student.php?id=<?php echo $s['id']; ?>" title="Edit" style="text-decoration:none; font-size:1.2em; margin-right:10px;">✏️</a>
                                
                                <a href="delete_student.php?id=<?php echo $s['id']; ?>" title="Delete" style="text-decoration:none; font-size:1.2em;" onclick="return confirm('Delete student?')">🗑️</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

</div>

</body>
</html>