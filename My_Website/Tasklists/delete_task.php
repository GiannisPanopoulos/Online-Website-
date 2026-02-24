<?php
include '../config.php';

if (isset($_GET['id'])) {
    $task_id = $_GET['id'];

    // Delete task from the database
    $sql = "DELETE FROM tasks WHERE id = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $task_id);
        if ($stmt->execute()) {
            header("Location: tasklist_main.php?success=1"); // Redirect back with success
            exit();
        } else {
            echo "Error deleting task: " . $stmt->error;
        }
        $stmt->close();
    } else {
        echo "Error preparing query: " . $conn->error;
    }
}
$conn->close();
?>
