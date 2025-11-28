<?php
// update_student.php
require_once 'db_connect.php';

$message = "";
$error = "";
$student = null;

// 1. Check if ID is present in URL
if (!isset($_GET['id'])) {
    header("Location: list_students.php");
    exit;
}

$id = $_GET['id'];
$pdo = getDatabaseConnection();

// 2. Handle Form Submission (The Update Logic)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $matricule = $_POST['matricule'];
    $fullname = $_POST['fullname'];
    $group_id = $_POST['group_id'];

    if (empty($matricule) || empty($fullname) || empty($group_id)) {
        $error = "All fields are required.";
    } else {
        try {
            $sql = "UPDATE students SET matricule = :m, fullname = :f, group_id = :g WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':m' => $matricule,
                ':f' => $fullname,
                ':g' => $group_id,
                ':id' => $id
            ]);

            $message = "Student updated successfully!";
            // Redirect back to list after 1 second (optional)
            // header("refresh:1;url=list_students.php"); 
        } catch (PDOException $e) {
            $error = "Error updating student: " . $e->getMessage();
        }
    }
}

// 3. Fetch current student data to fill the form
try {
    $stmt = $pdo->prepare("SELECT * FROM students WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        die("Student not found.");
    }
} catch (PDOException $e) {
    die("Error fetching data: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Student</title>
    <style>
        body { font-family: sans-serif; background: #f4f4f4; padding: 20px; display: flex; justify-content: center; }
        .card { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); width: 350px; }
        input { width: 100%; padding: 10px; margin: 5px 0 15px 0; border: 1px solid #ddd; box-sizing: border-box; }
        button { width: 100%; padding: 10px; background: #ffc107; color: black; border: none; cursor: pointer; font-weight: bold; border-radius: 4px; }
        button:hover { background: #e0a800; }
        .msg { padding: 10px; margin-bottom: 10px; border-radius: 4px; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        .back-link { display: block; text-align: center; margin-top: 15px; text-decoration: none; color: #555; }
    </style>
</head>
<body>

    <div class="card">
        <h2>Edit Student</h2>

        <?php if ($message): ?> <div class="msg success"><?php echo $message; ?></div> <?php endif; ?>
        <?php if ($error): ?> <div class="msg error"><?php echo $error; ?></div> <?php endif; ?>

        <form method="POST" action="">
            <label>Matricule (ID):</label>
            <input type="text" name="matricule" value="<?php echo htmlspecialchars($student['matricule']); ?>" required>

            <label>Full Name:</label>
            <input type="text" name="fullname" value="<?php echo htmlspecialchars($student['fullname']); ?>" required>

            <label>Group ID:</label>
            <input type="number" name="group_id" value="<?php echo htmlspecialchars($student['group_id']); ?>" required>

            <button type="submit">Update Student</button>
        </form>

        <a href="list_students.php" class="back-link">Cancel & Go Back</a>
    </div>

</body>
</html>