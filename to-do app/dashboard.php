<?php
session_start();
require 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$user_email = $_SESSION['user'];

// Status and priority options
$status_options = ['PLANNED', 'DONE', 'NOT DONE'];
$priority_options = ['დაბალი', 'საშუალო', 'მაღალი', 'აუცილებელი'];

// Sorting options
$sort_options = [
    'date ASC' => 'თარიღი ზრდადობით',
    'date DESC' => 'თარიღი კლებადობით',
    'priority ASC' => 'პრიორიტეტი ზრდადობით',
    'priority DESC' => 'პრიორიტეტი კლებადობით',
    'status ASC' => 'სტატუსი ზრდადობით',
    'status DESC' => 'სტატუსი კლებადობით'
];

// Filter parameters
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$priority = $_GET['priority'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$sort = $_GET['sort'] ?? 'date ASC';

// SQL query to fetch tasks (including collaboration tasks)
$sql = "SELECT p.*, c.user_email AS collaborator_email, c.status AS collaborator_status 
        FROM plans p 
        LEFT JOIN collaborators c ON p.id = c.task_id 
        WHERE (p.user_email = ? OR c.user_email = ?)";
$filters = [];
$params = [$user_email, $user_email];

// Search filter
if (!empty($search)) {
    $sql .= " AND (p.title LIKE ? OR p.description LIKE ?)";
    $filters[] = "%$search%";
    $filters[] = "%$search%";
}

// Date range filter
if (!empty($date_from)) {
    $sql .= " AND p.date >= ?";
    $filters[] = $date_from;
}
if (!empty($date_to)) {
    $sql .= " AND p.date <= ?";
    $filters[] = $date_to;
}

// Status and priority filters
if (!empty($status)) {
    $sql .= " AND p.status = ?";
    $filters[] = $status;
}
if (!empty($priority)) {
    $sql .= " AND p.priority = ?";
    $filters[] = $priority;
}

// Sorting
$sql .= " ORDER BY $sort";

// Execute query
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Preparation failed: " . $conn->error);
}

$stmt->bind_param(str_repeat("s", count($filters) + 2), ...array_merge([$user_email, $user_email], $filters));
$stmt->execute();
$result = $stmt->get_result();
// Count data for chart
$status_counts = ['PLANNED' => 0, 'DONE' => 0, 'NOT DONE' => 0];
$priority_counts = ['low' => 0, 'medium' => 0, 'high' => 0, 'essential' => 0];

$count_query = "SELECT status, priority FROM plans WHERE user_email = ? OR id IN 
                (SELECT task_id FROM collaborators WHERE user_email = ?)";
$count_stmt = $conn->prepare($count_query);
$count_stmt->bind_param("ss", $user_email, $user_email);
$count_stmt->execute();
$count_result = $count_stmt->get_result();

while ($row = $count_result->fetch_assoc()) {
    if (isset($status_counts[$row['status']])) {
        $status_counts[$row['status']]++;
    }
    if (isset($priority_counts[$row['priority']])) {
        $priority_counts[$row['priority']]++;
    }
}
?>

<!DOCTYPE html>
<html lang="ka">
<head>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <meta charset="UTF-8">
    <title>გეგმების მართვა</title>
    <style>
        /* 🌟 General Styles */
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }

        h1 {
            color: #333;
            margin-top: 20px;
            text-align: center;
        }

        /* 🌟 Container & Forms */
        .container, .filter-form {
            max-width: 1200px;
            margin: 20px auto;
            margin-left: 1px;
            margin-top: 1px;
            padding: 20px;
            background: white;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            text-align: left;
        }

        /* 🌟 Buttons */
        button, .btn {
            padding: 12px 20px;
            background-color:green;
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 5px;
            font-size: 16px;
            margin: 10px 0;
            text-align: center;
            display: inline-block;
            text-decoration: none;
        }

        button:hover, .btn:hover {
            background-color: #0056b3;
        }

        /* 🌟 Logout Link */
        .logout {
            display: inline-block;
            margin-left: 10px;
            color: #dc3545;
            font-size: 18px;
        }

        .logout:hover {
            text-decoration: underline;
        }

        /* 🌟 Filter Form Layout */
        .filter-form {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: space-between;
        }

        .filter-form input, 
        .filter-form select {
            width: 13%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 14px;
        }

        .filter-form button {
            width: auto;
            padding: 10px 20px;
            font-size: 14px;
            background-color: blue;
        }

        .filter-form button:hover {
            background-color: darkblue;
        }

        /* 🌟 Tasks Table */
        table {
            width: 100%;
            margin: 20px 0;
            border-collapse: collapse;
            border: 1px solid #ddd;
        }

        th, td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }

        th {
            background-color: #007bff;
            color: white;
            
        }

        td {
            background-color: #f9f9f9;
        }

        /* 🌟 Task Actions */
        a {
            text-decoration: none;
            color: #007bff;
            margin-right: 10px;
        }

        a:hover {
            text-decoration: underline;
        }

        /* 🌟 Task Details Actions */
        a.delete {
            color: #dc3545;
        }

        a.delete:hover {
            color: #c82333;
        }

        a.add-collaborator {
            color: #28a745;
        }

        a.add-collaborator:hover {
            color: #218838;
        }

        /* 🌟 Task Details (File Links and Images) */
        p {
            margin: 5px 0;
        }

        p img {
            width: 100px;
            height: 100px;
            border-radius: 5px;
            margin-right: 10px;
        }

        /* 🌟 Flex Container for User Settings and Logout Links */
        div[style="position: absolute; top: 10px; left: 10px;"] {
            display: flex;
            justify-content: flex-start;
            align-items: center;
        }

        div[style="position: absolute; top: 10px; left: 10px;"] a {
            margin-right: 10px;
        }

        /* 🌟 No Collaborators Text */
        i {
            font-style: italic;
            color: #6c757d;
        }
        /* Modal Styles */
        
