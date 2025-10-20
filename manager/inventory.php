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

/* ---------- config ---------- */
$low_threshold = 5;

/* ---------- products ---------- */
$products = [];
if (table_exists($conn, 'products')) {
    $sql = "SELECT id, name, category, IFNULL(stock,0) AS stock, IFNULL(updated_at, created_at) AS updated_at, COALESCE(status,'') AS status FROM products ORDER BY name";
    $res = $conn->query($sql);
    if ($res) {
        while ($r = $res->fetch_assoc()) $products[] = $r;
        $res->free();
    }
}

/* ---------- last restock from account_actions ---------- */
$last_restock = [];
if (table_exists($conn, 'account_actions')) {
    $sql = "SELECT action_time, action_details FROM account_actions WHERE action_details LIKE '%ProductID=%' ORDER BY action_time DESC LIMIT 2000";
    $res = $conn->query($sql);
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            if (preg_match('/ProductID=([0-9]+)/', $r['action_details'], $m)) {
                $pid = (int)$m[1];
                if (!isset($last_restock[$pid])) $last_restock[$pid] = $r['action_time'];
            }
        }
        $res->free();
    }
}

/* ---------- inventory change history ---------- */
$inventory_history = [];
if (table_exists($conn, 'account_actions')) {
    $sql = "SELECT aa.id, aa.user_id, aa.action_type, aa.action_details, aa.action_time, COALESCE(s.name,'') AS staff_name
            FROM account_actions aa
            LEFT JOIN staff s ON aa.user_id = s.id
            WHERE aa.action_details LIKE '%ProductID=%'
            ORDER BY aa.action_time DESC
            LIMIT 200";
    $res = $conn->query($sql);
    if ($res) {
        while ($r = $res->fetch_assoc()) $inventory_history[] = $r;
        $res->free();
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Flakies | Inventory Overview</title>
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
            margin-left: 8px;
        }

        .badge.low {
            background: #fff4b3;
            color: #7a5900;
        }

        .badge.out {
            background: #ffecec;
            color: #8a1e1e;
        }

        /* MUTED TEXT */
        .muted {
            color: #666;
            font-size: 13px;
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
            <li><a href="../manager/inventory.php" class="active">📦 Inventory</a></li>
            <li><a href="../manager/reports.php">📊 Reports</a></li>
        </ul>
        <a href="../login/logout.php" class="btn-logout">🚪 Logout</a>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <h1>📋 Inventory Overview</h1>

        <div class="table-card">
            <h3>Products & Stock</h3>
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Category</th>
                        <th>Stock</th>
                        <th>Last Restock</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $p): ?>
                        <tr>
                            <td style="font-weight: 500;"><?= esc($p['name']) ?></td>
                            <td class="muted"><?= esc($p['category']) ?></td>
                            <td style="font-weight: 600;">
                                <?= (int)$p['stock'] ?>
                                <?php if ((int)$p['stock'] <= 0): ?>
                                    <span class="badge out">Out of Stock</span>
                                <?php elseif ((int)$p['stock'] <= $low_threshold): ?>
                                    <span class="badge low">Low Stock</span>
                                <?php endif; ?>
                            </td>
                            <td class="muted"><?= esc($last_restock[$p['id']] ?? '—') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="table-card">
            <h3>Inventory Change History</h3>
            <?php if (empty($inventory_history)): ?>
                <div class="muted">No inventory changes logged.</div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>When</th>
                            <th>Staff</th>
                            <th>Action</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($inventory_history as $h): ?>
                            <tr>
                                <td class="muted"><?= esc($h['action_time']) ?></td>
                                <td style="font-weight: 500;"><?= esc($h['staff_name']) ?></td>
                                <td style="font-weight: 500;"><?= esc($h['action_type']) ?></td>
                                <td class="muted"><?= esc($h['action_details']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>