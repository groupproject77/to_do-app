<?php 
session_start();
require 'db.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$user_email = $_SESSION['user'];
$error_message = "";

// Default filter values
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : '';
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$priority = isset($_GET['priority']) ? $_GET['priority'] : '';

// Query to get user plans
$sql = "SELECT * FROM plans WHERE user_email = ?";

// Applying filters dynamically
$filters = [];
$params = [$user_email];

if (!empty($search)) {
    $sql .= " AND (title LIKE ? OR description LIKE ?)";
    $filters[] = "%$search%";
    $filters[] = "%$search%";
}

if (!empty($from_date)) {
    $sql .= " AND date >= ?";
    $filters[] = $from_date;
}

if (!empty($to_date)) {
    $sql .= " AND date <= ?";
    $filters[] = $to_date;
}

if (!empty($status)) {
    $sql .= " AND status = ?";
    $filters[] = $status;
}

if (!empty($priority)) {
    $sql .= " AND priority = ?";
    $filters[] = $priority;
}

$stmt = $conn->prepare($sql);
$stmt->bind_param(str_repeat("s", count($filters) + 1), ...array_merge([$user_email], $filters));
$stmt->execute();
$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="ka">
<head>
    <meta charset="UTF-8">
    <title>ჩემი გეგმები</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h2>📋 ჩემი გეგმები</h2>

        <!-- Filter Form -->
        <form method="GET" action="dashboard.php">
            <input type="text" name="search" placeholder="🔍 ძებნა..." value="<?= htmlspecialchars($search) ?>">
            <input type="date" name="from_date" value="<?= htmlspecialchars($from_date) ?>">
            <input type="date" name="to_date" value="<?= htmlspecialchars($to_date) ?>">
            <select name="status">
                <option value=""> სტატუსი</option>
                <option value="PLANNED" <?= $status == 'PLANNED' ? 'selected' : '' ?>>დაგეგმილი</option>
                <option value="DONE" <?= $status == 'DONE' ? 'selected' : '' ?>>შესრულებული</option>
                <option value="NOT DONE" <?= $status == 'NOT DONE' ? 'selected' : '' ?>>შეუსრულებელი</option>
            </select>
            <select name="priority">
                <option value=""> პრიორიტეტი</option>
                <option value="low" <?= $priority == 'low' ? 'selected' : '' ?>>დაბალი</option>
                <option value="medium" <?= $priority == 'medium' ? 'selected' : '' ?>>საშუალო</option>
                <option value="high" <?= $priority == 'high' ? 'selected' : '' ?>>მაღალი</option>
                <option value="essential" <?= $priority == 'essential' ? 'selected' : '' ?>>აუცილებელი</option>
            </select>
            <button type="submit">🔎 გაფილტვრა</button>
        </form>

        <!-- Display Tasks -->
        <?php while ($plan = $result->fetch_assoc()): ?>
            <div class="plan">
                <h3><?= htmlspecialchars($plan['title']) ?></h3>
                <p>📅 თარიღი: <?= htmlspecialchars($plan['date']) ?></p>
                <p>📌 სტატუსი: <?= htmlspecialchars($plan['status']) ?></p>
                <p>⚡ პრიორიტეტი: <?= htmlspecialchars($plan['priority']) ?></p>
                <p>✍ აღწერა: <?= htmlspecialchars($plan['description']) ?></p>
                <a href="edit_task.php?id=<?= $plan['id'] ?>">✏️ შეცვლა</a> | 
                <a href="delete_task.php?id=<?= $plan['id'] ?>" onclick="return confirm('ნამდვილად გსურთ წაშლა?')">❌ წაშლა</a>
            </div>
        <?php endwhile; ?>

        <a href="add_task.php">➕ ახალი გეგმის დამატება</a>
        <a href="logout.php" class="logout">🚪 გასვლა</a>
    </div>
</body>
</html>
