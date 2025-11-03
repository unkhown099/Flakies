<?php
require '../config/db_connect.php'; // adjust path if needed

header('Content-Type: application/json');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
    exit;
}

$order_id = intval($_GET['id']);

// Fetch order info
$orderQuery = $conn->prepare("
    SELECT id, order_date, payment_method, status, total_amount
    FROM orders
    WHERE id = ?
");
$orderQuery->bind_param("i", $order_id);
$orderQuery->execute();
$orderResult = $orderQuery->get_result();

if ($orderResult->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Order not found']);
    exit;
}

$order = $orderResult->fetch_assoc();

// Fetch order items
$itemsQuery = $conn->prepare("
    SELECT p.name AS product_name, oi.quantity, oi.price, (oi.quantity * oi.price) AS subtotal
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
");
$itemsQuery->bind_param("i", $order_id);
$itemsQuery->execute();
$itemsResult = $itemsQuery->get_result();

$items = [];
while ($row = $itemsResult->fetch_assoc()) {
    $items[] = $row;
}

// Return JSON response
echo json_encode([
    'success' => true,
    'order' => $order,
    'items' => $items
]);
?>
