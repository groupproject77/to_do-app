<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$user_email = $_SESSION['user'];

$sql = "SELECT p.*, c.id AS collaborator_id, c.status AS collaborator_status, p.user_email AS sender_email
        FROM collaborators c
        JOIN plans p ON c.task_id = p.id
        WHERE c.user_email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $user_email);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="ka">
<head>
    <meta charset="UTF-8">
    <title>ჩემი კოლაბორატორის გეგმები</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h1>ჩემი კოლაბორატორის გეგმები</h1>
 
    <table>
        <thead>
            <tr>
                <th>თარიღი</th>
                <th>სათაური</th>
                <th>აღწერა</th>
                <th>სტატუსი</th>
                <th>ვინ გამოგზავნა</th>
                <th>ქმედება</th>
            </tr>
        </thead>
        <a href="dashboard.php">⬅ უკან</a>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['date']) ?></td>
                    <td><?= htmlspecialchars($row['title']) ?></td>
                    <td><?= htmlspecialchars($row['description']) ?></td>
                    <td><?= htmlspecialchars($row['collaborator_status']) ?></td>
                    <td><?= htmlspecialchars($row['sender_email']) ?></td>
                    <td>
                        <?php if ($row['collaborator_status'] == 'PENDING'): ?>
                            <a href="accept_collaborator.php?id=<?= $row['collaborator_id'] ?>">✅ დადასტურება</a>
                            <a href="deny_collaborator.php?id=<?= $row['collaborator_id'] ?>" onclick="return confirm('ნამდვილად უარყოფთ ამ გეგმას?')">❌ უარყოფა</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</body>
</html>
