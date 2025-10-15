<?php
// admin/save_user.php
session_start();
require __DIR__ . '/../config/db_connect.php';
header('Content-Type: application/json');

if (!isset($_SESSION['staff_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['status'=>'error','message'=>'Unauthorized']); exit;
}

$id = isset($_POST['id']) && is_numeric($_POST['id']) ? intval($_POST['id']) : null;
$username = trim($_POST['username'] ?? '');
$name = trim($_POST['name'] ?? '');
$role = trim($_POST['role'] ?? '');
$password = $_POST['password'] ?? '';

if (!$username || !$name || !$role) { echo json_encode(['status'=>'error','message'=>'Missing fields']); exit; }

// check valid role
$valid_roles = ['admin','manager','encoder','cashier','inventory_clerk'];
if (!in_array($role, $valid_roles)) {
    echo json_encode(['status'=>'error','message'=>'Invalid role']); exit;
}

try {
    if ($id) {
        // editing: if username changed, ensure unique
        $stmt = $conn->prepare("SELECT id FROM staff WHERE username = ? AND id <> ?");
        $stmt->bind_param("si",$username,$id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) { echo json_encode(['status'=>'error','message'=>'Username already exists']); exit; }

        if ($password) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $u = $conn->prepare("UPDATE staff SET username=?, name=?, role=?, password=?, updated_at=NOW() WHERE id=?");
            $u->bind_param("ssssi",$username,$name,$role,$hash,$id);
        } else {
            $u = $conn->prepare("UPDATE staff SET username=?, name=?, role=?, updated_at=NOW() WHERE id=?");
            $u->bind_param("sssi",$username,$name,$role,$id);
        }
        $ok = $u->execute();
        echo json_encode(['status'=>$ok ? 'success':'error','message'=>$ok ? 'User updated':'DB error']);
        exit;
    } else {
        // insert: ensure unique
        $stmt = $conn->prepare("SELECT id FROM staff WHERE username = ?");
        $stmt->bind_param("s",$username);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) { echo json_encode(['status'=>'error','message'=>'Username already exists']); exit; }

        $hash = password_hash($password ?: bin2hex(random_bytes(4)), PASSWORD_DEFAULT);
        $ins = $conn->prepare("INSERT INTO staff (username,password,name,role,status) VALUES (?,?,?,?, 'active')");
        $ins->bind_param("ssss",$username,$hash,$name,$role);
        $ok = $ins->execute();
        echo json_encode(['status'=>$ok ? 'success':'error','message'=>$ok ? 'User created':'DB error']);
        exit;
    }
} catch (Exception $e) {
    echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
    exit;
}
