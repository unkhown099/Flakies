<?php 
require_once '../config/db_connect.php';
session_start();

$customer_id = $_SESSION['customer_id'] ?? null;
$message = '';
$messageType = '';

// Handle cart actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $cart_id = intval($_POST['product_id']);
    $quantity = intval($_POST['quantity'] ?? 1);

    if ($_POST['action'] === 'update') {
        if ($quantity > 0) {
            $conn->query("UPDATE cart SET quantity = $quantity WHERE customer_id = $customer_id AND id = $cart_id");
            $message = "Cart updated!";
        } else {
            $conn->query("DELETE FROM cart WHERE customer_id = $customer_id AND id = $cart_id");
            $message = "Product removed!";
        }
        $messageType = 'success';
    } elseif ($_POST['action'] === 'remove') {
        $conn->query("DELETE FROM cart WHERE customer_id = $customer_id AND id = $cart_id");
        $message = "Product removed from cart!";
        $messageType = 'success';
    }
}

// Fetch cart items
$cartQuery = $conn->query("CALL GetCartItems($customer_id)");

$cartItems = [];
$subtotal = 0;
if ($cartQuery) {
    while ($row = $cartQuery->fetch_assoc()) {
        $cartItems[] = $row;
        $subtotal += $row['price'] * $row['quantity'];
    }
    $cartQuery->free();
    $conn->next_result(); // Important after stored procedure
}

