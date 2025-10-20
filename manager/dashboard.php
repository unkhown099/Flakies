<?php
session_start();
require __DIR__ . '/../config/db_connect.php';

// Authorization: must be manager
if (!isset($_SESSION['staff_id']) || ($_SESSION['role'] ?? '') !== 'manager') {
    header('Location: ../login/login.php');
    exit;
}
$manager_id = (int) $_SESSION['staff_id'];
$role = $_SESSION['role'];
$username = $_SESSION['username'] ?? 'Manager';

/* ---------------------------
   DB helpers (single definitions)
   --------------------------- */
function flush_results(mysqli $conn)
{
    if ($res = $conn->store_result()) {
        $res->free();
    }
    while ($conn->more_results() && $conn->next_result()) {
        if ($tmp = $conn->store_result()) $tmp->free();
    }
}

function routine_exists(mysqli $conn, string $name): bool
{
    flush_results($conn);
    $name = $conn->real_escape_string($name);
    $qr = $conn->query("SELECT 1 FROM information_schema.ROUTINES WHERE ROUTINE_SCHEMA = DATABASE() AND ROUTINE_NAME = '{$name}' AND ROUTINE_TYPE = 'PROCEDURE' LIMIT 1");
    $exists = ($qr && $qr->num_rows > 0);
    if ($qr) $qr->free();
    return $exists;
}

function call_proc(mysqli $conn, string $sql)
{
    if (preg_match('/^\s*CALL\s+([`]?)([a-zA-Z0-9_]+)\1/i', $sql, $m)) {
        $proc = $m[2];
        if (!routine_exists($conn, $proc)) return false;
    }
    if (!$conn->multi_query($sql)) {
        flush_results($conn);
        return false;
    }
    $firstResult = null;
    if ($res = $conn->store_result()) {
        $firstResult = $res;
    }
    while ($conn->more_results() && $conn->next_result()) {
        if ($tmp = $conn->store_result()) $tmp->free();
    }
    return $firstResult;
}

function table_exists(mysqli $conn, string $table): bool
{
    $t = $conn->real_escape_string($table);
    $r = $conn->query("SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = '{$t}' LIMIT 1");
    $ok = ($r && $r->num_rows > 0);
    if ($r) $r->free();
    return $ok;
}

function column_exists(mysqli $conn, string $table, string $col): bool
{
    $t = $conn->real_escape_string($table);
    $c = $conn->real_escape_string($col);
    $r = $conn->query("SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '{$t}' AND COLUMN_NAME = '{$c}' LIMIT 1");
    $ok = ($r && $r->num_rows > 0);
    if ($r) $r->free();
    return $ok;
}

function customer_name_expr(mysqli $conn, $alias = 'c')
{
    if (column_exists($conn, 'customers', 'first_name') && column_exists($conn, 'customers', 'last_name')) {
        return "CONCAT_WS(' ', {$alias}.first_name, {$alias}.last_name)";
    }
    if (column_exists($conn, 'customers', 'name')) return "{$alias}.name";
    if (column_exists($conn, 'customers', 'username')) return "{$alias}.username";
    if (column_exists($conn, 'customers', 'email')) return "{$alias}.email";
    return "CAST({$alias}.id AS CHAR)";
}

$cust_name = customer_name_expr($conn, 'c');

/* ---------------------------
   Fetch manager profile
   --------------------------- */
$manager = ['name' => $username, 'profile_picture' => '../assets/pictures/default.png'];
if (table_exists($conn, 'staff')) {
    $stmt = $conn->prepare("SELECT name, profile_picture FROM staff WHERE id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("i", $manager_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && ($row = $res->fetch_assoc())) $manager = $row;
        $stmt->close();
    }
}

/* ---------------------------
   Group filter (day, month, year) & chart data
   --------------------------- */
$group_by = $_GET['group_by'] ?? 'day';
if (!in_array($group_by, ['day', 'month', 'year'])) $group_by = 'day';

$periods = $total_sales = [];

