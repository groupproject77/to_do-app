<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$user_email = $_SESSION['user'];

// Fetch activity logs
$sql = "SELECT a.timestamp, a.user_email, a.action, p.title 
        FROM activity_log a
        LEFT JOIN plans p ON a.task_id = p.id
        WHERE a.user_email = ? OR p.user_email = ?
        ORDER BY a.timestamp DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $user_email, $user_email);
$stmt->execute();
$result = $stmt->get_result();

// Set headers for Excel export
header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=activity_log_" . date("Y-m-d") . ".xls");
header("Pragma: no-cache");
header("Expires: 0");

// Output the Excel content
echo "<table border='1'>";
echo "<tr>
        <th>დრო</th>
        <th>ვინ გააკეთა</th>
        <th>ქმედება</th>
        <th>დავალება</th>
      </tr>";

while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['timestamp']) . "</td>";
    echo "<td>" . htmlspecialchars($row['user_email']) . "</td>";
    echo "<td>" . htmlspecialchars($row['action']) . "</td>";
    echo "<td>" . htmlspecialchars($row['title']) . "</td>";
    echo "</tr>";
}

echo "</table>";
exit;
