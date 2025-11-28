<?php
// delete_session.php
require_once 'db_connect.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    try {
        $pdo = getDatabaseConnection();
        
        // Delete the session
        // Note: Because we used ON DELETE CASCADE in our database setup, 
        // this automagically deletes all attendance records for this session too.
        $stmt = $pdo->prepare("DELETE FROM attendance_sessions WHERE id = :id");
        $stmt->execute([':id' => $id]);

        // Redirect back to the session manager
        header("Location: create_session.php");
        exit;

    } catch (PDOException $e) {
        die("Error deleting session: " . $e->getMessage());
    }
} else {
    header("Location: create_session.php");
    exit;
}
?>