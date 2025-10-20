<?php
session_start();
require_once '../config/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'cashier') {
    header("Location: login.php");
    exit;
}

// Assume your cart is stored in $_SESSION['cart']
$cart = $_SESSION['cart'] ?? [];

if (empty($cart)) {
    echo "<script>alert('Cart is empty!'); window.location='pos.php';</script>";
    exit;
}

// Calculate total
$totalAmount = 0;
foreach ($cart as $item) {
    $totalAmount += $item['price'] * $item['quantity'];
}

// Get customer and payment details
$customerName = $_POST['customer_name'] ?? 'Walk-in';
$paymentMode = $_POST['payment_mode'] ?? 'Cash';

// Insert new order
$orderStmt = $conn->prepare("INSERT INTO orders (customer_name, total_amount, payment_mode) VALUES (?, ?, ?)");
$orderStmt->bind_param("sds", $customerName, $totalAmount, $paymentMode);
$orderStmt->execute();
$orderId = $orderStmt->insert_id;

// Insert order items
$itemStmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
foreach ($cart as $item) {
    $itemStmt->bind_param("iiid", $orderId, $item['product_id'], $item['quantity'], $item['price']);
    $itemStmt->execute();

    // Optionally reduce stock
    $conn->query("UPDATE products SET stock = stock - {$item['quantity']} WHERE id = {$item['product_id']}");
}

// Clear the cart
unset($_SESSION['cart']);

echo "<script>alert('Checkout successful! Order ID: $orderId'); window.location='cashierdashboard.php';</script>";
?>