if (routine_exists($conn, 'sp_get_sales_grouped')) {
    $res = call_proc($conn, "CALL sp_get_sales_grouped('{$group_by}')");
    if ($res && $res instanceof mysqli_result) {
        while ($row = $res->fetch_assoc()) {
            $periods[] = $row['period'];
            $total_sales[] = (float)$row['total_sales'];
        }
        $res->free();
        flush_results($conn);
    }
} else {
    if (table_exists($conn, 'orders')) {
        if ($group_by === 'month') {
            $sql = "SELECT DATE_FORMAT(order_date, '%Y-%m') AS period, SUM(total_amount) AS total_sales FROM orders GROUP BY period ORDER BY period ASC";
        } elseif ($group_by === 'year') {
            $sql = "SELECT DATE_FORMAT(order_date, '%Y') AS period, SUM(total_amount) AS total_sales FROM orders GROUP BY period ORDER BY period ASC";
        } else {
            $sql = "SELECT DATE_FORMAT(order_date, '%Y-%m-%d') AS period, SUM(total_amount) AS total_sales FROM orders GROUP BY period ORDER BY period ASC";
        }
        if ($r = $conn->query($sql)) {
            while ($row = $r->fetch_assoc()) {
                $periods[] = $row['period'];
                $total_sales[] = (float)$row['total_sales'];
            }
            $r->free();
        }
    }
}

function formatDateLabel($date, $group_by)
{
    $timestamp = strtotime($date);
    if ($group_by === 'month') return date("F Y", $timestamp);
    if ($group_by === 'year') return date("Y", $timestamp);
    return date("M j, Y", $timestamp);
}
$formatted_periods = array_map(function ($p) use ($group_by) {
    return formatDateLabel($p, $group_by);
}, $periods);

/* ---------------------------
   Analytics (fallback)
   --------------------------- */
$analytics = ['total_products' => 0, 'total_users' => 0, 'total_orders' => 0, 'total_sales' => 0];
$res = call_proc($conn, "CALL sp_get_analytics()");
if ($res && $res instanceof mysqli_result) {
    $analytics = $res->fetch_assoc() ?: $analytics;
    $res->free();
    flush_results($conn);
} else {
    $prod_filter = '';
    if (column_exists($conn, 'products', 'status')) $prod_filter = "WHERE status = 'active'";
    elseif (column_exists($conn, 'products', 'is_available')) $prod_filter = "WHERE is_available = 1";

    $q = "SELECT 
            (SELECT COUNT(*) FROM products {$prod_filter}) AS total_products,
            (SELECT COUNT(*) FROM staff WHERE role != 'manager' AND status = 'active') AS total_users,
            (SELECT COUNT(*) FROM orders WHERE status IN ('pending','preparing','ready','completed')) AS total_orders,
            (SELECT IFNULL(SUM(total_amount),0) FROM orders WHERE status = 'completed') AS total_sales";
    if ($r = $conn->query($q)) {
        $analytics = $r->fetch_assoc() ?: $analytics;
        $r->free();
    }
}

/* ---------------------------
   Recent orders and orders list
   --------------------------- */
$recent_orders = [];
$orders = [];

$res = call_proc($conn, "CALL sp_get_recent_orders(5)");
if ($res && $res instanceof mysqli_result) {
    while ($row = $res->fetch_assoc()) $recent_orders[] = $row;
    $res->free();
    flush_results($conn);
} else {
    if (table_exists($conn, 'orders')) {
        $q = "SELECT o.id, o.total_amount, o.status, o.order_date AS created_at, COALESCE(" . $cust_name . ", '') AS customer_name, COALESCE(s.name,'') AS cashier_name
              FROM orders o
              LEFT JOIN customers c ON o.customer_id = c.id
              LEFT JOIN staff s ON o.cashier_id = s.id
              ORDER BY o.order_date DESC
              LIMIT 5";
        if ($r = $conn->query($q)) {
            while ($row = $r->fetch_assoc()) $recent_orders[] = $row;
            $r->free();
        }
    }
}

$res = call_proc($conn, "CALL sp_get_orders()");
if ($res && $res instanceof mysqli_result) {
    while ($row = $res->fetch_assoc()) $orders[] = $row;
    $res->free();
    flush_results($conn);
} else {
    if (table_exists($conn, 'orders')) {
        $q = "SELECT o.id AS order_id, o.total_amount, o.status, o.order_date AS created_at, COALESCE(" . $cust_name . ", '') AS customer_name, COALESCE(s.name,'') AS cashier_name
              FROM orders o
              LEFT JOIN customers c ON o.customer_id = c.id
              LEFT JOIN staff s ON o.cashier_id = s.id
              ORDER BY o.order_date DESC";
        if ($r = $conn->query($q)) {
            while ($row = $r->fetch_assoc()) $orders[] = $row;
            $r->free();
        }
    }
}

