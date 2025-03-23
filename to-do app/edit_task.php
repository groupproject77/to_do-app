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

// Fetch task details
$sql = "SELECT * FROM plans WHERE id = ? AND user_email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $task_id, $user_email);
$stmt->execute();
$result = $stmt->get_result();
$task = $result->fetch_assoc();

if (!$task) {
    die("Task not found or you do not have permission!");
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = htmlspecialchars($_POST['title']);
    $description = htmlspecialchars($_POST['description']);
    $date = $_POST['date'];
    $status = $_POST['status'];
    $priority = $_POST['priority'];

    $update_sql = "UPDATE plans SET title=?, description=?, date=?, status=?, priority=? WHERE id=? AND user_email=?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("sssssis", $title, $description, $date, $status, $priority, $task_id, $user_email);

    if ($update_stmt->execute()) {
        header("Location: dashboard.php");
        exit;
    } else {
        $error_message = "შეცდომა! სცადეთ თავიდან.";
    }
}

?>

<!DOCTYPE html>
<html lang="ka">
<head>
    <meta charset="UTF-8">
    <title>გეგმის რედაქტირება</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h2>გეგმის რედაქტირება</h2>
        <form action="edit_task.php?id=<?php echo $task_id; ?>" method="POST">
            <input type="text" name="title" value="<?php echo htmlspecialchars($task['title']); ?>" required>
            <textarea name="description" required><?php echo htmlspecialchars($task['description']); ?></textarea>
            <input type="date" name="date" value="<?php echo $task['date']; ?>" required>
            <select name="status">
                <option value="PLANNED" <?php if ($task['status'] == "PLANNED") echo "selected"; ?>>დაგეგმილი</option>
                <option value="DONE" <?php if ($task['status'] == "DONE") echo "selected"; ?>>შესრულებული</option>
                <option value="NOT DONE" <?php if ($task['status'] == "NOT DONE") echo "selected"; ?>>შეუსრულებელი</option>
            </select>
            <select name="priority">
                <option value="low" <?php if ($task['priority'] == "low") echo "selected"; ?>>დაბალი</option>
                <option value="medium" <?php if ($task['priority'] == "medium") echo "selected"; ?>>საშუალო</option>
                <option value="high" <?php if ($task['priority'] == "high") echo "selected"; ?>>მაღალი</option>
                <option value="essential" <?php if ($task['priority'] == "essential") echo "selected"; ?>>აუცილებელი</option>
            </select>
            <?php if (!empty($error_message)): ?>
                <div class="error"><?php echo $error_message; ?></div>
            <?php endif; ?>
            <button type="submit">შენახვა</button>
        </form>
        <a href="dashboard.php">⬅ უკან</a>
    </div>
</body>
</html>
