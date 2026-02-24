<?php
include '../config.php';

session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../user/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle search and filters
$search_query = '';
$filter_status = '';
$filter_priority = '';

$sql_conditions = "WHERE assigned_user_id = ?";

// Search filter
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_query = $_GET['search'];
    $sql_conditions .= " AND title LIKE ?";
}

// Status filter
if (isset($_GET['filter_status']) && !empty($_GET['filter_status'])) {
    $filter_status = $_GET['filter_status'];
    $sql_conditions .= " AND status = ?";
}

// Priority filter
if (isset($_GET['filter_priority']) && !empty($_GET['filter_priority'])) {
    $filter_priority = $_GET['filter_priority'];
    $sql_conditions .= " AND priority = ?";
}

// Prepare SQL query based on search and filters
$sql = "SELECT id, title, status, priority, creation_date FROM tasks " . $sql_conditions . " ORDER BY id ASC";

if ($stmt = $conn->prepare($sql)) {
    if (!empty($search_query) && !empty($filter_status) && !empty($filter_priority)) {
        $search_term = "%" . $search_query . "%";
        $stmt->bind_param("isss", $user_id, $search_term, $filter_status, $filter_priority);
    } elseif (!empty($search_query) && !empty($filter_status)) {
        $search_term = "%" . $search_query . "%";
        $stmt->bind_param("iss", $user_id, $search_term, $filter_status);
    } elseif (!empty($search_query) && !empty($filter_priority)) {
        $search_term = "%" . $search_query . "%";
        $stmt->bind_param("iss", $user_id, $search_term, $filter_priority);
    } elseif (!empty($filter_status) && !empty($filter_priority)) {
        $stmt->bind_param("iss", $user_id, $filter_status, $filter_priority);
    } elseif (!empty($search_query)) {
        $search_term = "%" . $search_query . "%";
        $stmt->bind_param("is", $user_id, $search_term);
    } elseif (!empty($filter_status)) {
        $stmt->bind_param("is", $user_id, $filter_status);
    } elseif (!empty($filter_priority)) {
        $stmt->bind_param("is", $user_id, $filter_priority);
    } else {
        $stmt->bind_param("i", $user_id);
    }

    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id, $title, $status, $priority, $creation_date);
    } else {
        echo "No tasks found.";
    }
} else {
    echo "Error preparing query: " . $conn->error;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task List</title>
    <link rel="stylesheet" href="../style.css">
    <style>
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
            margin-left: 20px;
        }
        .logout-btn {
            background-color: #dc3545;
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
        .task-container {
            margin-bottom: 20px;
        }
        .task-entry {
            margin-bottom: 15px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: #f8f9fa;
        }
        .task-title {
            font-weight: bold;
        }
        .task-details {
            margin-left: 10px;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            width: 50%;
        }
        .filter-icon {
            font-size: 18px;
            background-color: #007bff;
            padding: 5px 10px;
            color: white;
            border-radius: 5px;
            margin-left: 20px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <header class="header-container">
        <div class="header-left">
            <a href="../user/homepage.php" class="homepage-btn">Homepage</a>
        </div>
        <div class="theme-toggle" id="themeToggle">
            <span id="dark-mode">🌙</span>
            <span id="light-mode" style="display: none;">☀️</span>
        </div>
        <div class="header-right">
            <a href="../user/user.php" class="dashboard-btn">Dashboard</a>
            <a href="../user/logout.php" class="logout-btn">Logout</a>
        </div>
    </header>

    <div class="main-content">
        <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
            <div class="success-message" id="successMessage">
                Task created successfully!
            </div>
        <?php endif; ?>

        <div class="task-container" style="display: flex; align-items: center; margin-bottom: 20px;">
            <button id="openModal" style="padding: 10px 20px; background-color: #28a745; color: white; text-decoration: none; border-radius: 5px;">Create Task</button>
            
            <form action="tasklist_main.php" method="GET" style="margin-left: 20px;">
                <input type="text" name="search" placeholder="Search tasks..." value="<?php echo htmlspecialchars($search_query); ?>" style="padding: 5px; border-radius: 5px; border: 1px solid #ccc;">
                <button type="submit" style="padding: 5px 10px; background-color: #007bff; color: white; border: none; border-radius: 5px;">Search</button>
            </form>

            <span id="filterButton" class="filter-icon">⚙️ Filters</span>

            <a href="export_tasks.php" style="padding: 10px 20px; background-color: white; color: red; text-decoration: none; border: 2px solid red; border-radius: 5px; margin-left: 20px;">Extract Data</a>
        </div>

        <div id="taskModal" class="modal">
            <div class="modal-content">
                <h3>Create Task</h3>
                <form action="create_task.php" method="POST">
                    <label for="task-title">Task Title</label>
                    <input type="text" id="task-title" name="task-title" required>
                    
                    <label for="status">Status</label>
                    <select id="status" name="status" required>
                        <option value="To-do">To-do</option>
                        <option value="In-Progress">In-Progress</option>
                        <option value="Settled">Settled</option>
                    </select>

                    <label for="priority">Priority</label>
                    <select id="priority" name="priority" required>
                        <option value="High">High</option>
                        <option value="Medium">Medium</option>
                        <option value="Low">Low</option>
                    </select>

                    <button type="submit" style="padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px;">Create Task</button>
                </form>
            </div>
        </div>

        <div id="filterModal" class="modal">
            <div class="modal-content">
                <h3>Filter Tasks</h3>
                <form action="tasklist_main.php" method="GET">
                    <label for="filter-status">Status</label>
                    <select id="filter-status" name="filter_status">
                        <option value="">All</option>
                        <option value="To-do" <?php echo ($filter_status == 'To-do') ? 'selected' : ''; ?>>To-do</option>
                        <option value="In-Progress" <?php echo ($filter_status == 'In-Progress') ? 'selected' : ''; ?>>In-Progress</option>
                        <option value="Settled" <?php echo ($filter_status == 'Settled') ? 'selected' : ''; ?>>Settled</option>
                    </select>

                    <label for="filter-priority">Priority</label>
                    <select id="filter-priority" name="filter_priority">
                        <option value="">All</option>
                        <option value="High" <?php echo ($filter_priority == 'High') ? 'selected' : ''; ?>>High</option>
                        <option value="Medium" <?php echo ($filter_priority == 'Medium') ? 'selected' : ''; ?>>Medium</option>
                        <option value="Low" <?php echo ($filter_priority == 'Low') ? 'selected' : ''; ?>>Low</option>
                    </select>

                    <button type="submit" style="padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px;">Apply Filters</button>
                </form>
            </div>
        </div>

        <div class="task-container">
            <h3>Task List</h3>
            <?php
            if ($stmt->num_rows > 0) {
                while ($stmt->fetch()) {
                    echo "<div class='task-entry' style='display: flex; align-items: center; justify-content: space-between;'>";
                    echo "<div>";
                    echo "<div class='task-title'>Task ID: " . htmlspecialchars($id) . " - " . htmlspecialchars($title) . "</div>";
                    echo "<div class='task-details'>Status: " . htmlspecialchars($status) . " | Priority: " . htmlspecialchars($priority) . " | Created On: " . htmlspecialchars($creation_date) . "</div>";
                    echo "</div>";
                    echo "<a href='edit_task.php?task_id=" . $id . "' style='padding: 5px 10px; background-color: #28a745; color: white; text-decoration: none; border-radius: 5px;'>Edit</a>";
                    echo "</div>";
                }
            } else {
                echo "<p>No tasks found.</p>";
            }
            ?>
        </div>
    </div>

    <script>
        const openModal = document.getElementById("openModal");
        const taskModal = document.getElementById("taskModal");
        const filterButton = document.getElementById("filterButton");
        const filterModal = document.getElementById("filterModal");

        openModal.addEventListener("click", () => {
            taskModal.style.display = "flex";
        });

        filterButton.addEventListener("click", () => {
            filterModal.style.display = "flex";
        });

        window.onclick = function(event) {
            if (event.target == taskModal) {
                taskModal.style.display = "none";
            }
            if (event.target == filterModal) {
                filterModal.style.display = "none";
            }
        };

        setTimeout(function() {
            const successMessage = document.getElementById('successMessage');
            if (successMessage) {
                successMessage.style.display = 'none';
            }
        }, 5000);

        const themeToggle = document.getElementById("themeToggle");
        const darkModeToggle = document.getElementById('dark-mode');
        const lightModeToggle = document.getElementById('light-mode');
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
<?php
$stmt->close();
$conn->close();
?>
