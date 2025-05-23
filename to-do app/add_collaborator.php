<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$user_email = $_SESSION['user'];
$task_id = $_GET['task_id'] ?? null;
$error = '';

// Run only if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $collaborator_email = trim($_POST['email']);
    $permission = $_POST['permission'];

    // Check if user exists
    $stmt = $conn->prepare("SELECT email FROM users WHERE email = ?");
    $stmt->bind_param("s", $collaborator_email);
    $stmt->execute();
    $userExists = $stmt->get_result()->fetch_assoc();

    if (!$userExists) {
        $error = "მომხმარებელი ამ ელ. ფოსტით არ არსებობს.";
    } else {
        // Add collaborator with permission
        $stmt = $conn->prepare("INSERT INTO collaborators (task_id, user_email, status, permission) VALUES (?, ?, 'PENDING', ?)");
        $stmt->bind_param("iss", $task_id, $collaborator_email, $permission);
        $stmt->execute();
    
        // Log activity
        $action = "დაამატა კოლაბორატორი ($collaborator_email) უფლებებით: $permission";
        $stmt = $conn->prepare("INSERT INTO activity_log (task_id, user_email, action) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $task_id, $user_email, $action);
        $stmt->execute();
    
        header("Location: dashboard.php");
        exit;
    }
    
    
}
?>

<!DOCTYPE html>
<html lang="ka">
<head>
    <meta charset="UTF-8">
    <title>კოლაბორატორის დამატება</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h2>კოლაბორატორის დამატება</h2>
        <form method="POST">
            <input type="email" name="email" placeholder="შეიყვანეთ ელ.ფოსტა" required>

            <select name="permission" required>
                <option value="Edit">Edit</option>
                <option value="Remove">Remove</option>
                <option value="View Only">View Only</option>
            </select>

            <button type="submit">დამატება</button>
        </form>

        <a href="dashboard.php">⬅ უკან</a>
        <?php if (!empty($error)) echo "<p class='error'>$error</p>"; ?>
    </div>
</body>
</html>
