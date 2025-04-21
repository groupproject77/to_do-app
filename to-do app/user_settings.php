<?php
session_start();
require 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$user_email = $_SESSION['user'];
$message = "";

// Process password update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if password fields were submitted
    if (isset($_POST['new_password']) && isset($_POST['confirm_password'])) {
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if ($new_password === $confirm_password && !empty($new_password)) {
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
            $stmt->bind_param("ss", $hashed, $user_email);
            if ($stmt->execute()) {
                $message = "🔐 პაროლი წარმატებით განახლდა.";
            } else {
                $message = "⚠️ შეცდომა ბაზასთან კავშირში.";
            }
        } else {
            $message = "⚠️ პაროლები არ ემთხვევა ან ცარიელია.";
        }
    }

    // Check if email field was submitted
    if (isset($_POST['new_email'])) {
        $new_email = $_POST['new_email'] ?? '';

        // Validate the new email
        if (!empty($new_email) && filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
            // Check if the new email already exists
            $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
            $stmt->bind_param("s", $new_email);
            $stmt->execute();
            $stmt->bind_result($count);
            $stmt->fetch();
            
            if ($count > 0) {
                $message = "⚠️ ეს ელფოსტა უკვე გამოიყენება.";
            } else {
                // Update email
                $stmt = $conn->prepare("UPDATE users SET email = ? WHERE email = ?");
                $stmt->bind_param("ss", $new_email, $user_email);
                if ($stmt->execute()) {
                    $_SESSION['user'] = $new_email;  // Update the session with the new email
                    $message = "📧 ელფოსტა წარმატებით განახლდა.";
                } else {
                    $message = "⚠️ შეცდომა ბაზასთან კავშირში.";
                }
            }
        } else {
            $message = "⚠️ გთხოვთ შეიყვანოთ ვალიდური ელფოსტა.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ka">
<head>
    <meta charset="UTF-8">
    <title>მომხმარებლის პარამეტრები</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<a href="dashboard.php" class="btn">⬅️ უკან</a>
<h2>👤 მომხმარებლის პარამეტრები</h2>

<?php if ($message): ?>
    <div class="message"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<div class="container">
    <p><strong>ელფოსტა:</strong> <?= htmlspecialchars($user_email) ?></p>

    <hr>
    <h3>🔐 პაროლის შეცვლა</h3>
    <form method="post">
        <input type="password" name="new_password" placeholder="ახალი პაროლი" required>
        <input type="password" name="confirm_password" placeholder="გაიმეორე პაროლი" required>
        <button type="submit" name="update_password">პაროლის განახლება</button>
    </form>

    <hr>
    <h3>📧 ელფოსტის შეცვლა</h3>
    <form method="post">
        <input type="email" name="new_email" placeholder="ახალი ელფოსტა" required>
        <button type="submit" name="update_email">ელფოსტის განახლება</button>
    </form>
</div>

</body>
</html>
