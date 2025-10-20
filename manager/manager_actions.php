<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

include __DIR__ . '/../config/db_connect.php'; // must define $conn (mysqli)

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'manager') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$manager_id = (int) $_SESSION['user_id'];

function respond($ok, $msg = '', $data = []) {
    echo json_encode(array_merge(['success' => $ok, 'message' => $msg], $data));
    exit;
}

function local_log($conn, $user_id, $type, $details) {
    $stmt = $conn->prepare("INSERT INTO account_actions (user_id, action_type, action_details) VALUES (?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("iss", $user_id, $type, $details);
        $stmt->execute();
        $stmt->close();
    }
}

$action = $_POST['action'] ?? null;
if (!$action) {
    respond(false, 'No action specified');
}

try {
    switch ($action) {
        case 'toggle_product':
            $product_id = (int)($_POST['product_id'] ?? 0);
            $status = (isset($_POST['status']) && $_POST['status'] === 'active') ? 'active' : 'inactive';
            if ($product_id <= 0) respond(false, 'Invalid product id');

            $stmt = $conn->prepare("UPDATE products SET status = ? WHERE id = ?");
            if (!$stmt) respond(false, 'Prepare failed');
            $stmt->bind_param("si", $status, $product_id);
            $ok = $stmt->execute();
            $stmt->close();

            if ($ok) {
                local_log($conn, $manager_id, ucfirst($status) . ' Product', "{$status} product ID: {$product_id}");
                respond(true, "Product status updated", ['product_id' => $product_id, 'status' => $status]);
            }
            respond(false, 'Failed updating product');
            break;

        case 'update_order_status':
            $order_id = (int)($_POST['order_id'] ?? 0);
            $valid = ['approved','rejected','completed'];
            $status = $_POST['status'] ?? '';
            if ($order_id <= 0 || !in_array($status, $valid, true)) respond(false, 'Invalid inputs');

            $stmt = $conn->prepare("UPDATE sales SET status = ? WHERE id = ?");
            if (!$stmt) respond(false, 'Prepare failed');
            $stmt->bind_param("si", $status, $order_id);
            $ok = $stmt->execute();
            $stmt->close();

            if ($ok) {
                local_log($conn, $manager_id, ucfirst($status) . ' Order', "{$status} order ID: {$order_id}");
                respond(true, "Order status updated", ['order_id' => $order_id, 'status' => $status]);
            }
            respond(false, 'Failed updating order');
            break;

        case 'toggle_user':
            $user_id = (int)($_POST['user_id'] ?? 0);
            if ($user_id <= 0) respond(false, 'Invalid user id');
            if ($user_id === $manager_id) respond(false, 'Cannot change your own status');

            $status = (isset($_POST['status']) && $_POST['status'] === 'active') ? 'active' : 'inactive';
            $stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ?");
            if (!$stmt) respond(false, 'Prepare failed');
            $stmt->bind_param("si", $status, $user_id);
            $ok = $stmt->execute();
            $stmt->close();

            if ($ok) {
                local_log($conn, $manager_id, ucfirst($status) . ' User', "{$status} user ID: {$user_id}");
                respond(true, "User status updated", ['user_id' => $user_id, 'status' => $status]);
            }
            respond(false, 'Failed updating user');
            break;

        case 'create_user':
            $name = trim($_POST['name'] ?? '');
            $username = trim($_POST['username'] ?? '');
            $password_raw = $_POST['password'] ?? '';
            $role = $_POST['role'] ?? 'cashier';

            if ($name === '' || $username === '' || $password_raw === '') respond(false, 'Missing required fields');

            // ensure username unique
            $chk = $conn->prepare("SELECT id FROM users WHERE username = ? LIMIT 1");
            if (!$chk) respond(false, 'Prepare failed');
            $chk->bind_param("s", $username);
            $chk->execute();
            $chk->store_result();
            if ($chk->num_rows > 0) { $chk->close(); respond(false, 'Username already exists'); }
            $chk->close();

            $password = password_hash($password_raw, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (name, username, password, role, status) VALUES (?, ?, ?, ?, 'active')");
            if (!$stmt) respond(false, 'Prepare failed');
            $stmt->bind_param("ssss", $name, $username, $password, $role);
            $ok = $stmt->execute();
            $new_id = $conn->insert_id;
            $stmt->close();

            if ($ok) {
                local_log($conn, $manager_id, 'Create User', "Created user: {$username}");
                respond(true, 'User created', ['user_id' => $new_id]);
            }
            respond(false, 'Failed creating user');
            break;

        case 'edit_user':
            $edit_user_id = (int)($_POST['edit_user_id'] ?? 0);
            $edit_name = trim($_POST['edit_name'] ?? '');
            $edit_username = trim($_POST['edit_username'] ?? '');
            $edit_role = $_POST['edit_role'] ?? 'cashier';

            if ($edit_user_id <= 0 || $edit_name === '' || $edit_username === '') respond(false, 'Missing required fields');
            if ($edit_user_id === $manager_id && $edit_role !== 'manager') respond(false, 'Cannot remove manager role from yourself');

            // check username uniqueness excluding this user
            $chk = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ? LIMIT 1");
            if (!$chk) respond(false, 'Prepare failed');
            $chk->bind_param("si", $edit_username, $edit_user_id);
            $chk->execute();
            $chk->store_result();
            if ($chk->num_rows > 0) { $chk->close(); respond(false, 'Username already in use'); }
            $chk->close();

            $stmt = $conn->prepare("UPDATE users SET name = ?, username = ?, role = ? WHERE id = ?");
            if (!$stmt) respond(false, 'Prepare failed');
            $stmt->bind_param("sssi", $edit_name, $edit_username, $edit_role, $edit_user_id);
            $ok = $stmt->execute();
            $stmt->close();

            if ($ok) {
                local_log($conn, $manager_id, 'Edit User', "Edited user ID: {$edit_user_id}");
                respond(true, 'User updated', ['user_id' => $edit_user_id]);
            }
            respond(false, 'Failed updating user');
            break;

        default:
            respond(false, 'Unknown action');
    }
} catch (Exception $e) {
    respond(false, 'Server error');
}
?>