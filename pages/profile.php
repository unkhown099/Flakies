<?php
require_once '../config/db_connect.php';

// Start session
session_start();

// Check if customer is logged in
if (!isset($_SESSION['customer_id'])) {
    header('Location: ../login.php');
    exit;
}

$customer_id = $_SESSION['customer_id'];

// Fetch cart count
$cartQuery = $conn->query("SELECT SUM(quantity) AS total_items FROM cart WHERE customer_id = $customer_id");
$cartData = $cartQuery->fetch_assoc();
$cart_count = $cartData['total_items'] ?? 0;
$message = '';
$messageType = ''; // 'success' or 'error'

// Fetch customer data
$customerQuery = $conn->query("SELECT * FROM customers WHERE id = $customer_id");
if ($customerQuery->num_rows === 0) {
    header('Location: ../login.php');
    exit;
}
$customer = $customerQuery->fetch_assoc();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'update_info') {
        $first_name = $conn->real_escape_string(trim($_POST['first_name'] ?? ''));
        $middle_name = $conn->real_escape_string(trim($_POST['middle_name'] ?? ''));
        $last_name = $conn->real_escape_string(trim($_POST['last_name'] ?? ''));
        $email = $conn->real_escape_string(trim($_POST['email'] ?? ''));
        $phone = $conn->real_escape_string(trim($_POST['phone'] ?? ''));
        $address = $conn->real_escape_string(trim($_POST['address'] ?? ''));

        if (empty($first_name) || empty($last_name) || empty($email)) {
            $message = "Please fill in all required fields.";
            $messageType = 'error';
        } else {
            $updateQuery = "UPDATE customers SET 
                            first_name = '$first_name',
                            middle_name = '$middle_name',
                            last_name = '$last_name',
                            email = '$email',
                            phone = '$phone',
                            address = '$address',
                            updated_at = NOW()
                            WHERE id = $customer_id";

            if ($conn->query($updateQuery)) {
                $message = "Profile updated successfully!";
                $messageType = 'success';
                // Refresh customer data
                $customerQuery = $conn->query("SELECT * FROM customers WHERE id = $customer_id");
                $customer = $customerQuery->fetch_assoc();
            } else {
                $message = "Error updating profile. Please try again.";
                $messageType = 'error';
            }
        }
    } elseif ($_POST['action'] === 'change_password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $message = "Please fill in all password fields.";
            $messageType = 'error';
        } elseif (!password_verify($current_password, $customer['password'])) {
            $message = "Current password is incorrect.";
            $messageType = 'error';
        } elseif (strlen($new_password) < 6) {
            $message = "New password must be at least 6 characters.";
            $messageType = 'error';
        } elseif ($new_password !== $confirm_password) {
            $message = "New passwords do not match.";
            $messageType = 'error';
        } else {
            $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);
            $updateQuery = "UPDATE customers SET password = '$hashedPassword', updated_at = NOW() WHERE id = $customer_id";

            if ($conn->query($updateQuery)) {
                $message = "Password changed successfully!";
                $messageType = 'success';
            } else {
                $message = "Error changing password. Please try again.";
                $messageType = 'error';
            }
        }
    }
}

