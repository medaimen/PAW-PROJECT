<?php
// manage_groups.php - Level & Group Hub
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php"); exit;
}

$pdo = getDatabaseConnection();

// Handle "Create New Group" -> Redirect to Details
if (isset($_POST['create_group'])) {
    $new_level = $_POST['new_level'];
    $new_id = $_POST['new_group_id'];
    // Redirect to detail page with BOTH level and ID
    header("Location: group_details.php?level=$new_level&group_id=$new_id");
    exit;
}

// Fetch existing groups (Grouped by Level AND ID)
$sql = "SELECT level, group_id, COUNT(*) as count 
        FROM students 
        GROUP BY level, group_id 
        ORDER BY level ASC, group_id ASC";
$groups = $pdo->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Group Hub</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f4f7f6; padding: 40px; }
        .container { max-width: 900px; margin: auto; }
        .card { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); margin-bottom: 20px; }
        h1 { margin-top: 0; color: #333; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 15px; border-bottom: 1px solid #eee; text-align: left; }
        th { background: #007bff; color: white; }
        .btn { padding: 8px 15px; text-decoration: none; border-radius: 5px; font-weight: bold; color: white; display: inline-block;}
        .btn-manage { background: #17a2b8; }
        .btn-create { background: #28a745; border: none; cursor: pointer; padding: 12px 20px; font-size: 16px; }
        select, input { padding: 10px; border: 1px solid #ccc; border-radius: 5px; }
    </style>
</head>
<body>

<div class="container">
    <a href="admin_home.php" style="color:#555; text-decoration:none; font-weight:bold;">← Back to Dashboard</a>
    <h1>📂 Group Hub</h1>

    <div class="card" style="border-left: 5px solid #28a745;">
        <h2>➕ Create / Manage Group</h2>
        <form method="POST" style="display:flex; gap:10px; align-items:center;">
            <label>Level:</label>
            <select name="new_level" required>
                <option value="L1">L1</option>
                <option value="L2">L2</option>
                <option value="L3">L3</option>
                <option value="M1">M1</option>
                <option value="M2">M2</option>
            </select>
            
            <label>Group ID:</label>
            <input type="number" name="new_group_id" required style="width:80px;" placeholder="1">
            
            <button type="submit" name="create_group" class="btn btn-create">Go →</button>
        </form>
    </div>

    <div class="card">
        <h2>Existing Groups</h2>
        <table>
            <thead>
                <tr>
                    <th>Level</th>
                    <th>Group ID</th>
                    <th>Students</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $groups->fetch(PDO::FETCH_ASSOC)): ?>
                <tr>
                    <td><strong><?php echo $row['level']; ?></strong></td>
                    <td>Group <?php echo $row['group_id']; ?></td>
                    <td><?php echo $row['count']; ?> Students</td>
                    <td>
                        <a href="group_details.php?level=<?php echo $row['level']; ?>&group_id=<?php echo $row['group_id']; ?>" class="btn btn-manage">⚙️ Manage</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>