$total = $subtotal;
$deliveryFee = 10;
$finalTotal = $total + $deliveryFee;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flakies - Shopping Cart</title>
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
            display: flex;
            align-items: center;
            gap: 0.5rem;
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

        .cart-btn {
            background: linear-gradient(135deg, #f4e04d 0%, #d4a942 100%);
            color: #2d2d2d;
            padding: 0.6rem 1.5rem;
            border-radius: 50px;
            font-weight: 600;
            text-decoration: none;
            transition: transform 0.3s;
            border: none;
            cursor: pointer;
        }

        .cart-btn:hover {
            transform: scale(1.05);
        }

        .profile-link {
        display: inline-block;
        width: 35px;
        height: 35px;
        border-radius: 50%;
        overflow: hidden;
        margin-left: 10px;
        }

        .profile-link .profile-pic {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
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

        .content-wrapper {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 2rem;
        }

        .cart-items-section {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
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

        .cart-item {
            display: grid;
            grid-template-columns: 80px 1fr auto;
            gap: 1.5rem;
            padding: 1.5rem;
            border-bottom: 2px solid #f0f0f0;
            align-items: center;
        }

        .cart-item:last-child {
            border-bottom: none;
        }

        .item-emoji {
            width: 80px;
            height: 80px;
            border-radius: 15px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f4e04d; /* fallback background */
        }

        .item-emoji img {
            width: 100%;
            height: 100%;
            object-fit: cover; /* keeps aspect ratio, crops if necessary */
        }


        .item-details h3 {
            font-size: 1.1rem;
            color: #2d2d2d;
            margin-bottom: 0.3rem;
        }

        .item-details p {
            font-size: 0.85rem;
            color: #666;
            margin-bottom: 0.8rem;
        }

        .item-price {
            color: #d4a942;
            font-weight: 700;
            font-size: 1rem;
        }

        .item-controls {
            display: flex;
            gap: 0.5rem;
            align-items: center;
            margin-top: 0.8rem;
        }

        .qty-input {
            width: 60px;
            padding: 0.4rem;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            text-align: center;
            font-weight: 600;
            color: #2d2d2d;
        }

        .qty-input:focus {
            outline: none;
            border-color: #d4a942;
        }

        .qty-btn {
            background: #f0f0f0;
            border: none;
            padding: 0.4rem 0.8rem;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }

        .qty-btn:hover {
            background: #d4a942;
            color: white;
        }

        .remove-btn {
            background: #ffe6e6;
            color: #f56565;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            font-size: 0.9rem;
        }

        .remove-btn:hover {
            background: #f56565;
            color: white;
        }

        .item-right {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 0.5rem;
        }

        .item-total {
            font-size: 1.2rem;
            font-weight: 800;
            color: #d4a942;
        }

        .empty-cart {
            text-align: center;
            padding: 3rem;
            color: #666;
        }

        .empty-cart-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }

        .empty-cart a {
            display: inline-block;
            margin-top: 1rem;
            background: linear-gradient(135deg, #f4e04d 0%, #d4a942 100%);
            color: #2d2d2d;
            padding: 0.8rem 2rem;
            border-radius: 15px;
            text-decoration: none;
            font-weight: 700;
            transition: transform 0.3s;
        }

        .empty-cart a:hover {
            transform: scale(1.05);
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

        .checkout-btn {
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

        .checkout-btn:hover {
            transform: scale(1.02);
            box-shadow: 0 5px 15px rgba(212, 169, 66, 0.4);
        }

        .checkout-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .continue-shopping-btn {
            width: 100%;
            background: #2d2d2d;
            color: #f4e04d;
            border: none;
            padding: 0.8rem;
            border-radius: 15px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            margin-top: 0.5rem;
            text-decoration: none;
            display: block;
            text-align: center;
            transition: all 0.3s;
        }

        .continue-shopping-btn:hover {
            opacity: 0.8;
        }

        @media (max-width: 768px) {
            .content-wrapper {
                grid-template-columns: 1fr;
            }

            .order-summary {
                position: static;
            }

            .cart-item {
                grid-template-columns: 60px 1fr;
            }

            .item-right {
                grid-column: 1 / -1;
                margin-top: 1rem;
            }
        }
    </style>
</head>
<body>
    <nav>
        <div class="logo">
            <img src="../assets/pictures/45b0e7c9-8bc1-4ef3-bac2-cfc07174d613.png" alt="Flakies Logo" style="height:40px; margin-right:10px;">
            Flakies
        </div>
        <ul class="nav-links">
            <li><a href="../index.php">Home</a></li>
            <li><a href="menu.php">Menu</a></li>
            <li><a href="about.php">About</a></li>
            <li><a href="contact.php">Contact</a></li>
            <li><button class="cart-btn">🛒 Cart (<?php echo count($cartItems); ?>)</button></li>
            <li><a href="../login/logout.php" class="auth-btn login-btn">Logout</a></li>
            <li>
                <a href="./profile.php" class="profile-link">
                    <img src="<?php echo $_SESSION['profile_picture'] ?? '../assets/pictures/default-profile.png'; ?>" 
                         alt="Profile" class="profile-pic">
                </a>
            </li>
        </ul>
    </nav>

    <div class="container">
        <div class="header">
            <h1>🛒 Your Shopping Cart</h1>
            <p><?php echo count($cartItems); ?> item(s) in your cart</p>
        </div>

        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if (empty($cartItems)): ?>
            <div class="cart-items-section">
                <div class="empty-cart">
                    <div class="empty-cart-icon">🛒</div>
                    <h2>Your Cart is Empty</h2>
                    <p>Start adding some delicious Filipino treats!</p>
                    <a href="menu.php">Browse Menu</a>
                </div>
            </div>
        <?php else: ?>
                  <div class="content-wrapper">
            <div class="cart-items-section">
                <?php foreach ($cartItems as $item): ?>
                    <div class="cart-item">
                        <div class="item-emoji">
                            <img src="../cashier/images_path/<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                        </div>
                        <div class="item-details">
                            <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                            <p><?php echo htmlspecialchars(substr($item['description'], 0, 50)); ?>...</p>
                            <div class="item-price">₱<?php echo number_format($item['price'], 2); ?> each</div>
                            <div class="item-controls">
                                <form method="POST" style="display:flex;gap:0.5rem;align-items:center;">
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="product_id" value="<?php echo $item['cart_id']; ?>">
                                    <button type="button" class="qty-btn" onclick="decreaseQty(this)">−</button>
                                    <input type="number" name="quantity" class="qty-input" value="<?php echo $item['quantity']; ?>" min="1" max="50">
                                    <button type="button" class="qty-btn" onclick="increaseQty(this)">+</button>
                                    <button type="submit" class="qty-btn" style="background:#d4a942;color:white;">Update</button>
                                </form>
                            </div>
                        </div>
                        <div class="item-right">
                            <div class="item-total">
                                ₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                            </div>
                            <form method="POST" style="width:100%;">
                                <input type="hidden" name="action" value="remove">
                                <input type="hidden" name="product_id" value="<?php echo $item['cart_id']; ?>">
                                <button type="submit" class="remove-btn">Remove</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Order Summary -->
            <div class="order-summary">
                <div class="summary-title">📋 Order Summary</div>
                
                <div class="summary-row">
                    <span>Subtotal:</span>
                    <span>₱<?php echo number_format($subtotal, 2); ?></span>
                </div>
                
                <div class="summary-row">
                    <span>Delivery Fee:</span>
                    <span>₱<?php echo number_format($deliveryFee, 2); ?></span>
                </div>
                
                <div class="summary-row total">
                    <span>Total:</span>
                    <span class="amount">₱<?php echo number_format($finalTotal, 2); ?></span>
                </div>

                <form action="checkout.php" method="post">
                    <button type="submit" class="checkout-btn">Proceed to Checkout</button>
                </form>
                <a href="menu.php" class="continue-shopping-btn">Continue Shopping</a>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script>
        function increaseQty(btn) {
            const input = btn.parentElement.querySelector('.qty-input');
            input.value = parseInt(input.value) + 1;
        }

        function decreaseQty(btn) {
            const input = btn.parentElement.querySelector('.qty-input');
            if (parseInt(input.value) > 1) {
                input.value = parseInt(input.value) - 1;
            }
        }
    </script>
</body>
</html>