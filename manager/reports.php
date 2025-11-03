<?php
session_start();
require __DIR__ . '/../config/db_connect.php';

if (!isset($_SESSION['staff_id']) || !in_array($_SESSION['role'] ?? '', ['manager', 'admin'])) {
    header('Location: ../login/login.php');
    exit;
}

function esc($s)
{
    return htmlspecialchars($s ?? '', ENT_QUOTES);
}

if (!($conn instanceof mysqli)) {
    die('Database connection not available.');
}

/* ---------- helpers ---------- */
function table_exists(mysqli $conn, string $table): bool
{
    $sql = "SELECT COUNT(*) AS c FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) return false;
    $stmt->bind_param('s', $table);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();
    return !empty($row['c']);
}

function prepare_execute(mysqli $conn, string $sql, string $types = '', array $params = [])
{
    $stmt = $conn->prepare($sql);
    if (!$stmt) return false;
    if ($types !== '' && count($params) > 0) {
        // bind_param requires references
        $refs = [];
        foreach ($params as $k => $v) $refs[$k] = &$params[$k];
        array_unshift($refs, $types);
        call_user_func_array([$stmt, 'bind_param'], $refs);
    }
    if (!$stmt->execute()) {
        $stmt->close();
        return false;
    }
    $res = $stmt->get_result();
    // keep stmt so caller can close if needed; but return result
    $stmt->close();
    return $res;
}

/* ---------- filters ---------- */
$date_from  = $_GET['date_from'] ?? null;
$date_to    = $_GET['date_to'] ?? null;
$cashier_id = isset($_GET['cashier_id']) && $_GET['cashier_id'] !== '' ? (int)$_GET['cashier_id'] : null;
$status     = $_GET['status'] ?? null;

/* ---------- cashier list for filter ---------- */
$cashiers = [];
if (table_exists($conn, 'staff')) {
    $res = prepare_execute($conn, "SELECT id, name FROM staff WHERE role IN ('cashier','manager','encoder') ORDER BY name");
    if ($res) {
        while ($r = $res->fetch_assoc()) $cashiers[] = $r;
        $res->free();
    }
}

/* ---------- build WHERE ---------- */
$where = [];
$params = [];
$types = '';

if ($date_from) {
    $where[] = "o.order_date >= ?";
    $params[] = $date_from . " 00:00:00";
    $types .= 's';
}
if ($date_to) {
    $where[] = "o.order_date <= ?";
    $params[] = $date_to . " 23:59:59";
    $types .= 's';
}
if (!is_null($cashier_id)) {
    $where[] = "o.cashier_id = ?";
    $params[] = $cashier_id;
    $types .= 'i';
}
if ($status) {
    $where[] = "o.status = ?";
    $params[] = $status;
    $types .= 's';
}

$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

/* ---------- summary ---------- */
$summary = ['total_orders' => 0, 'total_sales' => 0.0];
$sql = "SELECT COUNT(*) AS total_orders, IFNULL(SUM(o.total_amount),0) AS total_sales FROM orders o {$where_sql}";
$res = prepare_execute($conn, $sql, $types, $params);
if ($res) {
    $summary = $res->fetch_assoc() ?: $summary;
    $res->free();
}

/* ---------- orders (limit 500) ---------- */
$orders = [];
$sql = "SELECT o.id AS order_id, o.total_amount, o.status, o.order_date,
               COALESCE(CONCAT_WS(' ', c.first_name, c.last_name), c.username, '') AS customer_name,
               COALESCE(s.name,'') AS cashier_name
        FROM orders o
        LEFT JOIN customers c ON o.customer_id = c.id
        LEFT JOIN staff s ON o.cashier_id = s.id
        {$where_sql}
        ORDER BY o.order_date DESC
        LIMIT 500";
$res = prepare_execute($conn, $sql, $types, $params);
if ($res) {
    while ($r = $res->fetch_assoc()) $orders[] = $r;
    $res->free();
}

