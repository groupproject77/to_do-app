<?php
session_start();
require 'db.php';

// Ensure user is logged in
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$user_email = $_SESSION['user'];
$task_id = isset($_GET['id']) ? intval($_GET['id']) : null;

// If no task ID is provided, redirect
if (!$task_id) {
    header('Location: dashboard.php');
    exit;
}

// Check if the user is either the task owner or a collaborator with "Remove" permission
$sql = "
    SELECT p.*, c.permission 
    FROM plans p
    LEFT JOIN collaborators c 
        ON p.id = c.task_id AND c.user_email = ?
    WHERE p.id = ? AND (p.user_email = ? OR (c.user_email = ? AND c.permission = 'Remove'))
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("siss", $user_email, $task_id, $user_email, $user_email);
$stmt->execute();
$result = $stmt->get_result();

// If no task found or user doesn't have permission
if ($result->num_rows === 0) {
    echo "<p style='color:red; text-align:center; font-family:sans-serif;'>❌ Task not found or unauthorized access.</p>";
    exit;
}

$task = $result->fetch_assoc();
$task_title = $task['title']; // Get task title before deletion

// ✅ Log the deletion BEFORE removing the task
$log_action = "გეგმა '{$task_title}' წაიშალა {$user_email}-ს მიერ";
$log_stmt = $conn->prepare("INSERT INTO activity_log (task_id, user_email, action) VALUES (?, ?, ?)");
$log_stmt->bind_param("iss", $task_id, $user_email, $log_action);
$log_stmt->execute();

// ✅ Delete the task
$sql_delete = "DELETE FROM plans WHERE id = ?";
$stmt_delete = $conn->prepare($sql_delete);
$stmt_delete->bind_param("i", $task_id);
$stmt_delete->execute();

// ✅ Clean up collaborators
$sql_cleanup = "DELETE FROM collaborators WHERE task_id = ?";
$stmt_cleanup = $conn->prepare($sql_cleanup);
$stmt_cleanup->bind_param("i", $task_id);
$stmt_cleanup->execute();

// ✅ Redirect to dashboard
header("Location: dashboard.php");
exit;
?>
