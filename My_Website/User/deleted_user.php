<?php
include '../config.php'; // Ensure the config file is included

session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); // Redirect to login if the user is not logged in
    exit();
}

$user_id = $_SESSION['user_id'];

// Delete the user from the database
$sql = "DELETE FROM users WHERE id = ?";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $user_id);
    if ($stmt->execute()) {
        // User deleted, destroy the session
        session_destroy();

        // Redirect to the login page after deletion
        header("Location: login.php");
        exit();
    } else {
        echo "Error deleting user: " . $conn->error;
    }
    $stmt->close();
} else {
    echo "Error preparing statement: " . $conn->error;
}

$conn->close();
?>
