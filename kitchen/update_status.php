<?php
require_once '../config/db_connect.php';

if (isset($_POST['id']) && isset($_POST['status'])) {
    $orderId = intval($_POST['id']);
    $status = $_POST['status'];

    $stmt = $conn->prepare("CALL update_order_status(?, ?)");
    $stmt->bind_param("is", $orderId, $status);
    $stmt->execute();

    echo "Order #$orderId status updated to '$status'";
}
?>
