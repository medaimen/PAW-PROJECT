<?php
session_start();
require_once 'db_connect.php';

// Security Check
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php"); exit;
}
$pdo = getDatabaseConnection();

$msg = "";

// --- 1. HANDLE ADD PROFESSOR (New Feature) ---
if (isset($_POST['create_prof'])) {
    $u = $_POST['prof_user'];
    $p = $_POST['prof_pass'];
    
    // Check if exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username=?");
    $stmt->execute([$u]);
    if ($stmt->fetch()) {
        $msg = "Error: Username '$u' already exists.";
    } else {
        $hash = password_hash($p, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'prof')");
        $stmt->execute([$u, $hash]);
        $msg = "Success: Professor '$u' created!";
    }
}

// --- 2. EXPORT LOGIC ---
if (isset($_POST['export_students'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="students_list.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, array('ID', 'Full Name', 'Matricule', 'Group ID'));
    
    $rows = $pdo->query("SELECT id, fullname, matricule, group_id FROM students");
    while ($row = $rows->fetch(PDO::FETCH_ASSOC)) fputcsv($output, $row);
    fclose($output);
    exit;
}

// --- 3. IMPORT LOGIC (Simple CSV) ---
if (isset($_POST['import_students']) && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file']['tmp_name'];
    $handle = fopen($file, "r");
    // Skip header row
    fgetcsv($handle); 
    
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        // Assuming CSV format: Name, Matricule, Group
        $stmt = $pdo->prepare("INSERT IGNORE INTO students (fullname, matricule, group_id) VALUES (?, ?, ?)");
        $stmt->execute([$data[0], $data[1], $data[2]]);
    }
    fclose($handle);
    $msg = "Import Successful!";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Admin Dashboard</title>
    <style>
        body { font-family: sans-serif; background: #343a40; color: #333; margin: 0; }
        .sidebar { width: 250px; background: #212529; color: white; height: 100vh; position: fixed; padding: 20px; box-sizing: border-box; }
        .content { margin-left: 250px; padding: 40px; }
        .card { background: white; padding: 30px; border-radius: 10px; margin-bottom: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        h1 { color: white; margin-top: 0; }
        h2 { border-bottom: 2px solid #007bff; padding-bottom: 10px; margin-top: 0; }
        button { padding: 10px 20px; background: #007bff; color: white; border: none; cursor: pointer; border-radius: 5px; }
        button:hover { background: #0056b3; }
        input[type="file"] { margin-bottom: 10px; }
        
        /* New Styles for Prof Form */
        label { display: block; margin-top: 10px; font-weight: bold; }
        input[type="text"] { width: 100%; padding: 8px; margin-top: 5px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px; }
        .success-msg { background: #d4edda; color: #155724; padding: 15px; margin-bottom: 20px; border-radius: 5px; border: 1px solid #c3e6cb; }

        /* Mobile Responsiveness */
        @media (max-width: 768px) {
            .sidebar { width: 100%; height: auto; position: static; padding: 10px; }
            .content { margin-left: 0; padding: 20px; }
            .card { margin-bottom: 15px; }
            button { width: 100%; margin-bottom: 10px; }
            input[type="file"] { margin-bottom: 15px; }
            .sidebar h2 { margin-top: 0; }
            .sidebar p { margin-bottom: 10px; }
        }
    </style>
</head>
<body>

<div class="sidebar">
    <h2>Admin Panel</h2>
    <p>Logged in as Admin</p>
    <hr>
    <p><a href="admin_home.php" style="color:white; text-decoration:none;">Dashboard</a></p>
    <p><a href="logout.php" style="color:#ff6b6b; text-decoration:none; font-weight:bold;">Logout</a></p>
</div>

<div class="content">
    <h1>System Management</h1>
    
    <?php if($msg) echo "<div class='success-msg'>$msg</div>"; ?>

    <div class="card" style="border-left: 5px solid #28a745;">
        <h2>➕ Add New Professor</h2>
        <p>Create a login account for a professor.</p>
        <form method="POST">
            <label>Username:</label>
            <input type="text" name="prof_user" required placeholder="e.g. dr_smith">
            
            <label>Password:</label>
            <input type="text" name="prof_pass" required placeholder="e.g. 123456">
            
            <button type="submit" name="create_prof" style="margin-top: 15px; background: #28a745;">Create Account</button>
        </form>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
        <div class="card">
            <h2>Export Student List</h2>
            <p>Download list for Excel.</p>
            <form method="POST">
                <button type="submit" name="export_students">⬇️ Download CSV</button>
            </form>
        </div>

        <div class="card">
            <h2>Import Student List</h2>
            <p>Upload CSV (Name, Matricule, Group).</p>
            <form method="POST" enctype="multipart/form-data">
                <input type="file" name="csv_file" required><br>
                <button type="submit" name="import_students" style="background: green;">⬆️ Upload & Import</button>
            </form>
        </div>
    </div>

 <div class="card">
    <h2>Quick Actions</h2>
    
    <a href="manage_groups.php" style="display:inline-block; padding:10px 20px; background:#ffc107; color:#333; text-decoration:none; border-radius:5px; font-weight:bold; margin-right:5px;">📂 Manage Groups</a>
    
    <a href="add_student.php" style="display:inline-block; padding:10px 20px; background:#17a2b8; color:white; text-decoration:none; border-radius:5px;">+ Add Single Student</a>
    <a href="list_students.php" style="display:inline-block; padding:10px 20px; background:#6c757d; color:white; text-decoration:none; border-radius:5px;">View Full List</a>
</div>
</div>

</body>
</html>