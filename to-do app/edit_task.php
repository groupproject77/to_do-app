<?php
session_start();
require 'db.php';

// Check if the user is logged in
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

// Retrieve the logged-in user's email
$user_email = $_SESSION['user'];

// Get the task ID from URL
$task_id = isset($_GET['id']) ? $_GET['id'] : null;

// If no task ID is provided, redirect to dashboard
if ($task_id === null) {
    header('Location: dashboard.php');
    exit;
}

// Fetch the task from the database
$sql = "SELECT * FROM plans WHERE id = ? AND user_email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $task_id, $user_email);
$stmt->execute();
$result = $stmt->get_result();

// If no task found, show an error
if ($result->num_rows == 0) {
    echo "Task not found or unauthorized access.";
    exit;
}

$task = $result->fetch_assoc();

// Handling form submission to update the task
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = htmlspecialchars($_POST['title']);
    $description = htmlspecialchars($_POST['description']);
    $date = $_POST['date'];
    $status = $_POST['status'];
    $priority = $_POST['priority'];
    $is_recurring = isset($_POST['is_recurring']) ? true : false; // Check if recurring is selected

    if ($is_recurring) {
        // If the task is recurring, handle it as multi-day
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];

        // Ensure valid start and end dates
        if (!empty($start_date) && !empty($end_date)) {
            $start = new DateTime($start_date);
            $end = new DateTime($end_date);

            if ($start > $end) {
                echo "Start date must be earlier than or equal to the end date.";
            } else {
                // Delete old task and add recurring tasks
                $sql_delete = "DELETE FROM plans WHERE id = ?";
                $stmt_delete = $conn->prepare($sql_delete);
                $stmt_delete->bind_param("i", $task_id);
                $stmt_delete->execute();

                while ($start <= $end) {
                    $rec_date = $start->format('Y-m-d');
                    $sql_insert = "INSERT INTO plans (user_email, title, description, date, status, priority) VALUES (?, ?, ?, ?, ?, ?)";
                    $stmt_insert = $conn->prepare($sql_insert);
                    $stmt_insert->bind_param("ssssss", $user_email, $title, $description, $rec_date, $status, $priority);
                    $stmt_insert->execute();
                    $start->modify('+1 day');
                }
                header("Location: dashboard.php");
                exit;
            }
        } else {
            echo "Please provide start and end dates for recurring tasks.";
        }
    } else {
        // If the task is not recurring, update the task normally
        $sql = "UPDATE plans SET title = ?, description = ?, date = ?, status = ?, priority = ? WHERE id = ? AND user_email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssis", $title, $description, $date, $status, $priority, $task_id, $user_email);

        if ($stmt->execute()) {
            header("Location: dashboard.php");
            exit;
        } else {
            echo "Error! Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ka">
<head>
    <meta charset="UTF-8">
    <title>Edit Task</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h2>გეგმის შეცვლა</h2>
        <form action="edit_task.php?id=<?= $task['id'] ?>" method="POST">
            <input type="text" name="title" value="<?= htmlspecialchars($task['title']) ?>" placeholder="სათაური" required>
            <textarea name="description" placeholder="აღწერა" required><?= htmlspecialchars($task['description']) ?></textarea>
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

            <!-- Recurring checkbox and date range -->
            <label><input type="checkbox" name="is_recurring" id="recurring_checkbox" <?= $task['date'] != $task['start_date'] ? 'checked' : '' ?>> განმეორებადი ყოველდღიურად</label>

            <div id="recurring_fields" style="display: <?= $task['date'] != $task['start_date'] ? 'block' : 'none' ?>;">
                <input type="date" name="start_date" placeholder="საწყისი თარიღი" value="<?= htmlspecialchars($task['start_date']) ?>" <?= $task['date'] != $task['start_date'] ? 'required' : '' ?>>
                <input type="date" name="end_date" placeholder="დასასრული თარიღი" value="<?= htmlspecialchars($task['end_date']) ?>" <?= $task['date'] != $task['start_date'] ? 'required' : '' ?>>
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
            
            // Show or hide the recurring fields
            recurringFields.style.display = this.checked ? 'block' : 'none';

            // If recurring is checked, make the fields required
            if (this.checked) {
                startDateInput.required = true;
                endDateInput.required = true;
            } else {
                startDateInput.required = false;
                endDateInput.required = false;
            }
        });
    </script>
</body>
</html>
