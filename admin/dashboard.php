<?php
session_start();
if (!isset($_SESSION['staff_id'])) {
    header("Location: login.php");
    exit();
}

require __DIR__ . '/../config/db_connect.php';

$role = $_SESSION['role'];
$username = $_SESSION['username'];

// Group filter (day, month, year)
$group_by = isset($_GET['group_by']) ? $_GET['group_by'] : 'day';

switch ($group_by) {
    case 'month':
        $sql = "SELECT DATE_FORMAT(date, '%Y-%m') AS period, SUM(sale_amount) AS total_sales 
                FROM sales 
                GROUP BY period ORDER BY period ASC";
        break;
    case 'year':
        $sql = "SELECT DATE_FORMAT(date, '%Y') AS period, SUM(sale_amount) AS total_sales 
                FROM sales 
                GROUP BY period ORDER BY period ASC";
        break;
    default:
        $sql = "SELECT DATE_FORMAT(date, '%Y-%m-%d') AS period, SUM(sale_amount) AS total_sales 
                FROM sales 
                GROUP BY period ORDER BY period ASC";
        break;
}

$result = $conn->query($sql);
$periods = [];
$total_sales = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $periods[] = $row['period'];
        $total_sales[] = $row['total_sales'];
    }
}

$conn->close();

// Helper function to format dates nicely
function formatDate($date, $group_by)
{
    $timestamp = strtotime($date);
    switch ($group_by) {
        case 'month':
            return date("F", $timestamp); // e.g., January
        case 'year':
            return date("Y", $timestamp);
        default:
            return date("M j, Y", $timestamp); // e.g., Jan 1, 2025
    }
}

$formatted_periods = array_map(function ($p) use ($group_by) {
    return formatDate($p, $group_by);
}, $periods);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Flakies | Analytics</title>
    <link rel="icon" type="image/x-icon" href="GEPOLEO-LOGO-FLAKIES-CIRCLE.png">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        body {
            display: flex;
            height: 100vh;
            margin: 0;
            font-family: "Poppins", sans-serif;
            background: #f6f7fb;
        }

        /* Sidebar (copied from dashboard.php — do not modify) */
        .sidebar {
            width: 250px;
            background: linear-gradient(180deg, #6b4226, #4e2d12);
            color: white;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 25px 0;
            box-shadow: 4px 0 15px rgba(0, 0, 0, 0.1);
            border-top-right-radius: 20px;
        }
        .logo {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 5px;
        }
        .welcome {
            font-size: 14px;
            color: #e5d1b8;
            margin-bottom: 20px;
        }
        .menu {
            list-style: none;
            width: 100%;
            padding: 0;
        }
        .menu li {
            margin: 10px 0;
        }
        .menu a {
            display: block;
            text-decoration: none;
            color: #fff;
            font-weight: 600;
            padding: 12px 20px;
            transition: all 0.3s;
            border-left: 4px solid transparent;
        }
        .menu a:hover {
            background: rgba(255,255,255,0.1);
            border-left: 4px solid #e5d1b8;
            color: #e5d1b8;
        }
        .btn-logout {
            margin-top: auto;
            padding: 10px 20px;
            background: white;
            color: #4e2d12;
            border-radius: 6px;
            font-weight: 600;
            text-decoration: none;
            transition: 0.3s;
        }
        .btn-logout:hover {
            background: #f4e3d1;
        }

        /* Main content */
        .main-content {
            flex-grow: 1;
            padding: 40px;
        }

        h1 {
            color: #4e2d12;
            margin-bottom: 20px;
        }

        .filter-container {
            margin-bottom: 25px;
        }

        .filter-container label {
            font-size: 16px;
            color: #4e2d12;
            font-weight: 500;
            margin-right: 10px;
        }

        .filter-container select {
            padding: 8px;
            border-radius: 5px;
            border: 1px solid #ccc;
            font-size: 16px;
        }

        .chart-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
    </style>
</head>

<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <h2 class="logo">Flakies</h2>
        <p class="welcome">Welcome, <?= htmlspecialchars($username); ?>!</p>
        <ul class="menu">
            <li><a href="dashboard.php">🏠 Dashboard</a></li>
            <?php if ($role === 'admin'): ?>
                <li><a href="manage_users.php">👤 Manage Users</a></li>
                <li><a href="manage_products.php">📦 Manage Products</a></li>
                <li><a href="manage_report.php">📊 Reports</a></li>
            <?php elseif ($role === 'manager'): ?>

                <li><a href="reports.php">📊 Reports</a></li>
            <?php elseif ($role === 'cashier'): ?>
                <li><a href="cashier/pos.php">💵 Point of Sale</a></li>
            <?php elseif ($role === 'encoder'): ?>
                <li><a href="encoder/inventory.php">📦 Inventory</a></li>
            <?php endif; ?>
        </ul>
        <a href="../login/logout.php" class="btn-logout">🚪 Logout</a>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <h1>📈 Sales Analytics</h1>

        <div class="filter-container">
            <form method="GET">
                <label for="group_by">Group by:</label>
                <select name="group_by" id="group_by" onchange="this.form.submit()">
                    <option value="day" <?= $group_by == 'day' ? 'selected' : ''; ?>>Day</option>
                    <option value="month" <?= $group_by == 'month' ? 'selected' : ''; ?>>Month</option>
                    <option value="year" <?= $group_by == 'year' ? 'selected' : ''; ?>>Year</option>
                </select>
            </form>
        </div>

        <div class="chart-card">
            <canvas id="salesChart"></canvas>
        </div>
    </div>

    <script>
        const ctx = document.getElementById('salesChart').getContext('2d');
        const chart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($formatted_periods); ?>,
                datasets: [{
                    label: 'Total Sales',
                    data: <?= json_encode($total_sales); ?>,
                    backgroundColor: '#6b4226',
                    borderColor: '#4e2d12',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: { beginAtZero: true }
                },
                plugins: {
                    legend: { display: false }
                }
            }
        });
    </script>
</body>
</html>
