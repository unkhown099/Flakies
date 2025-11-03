<?php
session_start();

if (!isset($_SESSION['staff_id']) || !isset($_SESSION['role'])) {
    header("Location: ../login.php");
    exit();
}

if ($_SESSION['role'] !== 'admin') {
    echo "Access denied. You must be an admin to view this page.";
    exit();
}

require __DIR__ . '/../config/db_connect.php';

$staff_id = $_SESSION['staff_id'];
$role = $_SESSION['role'];
$username = $_SESSION['username'];

$group_by = $_GET['group_by'] ?? 'day';

// === Combine Orders + Sales for Analytics ===
switch ($group_by) {
    case 'month':
        $sql = "
            SELECT period, SUM(total_sales) AS total_sales FROM (
                SELECT DATE_FORMAT(order_date, '%Y-%m') AS period, SUM(total_amount) AS total_sales 
                FROM orders 
                GROUP BY period
                UNION ALL
                SELECT DATE_FORMAT(date, '%Y-%m') AS period, SUM(sale_amount) AS total_sales 
                FROM sales 
                GROUP BY period
            ) AS combined
            GROUP BY period ORDER BY period ASC";
        break;
    case 'year':
        $sql = "
            SELECT period, SUM(total_sales) AS total_sales FROM (
                SELECT DATE_FORMAT(order_date, '%Y') AS period, SUM(total_amount) AS total_sales 
                FROM orders 
                GROUP BY period
                UNION ALL
                SELECT DATE_FORMAT(date, '%Y') AS period, SUM(sale_amount) AS total_sales 
                FROM sales 
                GROUP BY period
            ) AS combined
            GROUP BY period ORDER BY period ASC";
        break;
    default:
        $sql = "
            SELECT period, SUM(total_sales) AS total_sales FROM (
                SELECT DATE_FORMAT(order_date, '%Y-%m-%d') AS period, SUM(total_amount) AS total_sales 
                FROM orders 
                GROUP BY period
                UNION ALL
                SELECT DATE_FORMAT(date, '%Y-%m-%d') AS period, SUM(sale_amount) AS total_sales 
                FROM sales 
                GROUP BY period
            ) AS combined
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

// === Summary Cards (Daily, Monthly, Yearly combined) ===
$todaySalesQuery = "
    SELECT IFNULL(SUM(amount), 0) AS total FROM (
        SELECT SUM(total_amount) AS amount FROM orders WHERE DATE(order_date) = CURDATE()
        UNION ALL
        SELECT SUM(sale_amount) AS amount FROM sales WHERE DATE(date) = CURDATE()
    ) AS combined";
$todaySales = $conn->query($todaySalesQuery)->fetch_assoc()['total'];

$monthSalesQuery = "
    SELECT IFNULL(SUM(amount), 0) AS total FROM (
        SELECT SUM(total_amount) AS amount FROM orders 
        WHERE YEAR(order_date) = YEAR(CURDATE()) AND MONTH(order_date) = MONTH(CURDATE())
        UNION ALL
        SELECT SUM(sale_amount) AS amount FROM sales 
        WHERE YEAR(date) = YEAR(CURDATE()) AND MONTH(date) = MONTH(CURDATE())
    ) AS combined";
$monthSales = $conn->query($monthSalesQuery)->fetch_assoc()['total'];

$yearSalesQuery = "
    SELECT IFNULL(SUM(amount), 0) AS total FROM (
        SELECT SUM(total_amount) AS amount FROM orders WHERE YEAR(order_date) = YEAR(CURDATE())
        UNION ALL
        SELECT SUM(sale_amount) AS amount FROM sales WHERE YEAR(date) = YEAR(CURDATE())
    ) AS combined";
$yearSales = $conn->query($yearSalesQuery)->fetch_assoc()['total'];

$conn->close();

// Format date labels
function formatDate($date, $group_by)
{
    $timestamp = strtotime($date);
    switch ($group_by) {
        case 'month':
            return date("F", $timestamp);
        case 'year':
            return date("Y", $timestamp);
        default:
            return date("M j, Y", $timestamp);
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
    <link rel="icon" type="image/x-icon" href="../assets/pictures/45b0e7c9-8bc1-4ef3-bac2-cfc07174d613.png">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            display: flex;
            height: 100vh;
            margin: 0;
            font-family: "Poppins", sans-serif;
            background: #f7f8fa;
            color: #222;
        }

        /* SIDEBAR */
        .sidebar {
            width: 260px;
            flex-shrink: 0;
            background: linear-gradient(180deg, #d9ed42 0%, #d39e2a 60%, #e0d979ff 100%);
            color: #000;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            align-items: flex-start;
            padding: 25px 20px;
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.1);
            border-top-right-radius: 20px;
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            margin: 0 !important;
            box-sizing: border-box !important;
        }

        main.container,
        .main-content {
            margin-left: 260px !important;
            padding: 40px 50px;
            background: #fafafa;
            min-height: 100vh;
            box-sizing: border-box;
        }


        .sidebar .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 26px;
            font-weight: 800;
            margin-bottom: 8px;
        }

        .sidebar .logo img {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            object-fit: cover;
        }


        .sidebar .welcome {
            font-size: 14px;
            color: rgba(0, 0, 0, 0.7);
            margin-bottom: 25px;
            font-weight: 500;
        }

        /* MENU LINKS */
        .menu {
            list-style: none;
            width: 100%;
            padding: 0;
            margin: 0;
            display: flex;
            flex-direction: column;
            gap: 6px;
            /* consistent even spacing between each item */
        }

        .menu li {
            margin: 0;
        }

        .menu a {
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            color: #000;
            font-weight: 600;
            padding: 12px 18px;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .menu a:hover,
        .menu a.active {
            background: rgba(0, 0, 0, 0.1);
            color: #000;
            transform: translateX(4px);
        }

        /* LOGOUT BUTTON — Professional Version */
        .btn-logout {
            margin-top: auto;
            width: 100%;
            background: linear-gradient(135deg, #000 0%, #222 100%);
            color: #fff;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 15px;
            padding: 12px 0;
            text-align: center;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.25);
            position: relative;
            overflow: hidden;
        }

        /* Gold accent glow when hovered */
        .btn-logout::before {
            content: "";
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(120deg, rgba(255, 255, 255, 0.15), rgba(255, 255, 255, 0));
            transition: all 0.5s ease;
        }

        .btn-logout:hover::before {
            left: 100%;
        }

        .btn-logout:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            background: linear-gradient(135deg, #111 0%, #000 100%);
        }

        /* Optional small gold outline on hover */
        .btn-logout:hover {
            border: 1px solid #e0c65a;
        }



        /* MAIN CONTENT */
        .main-content {
            flex-grow: 1;
            margin-left: 260px;
            /* push beside sidebar */
            padding: 40px 50px;
            background: #fafafa;
            overflow-y: auto;
        }

        /* === GLOBAL HEADER STYLING === */
        h1,
        h2,
        h3 {
            font-family: "Poppins", sans-serif;
            font-weight: 700;
            color: #000;
            letter-spacing: 0.5px;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
            position: relative;
        }

        /* Subtle underline accent for top-level headers */
        h1::after,
        h2::after,
        h3::after {
            content: "";
            flex-grow: 1;
            height: 3px;
            border-radius: 10px;
            background: linear-gradient(90deg, #d9ed42, #d39e2a);
            margin-left: 12px;
            opacity: 0.4;
        }

        /* Sizes for hierarchy */
        h1 {
            font-size: 28px;
        }

        h2 {
            font-size: 22px;
        }

        h3 {
            font-size: 18px;
            font-weight: 600;
            color: #333;
        }

        /* Optional: give icons or emojis inside headers consistent look */
        h1 span.icon,
        h2 span.icon,
        h3 span.icon {
            font-size: 24px;
        }


        /* FILTER BAR */
        .filter-container {
            margin-bottom: 30px;
            background: #fff;
            padding: 15px 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            display: inline-block;
        }

        .filter-container label {
            font-size: 16px;
            font-weight: 500;
            color: #000;
        }

        .filter-container select {
            padding: 8px 10px;
            border-radius: 6px;
            border: 1px solid #ccc;
            font-size: 15px;
            margin-left: 10px;
        }

        /* DASHBOARD CARDS */
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .card {
            background: white;
            border-radius: 12px;
            padding: 20px 25px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card:hover {
            transform: translateY(-4px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.12);
        }

        .card h3 {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 5px;
            color: #333;
        }

        .card p {
            font-size: 26px;
            font-weight: 700;
            color: #000;
        }

        .card small {
            color: gray;
        }

        /* CHART CARD */
        .chart-card {
            background: #fff;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
        }
    </style>
</head>

<body>
    <div class="sidebar">
        <div class="logo">
            <img src="../assets/pictures/45b0e7c9-8bc1-4ef3-bac2-cfc07174d613.png" alt="Flakies Logo">
            <span>Flakies</span>
        </div>
        <div class="welcome"><?= ucfirst($role) ?> Panel</div>
        <ul class="menu">
            <li><a href="dashboard.php">🏠 Dashboard</a></li>
            <li><a href="manage_users.php">👥 Manage Users</a></li>
            <li><a href="manage_products.php">📦 Manage Products</a></li>
            <li><a href="manage_report.php">📊 Reports</a></li>
            <li><a href="manage_pages.php">📝 Manage Pages</a></li>
        </ul>
        <a href="../login/logout.php" class="btn-logout">🚪 Logout</a>
    </div>

    <div class="main-content">
        <h1>📈 Combined Sales Analytics</h1>

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

        <div class="dashboard-cards">
            <div class="card">
                <h3>Daily Sales</h3>
                <p>₱<?= number_format($todaySales, 2); ?></p><small>As of today</small>
            </div>
            <div class="card">
                <h3>Monthly Sales</h3>
                <p>₱<?= number_format($monthSales, 2); ?></p><small>For <?= date("F Y"); ?></small>
            </div>
            <div class="card">
                <h3>Yearly Sales</h3>
                <p>₱<?= number_format($yearSales, 2); ?></p><small>For <?= date("Y"); ?></small>
            </div>
        </div>

        <div class="chart-card">
            <canvas id="salesChart"></canvas>
        </div>
    </div>

    <script>
        const ctx = document.getElementById('salesChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($formatted_periods); ?>,
                datasets: [{
                    label: 'Total Sales (Orders + POS)',
                    data: <?= json_encode($total_sales); ?>,
                    backgroundColor: '#6b4226',
                    borderColor: '#4e2d12',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    </script>
</body>

</html>