<?php 
session_start();
require 'db.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$user_email = $_SESSION['user'];
$error_message = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = htmlspecialchars($_POST['title']);
    $description = htmlspecialchars($_POST['description']);
    $date = $_POST['date'];
    $status = $_POST['status'];
    $priority = $_POST['priority'];

    if (!empty($title) && !empty($description) && !empty($date)) {
        $sql = "INSERT INTO plans (user_email, title, description, date, status, priority) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssss", $user_email, $title, $description, $date, $status, $priority);

        if ($stmt->execute()) {
            header("Location: dashboard.php");
            exit;
        } else {
            $error_message = "შეცდომა! სცადეთ თავიდან.";
        }
    } else {
        $error_message = "გთხოვთ შეავსოთ ყველა ველი!";
    }
}

?>

<!DOCTYPE html>
<html lang="ka">
<head>
    <meta charset="UTF-8">
    <title>გეგმის დამატება</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h2>➕ გეგმის დამატება</h2>
        <form action="add_task.php" method="POST">
            <input type="text" name="title" placeholder="სათაური" required>
            <textarea name="description" placeholder="აღწერა" required></textarea>
            <input type="date" name="date" required>
            <select name="status">
                <option value="PLANNED">დაგეგმილი</option>
                <option value="DONE">შესრულებული</option>
                <option value="NOT DONE">შეუსრულებელი</option>
            </select>
            <select name="priority">
                <option value="low">დაბალი</option>
                <option value="medium">საშუალო</option>
                <option value="high">მაღალი</option>
                <option value="essential">აუცილებელი</option>
            </select>
            <?php if (!empty($error_message)): ?>
                <div class="error"><?php echo $error_message; ?></div>
            <?php endif; ?>
            <button type="submit">დამატება</button>
        </form>
        <a href="dashboard.php">⬅ უკან</a>
    </div>
</body>
</html>
