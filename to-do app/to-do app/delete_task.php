<?php 
session_start();
require 'db.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

if (!isset($_GET['id'])) {
    die("Invalid request!");
}

$task_id = $_GET['id'];
$user_email = $_SESSION['user'];

$sql = "DELETE FROM plans WHERE id = ? AND user_email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $task_id, $user_email);

if ($stmt->execute()) {
    header("Location: dashboard.php");
    exit;
} else {
    die("Task deletion failed!");
}
?>