/* Overlay background */
.modal {
  display: none; /* Hidden by default */
  position: fixed;
  z-index: 1000;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
  overflow: auto;
  background-color: rgba(0, 0, 0, 0.6);
  align-items: center;
  justify-content: center;
}

/* Modal content */
.modal-content {
  background-color: #fff;
  padding: 20px;
  border-radius: 10px;
  width: 90%;
  max-width: 900px;
}

/* Close button */
.close {
  color: #aaa;
  float: right;
  font-size: 28px;
  font-weight: bold;
  cursor: pointer;
}

/* Charts layout */
.charts-container {
  display: flex;
  flex-direction: row;
  justify-content: space-around;
  gap: 20px;
  flex-wrap: wrap;
}

.chart-box {
  flex: 1 1 45%;
  max-width: 45%;
  min-width: 300px;
}


.close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
}



.close:hover,
.close:focus {
    color: black;
    text-decoration: none;
    cursor: pointer;
}


    </style>
</head>
<body>

<h1>გეგმების სია</h1>

<a href="add_task.php" style="text-decoration: none;"class="btn">➕ ახალი გეგმა</a>
<a href="collaborator_tasks.php" style="text-decoration: none;" class="btn">🤝 ჩემი კოლაბორაციები</a>
<a  href="activity_log_view.php" style="text-decoration: none;"class="btn">📜 ცვლილებების ისტორია</a>


<div style="position: absolute; top: 10px; left: 10px;">
    <a href="logout.php" class="logout">🚪 გასვლა</a>
    <a href="user_settings.php" title="მომხმარებლის პარამეტრები">⚙️ პარამეტრები</a>
</div>


<!-- Filters Form -->
<form method="get" class="filter-form">
    <input type="text" name="search" placeholder="ძებნა სათაური ან აღწერა" value="<?= htmlspecialchars($search) ?>">
    <select name="status">
        <option value="">ყველა სტატუსი</option>
        <?php foreach ($status_options as $s): ?>
            <option value="<?= $s ?>" <?= ($status == $s) ? 'selected' : '' ?>><?= $s ?></option>
        <?php endforeach; ?>
    </select>
    <select name="priority">
        <option value="">ყველა პრიორიტეტი</option>
        <?php foreach ($priority_options as $p): ?>
            <option value="<?= $p ?>" <?= ($priority == $p) ? 'selected' : '' ?>><?= $p ?></option>
        <?php endforeach; ?>
    </select>
    <input type="date" name="date_from" value="<?= htmlspecialchars($date_from) ?>">
    <input type="date" name="date_to" value="<?= htmlspecialchars($date_to) ?>">
    <select name="sort">
        <?php foreach ($sort_options as $option_value => $option_label): ?>
            <option value="<?= $option_value ?>" <?= ($sort == $option_value) ? 'selected' : '' ?>><?= $option_label ?></option>
        <?php endforeach; ?>
    </select>
    <button type="submit">ფილტრი</button>
    
</form>
<!-- ღილაკი ჩარტის სანახავად -->
<button id="openChartModal" class="btn-primary">📊 სტატისტიკა</button>


<!-- Chart Modal -->
<div id="chartModal" class="modal">
  <div class="modal-content">
    <span class="close">&times;</span>
    <h3>სტატისტიკა</h3>
    
    <div class="charts-container">
      <div class="chart-box">
        <h4>სტატუსები</h4>
        <canvas id="statusPieChart"></canvas>
      </div>
      <div class="chart-box">
        <h4>პრიორიტეტები</h4>
        <canvas id="priorityPieChart"></canvas>
      </div>
    </div>
  </div>