/* ---------- view single order ---------- */
$view_order = null;
$order_items = [];
if (isset($_GET['view']) && $_GET['view'] === 'order' && !empty($_GET['id']) && is_numeric($_GET['id'])) {
    $oid = (int)$_GET['id'];
    $sql = "SELECT o.id AS order_id, o.total_amount, o.status, o.order_date,
                   COALESCE(CONCAT_WS(' ', c.first_name, c.last_name), c.username, '') AS customer_name,
                   COALESCE(s.name,'') AS cashier_name, c.email, c.phone, c.address
            FROM orders o
            LEFT JOIN customers c ON o.customer_id = c.id
            LEFT JOIN staff s ON o.cashier_id = s.id
            WHERE o.id = ? LIMIT 1";
    $res = prepare_execute($conn, $sql, 'i', [$oid]);
    if ($res) {
        $view_order = $res->fetch_assoc() ?: null;
        $res->free();
    }

    $sql = "SELECT oi.product_id, COALESCE(p.name,'') AS product_name, oi.quantity, oi.subtotal
            FROM order_items oi
            LEFT JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id = ?";
    $res = prepare_execute($conn, $sql, 'i', [$oid]);
    if ($res) {
        while ($r = $res->fetch_assoc()) $order_items[] = $r;
        $res->free();
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Flakies | Manager Reports</title>
    <link rel="icon" type="image/x-icon" href="../assets/pictures/45b0e7c9-8bc1-4ef3-bac2-cfc07174d613.png">
    
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

        /* FILTER CONTAINER */
        .filter-container {
            margin-bottom: 30px;
            background: #fff;
            padding: 20px 25px;
            border-radius: 12px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.08);
        }

        .filter-form {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }

        .filter-form label {
            font-size: 14px;
            font-weight: 600;
            color: #333;
        }

        .filter-form input,
        .filter-form select {
            padding: 10px 12px;
            border-radius: 8px;
            border: 1px solid #ddd;
            font-size: 14px;
            font-family: "Poppins", sans-serif;
        }

        .filter-form button {
            padding: 10px 20px;
            border-radius: 8px;
            background: #6b4226;
            color: #fff;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .filter-form button:hover {
            background: #5a3720;
            transform: translateY(-2px);
        }

        .reset-link {
            margin-left: auto;
            color: #666;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .reset-link:hover {
            color: #000;
        }

        /* TABLE CARDS */
        .table-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .table-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.12);
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
            font-size: 15px;
        }

        /* BADGES */
        .badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge.pending { background: #fff4b3; color: #7a5900; }
        .badge.preparing { background: #b3e0ff; color: #004d7a; }
        .badge.ready { background: #b3ffb3; color: #007a00; }
        .badge.completed { background: #d4ffb3; color: #2a7a00; }
        .badge.cancelled { background: #ffb3b3; color: #7a0000; }

        /* MUTED TEXT */
        .muted {
            color: #666;
            font-size: 13px;
        }

        /* VIEW LINK */
        .view-link {
            color: #6b4226;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .view-link:hover {
            color: #5a3720;
            text-decoration: underline;
        }

        /* RESPONSIVE */
        @media (max-width: 900px) {
            .main-content {
                margin-left: 0;
                padding: 20px;
            }
            
            .sidebar {
                position: static;
                width: 100%;
                display: flex;
                overflow: auto;
            }
            
            .filter-form {
                flex-direction: column;
                align-items: stretch;
            }
            
            .reset-link {
                margin-left: 0;
                text-align: center;
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
            <li><a href="dashboard.php">🏠 Dashboard</a></li>
            <li><a href="../manager/inventory.php">📦 Inventory</a></li>
            <li><a href="../manager/reports.php" class="active">📊 Reports</a></li>
        </ul>
        <a href="../login/logout.php" class="btn-logout">🚪 Logout</a>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <h1>📊 Reports</h1>

        <div class="dashboard-cards">
            <div class="card">
                <h3>Total Orders</h3>
                <p><?= number_format($summary['total_orders'] ?? 0) ?></p>
                <small>Filtered results</small>
            </div>
            <div class="card">
                <h3>Total Sales</h3>
                <p>₱<?= number_format($summary['total_sales'] ?? 0, 2) ?></p>
                <small>Filtered results</small>
            </div>
            <div class="card">
                <h3>Date Range</h3>
                <p style="font-size: 20px;"><?= esc($date_from ?: 'Any') ?> — <?= esc($date_to ?: 'Any') ?></p>
                <small>Selected period</small>
            </div>
        </div>

        <div class="filter-container">
            <form method="get" class="filter-form">
                <label>From</label>
                <input type="date" name="date_from" value="<?= esc($date_from) ?>">
                
                <label>To</label>
                <input type="date" name="date_to" value="<?= esc($date_to) ?>">
                
                <label>Cashier</label>
                <select name="cashier_id">
                    <option value="">All Cashiers</option>
                    <?php foreach ($cashiers as $c): ?>
                        <option value="<?= (int)$c['id'] ?>" <?= (!is_null($cashier_id) && $cashier_id == $c['id']) ? 'selected' : '' ?>>
                            <?= esc($c['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <label>Status</label>
                <select name="status">
                    <option value="">All Status</option>
                    <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="preparing" <?= $status === 'preparing' ? 'selected' : '' ?>>Preparing</option>
                    <option value="ready" <?= $status === 'ready' ? 'selected' : '' ?>>Ready</option>
                    <option value="completed" <?= $status === 'completed' ? 'selected' : '' ?>>Completed</option>
                    <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                </select>
                
                <button type="submit">Apply Filters</button>
                <a href="reports.php" class="reset-link">Reset Filters</a>
            </form>
        </div>

        <div class="table-card">
            <h3>Sales Transactions</h3>
            <?php if (empty($orders)): ?>
                <div class="muted">No transactions found for this filter.</div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Customer</th>
                            <th>Cashier</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $o): ?>
                            <tr>
                                <td style="font-weight: 500;"><?= esc($o['order_id']) ?></td>
                                <td><?= esc($o['customer_name']) ?></td>
                                <td><?= esc($o['cashier_name']) ?></td>
                                <td style="font-weight: 600;">$<?= number_format((float)$o['total_amount'], 2) ?></td>
                                <td>
                                    <span class="badge <?= esc($o['status'] ?? '') ?>">
                                        <?= esc(ucfirst($o['status'] ?? '')) ?>
                                    </span>
                                </td>
                                <td class="muted"><?= esc(date('Y-m-d H:i', strtotime($o['order_date'] ?? 'now'))) ?></td>
                                <td>
                                    <a href="reports.php?view=order&id=<?= (int)$o['order_id'] ?>" class="view-link">
                                        View
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <?php if ($view_order): ?>
            <div class="table-card">
                <h3>Order #<?= esc($view_order['order_id']) ?> Details</h3>
                <div style="margin-bottom: 15px;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 10px;">
                        <div>
                            <strong>Customer:</strong> <?= esc($view_order['customer_name']) ?>
                        </div>
                        <div>
                            <strong>Cashier:</strong> <?= esc($view_order['cashier_name']) ?>
                        </div>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div>
                            <strong>Status:</strong> 
                            <span class="badge <?= esc($view_order['status']) ?>">
                                <?= esc(ucfirst($view_order['status'])) ?>
                            </span>
                        </div>
                        <div>
                            <strong>Date:</strong> <?= esc($view_order['order_date']) ?>
                        </div>
                    </div>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Quantity</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($order_items as $it): ?>
                            <tr>
                                <td style="font-weight: 500;"><?= esc($it['product_name']) ?></td>
                                <td><?= (int)$it['quantity'] ?></td>
                                <td style="font-weight: 600;">$<?= number_format((float)$it['subtotal'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div style="text-align: right; font-weight: 700; margin-top: 15px; padding-top: 15px; border-top: 2px solid #eee;">
                    Total Amount: $<?= number_format((float)$view_order['total_amount'], 2) ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>