<?php 
require 'db.php';
session_start();

$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = htmlspecialchars($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, password FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user'] = $email; // Fix: Set the correct session variable
        header("Location: dashboard.php");
        exit;
    } else {
        $error_message = "არასწორი ელ.ფოსტა ან პაროლი!";
    }
}
?>

<!DOCTYPE html>
<html lang="ka">
<head>
    <meta charset="UTF-8">
    <title>შესვლა</title>
    <link rel="stylesheet" href="styles.css">


</head>
<body>
    <div class="container">
        <h2>შესვლა</h2>
        <form action="login.php" method="POST">
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <?php if ($error_message): ?>
            <div class="error"><?php echo $error_message; ?></div>
            <?php endif; ?>
            <button type="submit">შესვლა</button>
        </form>
        <p>არ გაქვთ ანგარიში? <a href="register.php">რეგისტრაცია</a></p>
    </div>
</body>
</html>
