<?php
session_start();
include __DIR__ . '/../config/db_connect.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'manager') {
    header("Location: ../login.php");
    exit;
}
$manager_id = (int) $_SESSION['user_id'];

function flush_results($conn)
{
    while ($conn->more_results() && $conn->next_result()) { /* flush */ }
}

/* fetch manager */
$manager = ['name' => 'Manager', 'profile_picture' => '../assets/pictures/default.png'];
if ($stmt = $conn->prepare("SELECT name, profile_picture FROM users WHERE id = ?")) {
    $stmt->bind_param("i", $manager_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res) $manager = $res->fetch_assoc() ?: $manager;
    $stmt->close();
}

/* central helper to call a procedure and get result set */
function call_proc($conn, $sql)
{
    $res = $conn->query($sql);
    if ($res === false) return false;
    // if multiple result sets, return the first result set
    if ($res instanceof mysqli_result) return $res;
    flush_results($conn);
    return false;
}

/* Use stored procedures (primary), fallback to inline queries if proc missing */
$analytics = ['total_products' => 0, 'total_users' => 0, 'total_orders' => 0, 'total_sales' => 0];
$res = call_proc($conn, "CALL sp_get_analytics()");
if ($res && $res instanceof mysqli_result) {
    $analytics = $res->fetch_assoc() ?: $analytics;
    $res->free();
    flush_results($conn);
} else {
    $q = "SELECT (SELECT COUNT(*) FROM products WHERE status='active') AS total_products,
                 (SELECT COUNT(*) FROM users WHERE status='active' AND role!='manager') AS total_users,
                 (SELECT COUNT(*) FROM sales WHERE status IN ('approved','completed')) AS total_orders,
                 (SELECT IFNULL(SUM(sale_amount),0) FROM sales WHERE status='completed') AS total_sales";
    $r = $conn->query($q);
    if ($r) $analytics = $r->fetch_assoc() ?: $analytics;
}

/* recent orders */
$recent_orders = [];
$res = call_proc($conn, "CALL sp_get_recent_orders(5)");
if ($res && $res instanceof mysqli_result) {
    while ($row = $res->fetch_assoc()) $recent_orders[] = $row;
    $res->free();
    flush_results($conn);
} else {
    $q = "SELECT s.id, s.sale_amount AS total_amount, s.status, s.created_at, u.name AS supplier_name
          FROM sales s LEFT JOIN users u ON s.cashier_id = u.id ORDER BY s.created_at DESC LIMIT 5";
    $r = $conn->query($q);
    if ($r) while ($row = $r->fetch_assoc()) $recent_orders[] = $row;
}

/* account actions & system messages */
$account_actions_result = call_proc($conn, "CALL sp_get_account_actions()");
if (!($account_actions_result && $account_actions_result instanceof mysqli_result)) {
    $account_actions_result = $conn->query("SELECT aa.id, u.name AS user_name, aa.action_type, aa.action_details, aa.action_time FROM account_actions aa JOIN users u ON aa.user_id = u.id ORDER BY aa.action_time DESC");
} else {
    flush_results($conn);
}

$system_messages_result = call_proc($conn, "CALL sp_get_system_messages()");
if (!($system_messages_result && $system_messages_result instanceof mysqli_result)) {
    $system_messages_result = $conn->query("SELECT * FROM system_messages ORDER BY created_at DESC");
} else {
    flush_results($conn);
}

/* products, orders, order_products, users */
$products_result = call_proc($conn, "CALL sp_get_products()");
if (!($products_result && $products_result instanceof mysqli_result)) {
    $products_result = $conn->query("SELECT * FROM products");
} else {
    flush_results($conn);
}

$orders_result = call_proc($conn, "CALL sp_get_orders()");
if (!($orders_result && $orders_result instanceof mysqli_result)) {
    $orders_result = $conn->query("SELECT s.id AS order_id, s.sale_amount AS total_amount, s.status, s.created_at, u.name AS supplier_name FROM sales s LEFT JOIN users u ON s.cashier_id = u.id ORDER BY s.created_at DESC");
} else {
    flush_results($conn);
}

