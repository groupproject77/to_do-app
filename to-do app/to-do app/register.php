<?php
require 'db.php';
session_start();

$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = htmlspecialchars($_POST['email']);
    $password = $_POST['password'];

    
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $error_message = "მომხმარებელი უკვე არსებობს!";
    } else {
      
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);

     
        $stmt = $conn->prepare("INSERT INTO users (email, password) VALUES (?, ?)");
        $stmt->bind_param("ss", $email, $hashed_password);

        if ($stmt->execute()) {
            $_SESSION['email'] = $email; 
            header("Location: dashboard.php");
            exit;
        } else {
            $error_message = "შეცდომა!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ka">
<head>
    <meta charset="UTF-8">
    <title>რეგისტრაცია</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h2>რეგისტრაცია</h2>

        <form action="register.php" method="POST">
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <?php if ($error_message): ?>
            <div class="error"><?php echo $error_message; ?></div>
        <?php endif; ?>
            <button type="submit">რეგისტრაცია</button>
        </form>

        <p>უკვე გაქვთ ანგარიში? <a href="login.php">შესვლა</a></p>
    </div>
    <script src="script.js"></script>
</body>
</html>