// Fetch customer's order history
$ordersQuery = $conn->query("
    SELECT o.*, COUNT(oi.id) as item_count
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    WHERE o.customer_id = $customer_id
    GROUP BY o.id
    ORDER BY o.order_date DESC
    LIMIT 10
");
$orders = [];
if ($ordersQuery) {
    while ($row = $ordersQuery->fetch_assoc()) {
        $orders[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flakies - My Profile</title>
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
        }

        nav {
            background: #2d2d2d;
            color: #f4e04d;
            padding: 1.5rem 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.3);
        }

        .logo {
            font-size: 1.8rem;
            font-weight: 800;
            letter-spacing: -1px;
        }

        .nav-links {
            display: flex;
            gap: 2rem;
            list-style: none;
            align-items: center;
        }

        .nav-links a {
            color: #f4e04d;
            text-decoration: none;
            font-weight: 500;
            transition: opacity 0.3s;
        }

        .nav-links a:hover {
            opacity: 0.8;
        }

        .logout-btn {
            background: linear-gradient(135deg, #f4e04d 0%, #d4a942 100%);
            color: #2d2d2d;
            padding: 0.6rem 1.5rem;
            border-radius: 50px;
            font-weight: 600;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: transform 0.3s;
        }

        .logout-btn:hover {
            transform: scale(1.05);
        }

        .cart-link {
            position: relative;
            display: inline-block;
        }

        .cart-badge {
            position: absolute;
            top: -8px;
            right: -12px;
            background: #f56565;
            color: white;
            font-size: 11px;
            font-weight: bold;
            padding: 2px 6px;
            border-radius: 50%;
            min-width: 20px;
            text-align: center;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .header {
            background: white;
            border-radius: 20px;
            padding: 2.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-info h1 {
            font-size: 2rem;
            color: #2d2d2d;
            margin-bottom: 0.5rem;
        }

        .header-info p {
            color: #666;
            font-size: 1rem;
        }

        .user-avatar {
            position: relative;
            width: 90px;
            height: 90px;
            border-radius: 50%;
            overflow: hidden;
            cursor: pointer;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            background: linear-gradient(135deg, #f4e04d 0%, #d4a942 100%);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .user-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .user-avatar:hover {
            transform: scale(1.05);
            box-shadow: 0 6px 20px rgba(212, 169, 66, 0.5);
        }

        .user-avatar::after {
            content: "📸 Change";
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            background: rgba(0, 0, 0, 0.6);
            color: white;
            text-align: center;
            font-size: 0.8rem;
            padding: 4px 0;
            opacity: 0;
            transition: opacity 0.3s ease;
            border-bottom-left-radius: 50%;
            border-bottom-right-radius: 50%;
        }

        .user-avatar:hover::after {
            opacity: 1;
        }

        .message {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }

        .message.success {
            background: #e6ffe6;
            border-left: 4px solid #48bb78;
            color: #22543d;
        }

        .message.error {
            background: #ffe6e6;
            border-left: 4px solid #f56565;
            color: #742a2a;
        }

        .content-wrapper {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .section {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        }

        .section h2 {
            font-size: 1.5rem;
            color: #2d2d2d;
            margin-bottom: 1.5rem;
            border-bottom: 3px solid #f4e04d;
            padding-bottom: 1rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .form-row.full {
            grid-template-columns: 1fr;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #2d2d2d;
            font-size: 0.95rem;
        }

        input,
        textarea {
            width: 100%;
            padding: 0.8rem;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-family: inherit;
            font-size: 1rem;
            color: #2d2d2d;
            transition: border-color 0.3s;
        }

        input:focus,
        textarea:focus {
            outline: none;
            border-color: #d4a942;
            box-shadow: 0 0 0 3px rgba(212, 169, 66, 0.1);
        }

        textarea {
            resize: vertical;
            min-height: 80px;
        }

        .required {
            color: #f56565;
        }

        .submit-btn {
            background: linear-gradient(135deg, #f4e04d 0%, #d4a942 100%);
            color: #2d2d2d;
            padding: 0.8rem 2rem;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            width: 100%;
        }

        .submit-btn:hover {
            transform: scale(1.02);
            box-shadow: 0 5px 15px rgba(212, 169, 66, 0.4);
        }

        .info-group {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .info-item {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 10px;
        }

        .info-label {
            font-weight: 600;
            color: #d4a942;
            font-size: 0.85rem;
            text-transform: uppercase;
            margin-bottom: 0.3rem;
        }

        .info-value {
            color: #2d2d2d;
            font-size: 1.05rem;
        }

        .orders-section {
            grid-column: 1 / -1;
        }

        .order-card {
            background: #f8f9fa;
            border-left: 4px solid #d4a942;
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            transition: all 0.3s;
        }

        .order-card:hover {
            background: #f0f0f0;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.8rem;
        }

        .order-number {
            font-weight: 800;
            color: #2d2d2d;
            font-size: 1.1rem;
        }

        .order-status {
            display: inline-block;
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
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

        .status-completed {
            background: #e8e8e8;
            color: #666;
        }

        .order-details {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 1rem;
            font-size: 0.9rem;
            color: #666;
        }

        .order-detail {
            display: flex;
            justify-content: space-between;
        }

        .order-detail strong {
            color: #2d2d2d;
        }

        .empty-orders {
            text-align: center;
            padding: 2rem;
            color: #666;
        }

        @media (max-width: 768px) {
            .content-wrapper {
                grid-template-columns: 1fr;
            }

            .orders-section {
                grid-column: 1;
            }

            .header {
                flex-direction: column;
                text-align: center;
                gap: 1.5rem;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .info-group {
                grid-template-columns: 1fr;
            }

            .order-details {
                grid-template-columns: 1fr;
            }

            .nav-links {
                gap: 1rem;
                font-size: 0.9rem;
            }
        }
    </style>
</head>

<body>
    <nav>
        <div class="logo">🌺 Flakies</div>
        <ul class="nav-links">
            <li><a href="../index.html">Home</a></li>
            <li><a href="menu.php">Menu</a></li>
            <li><a href="about.php">About</a></li>
            <li>
                <a href="cart.php" class="cart-link">
                    🛒
                    <?php if ($cart_count > 0): ?>
                        <span class="cart-badge"><?php echo $cart_count; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li><button class="logout-btn" onclick="logout()">Logout</button></li>
        </ul>
    </nav>

    <div class="container">
        <div class="header">
            <div class="header-info">
                <h1>Welcome, <?php echo htmlspecialchars($customer['first_name']); ?>! 👋</h1>
                <p>Member since <?php echo date('F Y', strtotime($customer['created_at'])); ?></p>
            </div>
                <div class="user-avatar">
                    <form id="avatarForm" action="upload_avatar.php" method="POST" enctype="multipart/form-data">
                        <label for="avatarInput">
                            <?php if (!empty($customer['profile_picture'])): ?>
                                <img src="../assets/pictures/<?php echo htmlspecialchars($customer['profile_picture']); ?>" 
                                    alt="Profile Picture" 
                                    style="width: 60px; height: 60px; border-radius: 50%; object-fit: cover; cursor: pointer;">
                            <?php else: ?>
                                <div style="width: 60px; height: 60px; border-radius: 50%; background-color: #ccc; display: flex; align-items: center; justify-content: center; font-size: 28px; cursor: pointer;">
                                    👤
                                </div>
                            <?php endif; ?>
                        </label>
                        <input type="file" name="avatar" id="avatarInput" accept="image/*" style="display: none;" onchange="document.getElementById('avatarForm').submit();">
                    </form>
                </div>
        </div>

        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="content-wrapper">
            <!-- Basic Information Section -->
            <div class="section">
                <h2>📋 Basic Information</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="update_info">

                    <div class="form-row">
                        <div class="form-group">
                            <label for="first_name">First Name <span class="required">*</span></label>
                            <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($customer['first_name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="middle_name">Middle Name</label>
                            <input type="text" id="middle_name" name="middle_name" value="<?php echo htmlspecialchars($customer['middle_name'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="last_name">Last Name <span class="required">*</span></label>
                        <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($customer['last_name']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email <span class="required">*</span></label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($customer['email']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($customer['phone'] ?? ''); ?>" placeholder="+63 917 000 0000">
                    </div>

                    <div class="form-group form-row full">
                        <label for="address">Address</label>
                        <textarea id="address" name="address" placeholder="Your delivery address"><?php echo htmlspecialchars($customer['address'] ?? ''); ?></textarea>
                    </div>

                    <button type="submit" class="submit-btn">💾 Save Changes</button>
                </form>
            </div>

            <!-- Password Management Section -->
            <div class="section">
                <h2>🔒 Password Management</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="change_password">

                    <div class="form-group">
                        <label for="current_password">Current Password <span class="required">*</span></label>
                        <input type="password" id="current_password" name="current_password" required>
                    </div>

                    <div class="form-group">
                        <label for="new_password">New Password <span class="required">*</span></label>
                        <input type="password" id="new_password" name="new_password" placeholder="At least 6 characters" required>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password <span class="required">*</span></label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>

                    <button type="submit" class="submit-btn">🔐 Change Password</button>
                </form>

                <div style="margin-top: 2rem; padding-top: 2rem; border-top: 2px solid #f0f0f0;">
                    <h3 style="color: #2d2d2d; font-size: 1rem; margin-bottom: 0.5rem;">👤 Account Info</h3>
                    <div class="info-group" style="margin-bottom: 0;">
                        <div class="info-item">
                            <div class="info-label">Username</div>
                            <div class="info-value"><?php echo htmlspecialchars($customer['username']); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Member Since</div>
                            <div class="info-value"><?php echo date('M d, Y', strtotime($customer['created_at'])); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order History Section -->
            <div class="section orders-section">
                <h2>📦 Order History</h2>

                <?php if (empty($orders)): ?>
                    <div class="empty-orders">
                        <div style="font-size: 2rem; margin-bottom: 0.5rem;">🛒</div>
                        <p>No orders yet. Start shopping to see your order history!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($orders as $order): ?>
                        <div class="order-card">
                            <div class="order-header">
                                <div class="order-number">Order #<?php echo str_pad($order['id'], 3, '0', STR_PAD_LEFT); ?></div>
                                <span class="order-status status-<?php echo $order['status']; ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                            </div>
                            <div class="order-details">
                                <div class="order-detail">
                                    <strong>Date:</strong>
                                    <span><?php echo date('M d, Y', strtotime($order['created_at'])); ?></span>
                                </div>
                                <div class="order-detail">
                                    <strong>Items:</strong>
                                    <span><?php echo $order['item_count']; ?> item<?php echo $order['item_count'] !== 1 ? 's' : ''; ?></span>
                                </div>
                                <div class="order-detail">
                                    <strong>Total:</strong>
                                    <span>₱<?php echo number_format($order['total'], 2); ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function logout() {
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = '../login/logout.php';
            }
        }
    </script>
</body>

</html>