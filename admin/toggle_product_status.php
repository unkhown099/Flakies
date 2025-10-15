<?php
// admin/toggle_product_status.php
session_start();
require __DIR__ . '/../config/db_connect.php';
header('Content-Type: application/json');

// ✅ Check if the user is logged in and is Admin
if (!isset($_SESSION['staff_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
if (!$id) {
    echo json_encode(['status' => 'error', 'message' => 'Missing product ID']);
    exit;
}

// ✅ Check if product exists
$stmt = $conn->prepare("SELECT product_status FROM products WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows !== 1) {
    echo json_encode(['status' => 'error', 'message' => 'Product not found']);
    exit;
}

$row = $res->fetch_assoc();
$newStatus = $row['product_status'] == 1 ? 0 : 1;

// ✅ Update product status
$update = $conn->prepare("UPDATE products SET product_status = ? WHERE id = ?");
$update->bind_param("ii", $newStatus, $id);
$ok = $update->execute();

echo json_encode([
    'status' => $ok ? 'success' : 'error',
    'message' => $ok ? ($newStatus ? 'Product activated' : 'Product deactivated') : 'Failed to update'
]);
?>
