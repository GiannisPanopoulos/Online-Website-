<?php
include '../config.php';

session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../user/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $search_query = '%' . $_POST['search-query'] . '%'; // Add wildcards for partial matches

    $sql = "SELECT id, title, status, priority FROM tasks WHERE assigned_user_id = ? AND title LIKE ? ORDER BY id ASC";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("is", $user_id, $search_query);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($id, $title, $status, $priority);
        } else {
            echo "No tasks found matching the search query.<br>";
        }
    } else {
        echo "Error preparing query: " . $conn->error . "<br>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Task Results</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <div class="main-content">
        <
