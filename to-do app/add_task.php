<?php  
session_start();
require 'db.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$user_email = $_SESSION['user'];
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

        if (!empty($title) && !empty($description) && !empty($start_date) && !empty($end_date)) {
            $start = new DateTime($start_date);
            $end = new DateTime($end_date);

            if ($start > $end) {
                $error_message = "დასაწყისი თარიღი უნდა იყოს ადრე ან ტოლი დასრულების თარიღზე.";
            } else {
                $file_uploaded = false;
                $file_name = "";
                $file_path = "";
                $file_type = "";

                // Upload file ONCE
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
                            $file_uploaded = true;
                        }
                    }
                }

                $link_uploaded = !empty($_POST['attachment_link']);
                $link = $link_uploaded ? htmlspecialchars($_POST['attachment_link']) : "";

                while ($start <= $end) {
                    $rec_date = $start->format('Y-m-d');
                    $sql = "INSERT INTO plans (user_email, title, description, date, status, priority, start_date, end_date, is_recurring) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("sssssssss", $user_email, $title, $description, $rec_date, $status, $priority, $start_date, $end_date, $is_recurring);
                    $stmt->execute();

                    $task_id = $stmt->insert_id;

                    // Activity log
                    $action = "დაამატა განმეორებადი გეგმა '{$title}' - თარიღი: $rec_date";
                    $log_stmt = $conn->prepare("INSERT INTO activity_log (task_id, user_email, action) VALUES (?, ?, ?)");
                    $log_stmt->bind_param("iss", $task_id, $user_email, $action);
                    $log_stmt->execute();

                    // Add FILE to each
                    if ($file_uploaded) {
                        $stmt = $conn->prepare("INSERT INTO task_files (task_id, file_name, file_path, file_type, uploaded_by) VALUES (?, ?, ?, ?, ?)");
                        $stmt->bind_param("issss", $task_id, $file_name, $file_path, $file_type, $user_email);
                        $stmt->execute();

                        $action = "$file_type file '$file_name' uploaded";
                        $log_stmt = $conn->prepare("INSERT INTO activity_log (task_id, user_email, action) VALUES (?, ?, ?)");
                        $log_stmt->bind_param("iss", $task_id, $user_email, $action);
                        $log_stmt->execute();
                    }

                    // Add LINK to each
                    if ($link_uploaded) {
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

                    $start->modify('+1 day');
                }

                header("Location: dashboard.php");
                exit;
            }
        } else {
            $error_message = "გთხოვთ შეავსოთ ყველა ველი!";
        }
    } else {
        if (!empty($title) && !empty($description) && !empty($date)) {
            $sql = "INSERT INTO plans (user_email, title, description, date, status, priority, is_recurring) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssss", $user_email, $title, $description, $date, $status, $priority, $is_recurring);

            if ($stmt->execute()) {
                $task_id = $stmt->insert_id;

                $action = "დაამატა გეგმა '{$title}' თარიღით: $date";
                $log_stmt = $conn->prepare("INSERT INTO activity_log (task_id, user_email, action) VALUES (?, ?, ?)");
                $log_stmt->bind_param("iss", $task_id, $user_email, $action);
                $log_stmt->execute();

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

                // LINK
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
        <form action="add_task.php" method="POST" enctype="multipart/form-data">
    <input type="text" name="title" placeholder="სათაური" required>
    <textarea name="description" placeholder="აღწერა" required></textarea>
    <input type="date" name="date" value="<?= date('Y-m-d'); ?>" required>
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

    <label>ფაილის დამატება (PDF, სურათი, ან ბმული):</label>
    <input type="file" name="attachment" accept=".pdf,image/*">
    <br>
    <label>ან ბმული:</label>
    <input type="url" name="attachment_link" placeholder="https://example.com">

    <label><input type="checkbox" name="is_recurring" id="recurring_checkbox"> განმეორებადი ყოველდღიურად</label>

    <div id="recurring_fields" style="display:none;">
        <input type="date" name="start_date" id="start_date">
        <input type="date" name="end_date" id="end_date">
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
