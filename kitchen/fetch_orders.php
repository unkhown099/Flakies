<?php
require_once '../config/db_connect.php';

$result = $conn->query("SELECT * FROM view_active_orders");

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "
        <div class='order-card'>
            <h4>Order #{$row['id']}</h4>
            <p><strong>Cashier:</strong> {$row['cashier_name']}</p>
            <p><strong>Customer:</strong> " . ($row['customer_name'] ?? 'Walk-in') . "</p>
            <p><strong>Payment:</strong> {$row['payment_method']}</p>
            <p><strong>Total:</strong> ₱{$row['total_amount']}</p>
            <p><strong>Status:</strong> {$row['status']}</p>
            <div class='buttons'>
                <button class='updateStatus' data-id='{$row['id']}' data-status='preparing'>Accept</button>
                <button class='updateStatus' data-id='{$row['id']}' data-status='ready'>Ready</button>
                <button class='updateStatus' data-id='{$row['id']}' data-status='completed'>Complete</button>
            </div>
        </div>
        ";
    }
} else {
    echo "<p>No current orders.</p>";
}
?>
