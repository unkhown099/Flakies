<?php
// checkout.php - Flakies Checkout Page

// Include database connection
require_once '../config/db_connect.php';

// Start session
session_start();

// Check if customer is logged in
if (!isset($_SESSION['customer_id'])) {
    header('Location: ../login.php');
    exit;
}

$customer_id = $_SESSION['customer_id'];
$message = '';
$messageType = '';

// Fetch customer data
$customerQuery = $conn->query("SELECT * FROM customers WHERE id = $customer_id");
if ($customerQuery->num_rows === 0) {
    header('Location: ../login.php');
    exit;
}
$customer = $customerQuery->fetch_assoc();

// Fetch cart items
$cartQuery = $conn->query("
    SELECT c.id as cart_id, c.quantity, p.id as product_id, p.name, p.image, p.price, p.description
    FROM cart c
    JOIN products p ON c.product_id = p.id
    WHERE c.customer_id = $customer_id
    ORDER BY c.added_at DESC
");

$cartItems = [];
$subtotal = 0;

if ($cartQuery) {
    while ($row = $cartQuery->fetch_assoc()) {
        $cartItems[] = $row;
        $subtotal += $row['price'] * $row['quantity'];
    }
}

// If cart is empty, redirect to cart page
if (empty($cartItems)) {
    header('Location: cart.php');
    exit;
}

$tax = 0; // No tax
$deliveryFee = 50; // Fixed delivery fee
$total = $subtotal + $deliveryFee;

// Handle order placement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $delivery_address = $conn->real_escape_string(trim($_POST['delivery_address']));
    $phone = $conn->real_escape_string(trim($_POST['phone']));
    $payment_method = $conn->real_escape_string($_POST['payment_method']);
    $notes = $conn->real_escape_string(trim($_POST['notes'] ?? ''));

    if (empty($delivery_address) || empty($phone)) {
        $message = "Please fill in all required fields.";
        $messageType = 'error';
    } else {
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Insert order
            $orderQuery = "INSERT INTO orders (customer_id, total, status, delivery_address, phone, payment_method, notes, order_date, created_at) 
                          VALUES ($customer_id, $total, 'new', '$delivery_address', '$phone', '$payment_method', '$notes', NOW(), NOW())";
            
            if ($conn->query($orderQuery)) {
                $order_id = $conn->insert_id;
                
                // Insert order items from cart
                foreach ($cartItems as $item) {
                    $product_id = $item['product_id'];
                    $quantity = $item['quantity'];
                    $price = $item['price'];
                    
                    $itemQuery = "INSERT INTO order_items (order_id, product_id, quantity, price) 
                                 VALUES ($order_id, $product_id, $quantity, $price)";
                    $conn->query($itemQuery);
                }
                
                // Clear cart
                $conn->query("DELETE FROM cart WHERE customer_id = $customer_id");
                
                // Commit transaction
                $conn->commit();
                
                // Redirect to success page
                header("Location: order_success.php?order_id=$order_id");
                exit;
            }
        } catch (Exception $e) {
            $conn->rollback();
            $message = "Error placing order. Please try again.";
            $messageType = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flakies - Checkout</title>
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
            box-shadow: 0 2px 20px rgba(0,0,0,0.3);
            position: sticky;
            top: 0;
            z-index: 100;
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

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .header {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            color: #2d2d2d;
        }

        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }

        .header p {
            color: #666;
        }

        .message {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }

        .message.error {
            background: #ffe6e6;
            border-left: 4px solid #f56565;
            color: #742a2a;
        }

        .checkout-wrapper {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 2rem;
        }

        .checkout-form {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }

        .section-title {
            font-size: 1.5rem;
            color: #2d2d2d;
            margin-bottom: 1.5rem;
            border-bottom: 3px solid #f4e04d;
            padding-bottom: 1rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #2d2d2d;
            font-size: 0.95rem;
        }

        input, textarea, select {
            width: 100%;
            padding: 0.8rem;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-family: inherit;
            font-size: 1rem;
            color: #2d2d2d;
            transition: border-color 0.3s;
        }

        input:focus, textarea:focus, select:focus {
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

        .payment-methods {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }

        .payment-option {
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 1rem;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
        }

        .payment-option input[type="radio"] {
            display: none;
        }

        .payment-option:hover {
            border-color: #d4a942;
        }

        .payment-option input[type="radio"]:checked + label {
            border-color: #d4a942;
            background: #fff9e6;
        }

        .payment-option label {
            cursor: pointer;
            font-weight: 600;
            color: #2d2d2d;
            display: block;
        }

        .order-summary {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            height: fit-content;
            position: sticky;
            top: 100px;
        }

        .summary-title {
            font-size: 1.3rem;
            font-weight: 800;
            color: #2d2d2d;
            margin-bottom: 1.5rem;
            border-bottom: 2px solid #f4e04d;
            padding-bottom: 1rem;
        }

        .order-items {
            max-height: 300px;
            overflow-y: auto;
            margin-bottom: 1.5rem;
        }

        .order-item {
            display: flex;
            justify-content: space-between;
            padding: 0.8rem 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .item-details {
            flex: 1;
        }

        .item-name {
            font-weight: 600;
            color: #2d2d2d;
            margin-bottom: 0.3rem;
        }

        .item-qty {
            font-size: 0.85rem;
            color: #666;
        }

        .item-price {
            font-weight: 700;
            color: #d4a942;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 0.8rem 0;
            color: #666;
            font-weight: 500;
        }

        .summary-row.total {
            border-top: 2px solid #f0f0f0;
            padding-top: 1rem;
            margin-top: 1rem;
            font-size: 1.3rem;
            color: #2d2d2d;
            font-weight: 800;
        }

        .summary-row.total .amount {
            color: #d4a942;
        }

        .place-order-btn {
            width: 100%;
            background: linear-gradient(135deg, #f4e04d 0%, #d4a942 100%);
            color: #2d2d2d;
            border: none;
            padding: 1rem;
            border-radius: 15px;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            margin-top: 1.5rem;
            transition: all 0.3s;
        }

        .place-order-btn:hover {
            transform: scale(1.02);
            box-shadow: 0 5px 15px rgba(212, 169, 66, 0.4);
        }

        .back-to-cart {
            display: block;
            text-align: center;
            margin-top: 1rem;
            color: #666;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }

        .back-to-cart:hover {
            color: #2d2d2d;
        }

        @media (max-width: 768px) {
            .checkout-wrapper {
                grid-template-columns: 1fr;
            }

            .order-summary {
                position: static;
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
            <li><a href="cart.php">Cart</a></li>
            <li><a href="profile.php">Profile</a></li>
        </ul>
    </nav>

    <div class="container">
        <div class="header">
            <h1>🛒 Checkout</h1>
            <p>Complete your order information</p>
        </div>

        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="checkout-wrapper">
                <div class="checkout-form">
                    <!-- Delivery Information -->
                    <h2 class="section-title">📍 Delivery Information</h2>
                    
                    <div class="form-group">
                        <label for="customer_name">Full Name</label>
                        <input type="text" id="customer_name" value="<?php echo htmlspecialchars($customer['first_name'] . ' ' . ($customer['middle_name'] ? $customer['middle_name'] . ' ' : '') . $customer['last_name']); ?>" readonly>
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone Number <span class="required">*</span></label>
                        <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($customer['phone'] ?? ''); ?>" placeholder="+63 917 000 0000" required>
                    </div>

                    <div class="form-group">
                        <label for="delivery_address">Delivery Address <span class="required">*</span></label>
                        <textarea id="delivery_address" name="delivery_address" placeholder="Complete delivery address" required><?php echo htmlspecialchars($customer['address'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="notes">Order Notes (Optional)</label>
                        <textarea id="notes" name="notes" placeholder="Any special instructions for your order"></textarea>
                    </div>

                    <!-- Payment Method -->
                    <h2 class="section-title">💳 Payment Method</h2>
                    
                    <div class="payment-methods">
                        <div class="payment-option">
                            <input type="radio" id="cod" name="payment_method" value="cod" checked>
                            <label for="cod">
                                💵<br>Cash on Delivery
                            </label>
                        </div>
                        <div class="payment-option">
                            <input type="radio" id="gcash" name="payment_method" value="gcash">
                            <label for="gcash">
                                📱<br>GCash
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Order Summary -->
                <div class="order-summary">
                    <div class="summary-title">📋 Order Summary</div>
                    
                    <div class="order-items">
                        <?php foreach ($cartItems as $item): ?>
                            <div class="order-item">
                                <div class="item-details">
                                    <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                    <div class="item-qty">Qty: <?php echo $item['quantity']; ?> × ₱<?php echo number_format($item['price'], 2); ?></div>
                                </div>
                                <div class="item-price">
                                    ₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="summary-row">
                        <span>Subtotal:</span>
                        <span>₱<?php echo number_format($subtotal, 2); ?></span>
                    </div>
                    
                    <div class="summary-row">
                        <span>Tax (12%):</span>
                        <span>₱<?php echo number_format($tax, 2); ?></span>
                    </div>
                    
                    <div class="summary-row">
                        <span>Delivery Fee:</span>
                        <span>₱<?php echo number_format($deliveryFee, 2); ?></span>
                    </div>
                    
                    <div class="summary-row total">
                        <span>Total:</span>
                        <span class="amount">₱<?php echo number_format($total, 2); ?></span>
                    </div>

                    <button type="submit" name="place_order" class="place-order-btn">
                        🎉 Place Order
                    </button>
                    
                    <a href="cart.php" class="back-to-cart">← Back to Cart</a>
                </div>
            </div>
        </form>
    </div>

    <script>
        // Style selected payment method
        document.querySelectorAll('.payment-option').forEach(option => {
            const radio = option.querySelector('input[type="radio"]');
            const label = option.querySelector('label');
            
            option.addEventListener('click', () => {
                radio.checked = true;
                updatePaymentSelection();
            });
            
            radio.addEventListener('change', updatePaymentSelection);
        });

        function updatePaymentSelection() {
            document.querySelectorAll('.payment-option').forEach(opt => {
                const radio = opt.querySelector('input[type="radio"]');
                if (radio.checked) {
                    opt.style.borderColor = '#d4a942';
                    opt.style.background = '#fff9e6';
                } else {
                    opt.style.borderColor = '#e0e0e0';
                    opt.style.background = 'white';
                }
            });
        }

        updatePaymentSelection();
    </script>
</body>
</html>