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
    $date = $_POST['date'];  // This is the date for a single day task
    $status = $_POST['status'];
    $priority = $_POST['priority'];
    $is_recurring = isset($_POST['is_recurring']) ? true : false; // Check if recurring is selected

    // If the task is recurring
    if ($is_recurring) {
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];

        if (!empty($title) && !empty($description) && !empty($start_date) && !empty($end_date)) {
            $start = new DateTime($start_date);
            $end = new DateTime($end_date);

            if ($start > $end) {
                $error_message = "დასაწყისი თარიღი უნდა იყოს ადრე ან ტოლი დასრულების თარიღზე.";
            } else {
                // Insert recurring tasks for each day in the range
                while ($start <= $end) {
                    $rec_date = $start->format('Y-m-d');  // Get the current date in the range
                    $sql = "INSERT INTO plans (user_email, title, description, date, status, priority, start_date, end_date, is_recurring) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("sssssssss", $user_email, $title, $description, $rec_date, $status, $priority, $start_date, $end_date, $is_recurring);
                    $stmt->execute();
                    $start->modify('+1 day');  // Move to the next day in the range
                }
                header("Location: dashboard.php");
                exit;
            }
        } else {
            $error_message = "გთხოვთ შეავსოთ ყველა ველი!";
        }
    } else {
        // If the task is not recurring, handle it normally (i.e., a single-day task)
        if (!empty($title) && !empty($description) && !empty($date)) {
            $sql = "INSERT INTO plans (user_email, title, description, date, status, priority, is_recurring) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssss", $user_email, $title, $description, $date, $status, $priority, $is_recurring);

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

            <!-- Recurring checkbox and date range -->
            <label><input type="checkbox" name="is_recurring" id="recurring_checkbox"> განმეორებადი ყოველდღიურად</label>

            <div id="recurring_fields" style="display:none;">
                <input type="date" name="start_date" placeholder="საწყისი თარიღი" id="start_date">
                <input type="date" name="end_date" placeholder="დასასრული თარიღი" id="end_date">
            </div>

            <?php if (!empty($error_message)): ?>
                <div class="error"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <button type="submit">დამატება</button>
        </form>
        <a href="dashboard.php">⬅ უკან</a>
    </div>

    <script>
        document.getElementById('recurring_checkbox').addEventListener('change', function() {
            document.getElementById('recurring_fields').style.display = this.checked ? 'block' : 'none';
            // If checkbox is unchecked, ensure that the date fields are not required
            if (!this.checked) {
                document.getElementById('start_date').removeAttribute('required');
                document.getElementById('end_date').removeAttribute('required');
            } else {
                document.getElementById('start_date').setAttribute('required', 'required');
                document.getElementById('end_date').setAttribute('required', 'required');
            }
        });
    </script>
</body>
</html>
