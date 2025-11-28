<?php
// delete_student.php
require_once 'db_connect.php';

// 1. Check if an ID was passed in the URL (e.g., ?id=5)
if (isset($_GET['id'])) {
    $id = $_GET['id'];

    try {
        $pdo = getDatabaseConnection();
        
        // 2. Prepare the DELETE statement
        // We use :id to prevent SQL injection
        $stmt = $pdo->prepare("DELETE FROM students WHERE id = :id");
        
        // 3. Execute the deletion
        $stmt->execute([':id' => $id]);

        // 4. Redirect back to the main dashboard immediately
        header("Location: TP2.php");
        exit;

    } catch (PDOException $e) {
        // If there is an error (like a database connection issue), show it
        die("Error deleting student: " . $e->getMessage());
    }
} else {
    // If someone tries to open this file without an ID, send them back
    header("Location: TP2.php");
    exit;
}
?>