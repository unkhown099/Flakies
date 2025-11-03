<?php
session_start();
require_once '../config/db_connect.php';

header('Content-Type: application/json'); // Important for JSON responses

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'cashier') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit;
}

// Decode JSON data from the fetch request
$data = json_decode(file_get_contents("php://input"), true);
$cart = $data['cart'] ?? [];

if (empty($cart)) {
    echo json_encode(['status' => 'error', 'message' => 'Cart is empty']);
    exit;
}

$totalAmount = 0;
foreach ($cart as $item) {
    $totalAmount += $item['price'] * $item['qty'];
}

$customerName = null ; // Default for now

try {
    $conn->begin_transaction();

    // Insert order
    $orderStmt = $conn->prepare("INSERT INTO orders (customer_id, total_amount, order_date) VALUES (?, ?, NOW())");
    $orderStmt->bind_param("id", $customerName, $totalAmount);
    $orderStmt->execute();
    $orderId = $conn->insert_id;

    // Insert order items
    $itemStmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
    foreach ($cart as $item) {
        $itemStmt->bind_param("iiid", $orderId, $item['id'], $item['qty'], $item['price']);
        $itemStmt->execute();

        // Reduce stock
        $updateStock = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
        $updateStock->bind_param("ii", $item['qty'], $item['id']);
        $updateStock->execute();
    }

    $conn->commit();

    echo json_encode(['status' => 'success', 'message' => 'Checkout successful!', 'order_id' => $orderId]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['status' => 'error', 'message' => 'Transaction failed: ' . $e->getMessage()]);
}