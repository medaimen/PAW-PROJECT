<?php
require_once 'db_connect.php';

if (isset($_GET['id'])) {
    $session_id = $_GET['id'];

    try {
        $pdo = getDatabaseConnection();
        
        // Update status to 'closed'
        $sql = "UPDATE attendance_sessions SET status = 'closed' WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $session_id]);

        echo "<h2>Session ID $session_id has been closed.</h2>";
        echo "<a href='create_session.php'>Back to Create Session</a>";

    } catch (PDOException $e) {
        die("Error closing session: " . $e->getMessage());
    }
} else {
    echo "No Session ID provided.";
}
?>