/* ---------------------------
   Order items mapping
   --------------------------- */
$order_items_map = [];
$res = call_proc($conn, "CALL sp_get_order_products()");
if ($res && $res instanceof mysqli_result) {
    while ($row = $res->fetch_assoc()) $order_items_map[(int)$row['order_id']][] = $row;
    $res->free();
    flush_results($conn);
} else {
    if (table_exists($conn, 'order_items')) {
        $q = "SELECT oi.order_id, p.id AS product_id, p.name AS product_name, oi.quantity, oi.subtotal
              FROM order_items oi
              LEFT JOIN products p ON oi.product_id = p.id";
        if ($r = $conn->query($q)) {
            while ($row = $r->fetch_assoc()) $order_items_map[(int)$row['order_id']][] = $row;
            $r->free();
        }
    }
}

/* ---------------------------
   Account actions & system messages
   --------------------------- */
$account_actions = [];
if (table_exists($conn, 'account_actions')) {
    $q = "SELECT aa.id, COALESCE(s.name,'system') AS user_name, aa.action_type, aa.action_details, aa.action_time
          FROM account_actions aa
          LEFT JOIN staff s ON aa.user_id = s.id
          ORDER BY aa.action_time DESC
          LIMIT 10";
    if ($r = $conn->query($q)) {
        while ($row = $r->fetch_assoc()) $account_actions[] = $row;
        $r->free();
    }
}

$system_messages = [];
if (table_exists($conn, 'system_messages')) {
    if ($r = $conn->query("SELECT id, message, created_at FROM system_messages ORDER BY created_at DESC LIMIT 10")) {
        while ($row = $r->fetch_assoc()) $system_messages[] = $row;
        $r->free();
    }
}

