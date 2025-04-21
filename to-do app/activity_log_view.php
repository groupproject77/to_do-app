<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$user_email = $_SESSION['user'];

// მოაქვს მომხმარებლის დავალებების ან მისი მიერ ჩაწერილი ლოგები
$sql = "SELECT a.*, p.title 
        FROM activity_log a
        LEFT JOIN plans p ON a.task_id = p.id
        WHERE a.user_email = ? OR p.user_email = ?
        ORDER BY a.timestamp DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $user_email, $user_email);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="ka">
<head>
    <meta charset="UTF-8">
    <title>ცვლილებების ლოგი</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<h1>📜 ცვლილებების ლოგი</h1>

<a href="dashboard.php" class="btn">⬅️ უკან</a>
<a href="export_activity_log.php" class="btn">📤 გატანა Excel-ში</a>

<table>
    <thead>
        <tr>
            <th>დრო</th>
            <th>ვინ გააკეთა</th>
            <th>ქმედება</th>
        </tr>
    </thead>
    <tbody>
    <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= htmlspecialchars($row['timestamp']) ?></td>
            <td><?= htmlspecialchars($row['user_email']) ?></td>
            <td><?= htmlspecialchars($row['action']) ?></td>
        </tr>
    <?php endwhile; ?>
    </tbody>
</table>
</body>
</html>
