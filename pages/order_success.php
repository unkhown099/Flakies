<?php
// order_success.php - Order Confirmation Page

require_once '../config/db_connect.php';
session_start();

if (!isset($_SESSION['customer_id']) || !isset($_GET['order_id'])) {
    header('Location: menu.php');
    exit;
}

$customer_id = $_SESSION['customer_id'];
$order_id = intval($_GET['order_id']);

// Fetch order details
$orderQuery = $conn->query("
    SELECT o.*, c.first_name, c.last_name, c.email
    FROM orders o
    JOIN customers c ON o.customer_id = c.id
    WHERE o.id = $order_id AND o.customer_id = $customer_id
");

if ($orderQuery->num_rows === 0) {
    header('Location: menu.php');
    exit;
}

$order = $orderQuery->fetch_assoc();

// Fetch order items
$itemsQuery = $conn->query("
    SELECT oi.*, p.name, p.image
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = $order_id
");

$items = [];
while ($row = $itemsQuery->fetch_assoc()) {
    $items[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flakies - Order Confirmed!</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #f4e04d 0%, #d4a942 100%);
            min-height: 100vh;
            padding: 2rem;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
        }

        .success-card {
            background: white;
            border-radius: 30px;
            padding: 3rem;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
            text-align: center;
            margin-bottom: 2rem;
        }

        .success-icon {
            font-size: 5rem;
            margin-bottom: 1rem;
            animation: bounce 1s ease;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }

        .success-title {
            font-size: 2.5rem;
            color: #2d2d2d;
            margin-bottom: 0.5rem;
            font-weight: 800;
        }

        .success-message {
            font-size: 1.1rem;
            color: #666;
            margin-bottom: 1.5rem;
        }

        .order-number {
            display: inline-block;
            background: linear-gradient(135deg, #f4e04d 0%, #d4a942 100%);
            color: #2d2d2d;
            padding: 1rem 2rem;
            border-radius: 15px;
            font-size: 1.5rem;
            font-weight: 800;
            margin-bottom: 2rem;
        }

        .order-details {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            margin-bottom: 2rem;
            text-align: left;
        }

        .section-title {
            font-size: 1.3rem;
            color: #2d2d2d;
            margin-bottom: 1rem;
            border-bottom: 2px solid #f4e04d;
            padding-bottom: 0.5rem;
            font-weight: 800;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .info-item {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 10px;
        }

        .info-label {
            font-size: 0.85rem;
            color: #666;
            margin-bottom: 0.3rem;
            text-transform: uppercase;
            font-weight: 600;
        }

        .info-value {
            color: #2d2d2d;
            font-size: 1.05rem;
            font-weight: 600;
        }

        .order-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .order-item:last-child {
            border-bottom: none;
        }

        .item-info {
            flex: 1;
        }

        .item-name {
            font-weight: 600;
            color: #2d2d2d;
            margin-bottom: 0.3rem;
        }

        .item-qty {
            font-size: 0.9rem;
            color: #666;
        }

        .item-total {
            font-weight: 700;
            color: #d4a942;
            font-size: 1.1rem;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 0.8rem 0;
            font-size: 1rem;
        }

        .summary-row.total {
            border-top: 2px solid #f4e04d;
            margin-top: 1rem;
            padding-top: 1rem;
            font-size: 1.5rem;
            font-weight: 800;
            color: #2d2d2d;
        }

        .summary-row.total .amount {
            color: #d4a942;
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            padding: 1rem 2rem;
            border: none;
            border-radius: 15px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s;
            display: inline-block;
        }

        .btn-primary {
            background: linear-gradient(135deg, #f4e04d 0%, #d4a942 100%);
            color: #2d2d2d;
        }

        .btn-primary:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(212, 169, 66, 0.4);
        }

        .btn-secondary {
            background: #2d2d2d;
            color: #f4e04d;
        }

        .btn-secondary:hover {
            background: #1a1a1a;
        }

        .timeline {
            margin-top: 2rem;
            padding: 1.5rem;
            background: #f8f9fa;
            border-radius: 15px;
        }

        .timeline-title {
            font-weight: 700;
            color: #2d2d2d;
            margin-bottom: 1rem;
        }

        .timeline-step {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
            color: #666;
        }

        .timeline-step.active {
            color: #d4a942;
            font-weight: 600;
        }

        .step-icon {
            font-size: 1.5rem;
        }

        @media (max-width: 768px) {
            .success-card {
                padding: 2rem;
            }

            .success-title {
                font-size: 2rem;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="success-card">
            <div class="success-icon">🎉</div>
            <h1 class="success-title">Order Confirmed!</h1>
            <p class="success-message">Thank you for your order. We've received it and will start preparing your delicious Filipino treats!</p>
            
            <div class="order-number">
                Order #<?php echo str_pad($order_id, 5, '0', STR_PAD_LEFT); ?>
            </div>

            <div class="action-buttons">
                <a href="menu.php" class="btn btn-primary">Continue Shopping</a>
                <a href="profile.php" class="btn btn-secondary">View Orders</a>
            </div>
        </div>

        <div class="order-details">
            <h2 class="section-title">📋 Order Details</h2>
            
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Customer</div>
                    <div class="info-value"><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Order Date</div>
                    <div class="info-value"><?php echo date('M d, Y - g:i A', strtotime($order['order_date'])); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Phone</div>
                    <div class="info-value"><?php echo htmlspecialchars($order['phone']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Payment Method</div>
                    <div class="info-value"><?php echo strtoupper($order['payment_method']); ?></div>
                </div>
            </div>

            <div class="info-item" style="margin-bottom: 2rem;">
                <div class="info-label">Delivery Address</div>
                <div class="info-value"><?php echo htmlspecialchars($order['delivery_address']); ?></div>
            </div>

            <?php if (!empty($order['notes'])): ?>
                <div class="info-item" style="margin-bottom: 2rem;">
                    <div class="info-label">Order Notes</div>
                    <div class="info-value"><?php echo htmlspecialchars($order['notes']); ?></div>
                </div>
            <?php endif; ?>

            <h3 class="section-title">🍽️ Items Ordered</h3>
            
            <?php foreach ($items as $item): ?>
                <div class="order-item">
                    <div class="item-info">
                        <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                        <div class="item-qty">Qty: <?php echo $item['quantity']; ?> × ₱<?php echo number_format($item['price'], 2); ?>
                                                </div>
                    </div>
                    <div class="item-total">₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?></div>
                </div>
            <?php endforeach; ?>

            <?php
            $subtotal = array_reduce($items, function($carry, $item) {
                return $carry + ($item['price'] * $item['quantity']);
            }, 0);

            $delivery_fee = 10.00;
            $total = $subtotal + $delivery_fee;
            ?>

            <div class="summary-row">
                <div>Subtotal</div>
                <div class="amount">₱<?php echo number_format($subtotal, 2); ?></div>
            </div>
            <div class="summary-row">
                <div>Delivery Fee</div>
                <div class="amount">₱<?php echo number_format($delivery_fee, 2); ?></div>
            </div>
            <div class="summary-row total">
                <div>Total</div>
                <div class="amount">₱<?php echo number_format($total, 2); ?></div>
            </div>

            <div class="timeline">
                <div class="timeline-title">Order Status</div>
                <div class="timeline-step active">
                    <span class="step-icon">✅</span>
                    <span>Order Placed</span>
                </div>
                <div class="timeline-step">
                    <span class="step-icon">🍳</span>
                    <span>Preparing</span>
                </div>
                <div class="timeline-step">
                    <span class="step-icon">🛵</span>
                    <span>Out for Delivery</span>
                </div>
                <div class="timeline-step">
                    <span class="step-icon">🏠</span>
                    <span>Delivered</span>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