</div>


<!-- Tasks Table -->
<table>
    <thead>
        <tr>
            <th>თარიღი</th>
            <th>სათაური</th>
            <th>აღწერა</th>
            <th>სტატუსი</th>
            <th>პრიორიტეტი</th>
            <th>კოლაბორატორი</th>
            <th>ქმედება</th>
        </tr>
    </thead>
    <tbody>
    <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= htmlspecialchars($row['date']) ?></td>
            <td><?= htmlspecialchars($row['title']) ?></td>
            <td><?= htmlspecialchars($row['description']) ?></td>
            <td><?= htmlspecialchars($row['status']) ?></td>
            <td><?= htmlspecialchars($row['priority']) ?></td>
            <td>
                <?php if (!empty($row['collaborator_email'])): ?>
                    <?= htmlspecialchars($row['collaborator_email']) ?> (<?= htmlspecialchars($row['collaborator_status']) ?>)
                <?php else: ?>
                    <i>No collaborators</i>
                <?php endif; ?>
            </td>
            <td>
                <a href="edit_task.php?id=<?= $row['id'] ?>">✏️ შეცვლა</a>
                <a  href="delete_task.php?id=<?= $row['id'] ?>" onclick="return confirm('ნამდვილად წაშალო?')">🗑️ წაშლა </a>
                <a href="add_collaborator.php?task_id=<?= $row['id'] ?>">➕ კოლაბორატორი</a>
                
                <!-- Fetch and display attached files/links -->
                <?php
                    // Fetch task files for the current task
                    $task_id = $row['id']; // Assuming $row['id'] is the task's ID
                    $file_query = $conn->prepare("SELECT * FROM task_files WHERE task_id = ?");
                    $file_query->bind_param("i", $task_id);
                    $file_query->execute();
                    $file_result = $file_query->get_result();

                    while ($file = $file_result->fetch_assoc()) {
                        if ($file['file_type'] === 'link') {
                            // For links
                            echo "<p><a href='" . htmlspecialchars($file['file_path']) . "' target='_blank'>🔗 " . htmlspecialchars($file['file_name']) . "</a>";
                            echo " <a href='delete_file.php?id=" . $file['id'] . "' onclick='return confirm(\"ნამდვილად გსურთ ლიკის წაშლა?\")'>წაშლა</a></p>";
                        } else {
                            // For images or other files
                            echo "<p><img src='" . htmlspecialchars($file['file_path']) . "' alt='" . htmlspecialchars($file['file_name']) . "' width='100' height='100'>";
                            echo " <a href='delete_file.php?id=" . $file['id'] . "' onclick='return confirm(\"ნამდვილად გსურთ ამ ფაილსი წაშლა?/file?\")'>ფაილის წაშლა</a></p>";
                        }
                    }
                ?>
            </td>
        </tr>
    <?php endwhile; ?>
    </tbody>
</table>

<script>
    // Open modal when the button is clicked
    document.getElementById('openChartModal').addEventListener('click', function() {
        document.getElementById('chartModal').style.display = 'flex'; // Show the modal
    });

    // Close modal when the close button is clicked
    document.querySelector('.close').addEventListener('click', function() {
        document.getElementById('chartModal').style.display = 'none'; // Hide the modal
    });

    // Pie chart data
    const statusData = <?= json_encode(array_values($status_counts)) ?>;
    const priorityData = <?= json_encode(array_values($priority_counts)) ?>;

    // Create status pie chart
    const ctxStatus = document.getElementById('statusPieChart').getContext('2d');
    new Chart(ctxStatus, {
        type: 'pie',
        data: {
            labels: ['PLANNED', 'DONE', 'NOT DONE'],
            datasets: [{
                label: 'სტატუსი',
                data: statusData,
                backgroundColor: ['#f1c40f', '#2ecc71', '#e74c3c']
            }]
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: 'სტატუსის გადანაწილება (%)'
                }
            }
        }
    });

    // Create priority pie chart
    const ctxPriority = document.getElementById('priorityPieChart').getContext('2d');
    new Chart(ctxPriority, {
        type: 'pie',
        data: {
            labels: ['low', 'medium', 'high', 'essential'],
            datasets: [{
                label: 'პრიორიტეტი',
                data: priorityData,
                backgroundColor: ['#3498db', '#9b59b6', '#e67e22', '#c0392b']
            }]
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: 'პრიორიტეტების გადანაწილება (%)'
                }
            }
        }
    });
</script>

</body>
</html> 