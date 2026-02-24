<?php
include '../config.php';

session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user details
$stmt = $conn->prepare("SELECT id, first_name, last_name, email, username, password FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($id, $first_name, $last_name, $email, $username, $password);
$stmt->fetch();
$stmt->close();

// Handle username update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_username'])) {
    $new_username = $_POST['new_username'];

    if (!empty($new_username)) {
        $update_stmt = $conn->prepare("UPDATE users SET username = ? WHERE id = ?");
        $update_stmt->bind_param("si", $new_username, $user_id);

        if ($update_stmt->execute()) {
            $username = $new_username;
            echo "<script>alert('Username updated successfully!');</script>";
        } else {
            echo "<script>alert('Failed to update username. Please try again.');</script>";
        }

        $update_stmt->close();
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['old_password']) && isset($_POST['new_password'])) {
    $old_password = $_POST['old_password'];
    $new_password = $_POST['new_password'];

    // Verify old password
    if (password_verify($old_password, $password)) {
        // Update password
        $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);
        $update_password_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $update_password_stmt->bind_param("si", $hashed_new_password, $user_id);

        if ($update_password_stmt->execute()) {
            echo "<script>alert('Password updated successfully!');</script>";
        } else {
            echo "<script>alert('Failed to update password. Please try again.');</script>";
        }

        $update_password_stmt->close();
    } else {
        echo "<script>alert('Incorrect old password. Please try again.');</script>";
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .edit-btn, .save-btn, .change-password-btn {
            background-color: #007bff;
            color: white;
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .delete-btn {
            background-color: #ff0000;
            color: white;
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .delete-btn:hover {
            background-color: #cc0000;
        }
        .edit-btn:hover, .save-btn:hover, .change-password-btn:hover {
            background-color: #0056b3;
        }
        .info-container input {
            padding: 5px;
            margin-right: 10px;
            font-size: 16px;
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
            width: 30%;
        }
    </style>
</head>
<body>
    <header>
        <a href="homepage.php" class="logo">MySite</a>
        <button id="theme-toggle" onclick="toggleTheme()">🌙</button>
        <nav>
            <a href="logout.php" class="nav-link">Logout</a>
            <!-- Tasklist Redirect -->
            <a href="../tasklists/tasklist_main.php" class="nav-link">Tasklists</a>
        </nav>
    </header>

    <div class="signup-container">
        <h2 id="greeting">Welcome, <?php echo htmlspecialchars($username); ?>!</h2>
        <h2>Users Information</h2>
        <div class="info-container">
            <!-- User Information Display -->
            <div class="info-item">
                <label for="id">ID:</label>
                <span><?php echo htmlspecialchars($id); ?></span>
            </div>
            <div class="info-item">
                <label for="first_name">First Name:</label>
                <span><?php echo htmlspecialchars($first_name); ?></span>
            </div>
            <div class="info-item">
                <label for="last_name">Last Name:</label>
                <span><?php echo htmlspecialchars($last_name); ?></span>
            </div>
            <div class="info-item">
                <label for="email">Email:</label>
                <span><?php echo htmlspecialchars($email); ?></span>
            </div>

            <!-- Editable Username Field -->
            <div class="info-item">
                <label for="username">Username:</label>
                <form action="" method="POST" style="display: inline;">
                    <input type="text" name="new_username" id="new_username" value="<?php echo htmlspecialchars($username); ?>" readonly>
                    <button type="button" class="edit-btn" onclick="editUsername()">Edit</button>
                    <button type="submit" class="save-btn" id="saveBtn" style="display: none;">Save</button>
                </form>
            </div>

            <!-- Password Field (Hidden) and Change Password Button -->
            <div class="info-item">
                <label for="password">Password:</label>
                <input type="password" value="********" readonly>
                <button class="change-password-btn" onclick="openPasswordModal()">Change Password</button>
            </div>

            <!-- Delete Account Button -->
            <div class="info-item">
                <button class="delete-btn" onclick="confirmDelete()">Delete Account</button>
            </div>
        </div>
    </div>

    <!-- Password Change Modal -->
    <div id="passwordModal" class="modal">
        <div class="modal-content">
            <h3>Change Password</h3>
            <form action="" method="POST">
                <label for="old_password">Old Password</label>
                <input type="password" name="old_password" required>
                
                <label for="new_password">New Password</label>
                <input type="password" name="new_password" required>
                
                <button type="submit" class="change-password-btn">Update Password</button>
                <button type="button" onclick="closePasswordModal()">Cancel</button>
            </form>
        </div>
    </div>

    <script>
        function editUsername() {
            document.getElementById('new_username').removeAttribute('readonly');
            document.getElementById('new_username').focus();
            document.getElementById('saveBtn').style.display = 'inline';
        }

        function confirmDelete() {
            if (confirm("Are you sure you want to delete your account? This action cannot be undone.")) {
                window.location.href = 'deleted_user.php';
            }
        }

        function openPasswordModal() {
            document.getElementById('passwordModal').style.display = 'flex';
        }

        function closePasswordModal() {
            document.getElementById('passwordModal').style.display = 'none';
        }
    </script>
</body>
</html>
