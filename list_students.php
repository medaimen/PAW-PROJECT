<?php
// list_students.php - Role-Aware Navigation
session_start();
require_once 'db_connect.php';

// 1. Security Check
if (!isset($_SESSION['role'])) {
    header("Location: login.php"); exit;
}

// 2. Determine where the "Back" button goes based on Role
$back_link = "login.php"; // Default fallback
$role = $_SESSION['role'];

if ($role == 'admin') {
    $back_link = "admin_home.php";
} elseif ($role == 'prof') {
    $back_link = "prof_home.php";
}

// 3. Fetch Students
try {
    $pdo = getDatabaseConnection();
    $stmt = $pdo->query("SELECT * FROM students ORDER BY group_id ASC, fullname ASC");
} catch (PDOException $e) {
    die("Error fetching students: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Student List</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; padding: 20px; background-color: #f4f7f6; }
        .container { max-width: 1000px; margin: auto; background: white; padding: 30px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); border-radius: 10px; }
        
        h2 { color: #333; margin-top: 0; border-bottom: 2px solid #007bff; padding-bottom: 10px; display: inline-block; }
        
        /* Table Styles */
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border-bottom: 1px solid #eee; padding: 12px; text-align: left; }
        th { background-color: #007bff; color: white; }
        tr:hover { background-color: #f9f9f9; }

        /* Button Styles */
        .btn { padding: 8px 15px; text-decoration: none; color: white; border-radius: 5px; font-size: 14px; margin-right: 5px; font-weight: bold; }
        .btn-add { background-color: #28a745; float: right; }
        .btn-edit { background-color: #ffc107; color: black; }
        .btn-delete { background-color: #dc3545; }
        
        /* Dynamic Back Button */
        .btn-back { background-color: #6c757d; float: left; margin-right: 20px; }
        .btn-back:hover { background-color: #5a6268; }
        
        .header-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    </style>
</head>
<body>

<div class="container">
    
    <div class="header-row">
        <a href="<?php echo $back_link; ?>" class="btn btn-back">← Back to Dashboard</a>
        
        <?php if($role == 'admin'): ?>
            <a href="add_student.php" class="btn btn-add">+ Add New Student</a>
        <?php endif; ?>
    </div>

    <h2>Registered Students List</h2>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Matricule</th>
                <th>Full Name</th>
                <th>Group</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['id']); ?></td>
                <td><strong><?php echo htmlspecialchars($row['matricule']); ?></strong></td>
                <td><?php echo htmlspecialchars($row['fullname']); ?></td>
                <td><span style="background:#eee; padding:2px 6px; border-radius:4px;">Grp <?php echo htmlspecialchars($row['group_id']); ?></span></td>
                <td>
                    <a href="update_student.php?id=<?php echo $row['id']; ?>" class="btn btn-edit">Edit</a>
                    
                    <a href="delete_student.php?id=<?php echo $row['id']; ?>" 
                       class="btn btn-delete" 
                       onclick="return confirm('Are you sure you want to delete this student?');">Delete</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

</body>
</html>