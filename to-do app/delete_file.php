<?php
session_start();
require 'db.php';

// Ensure user is logged in
if (!isset($_SESSION['user'])) {
    exit("Unauthorized access");
}

$file_id = $_GET['id'];
$user_email = $_SESSION['user'];

// Sanitize input (file_id) to avoid malicious input
if (!filter_var($file_id, FILTER_VALIDATE_INT)) {
    exit("Invalid file ID");
}

// Prepare SQL to fetch the file info
$stmt = $conn->prepare("SELECT * FROM task_files WHERE id = ?");
$stmt->bind_param("i", $file_id);
$stmt->execute();
$result = $stmt->get_result();

// Check if the file exists in the database
if ($result->num_rows) {
    $file = $result->fetch_assoc();
    
    // If it's not a link, delete the actual file
    if ($file['file_type'] !== 'link') {
        $file_path = $file['file_path'];
        
        // Check if the file exists before trying to delete it
        if (file_exists($file_path)) {
            unlink($file_path); // Delete the file from the server
        } else {
            exit("File not found: " . htmlspecialchars($file_path));
        }
    }

    // Log the file deletion action
    $task_id = $file['task_id'];
    $action = "{$file['file_type']} '{$file['file_name']}' removed";
    $log_stmt = $conn->prepare("INSERT INTO activity_log (task_id, user_email, action) VALUES (?, ?, ?)");
    $log_stmt->bind_param("iss", $task_id, $user_email, $action);
    $log_stmt->execute();

    // Delete the record from the database
    $del_stmt = $conn->prepare("DELETE FROM task_files WHERE id = ?");
    $del_stmt->bind_param("i", $file_id);
    $del_stmt->execute();
} else {
    exit("File not found in the database.");
}

// Redirect back to dashboard
header("Location: dashboard.php");
exit();
?>
