<?php
include '../config.php';

session_start();

// Get the logged-in user's ID from session
$user_id = $_SESSION['user_id']; // This is dynamically set

// Check if the form is submitted via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $task_title = $_POST['task-title'];
    $status = $_POST['status'];
    $priority = $_POST['priority'];
    $list_id = 1; // Assuming list_id is 1; adjust as per your logic

    // Prepare the SQL query to insert the new task, using the actual user ID
    $sql = "INSERT INTO tasks (title, status, priority, assigned_user_id, list_id, creation_date) 
            VALUES (?, ?, ?, ?, ?, NOW())";
    
    if ($stmt = $conn->prepare($sql)) {
        // Bind the parameters, including the user ID from the session
        $stmt->bind_param("sssii", $task_title, $status, $priority, $user_id, $list_id);

        // Execute the query
        if ($stmt->execute()) {
            // Redirect to tasklist_main.php with success message
            header("Location: tasklist_main.php?success=1");
            exit();
        } else {
            echo "Error executing query: " . $stmt->error;
        }
        $stmt->close();
    } else {
        echo "Error preparing query: " . $conn->error;
    }
}
$conn->close();
?>