$order_products_result = call_proc($conn, "CALL sp_get_order_products()");
if (!($order_products_result && $order_products_result instanceof mysqli_result)) {
    $order_products_result = $conn->query("SELECT sp.sale_id AS order_id, p.name AS product_name, sp.quantity, sp.price FROM sales_products sp JOIN products p ON sp.product_id = p.id");
} else {
    flush_results($conn);
}

/* build order_products map */
$order_products = [];
if ($order_products_result && $order_products_result instanceof mysqli_result) {
    while ($row = $order_products_result->fetch_assoc()) {
        $order_products[(int)$row['order_id']][] = $row;
    }
}

/* users (exclude manager) */
$users_result = call_proc($conn, "CALL sp_get_users_excluding({$manager_id})");
if (!($users_result && $users_result instanceof mysqli_result)) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id != ?");
    $stmt->bind_param("i", $manager_id);
    $stmt->execute();
    $users_result = $stmt->get_result();
    $stmt->close();
} else {
    flush_results($conn);
}

/* POST handlers now use stored procs first, fallback to prepared statements */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    function local_log($conn, $user_id, $type, $details)
    {
        $user_id = (int)$user_id;
        if ($conn->query("CALL sp_log_action({$user_id}, " . $conn->real_escape_string("'{$type}'") . ", " . $conn->real_escape_string("'{$details}'") . ")")) {
            flush_results($conn);
            return;
        }
        $s = $conn->prepare("INSERT INTO account_actions (user_id, action_type, action_details) VALUES (?, ?, ?)");
        $s->bind_param("iss", $user_id, $type, $details);
        $s->execute();
        $s->close();
    }

    if (isset($_POST['deactivate_product']) || isset($_POST['activate_product'])) {
        $product_id = (int)($_POST['product_id'] ?? 0);
        $status = isset($_POST['deactivate_product']) ? 'inactive' : 'active';
        if (!$conn->query("CALL sp_update_product_status({$product_id}, '{$status}')")) {
            $u = $conn->prepare("UPDATE products SET status=? WHERE id=?");
            $u->bind_param("si", $status, $product_id);
            $u->execute();
            $u->close();
        } else {
            flush_results($conn);
        }
        local_log($conn, $manager_id, ucfirst($status) . ' Product', "{$status} product ID: {$product_id}");
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    if (isset($_POST['approve_order']) || isset($_POST['reject_order']) || isset($_POST['complete_order'])) {
        $order_id = (int)($_POST['order_id'] ?? 0);
        $status = isset($_POST['approve_order']) ? 'approved' : (isset($_POST['reject_order']) ? 'rejected' : 'completed');
        if (!$conn->query("CALL sp_update_sale_status({$order_id}, '{$status}')")) {
            $u = $conn->prepare("UPDATE sales SET status=? WHERE id=?");
            $u->bind_param("si", $status, $order_id);
            $u->execute();
            $u->close();
        } else {
            flush_results($conn);
        }
        local_log($conn, $manager_id, ucfirst($status) . ' Order', "{$status} order ID: {$order_id}");
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    if (isset($_POST['deactivate_user']) || isset($_POST['activate_user'])) {
        $user_id = (int)($_POST['user_id'] ?? 0);
        $status = isset($_POST['deactivate_user']) ? 'inactive' : 'active';
        if (!$conn->query("CALL sp_update_user_status({$user_id}, '{$status}')")) {
            $u = $conn->prepare("UPDATE users SET status=? WHERE id=?");
            $u->bind_param("si", $status, $user_id);
            $u->execute();
            $u->close();
        } else {
            flush_results($conn);
        }
        local_log($conn, $manager_id, ucfirst($status) . ' User', "{$status} user ID: {$user_id}");
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    if (isset($_POST['create_user'])) {
        $name = $_POST['name'] ?? '';
        $username = $_POST['username'] ?? '';
        $password = password_hash($_POST['password'] ?? '', PASSWORD_DEFAULT);
        $role = $_POST['role'] ?? 'cashier';
        if (!$conn->query("CALL sp_create_user(" . $conn->real_escape_string("'{$name}'") . ", " . $conn->real_escape_string("'{$username}'") . ", " . $conn->real_escape_string("'{$password}'") . ", " . $conn->real_escape_string("'{$role}'") . ")")) {
            $u = $conn->prepare("INSERT INTO users (name, username, password, role, status) VALUES (?, ?, ?, ?, 'active')");
            $u->bind_param("ssss", $name, $username, $password, $role);
            $u->execute();
            $u->close();
        } else {
            flush_results($conn);
        }
        local_log($conn, $manager_id, 'Create User', "Created user: {$username}");
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    if (isset($_POST['edit_user'])) {
        $edit_user_id = (int)($_POST['edit_user_id'] ?? 0);
        $edit_name = $_POST['edit_name'] ?? '';
        $edit_username = $_POST['edit_username'] ?? '';
        $edit_role = $_POST['edit_role'] ?? 'cashier';
        if (!$conn->query("CALL sp_edit_user({$edit_user_id}, " . $conn->real_escape_string("'{$edit_name}'") . ", " . $conn->real_escape_string("'{$edit_username}'") . ", " . $conn->real_escape_string("'{$edit_role}'") . ")")) {
            $u = $conn->prepare("UPDATE users SET name=?, username=?, role=? WHERE id=?");
            $u->bind_param("sssi", $edit_name, $edit_username, $edit_role, $edit_user_id);
            $u->execute();
            $u->close();
        } else {
            flush_results($conn);
        }
        local_log($conn, $manager_id, 'Edit User', "Edited user ID: {$edit_user_id}");
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

/* order count today via proc */
$current_date = date('Y-m-d');
$order_count_today = 0;
$res = call_proc($conn, "CALL sp_get_order_count_by_date('{$current_date}')");
if ($res && $res instanceof mysqli_result) {
    $r = $res->fetch_assoc();
    if ($r && isset($r['order_count'])) $order_count_today = (int)$r['order_count'];
    $res->free();
    flush_results($conn);
} else {
    $stmt = $conn->prepare("SELECT COUNT(*) AS order_count FROM sales WHERE DATE(created_at) = ?");
    $stmt->bind_param("s", $current_date);
    $stmt->execute();
    $stmt->bind_result($order_count_today);
    $stmt->fetch();
    $stmt->close();
}

$threshold = 10;
$is_busy_day = ($order_count_today > $threshold);

/* prepare chart arrays */
$chart_labels = [];
$chart_data = [];
foreach ($recent_orders as $ro) {
    $chart_labels[] = 'ID ' . ($ro['id'] ?? $ro['order_id'] ?? '');
    $chart_data[] = (float)($ro['total_amount'] ?? 0);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Flakies | Manager Dashboard</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="icon" href="../assets/pictures/45b0e7c9-8bc1-4ef3-bac2-cfc07174d613.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* kept merged design styles (same as before) */
        body {
            display: flex;
            min-height: 100vh;
            margin: 0;
            font-family: "Poppins", sans-serif;
            background: #f7f8fa;
            color: #222;
        }

        .sidebar {
            width: 260px;
            flex-shrink: 0;
            background: linear-gradient(180deg, #d9ed42 0%, #d39e2a 60%, #e0d979ff 100%);
            padding: 25px 20px;
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            box-shadow: 4px 0 20px rgba(0, 0, 0, .1);
            border-top-right-radius: 20px;
        }

        .main-content {
            flex-grow: 1;
            margin-left: 260px;
            padding: 40px 50px;
            background: #fafafa;
            min-height: 100vh;
            box-sizing: border-box;
            overflow-y: auto;
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

        .nav {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            color: #000;
            font-weight: 600;
            padding: 12px 18px;
            border-radius: 10px;
            transition: all .15s ease;
        }

        .nav-link.active {
            background: rgba(0, 0, 0, .08);
            transform: translateX(4px);
        }

        .btn-logout {
            margin-top: auto;
            width: 100%;
            background: linear-gradient(135deg, #000 0%, #222 100%);
            color: #fff;
            border-radius: 10px;
            padding: 12px 0;
            border: none;
            font-weight: 600;
        }

        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .card-brief {
            background: #fff;
            border-radius: 12px;
            padding: 18px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, .06);
            text-align: center;
        }

        .chart-card {
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, .06);
        }

        .content-section {
            display: none;
        }

        @media(max-width:800px) {
            .sidebar {
                position: relative;
                width: 100%;
                border-radius: 0;
            }

            .main-content {
                margin-left: 0;
                padding: 20px;
            }
        }
    </style>
</head>

<body>
    <div class="sidebar">
        <div class="logo">
            <img src="../assets/pictures/45b0e7c9-8bc1-4ef3-bac2-cfc07174d613.png" alt="Flakies Logo">
            <span>Flakies</span>
        </div>
        <div class="welcome">Manager Panel</div>
        <ul class="nav">
            <li><a href="#" class="nav-link active" data-section="dashboard"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="#" class="nav-link" data-section="reports"><i class="fas fa-chart-line"></i> Reports</a></li>
            <li><a href="#" class="nav-link" data-section="products"><i class="fas fa-box"></i> Products</a></li>
            <li><a href="#" class="nav-link" data-section="orders"><i class="fas fa-shopping-cart"></i> Orders</a></li>
            <li><a href="#" class="nav-link" data-section="users"><i class="fas fa-users"></i> Users</a></li>
        </ul>

        <div style="width:100%;margin-top:20px;text-align:center;">
            <img src="<?php echo htmlspecialchars($manager['profile_picture']); ?>" alt="Profile" style="width:72px;height:72px;border-radius:50%;object-fit:cover;">
            <div style="margin-top:8px;font-weight:700;"><?php echo htmlspecialchars($manager['name']); ?></div>
        </div>

        <form method="POST" action="../login/logout.php" style="width:100%;margin-top:20px;">
            <button type="submit" class="btn-logout">🚪 Logout</button>
        </form>
    </div>

    <div class="main-content container-fluid">
        <!-- Dashboard -->
        <div id="dashboard" class="content-section" style="display:block;">
            <h1>📊 Manager Dashboard</h1>
            <p>Welcome back, <strong><?php echo htmlspecialchars($manager['name']); ?></strong></p>

            <div class="alert <?php echo $is_busy_day ? 'alert-danger' : 'alert-success'; ?>">
                Today is <strong><?php echo $is_busy_day ? 'a busy day' : 'not a busy day'; ?>!</strong>
                We have <strong><?php echo $order_count_today; ?></strong> orders today.
            </div>

            <div class="dashboard-cards mb-3">
                <div class="card-brief">
                    <h5>Total Products</h5>
                    <p class="h4 mb-0"><?php echo number_format($analytics['total_products'] ?? 0); ?></p>
                </div>
                <div class="card-brief">
                    <h5>Total Users</h5>
                    <p class="h4 mb-0"><?php echo number_format($analytics['total_users'] ?? 0); ?></p>
                </div>
                <div class="card-brief">
                    <h5>Total Orders</h5>
                    <p class="h4 mb-0"><?php echo number_format($analytics['total_orders'] ?? 0); ?></p>
                </div>
                <div class="card-brief">
                    <h5>Total Sales</h5>
                    <p class="h4 mb-0">₱<?php echo number_format((float)($analytics['total_sales'] ?? 0), 2); ?></p>
                </div>
            </div>

            <div class="chart-card mb-4">
                <h3>Recent Sales (by order)</h3>
                <canvas id="recentSalesChart" height="80"></canvas>
            </div>

            <h4 class="mt-4">Recent Orders</h4>
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Supplier</th>
                            <th>Status</th>
                            <th>Total Amount</th>
                            <th>Created At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_orders as $order) { ?>
                            <tr>
                                <td><?php echo htmlspecialchars($order['id'] ?? $order['order_id']); ?></td>
                                <td><?php echo htmlspecialchars($order['supplier_name'] ?? ''); ?></td>
                                <td><?php echo ucfirst(htmlspecialchars($order['status'] ?? '')); ?></td>
                                <td>₱<?php echo number_format((float)($order['total_amount'] ?? 0), 2); ?></td>
                                <td><?php echo htmlspecialchars(date("Y-m-d H:i", strtotime($order['created_at'] ?? 'now'))); ?></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Reports -->
        <div id="reports" class="content-section">
            <h2>Reports</h2>

            <h3>Account Actions</h3>
            <div class="table-responsive mb-4">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Action ID</th>
                            <th>User</th>
                            <th>Action Type</th>
                            <th>Details</th>
                            <th>Action Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($account_actions_result && $account_actions_result instanceof mysqli_result) {
                            while ($action = $account_actions_result->fetch_assoc()) { ?>
                                <tr>
                                    <td><?php echo (int)$action['id']; ?></td>
                                    <td><?php echo htmlspecialchars($action['user_name']); ?></td>
                                    <td><?php echo htmlspecialchars($action['action_type']); ?></td>
                                    <td><?php echo htmlspecialchars($action['action_details']); ?></td>
                                    <td><?php echo htmlspecialchars($action['action_time']); ?></td>
                                </tr>
                        <?php }
                        } ?>
                    </tbody>
                </table>
            </div>

            <h3>System Messages</h3>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Message ID</th>
                            <th>Message</th>
                            <th>Created At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($system_messages_result && $system_messages_result instanceof mysqli_result) {
                            while ($message = $system_messages_result->fetch_assoc()) { ?>
                                <tr>
                                    <td><?php echo (int)$message['id']; ?></td>
                                    <td><?php echo htmlspecialchars($message['message']); ?></td>
                                    <td><?php echo htmlspecialchars($message['created_at']); ?></td>
                                </tr>
                        <?php }
                        } ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Products -->
        <div id="products" class="content-section">
            <h2>Products</h2>
            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($products_result && $products_result instanceof mysqli_result) {
                            while ($product = $products_result->fetch_assoc()) { ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                                    <td><?php echo htmlspecialchars($product['description']); ?></td>
                                    <td>₱<?php echo number_format((float)$product['price'], 2); ?></td>
                                    <td><?php echo (int)$product['stock'];
                                        if ((int)$product['stock'] < 5) echo ' <span class="badge bg-danger">Low Stock</span>'; ?></td>
                                    <td><?php echo ucfirst(htmlspecialchars($product['status'])); ?></td>
                                    <td>
                                        <form method="POST" class="d-inline-block">
                                            <input type="hidden" name="product_id" value="<?php echo (int)$product['id']; ?>">
                                            <?php if ($product['status'] == 'active') { ?>
                                                <button type="submit" name="deactivate_product" class="btn btn-warning btn-sm">Deactivate</button>
                                            <?php } else { ?>
                                                <button type="submit" name="activate_product" class="btn btn-success btn-sm">Activate</button>
                                            <?php } ?>
                                        </form>
                                    </td>
                                </tr>
                        <?php }
                        } ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Orders -->
        <div id="orders" class="content-section">
            <h2>Purchase Orders</h2>
            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Supplier</th>
                            <th>Total Amount</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($orders_result && $orders_result instanceof mysqli_result) {
                            while ($order = $orders_result->fetch_assoc()) { ?>
                                <tr>
                                    <td><?php echo (int)$order['order_id']; ?></td>
                                    <td><?php echo htmlspecialchars($order['supplier_name']); ?></td>
                                    <td>₱<?php echo number_format((float)$order['total_amount'], 2); ?></td>
                                    <td><?php echo ucfirst(htmlspecialchars($order['status'])); ?></td>
                                    <td><?php echo htmlspecialchars($order['created_at']); ?></td>
                                    <td>
                                        <?php if ($order['status'] == 'pending') { ?>
                                            <form method="POST" class="d-inline-block"><input type="hidden" name="order_id" value="<?php echo (int)$order['order_id']; ?>"><button type="submit" name="approve_order" class="btn btn-success btn-sm">Approve</button></form>
                                            <form method="POST" class="d-inline-block"><input type="hidden" name="order_id" value="<?php echo (int)$order['order_id']; ?>"><button type="submit" name="reject_order" class="btn btn-danger btn-sm">Reject</button></form>
                                        <?php } elseif ($order['status'] == 'approved') { ?>
                                            <form method="POST" class="d-inline-block"><input type="hidden" name="order_id" value="<?php echo (int)$order['order_id']; ?>"><button type="submit" name="complete_order" class="btn btn-primary btn-sm">Mark as Completed</button></form>
                                        <?php } else {
                                            echo '<span class="text-muted">' . htmlspecialchars(ucfirst($order['status'])) . '</span>';
                                        } ?>
                                    </td>
                                </tr>
                                <?php if (isset($order_products[(int)$order['order_id']])) { ?>
                                    <tr>
                                        <td colspan="6">
                                            <strong>Products:</strong>
                                            <div class="table-responsive mt-2">
                                                <table class="table table-sm table-bordered">
                                                    <thead>
                                                        <tr>
                                                            <th>Product Name</th>
                                                            <th>Quantity</th>
                                                            <th>Price</th>
                                                            <th>Total Price</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($order_products[(int)$order['order_id']] as $p) { ?>
                                                            <tr>
                                                                <td><?php echo htmlspecialchars($p['product_name']); ?></td>
                                                                <td><?php echo (int)$p['quantity']; ?></td>
                                                                <td>₱<?php echo number_format((float)$p['price'], 2); ?></td>
                                                                <td>₱<?php echo number_format((float)$p['quantity'] * (float)$p['price'], 2); ?></td>
                                                            </tr>
                                                        <?php } ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </td>
                                    </tr>
                                <?php } ?>
                        <?php }
                        } ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Users -->
        <div id="users" class="content-section">
            <h2>Users</h2>
            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($users_result && $users_result instanceof mysqli_result) {
                            while ($user = $users_result->fetch_assoc()) { ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo ucfirst(htmlspecialchars($user['role'])); ?></td>
                                    <td><?php echo ucfirst(htmlspecialchars($user['status'])); ?></td>
                                    <td>
                                        <?php if ($user['status'] == 'active') { ?>
                                            <form method="POST" class="d-inline-block"><input type="hidden" name="user_id" value="<?php echo (int)$user['id']; ?>"><button type="submit" name="deactivate_user" class="btn btn-warning btn-sm">Deactivate</button></form>
                                        <?php } else { ?>
                                            <form method="POST" class="d-inline-block"><input type="hidden" name="user_id" value="<?php echo (int)$user['id']; ?>"><button type="submit" name="activate_user" class="btn btn-success btn-sm">Activate</button></form>
                                        <?php } ?>
                                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editUserModal-<?php echo (int)$user['id']; ?>">Edit</button>
                                    </td>
                                </tr>

                                <!-- Edit Modal -->
                                <div class="modal fade" id="editUserModal-<?php echo (int)$user['id']; ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form method="POST" enctype="multipart/form-data">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Edit User</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <input type="hidden" name="edit_user_id" value="<?php echo (int)$user['id']; ?>">
                                                    <div class="mb-3"><label class="form-label">Full Name</label><input type="text" name="edit_name" class="form-control" value="<?php echo htmlspecialchars($user['name']); ?>" required></div>
                                                    <div class="mb-3"><label class="form-label">Username</label><input type="text" name="edit_username" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" required></div>
                                                    <div class="mb-3"><label class="form-label">Role</label>
                                                        <select name="edit_role" class="form-select" required>
                                                            <option value="cashier" <?php echo $user['role'] == 'cashier' ? 'selected' : ''; ?>>Cashier</option>
                                                            <option value="encoder" <?php echo $user['role'] == 'encoder' ? 'selected' : ''; ?>>Encoder</option>
                                                            <option value="supervisor" <?php echo $user['role'] == 'supervisor' ? 'selected' : ''; ?>>Supervisor</option>
                                                        </select>
                                                    </div>
                                                    <div class="mb-3"><label class="form-label">Profile Picture</label><input type="file" name="edit_profile_picture" class="form-control"></div>
                                                </div>
                                                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button><button type="submit" name="edit_user" class="btn btn-primary">Save Changes</button></div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                        <?php }
                        } ?>
                    </tbody>
                </table>
            </div>

            <h3 class="mt-4">Add New User</h3>
            <form method="POST" enctype="multipart/form-data" class="mb-4">
                <div class="row">
                    <div class="col-md-4 mb-3"><label class="form-label">Full Name</label><input type="text" name="name" class="form-control" required></div>
                    <div class="col-md-4 mb-3"><label class="form-label">Username</label><input type="text" name="username" class="form-control" required></div>
                    <div class="col-md-4 mb-3"><label class="form-label">Password</label><input type="password" name="password" class="form-control" required></div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3"><label class="form-label">Role</label>
                        <select name="role" class="form-select" required>
                            <option value="cashier">Cashier</option>
                            <option value="encoder">Encoder</option>
                            <option value="supervisor">Supervisor</option>
                        </select>
                    </div>
                    <div class="col-md-8 mb-3"><label class="form-label">Profile Picture</label><input type="file" name="profile_picture" class="form-control"></div>
                </div>
                <button type="submit" name="create_user" class="btn btn-primary">Add User</button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // NAV / section switching
            const navLinks = document.querySelectorAll('.nav-link[data-section]');
            const sections = document.querySelectorAll('.content-section');
            navLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    sections.forEach(s => s.style.display = 'none');
                    navLinks.forEach(l => l.classList.remove('active'));
                    const id = this.getAttribute('data-section');
                    const target = document.getElementById(id);
                    if (target) target.style.display = 'block';
                    this.classList.add('active');
                });
            });
            const defaultSection = document.getElementById('dashboard');
            if (defaultSection) defaultSection.style.display = 'block';

            // Chart initialization (ensure canvas exists)
            const ctx = document.getElementById('recentSalesChart');
            if (ctx) {
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: <?php echo json_encode($chart_labels); ?>,
                        datasets: [{
                            label: 'Order Amount',
                            data: <?php echo json_encode($chart_data); ?>,
                            backgroundColor: 'rgba(107,66,38,0.9)',
                            borderColor: 'rgba(78,45,18,0.9)',
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
            }

            // Intercept local action forms (forms without action attribute)
            document.querySelectorAll('form').forEach(form => {
                if (form.getAttribute('action')) return; // skip forms with explicit action (e.g., logout)
                form.addEventListener('submit', function (e) {
                    e.preventDefault();

                    // determine action type from form fields / buttons
                    const fd = new FormData(form);
                    // product toggle
                    if (fd.has('product_id') && (form.querySelector('button[name="deactivate_product"]') || form.querySelector('button[name="activate_product"]'))) {
                        const status = form.querySelector('button[name="deactivate_product"]') ? 'inactive' : 'active';
                        fd.set('action', 'toggle_product');
                        fd.set('status', status);
                    }
                    // order statuses
                    else if (fd.has('order_id') && (form.querySelector('button[name="approve_order"]') || form.querySelector('button[name="reject_order"]') || form.querySelector('button[name="complete_order"]'))) {
                        const status = form.querySelector('button[name="approve_order"]') ? 'approved' : (form.querySelector('button[name="reject_order"]') ? 'rejected' : 'completed');
                        fd.set('action', 'update_order_status');
                        fd.set('status', status);
                    }
                    // user activate/deactivate
                    else if (fd.has('user_id') && (form.querySelector('button[name="deactivate_user"]') || form.querySelector('button[name="activate_user"]'))) {
                        const status = form.querySelector('button[name="deactivate_user"]') ? 'inactive' : 'active';
                        fd.set('action', 'toggle_user');
                        fd.set('status', status);
                    }
                    // create user
                    else if (form.querySelector('button[name="create_user"]')) {
                        fd.set('action', 'create_user');
                    }
                    // edit user
                    else if (form.querySelector('button[name="edit_user"]')) {
                        fd.set('action', 'edit_user');
                    } else {
                        // not one of the managed forms - submit normally
                        form.removeEventListener('submit', arguments.callee);
                        form.submit();
                        return;
                    }

                    // send to server
                    fetch('manager_actions.php', {
                        method: 'POST',
                        body: fd,
                        credentials: 'same-origin'
                    }).then(r => r.json()).then(json => {
                        if (json.success) {
                            // reload to reflect changes
                            location.reload();
                        } else {
                            alert('Error: ' + (json.message || 'Unknown error'));
                        }
                    }).catch(err => {
                        alert('Network error');
                        console.error(err);
                    });
                });
            });
        });
    </script>
</body>

</html>
<?php
$conn->close();
?>