/* free any leftover and close connection when done with DB usage */
flush_results($conn);
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Flakies | Manager Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
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

        .btn-logout:hover {
            border: 1px solid #e0c65a;
        }

        /* MAIN CONTENT */
        .main-content {
            flex-grow: 1;
            margin-left: 260px;
            padding: 40px 50px;
            background: #fafafa;
            overflow-y: auto;
        }

        /* === GLOBAL HEADER STYLING === */
        h1, h2, h3 {
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

        h1::after, h2::after, h3::after {
            content: "";
            flex-grow: 1;
            height: 3px;
            border-radius: 10px;
            background: linear-gradient(90deg, #d9ed42, #d39e2a);
            margin-left: 12px;
            opacity: 0.4;
        }

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
            margin-bottom: 30px;
        }

        /* GRID LAYOUT */
        .grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 25px;
        }

        /* TABLES */
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        th, td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
            text-align: left;
        }

        th {
            font-weight: 600;
            color: #333;
        }

        /* SIDEBAR LISTS */
        .sidebar-list {
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.08);
            margin-bottom: 20px;
        }

        .sidebar-list h3 {
            margin-top: 0;
        }

        .muted {
            color: #666;
            font-size: 13px;
        }

        /* HEADER STYLING */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .user-info {
            text-align: right;
        }

        .user-info .name {
            font-weight: 700;
            font-size: 16px;
        }

        .user-info .date {
            color: #666;
            font-size: 14px;
        }

        /* RESPONSIVE */
        @media (max-width: 900px) {
            .grid {
                grid-template-columns: 1fr;
            }
            
            .sidebar {
                position: static;
                width: 100%;
                display: flex;
                overflow: auto;
            }
            
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>

<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="logo">
            <img src="../assets/pictures/45b0e7c9-8bc1-4ef3-bac2-cfc07174d613.png" alt="Flakies Logo">
            <span>Flakies</span>
        </div>
        <div class="welcome">Manager Panel</div>
        <ul class="menu">
    <li><a href="dashboard.php" class="active">🏠 Dashboard</a></li>
    <li><a href="../manager/inventory.php">📦 Inventory</a></li>
    <li><a href="../manager/reports.php">📊 Reports</a></li>
</ul>
        <a href="../login/logout.php" class="btn-logout">🚪 Logout</a>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="page-header">
            <h1>📈 Manager Dashboard</h1>
            <div class="user-info">
                <div class="name"><?= htmlspecialchars($manager['name']); ?></div>
                <div class="date"><?= date('M j, Y H:i') ?></div>
            </div>
        </div>

        <div class="dashboard-cards">
            <div class="card">
                <h3>Total Products</h3>
                <p><?= number_format($analytics['total_products'] ?? 0); ?></p>
                <small>Active / available products</small>
            </div>
            <div class="card">
                <h3>Active Staff</h3>
                <p><?= number_format($analytics['total_users'] ?? 0); ?></p>
                <small>Excludes managers</small>
            </div>
            <div class="card">
                <h3>Total Orders</h3>
                <p><?= number_format($analytics['total_orders'] ?? 0); ?></p>
                <small>All recorded orders</small>
            </div>
            <div class="card">
                <h3>Total Sales</h3>
                <p>$<?= number_format($analytics['total_sales'] ?? 0, 2); ?></p>
                <small>Completed orders</small>
            </div>
        </div>

        <div class="chart-card">
            <div class="filter-container">
                <form method="get">
                    <label for="group_by">Group by:</label>
                    <select name="group_by" id="group_by" onchange="this.form.submit()">
                        <option value="day" <?= $group_by == 'day' ? 'selected' : '' ?>>Day</option>
                        <option value="month" <?= $group_by == 'month' ? 'selected' : '' ?>>Month</option>
                        <option value="year" <?= $group_by == 'year' ? 'selected' : '' ?>>Year</option>
                    </select>
                </form>
            </div>
            <canvas id="salesChart" height="80"></canvas>
        </div>

        <div class="grid">
            <div>
                <div class="sidebar-list">
                    <h3>Recent Orders</h3>
                    <?php if (count($recent_orders) === 0): ?>
                        <div class="muted">No recent orders</div>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Customer</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>When</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_orders as $o): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($o['id'] ?? $o['order_id'] ?? ''); ?></td>
                                        <td><?= htmlspecialchars($o['customer_name'] ?? $o['supplier_name'] ?? $o['cashier_name'] ?? ''); ?></td>
                                        <td>$<?= number_format((float)($o['total_amount'] ?? $o['sale_amount'] ?? 0), 2); ?></td>
                                        <td><?= htmlspecialchars($o['status'] ?? ''); ?></td>
                                        <td class="muted"><?= htmlspecialchars(date('M j, H:i', strtotime($o['created_at'] ?? $o['order_date'] ?? date('Y-m-d H:i')))); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>

                <div class="sidebar-list">
                    <h3>All Orders</h3>
                    <?php if (count($orders) === 0): ?>
                        <div class="muted">No orders found</div>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Customer</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $o): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($o['order_id'] ?? $o['id'] ?? ''); ?></td>
                                        <td><?= htmlspecialchars($o['customer_name'] ?? ''); ?></td>
                                        <td>$<?= number_format((float)($o['total_amount'] ?? 0), 2); ?></td>
                                        <td><?= htmlspecialchars($o['status'] ?? ''); ?></td>
                                        <td class="muted"><?= htmlspecialchars(date('M j, Y H:i', strtotime($o['created_at'] ?? date('Y-m-d H:i')))); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>

            <div>
                <div class="sidebar-list">
                    <h3>Account Actions</h3>
                    <?php if (empty($account_actions)): ?>
                        <div class="muted">No recent actions</div>
                    <?php else: ?>
                        <ul style="list-style:none;padding:0;margin:0">
                            <?php foreach ($account_actions as $a): ?>
                                <li style="padding:12px 0;border-bottom:1px solid #f0f0f0">
                                    <div style="font-weight:700"><?= htmlspecialchars($a['user_name'] ?? 'system') ?></div>
                                    <div class="muted"><?= htmlspecialchars($a['action_type'] ?? '') ?> — <?= htmlspecialchars($a['action_time'] ?? '') ?></div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>

                <div class="sidebar-list">
                    <h3>System Messages</h3>
                    <?php if (empty($system_messages)): ?>
                        <div class="muted">No system messages</div>
                    <?php else: ?>
                        <ul style="list-style:none;padding:0;margin:0">
                            <?php foreach ($system_messages as $m): ?>
                                <li style="padding:12px 0;border-bottom:1px solid #f0f0f0">
                                    <div><?= htmlspecialchars(substr($m['message'] ?? '', 0, 80)) ?></div>
                                    <div class="muted"><?= htmlspecialchars($m['created_at'] ?? '') ?></div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        const labels = <?= json_encode($formatted_periods); ?>;
        const data = <?= json_encode($total_sales); ?>;
        const ctx = document.getElementById('salesChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Sales',
                    data: data,
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