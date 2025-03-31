<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user']) || !isset($_GET['id'])) {
    header("Location: login.php");
    exit;
}

$collaborator_id = $_GET['id'];

$sql = "UPDATE collaborators SET status = 'ACCEPTED' WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $collaborator_id);

if ($stmt->execute()) {
    header("Location: collaborator_tasks.php");
} else {
    echo "დაფიქსირდა შეცდომა!";
}
?>
