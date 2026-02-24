<?php
include '../config.php';

session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../user/login.php");
    exit();
}

// Get the task ID from the URL
if (isset($_GET['task_id'])) {
    $task_id = $_GET['task_id'];
    $user_id = $_SESSION['user_id'];

    // Fetch the task details from the database
    $sql = "SELECT title, status, priority FROM tasks WHERE id = ? AND assigned_user_id = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("ii", $task_id, $user_id);
        $stmt->execute();
        $stmt->bind_result($title, $status, $priority);
        $stmt->fetch();
        $stmt->close();
    } else {
        echo "Error fetching task details: " . $conn->error;
        exit();
    }

    // Update the task if the form is submitted
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update'])) {
        $new_title = $_POST['task-title'];
        $new_status = $_POST['status'];
        $new_priority = $_POST['priority'];

        $sql = "UPDATE tasks SET title = ?, status = ?, priority = ? WHERE id = ? AND assigned_user_id = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("sssii", $new_title, $new_status, $new_priority, $task_id, $user_id);
            if ($stmt->execute()) {
                header("Location: tasklist_main.php?success=2");
                exit();
            } else {
                echo "Error updating task: " . $stmt->error;
            }
            $stmt->close();
        }
    }

    // Delete the task if the delete button is pressed
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete'])) {
        $sql = "DELETE FROM tasks WHERE id = ? AND assigned_user_id = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("ii", $task_id, $user_id);
            if ($stmt->execute()) {
                header("Location: tasklist_main.php?success=3");
                exit();
            } else {
                echo "Error deleting task: " . $stmt->error;
            }
            $stmt->close();
        }
    }
} else {
    echo "No task ID provided!";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Task</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        /* Header layout and button styling */
        .header-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 60px;
            background-color: #f8f9fa;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
            z-index: 100;
        }
        .header-left a, .header-right a {
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }
        .header-right {
            margin-right: 20px;
        }
        .header-right a {
            margin-left: 20px; /* Space between buttons */
        }
        .theme-toggle {
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border-radius: 5px;
            cursor: pointer;
            text-align: center;
        }
        .theme-toggle span {
            font-size: 24px;
        }
        .main-content {
            margin-top: 80px;
            padding-left: 20px;
            padding-right: 20px;
        }
    </style>
</head>
<body>

    <!-- Header: Homepage, My Tasks, and Dark Theme buttons -->
    <header class="header-container">
        <!-- Left: My Tasks button -->
        <div class="header-left">
            <a href="tasklist_main.php" class="my-tasks-btn">My Tasks</a>
        </div>

        <!-- Center: Dark Theme Toggle (Icon Only) -->
        <div class="theme-toggle" id="themeToggle">
            <span id="dark-mode">🌙</span>
            <span id="light-mode" style="display: none;">☀️</span>
        </div>

        <!-- Right: Homepage button -->
        <div class="header-right">
            <a href="../user/homepage.php" class="homepage-btn">Homepage</a>
        </div>
    </header>

    <!-- Main Content: Edit Task Form -->
    <div class="main-content">
        <h2>Edit Task</h2>
        <form method="POST">
            <label for="task-title">Task Title</label>
            <input type="text" id="task-title" name="task-title" value="<?php echo htmlspecialchars($title); ?>" required>

            <label for="status">Status</label>
            <select id="status" name="status" required>
                <option value="To-do" <?php echo ($status == 'To-do') ? 'selected' : ''; ?>>To-do</option>
                <option value="In-Progress" <?php echo ($status == 'In-Progress') ? 'selected' : ''; ?>>In-Progress</option>
                <option value="Settled" <?php echo ($status == 'Settled') ? 'selected' : ''; ?>>Settled</option>
            </select>

            <label for="priority">Priority</label>
            <select id="priority" name="priority" required>
                <option value="High" <?php echo ($priority == 'High') ? 'selected' : ''; ?>>High</option>
                <option value="Medium" <?php echo ($priority == 'Medium') ? 'selected' : ''; ?>>Medium</option>
                <option value="Low" <?php echo ($priority == 'Low') ? 'selected' : ''; ?>>Low</option>
            </select>

            <!-- Save Changes Button -->
            <button type="submit" name="update" style="padding: 10px 20px; background-color: #007bff; color: white; border-radius: 5px;">Save Changes</button>

            <!-- Delete Task Button -->
            <button type="submit" name="delete" style="padding: 10px 20px; background-color: #dc3545; color: white; border-radius: 5px;">Delete Task</button>
        </form>
    </div>

    <script>
        // JavaScript for Dark Theme Toggle
        const darkModeToggle = document.getElementById('dark-mode');
        const lightModeToggle = document.getElementById('light-mode');
        const themeToggle = document.getElementById('themeToggle');
        let isDarkMode = false;

        themeToggle.addEventListener('click', () => {
            if (isDarkMode) {
                document.body.classList.remove('dark-theme');
                darkModeToggle.style.display = 'inline';
                lightModeToggle.style.display = 'none';
                isDarkMode = false;
            } else {
                document.body.classList.add('dark-theme');
                darkModeToggle.style.display = 'none';
                lightModeToggle.style.display = 'inline';
                isDarkMode = true;
            }
        });
    </script>
</body>
</html>
