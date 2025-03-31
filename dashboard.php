<?php
session_start();
require 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$user_email = $_SESSION['user'];

// Status and priority options
$status_options = ['PLANNED', 'DONE', 'NOT DONE'];
$priority_options = ['დაბალი', 'საშუალო', 'მაღალი', 'აუცილებელი'];

// Sorting options
$sort_options = [
    'date ASC' => 'თარიღი ზრდადობით',
    'date DESC' => 'თარიღი კლებადობით',
    'priority ASC' => 'პრიორიტეტი ზრდადობით',
    'priority DESC' => 'პრიორიტეტი კლებადობით',
    'status ASC' => 'სტატუსი ზრდადობით',
    'status DESC' => 'სტატუსი კლებადობით'
];

// Filter parameters
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$priority = $_GET['priority'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$sort = $_GET['sort'] ?? 'date ASC';

// SQL query to fetch tasks (including collaboration tasks)
$sql = "SELECT p.*, c.user_email AS collaborator_email, c.status AS collaborator_status 
        FROM plans p 
        LEFT JOIN collaborators c ON p.id = c.task_id 
        WHERE p.user_email = ? OR c.user_email = ?";
$filters = [];
$params = [$user_email, $user_email];

// Search filter
if (!empty($search)) {
    $sql .= " AND (p.title LIKE ? OR p.description LIKE ?)";
    $filters[] = "%$search%";
    $filters[] = "%$search%";
}

// Date range filter
if (!empty($date_from)) {
    $sql .= " AND p.date >= ?";
    $filters[] = $date_from;
}
if (!empty($date_to)) {
    $sql .= " AND p.date <= ?";
    $filters[] = $date_to;
}

// Status and priority filters
if (!empty($status)) {
    $sql .= " AND p.status = ?";
    $filters[] = $status;
}
if (!empty($priority)) {
    $sql .= " AND p.priority = ?";
    $filters[] = $priority;
}

// Sorting
$sql .= " ORDER BY " . $sort;

// Execute query
$stmt = $conn->prepare($sql);
$stmt->bind_param(str_repeat("s", count($filters) + 2), ...array_merge([$user_email, $user_email], $filters));
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="ka">
<head>
    <meta charset="UTF-8">
    <title>გეგმების მართვა</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<h1>გეგმების სია</h1>

<a href="add_task.php" class="btn">➕ ახალი გეგმა</a>
<a href="collaborator_tasks.php" class="btn">🤝 ჩემი კოლაბორაციები</a>
<a href="logout.php" class="logout">🚪 გასვლა</a>

<!-- Filters Form -->
<form method="get" class="filter-form">
    <input type="text" name="search" placeholder="ძებნა სათაური ან აღწერა" value="<?= htmlspecialchars($search) ?>">
    <select name="status">
        <option value="">ყველა სტატუსი</option>
        <?php foreach ($status_options as $s): ?>
            <option value="<?= $s ?>" <?= ($status == $s) ? 'selected' : '' ?>><?= $s ?></option>
        <?php endforeach; ?>
    </select>
    <select name="priority">
        <option value="">ყველა პრიორიტეტი</option>
        <?php foreach ($priority_options as $p): ?>
            <option value="<?= $p ?>" <?= ($priority == $p) ? 'selected' : '' ?>><?= $p ?></option>
        <?php endforeach; ?>
    </select>
    <input type="date" name="date_from" value="<?= htmlspecialchars($date_from) ?>">
    <input type="date" name="date_to" value="<?= htmlspecialchars($date_to) ?>">
    <select name="sort">
        <?php foreach ($sort_options as $option_value => $option_label): ?>
            <option value="<?= $option_value ?>" <?= ($sort == $option_value) ? 'selected' : '' ?>><?= $option_label ?></option>
        <?php endforeach; ?>
    </select>
    <button type="submit">ფილტრი</button>
</form>

<!-- Tasks Table -->
<table>
    <thead>
        <tr>
            <th>თარიღი</th>
            <th>სათაური</th>
            <th>აღწერა</th>
            <th>სტატუსი</th>
            <th>პრიორიტეტი</th>
            <th>კოლაბორატორი</th>
            <th>ქმედება</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['date']) ?></td>
                <td><?= htmlspecialchars($row['title']) ?></td>
                <td><?= htmlspecialchars($row['description']) ?></td>
                <td><?= htmlspecialchars($row['status']) ?></td>
                <td><?= htmlspecialchars($row['priority']) ?></td>
                <td>
                    <?php if (!empty($row['collaborator_email'])): ?>
                        <?= htmlspecialchars($row['collaborator_email']) ?> (<?= htmlspecialchars($row['collaborator_status']) ?>)
                    <?php else: ?>
                        <i>No collaborators</i>
                    <?php endif; ?>
                </td>
                <td>
                    <a href="edit_task.php?id=<?= $row['id'] ?>">✏️ შეცვლა</a>
                    <a href="delete_task.php?id=<?= $row['id'] ?>" onclick="return confirm('ნამდვილად წაშალო?')">🗑️ წაშლა </a>
                    <a href="add_collaborator.php?task_id=<?= $row['id'] ?>">➕ კოლაბორატორი</a>
                </td>
            </tr>
        <?php endwhile; ?>
    </tbody>
</table>

</body>
</html>
