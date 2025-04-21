<?php 
session_start();
require 'db.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$user_email = $_SESSION['user'];
$task_id = isset($_GET['id']) ? $_GET['id'] : null;

if ($task_id === null) {
    header('Location: dashboard.php');
    exit;
}

// Get original task before editing
$sql = "SELECT * FROM plans WHERE id = ? AND user_email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $task_id, $user_email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo "Task not found or unauthorized access.";
    exit;
}

$task = $result->fetch_assoc();
$error_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = htmlspecialchars($_POST['title']);
    $description = htmlspecialchars($_POST['description']);
    $date = $_POST['date'];
    $status = $_POST['status'];
    $priority = $_POST['priority'];
    $is_recurring = isset($_POST['is_recurring']) ? true : false;

    if ($is_recurring) {
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];

        if (!empty($start_date) && !empty($end_date)) {
            $start = new DateTime($start_date);
            $end = new DateTime($end_date);

            if ($start > $end) {
                $error_message = "Start date must be earlier than or equal to the end date.";
            } else {
                // Delete old task
                $sql_delete = "DELETE FROM plans WHERE id = ?";
                $stmt_delete = $conn->prepare($sql_delete);
                $stmt_delete->bind_param("i", $task_id);
                $stmt_delete->execute();

                // Log deletion
                $log_action = "წაშალა ძველი გეგმა '{$task['title']}' და შეცვალა განმეორებადით (საწყისი: $start_date, დასასრული: $end_date)";
                $log_stmt = $conn->prepare("INSERT INTO activity_log (task_id, user_email, action) VALUES (?, ?, ?)");
                $log_stmt->bind_param("iss", $task_id, $user_email, $log_action);
                $log_stmt->execute();

                // Insert new recurring tasks
                while ($start <= $end) {
                    $rec_date = $start->format('Y-m-d');
                    $sql_insert = "INSERT INTO plans (user_email, title, description, date, status, priority, start_date, end_date, is_recurring)
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt_insert = $conn->prepare($sql_insert);
                    $stmt_insert->bind_param("sssssssss", $user_email, $title, $description, $rec_date, $status, $priority, $start_date, $end_date, $is_recurring);
                    $stmt_insert->execute();

                    $new_task_id = $stmt_insert->insert_id;

                    // Log new task creation
                    $log_action = "დაამატა განმეორებადი განახლებული გეგმა '{$title}' - თარიღი: $rec_date";
                    $log_stmt = $conn->prepare("INSERT INTO activity_log (task_id, user_email, action) VALUES (?, ?, ?)");
                    $log_stmt->bind_param("iss", $new_task_id, $user_email, $log_action);
                    $log_stmt->execute();

                    $start->modify('+1 day');
                }

                header("Location: dashboard.php");
                exit;
            }
        } else {
            $error_message = "Please provide start and end dates for recurring tasks.";
        }
    } else {
        $sql_update = "UPDATE plans SET title = ?, description = ?, date = ?, status = ?, priority = ?, is_recurring = 0, start_date = NULL, end_date = NULL WHERE id = ? AND user_email = ?";
        $stmt = $conn->prepare($sql_update);
        $stmt->bind_param("sssssis", $title, $description, $date, $status, $priority, $task_id, $user_email);

        if ($stmt->execute()) {
            $log_action = "განაახლა გეგმა '{$title}' (თარიღი: $date)";
            $log_stmt = $conn->prepare("INSERT INTO activity_log (task_id, user_email, action) VALUES (?, ?, ?)");
            $log_stmt->bind_param("iss", $task_id, $user_email, $log_action);
            $log_stmt->execute();

            // FILE upload
            // FILE
            if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
                $file_tmp = $_FILES['attachment']['tmp_name'];
                $file_name = basename($_FILES['attachment']['name']);
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                $file_type = in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif']) ? 'image' : ($file_ext === 'pdf' ? 'pdf' : null);

                if ($file_type) {
                    $upload_dir = 'uploads/';
                    if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
                    $file_path = $upload_dir . time() . '_' . $file_name;

                    if (move_uploaded_file($file_tmp, $file_path)) {
                        $stmt = $conn->prepare("INSERT INTO task_files (task_id, file_name, file_path, file_type, uploaded_by) VALUES (?, ?, ?, ?, ?)");
                        $stmt->bind_param("issss", $task_id, $file_name, $file_path, $file_type, $user_email);
                        $stmt->execute();

                        $action = "$file_type file '$file_name' uploaded";
                        $log_stmt = $conn->prepare("INSERT INTO activity_log (task_id, user_email, action) VALUES (?, ?, ?)");
                        $log_stmt->bind_param("iss", $task_id, $user_email, $action);
                        $log_stmt->execute();
                    }
                }
            }

            // LINK upload
            if (!empty($_POST['attachment_link'])) {
                $link = htmlspecialchars($_POST['attachment_link']);
                $file_type = 'link';
                $file_name = 'Link';

                $stmt = $conn->prepare("INSERT INTO task_files (task_id, file_name, file_path, file_type, uploaded_by) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("issss", $task_id, $file_name, $link, $file_type, $user_email);
                $stmt->execute();

                $action = "Link attached: $link";
                $log_stmt = $conn->prepare("INSERT INTO activity_log (task_id, user_email, action) VALUES (?, ?, ?)");
                $log_stmt->bind_param("iss", $task_id, $user_email, $action);
                $log_stmt->execute();
            }

            header("Location: dashboard.php");
            exit;
        } else {
            $error_message = "Error! Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ka">
<head>
    <meta charset="UTF-8">
    <title>გეგმის შეცვლა</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h2>გეგმის შეცვლა</h2>

        <?php if (!empty($error_message)): ?>
            <p style="color:red;"><?= htmlspecialchars($error_message) ?></p>
        <?php endif; ?>

        <form action="edit_task.php?id=<?= $task['id'] ?>" method="POST" enctype="multipart/form-data">
            <input type="text" name="title" value="<?= htmlspecialchars($task['title']) ?>" required placeholder="სათაური">
            <textarea name="description" required placeholder="აღწერა"><?= htmlspecialchars($task['description']) ?></textarea>
            <input type="date" name="date" value="<?= htmlspecialchars($task['date']) ?>" required>

            <select name="status">
                <option value="PLANNED" <?= ($task['status'] == 'PLANNED') ? 'selected' : '' ?>>დაგეგმილი</option>
                <option value="DONE" <?= ($task['status'] == 'DONE') ? 'selected' : '' ?>>შესრულებული</option>
                <option value="NOT DONE" <?= ($task['status'] == 'NOT DONE') ? 'selected' : '' ?>>შეუსრულებელი</option>
            </select>

            <select name="priority">
                <option value="low" <?= ($task['priority'] == 'low') ? 'selected' : '' ?>>დაბალი</option>
                <option value="medium" <?= ($task['priority'] == 'medium') ? 'selected' : '' ?>>საშუალო</option>
                <option value="high" <?= ($task['priority'] == 'high') ? 'selected' : '' ?>>მაღალი</option>
                <option value="essential" <?= ($task['priority'] == 'essential') ? 'selected' : '' ?>>აუცილებელი</option>
            </select>

            <label>ფაილის დამატება (PDF, სურათი, ან ბმული):</label>
            <input type="file" name="attachment" accept=".pdf,image/*"><br>
            <label>ან ბმული:</label>
            <input type="url" name="attachment_link" placeholder="https://example.com">

            <label><input type="checkbox" name="is_recurring" id="recurring_checkbox" <?= ($task['is_recurring'] && $task['start_date'] && $task['end_date']) ? 'checked' : '' ?>> განმეორებადი ყოველდღიურად</label>

            <div id="recurring_fields" style="display: <?= ($task['is_recurring'] && $task['start_date'] && $task['end_date']) ? 'block' : 'none' ?>;">
                <input type="date" name="start_date" value="<?= htmlspecialchars($task['start_date']) ?>">
                <input type="date" name="end_date" value="<?= htmlspecialchars($task['end_date']) ?>">
            </div>

            <button type="submit">შეცვლა</button>
        </form>
        <a href="dashboard.php">⬅ უკან</a>
    </div>

    <script>
        document.getElementById('recurring_checkbox').addEventListener('change', function() {
            let recurringFields = document.getElementById('recurring_fields');
            let startDateInput = recurringFields.querySelector('input[name="start_date"]');
            let endDateInput = recurringFields.querySelector('input[name="end_date"]');
            
            recurringFields.style.display = this.checked ? 'block' : 'none';
            startDateInput.required = this.checked;
            endDateInput.required = this.checked;
        });
    </script>
</body>
</html>
