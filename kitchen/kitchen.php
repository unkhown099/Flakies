<?php
// kitchen.php - Kitchen Dashboard for Flakies

// Include database connection
require_once '../config/db_connect.php';

// Fetch active orders
$result = $conn->query("CALL GetActiveOrders()");
$orders = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
    $conn->next_result();
}

// Get order items for each order
foreach ($orders as &$order) {
    $orderId = $order['id'];
    $itemsResult = $conn->query("CALL GetOrderItems($orderId)");
    $order['items'] = [];
    if ($itemsResult) {
        while ($item = $itemsResult->fetch_assoc()) {
            $order['items'][] = $item;
        }
        // Clear remaining results after stored procedure
        $conn->next_result();
    }
}

// Count orders by status
$result = $conn->query("CALL GetOrderStatusCounts()");
$newCount = 0;
$preparingCount = 0;
if ($result) {
    while ($row = $result->fetch_assoc()) {
        if ($row['status'] === 'new') $newCount = $row['count'];
        if ($row['status'] === 'preparing') $preparingCount = $row['count'];
    }
    $conn->next_result();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flakies Kitchen - Order Management</title>
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
            padding-bottom: 2rem;
        }

        nav {
            background: #2d2d2d;
            color: #f4e04d;
            padding: 1.5rem 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 20px rgba(0,0,0,0.3);
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }

        .logo img {
            height: 45px;
            width: 45px;
            border-radius: 50%;
            object-fit: cover;
        }

        .logo span {
            font-size: 1.6rem;
            font-weight: 700;
            color: #f4e04d;
        }

        .nav-info {
            display: flex;
            gap: 2rem;
            align-items: center;
        }

        .time {
            font-size: 1.1rem;
            font-weight: 500;
        }

        .stats {
            display: flex;
            gap: 1rem;
        }

        .stat-badge {
            background: rgba(244, 224, 77, 0.3);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            color: #2d2d2d;
        }

        .container {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .header {
            margin-bottom: 2rem;
        }

        .header h1 {
            font-size: 2.5rem;
            color: #2d3748;
            margin-bottom: 0.5rem;
        }

        .header p {
            color: #718096;
            font-size: 1.1rem;
        }

        .filter-tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }

        .tab {
            background: white;
            border: 2px solid #e2e8f0;
            padding: 0.8rem 1.5rem;
            border-radius: 15px;
            cursor: pointer;
            font-weight: 600;
            color: #718096;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .tab:hover {
            border-color: #d4a942;
            color: #d4a942;
        }

        .tab.active {
            background: linear-gradient(135deg, #f4e04d 0%, #d4a942 100%);
            color: #2d2d2d;
            border-color: transparent;
        }

        .orders-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
        }

        .order-card {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
            transition: all 0.3s ease;
            border-left: 5px solid #d4a942;
        }

        .order-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(212, 169, 66, 0.3);
        }

        .order-card.preparing {
            border-left-color: #f4e04d;
        }

        .order-card.ready {
            border-left-color: #48bb78;
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f7fafc;
        }

        .order-number {
            font-size: 1.5rem;
            font-weight: 800;
            color: #2d3748;
        }

        .order-time {
            font-size: 0.9rem;
            color: #718096;
        }

        .order-status {
            display: inline-block;
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .status-new {
            background: #e6f2ff;
            color: #667eea;
        }

        .status-preparing {
            background: #fff7e6;
            color: #feca57;
        }

        .status-ready {
            background: #e6ffe6;
            color: #48bb78;
        }

        .order-items {
            margin-bottom: 1.5rem;
        }

        .order-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.8rem 0;
            border-bottom: 1px solid #f7fafc;
        }

        .item-info {
            flex: 1;
        }

        .item-name {
            font-weight: 600;
            color: #2d3748;
            font-size: 1rem;
        }

        .item-notes {
            font-size: 0.85rem;
            color: #718096;
            margin-top: 0.3rem;
        }

        .item-quantity {
            background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
            padding: 0.3rem 0.8rem;
            border-radius: 10px;
            font-weight: 700;
            color: #2d3748;
            margin-right: 1rem;
        }

        .customer-info {
            background: #f7fafc;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }

        .customer-name {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 0.3rem;
        }

        .customer-details {
            color: #718096;
        }

        .order-actions {
            display: flex;
            gap: 0.8rem;
        }

        .action-btn {
            flex: 1;
            padding: 0.8rem;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.95rem;
        }

        .btn-accept {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-accept:hover {
            transform: scale(1.02);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }

        .btn-ready {
            background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
            color: white;
        }

        .btn-ready:hover {
            transform: scale(1.02);
            box-shadow: 0 5px 15px rgba(72, 187, 120, 0.3);
        }

        .btn-complete {
            background: #cbd5e0;
            color: #4a5568;
        }

        .btn-complete:hover {
            background: #a0aec0;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #718096;
            grid-column: 1 / -1;
        }

        .empty-state-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }

        .timer {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            color: #718096;
            margin-top: 0.5rem;
        }

        .timer.urgent {
            color: #f56565;
            font-weight: 600;
        }

        .auth-btn {
        display: inline-block;
        padding: 0.5rem 1.2rem;
        border-radius: 50px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.3s ease;
        border: 2px solid #f4e04d;
        }

        .login-btn {
            background: transparent;
            color: #f4e04d;
        }

        .login-btn:hover {
            background: #f4e04d;
            color: #2d2d2d;
        }


        @media (max-width: 768px) {
            .orders-grid {
                grid-template-columns: 1fr;
            }

            .nav-info {
                flex-direction: column;
                gap: 0.5rem;
            }

            .header h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <nav>
        <div class="logo">
            <img src="../assets/pictures/45b0e7c9-8bc1-4ef3-bac2-cfc07174d613.png" alt="Flakies Logo">
            <span>Flakies</span>
        </div>
        <div class="nav-info">
            <div class="time" id="currentTime"></div>
            <div class="stats">
                <div class="stat-badge">
                    <?php echo $newCount; ?> Pending
                </div>
                <div class="stat-badge">
                    <?php echo $preparingCount; ?> Preparing
                </div>
            </div>
            <a href="../login/logout.php" class="auth-btn login-btn">Logout</a>
        </div>
    </nav>

    <div class="container">
        <div class="header">
            <h1>Active Orders</h1>
            <p>Manage and track all incoming orders</p>
        </div>

        <div class="filter-tabs">
            <a href="kitchen.php" class="tab active">All Orders</a>
            <a href="kitchen.php?filter=new" class="tab">New</a>
            <a href="kitchen.php?filter=preparing" class="tab">Preparing</a>
            <a href="kitchen.php?filter=ready" class="tab">Ready</a>
        </div>

        <div class="orders-grid">
            <?php if (empty($orders)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">🎉</div>
                    <h2>All Caught Up!</h2>
                    <p>No active orders at the moment</p>
                </div>
            <?php else: ?>
                <?php foreach ($orders as $order): ?>
                    <div class="order-card <?php echo $order['status']; ?>">
                        <div class="order-header">
                            <div>
                                <div class="order-number">#ORD-<?php echo str_pad($order['id'], 3, '0', STR_PAD_LEFT); ?></div>
                                <div class="order-time">Received: <?php echo date('g:i A', strtotime($order['order_date'])); ?></div>
                                <div class="timer <?php echo $order['elapsed_minutes'] > 30 ? 'urgent' : ''; ?>">
                                    ⏱️ <span><?php echo $order['elapsed_minutes']; ?> mins ago</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="order-status status-<?php echo $order['status']; ?>">
                            <?php
                            switch($order['status']) {
                                case 'new':
                                    echo '🔔 New Order';
                                    break;
                                case 'preparing':
                                    echo '👨‍🍳 Preparing';
                                    break;
                                case 'ready':
                                    echo '✅ Ready for Pickup';
                                    break;
                            }
                            ?>
                        </div>
                        
                        <div class="customer-info">
                            <div class="customer-name">👤 <?php echo htmlspecialchars($order['customer_name']); ?></div>
                            <div class="customer-details">📱 <?php echo htmlspecialchars($order['customer_phone']); ?></div>
                        </div>

                        <div class="order-items">
                            <?php foreach ($order['items'] as $item): ?>
                                <div class="order-item">
                                    <div class="item-info">
                                        <div class="item-name">
                                            <?php echo $item['emoji']; ?> <?php echo htmlspecialchars($item['product_name']); ?>
                                        </div>
                                        <?php if (!empty($item['notes'])): ?>
                                            <div class="item-notes"><?php echo htmlspecialchars($item['notes']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="item-quantity">x<?php echo $item['quantity']; ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="order-actions">
                            <div class="order-actions">
                                <?php if ($order['status'] === 'new'): ?>
                                    <button class="action-btn btn-accept updateStatus" data-id="<?php echo $order['id']; ?>" data-status="preparing">
                                        Accept Order
                                    </button>
                                <?php elseif ($order['status'] === 'preparing'): ?>
                                    <button class="action-btn btn-ready updateStatus" data-id="<?php echo $order['id']; ?>" data-status="ready">
                                        Mark as Ready
                                    </button>
                                <?php elseif ($order['status'] === 'ready'): ?>
                                    <button class="action-btn btn-complete updateStatus" data-id="<?php echo $order['id']; ?>" data-status="completed">
                                        Complete Order
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Update current time
        function updateTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('en-US', { 
                hour: '2-digit', 
                minute: '2-digit',
                hour12: true 
            });
            document.getElementById('currentTime').textContent = timeString;
        }
        updateTime();
        setInterval(updateTime, 1000);

        // Auto-refresh every 30 seconds to check for new orders
        setInterval(function() {
            location.reload();
        }, 30000);
    </script>
    <script>
        document.addEventListener('click', function(e) {
        if (e.target.classList.contains('updateStatus')) {
            const id = e.target.dataset.id;
            const status = e.target.dataset.status;

            fetch('update_status.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `id=${id}&status=${status}`
            })
            .then(res => res.text())
            .then(msg => {
            alert(msg);
            location.reload();
            })
            .catch(() => alert('Failed to update order status.'));
        }
        });
    </script>
    <script>
    function updateStatus(orderId, action) {
        fetch('update_status.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `order_id=${orderId}&action=${action}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Reload the page or dynamically update the status
                alert('Order updated to ' + action + '!');
                location.reload(); 
            } else {
                alert('Failed to update order.');
            }
        })
        .catch(error => console.error('Error:', error));
    }
</script>
</body>
</html>