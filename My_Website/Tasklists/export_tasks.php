<?php
include '../config.php';

session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../user/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch the username of the logged-in user
$sql_user = "SELECT username FROM users WHERE id = ?";
$username = '';

if ($stmt_user = $conn->prepare($sql_user)) {
    $stmt_user->bind_param("i", $user_id);
    $stmt_user->execute();
    $stmt_user->bind_result($username);
    $stmt_user->fetch();
    $stmt_user->close();
}

// Fetch the user's tasks
$sql_tasks = "SELECT title, status, priority, creation_date FROM tasks WHERE assigned_user_id = ?";
$tasks = [];

if ($stmt_tasks = $conn->prepare($sql_tasks)) {
    $stmt_tasks->bind_param("i", $user_id);
    $stmt_tasks->execute();
    $stmt_tasks->bind_result($title, $status, $priority, $creation_date);

    while ($stmt_tasks->fetch()) {
        $tasks[] = [
            'title' => $title,
            'status' => $status,
            'priority' => $priority,
            'creation_date' => $creation_date,
        ];
    }
    $stmt_tasks->close();
}

// Set the content-type to XML
header('Content-Type: text/xml');
header('Content-Disposition: attachment; filename="tasks.xml"');

// Start outputting the XML content
$xml = new SimpleXMLElement('<UserTasks/>');
$xml->addChild('username', htmlspecialchars($username));

// Add tasks to the XML
$tasks_xml = $xml->addChild('tasks');

foreach ($tasks as $task) {
    $task_node = $tasks_xml->addChild('task');
    $task_node->addChild('title', htmlspecialchars($task['title']));
    $task_node->addChild('status', htmlspecialchars($task['status']));
    $task_node->addChild('priority', htmlspecialchars($task['priority']));
    $task_node->addChild('creation_date', htmlspecialchars($task['creation_date']));
}

// Print the XML output
echo $xml->asXML();

$conn->close();
?>
