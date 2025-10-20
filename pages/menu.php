<?php
session_start();
require_once '../config/db_connect.php';

// Fetch all products from database
$result = $conn->query("CALL GetAvailableProducts()");
$products = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    $result->free();          // free the result set
    $conn->next_result();     // flush multi-query buffer
}

// Group products by category
$productsByCategory = [];
foreach ($products as $product) {
    $category = $product['category'] ?? 'Other';
    if (!isset($productsByCategory[$category])) {
        $productsByCategory[$category] = [];
    }
    $productsByCategory[$category][] = $product;
}

// Get customer ID if logged in
$customer_id = $_SESSION['customer_id'] ?? null;

// Fetch number of items in cart
$cartCount = 0;
if ($customer_id) {
    $cartQuery = $conn->query("SELECT SUM(quantity) as total_qty FROM cart WHERE customer_id = $customer_id");
    if ($cartQuery && $row = $cartQuery->fetch_assoc()) {
        $cartCount = $row['total_qty'] ?? 0;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flakies - Menu</title>
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
            opacity: 0;
            transform: translateY(15px);
            transition: opacity 0.6s ease, transform 0.6s ease;
        }

        /* When the page is fully loaded */
        body.loaded {
            opacity: 1;
            transform: translateY(0);
        }

        /* Optional: make link clicks fade out smoothly */
        .fade-out {
            opacity: 0;
            transform: translateY(10px);
            transition: opacity 0.4s ease, transform 0.4s ease;
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

    .register-btn {
        background: transparent;
        color: #2d2d2d;
    }

    .register-btn:hover {
        background: #f4e04d;
        color: #2d2d2d;
    }


        .cart-btn {
            background: #2d2d2d;
            color: #f4e04d;
            padding: 0.6rem 1.5rem;
            border-radius: 50px;
            font-weight: 600;
            text-decoration: none;
            transition: transform 0.3s;
            border: 2px solid #f4e04d;
            cursor: pointer;
        }

        .cart-btn:hover {
            transform: scale(1.05);
            background: #f4e04d;
            color: #2d2d2d;
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
            padding: 3rem 2rem;
        }

        .header {
            text-align: center;
            margin-bottom: 3rem;
            color: #2d2d2d;
        }

        .header h1 {
            font-size: 3.5rem;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .header p {
            font-size: 1.2rem;
            opacity: 0.9;
        }

        .category-section {
            margin-bottom: 4rem;
        }

        .category-title {
            font-size: 2rem;
            color: #2d2d2d;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 3px solid #2d2d2d;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 2rem;
        }

        .product-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .product-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 50px rgba(0,0,0,0.25);
            border-color: #f4e04d;
        }

        .product-image {
            width: 100%;
            height: 200px;
            background: linear-gradient(135deg, #f4e04d 0%, #d4a942 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 5rem;
            position: relative;
            overflow: hidden;
        }

        .product-image::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, transparent 30%, rgba(255,255,255,0.1) 50%, transparent 70%);
            animation: shine 3s infinite;
        }

        @keyframes shine {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }

        .product-info {
            padding: 1.5rem;
        }

        .product-name {
            font-size: 1.3rem;
            font-weight: 800;
            color: #2d2d2d;
            margin-bottom: 0.5rem;
        }

        .product-description {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 1rem;
            line-height: 1.5;
            min-height: 2.7rem;
        }

        .product-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 2px solid #f0f0f0;
            padding-top: 1rem;
        }

        .product-price {
            font-size: 1.8rem;
            font-weight: 800;
            color: #d4a942;
        }

        .add-to-cart-btn {
            background: linear-gradient(135deg, #f4e04d 0%, #d4a942 100%);
            color: #2d2d2d;
            border: none;
            padding: 0.6rem 1.2rem;
            border-radius: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 0.9rem;
        }

        .add-to-cart-btn:hover {
            transform: scale(1.08);
            box-shadow: 0 5px 15px rgba(212, 169, 66, 0.4);
        }

        .add-to-cart-btn:active {
            transform: scale(0.95);
        }

        .empty-menu {
            text-align: center;
            padding: 4rem 2rem;
            color: #2d2d2d;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .empty-menu-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }

        @media (max-width: 768px) {
            .header h1 {
                font-size: 2.5rem;
            }

            .category-title {
                font-size: 1.5rem;
            }

            .products-grid {
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
        <div class="logo">
            <img src="../assets/pictures/45b0e7c9-8bc1-4ef3-bac2-cfc07174d613.png" alt="Flakies Logo" style="height:40px; margin-right:10px;">
            Flakies
        </div>
        <ul class="nav-links">
            <li><a href="../index.php">Home</a></li>
            <li><a href="./about.php">About</a></li>
            <li><a href="./contact.php">Contact</a></li>

            <?php if (isset($_SESSION['customer_id'])): ?>
                <li>
                    <a href="./cart.php" class="auth-btn cart-btn">
                        🛒 Cart (<?php echo $cartCount; ?>)
                    </a>
                </li>
                <li><a href="/login/logout.php" class="auth-btn login-btn">Logout</a></li>
                <li>
                <a href="./profile.php" class="profile-link">
                    <img src="<?php echo $_SESSION['profile_picture'] ?? '../assets/pictures/default-profile.png'; ?>" 
                         alt="Profile" class="profile-pic">
                </a>
            </li>
            <?php else: ?>
                <li><a href="../login/login.php" class="auth-btn login-btn">Login</a></li>
                <li><a href="../login/register.php" class="auth-btn register-btn">Register</a></li>
            <?php endif; ?>
        </ul>
    </nav>

    <div class="container">
        <div class="header">
            <h1>Our Menu</h1>
            <p>Sarap ng Pilipinas - Authentic Filipino Delicacies 🇵🇭</p>
        </div>

        <?php if (empty($productsByCategory)): ?>
            <div class="empty-menu">
                <div class="empty-menu-icon">📋</div>
                <h2>Menu Coming Soon</h2>
                <p>We're preparing our delicious offerings for you!</p>
            </div>
        <?php else: ?>
            <?php foreach ($productsByCategory as $category => $categoryProducts): ?>
                <div class="category-section">
                    <h2 class="category-title"><?php echo htmlspecialchars($category); ?></h2>
                    <div class="products-grid">
                        <?php foreach ($categoryProducts as $product): ?>
                            <div class="product-card">
                                <div class="product-image">
                                    <?php echo $product['emoji']; ?>
                                </div>
                                <div class="product-info">
                                    <div class="product-name">
                                        <?php echo htmlspecialchars($product['name']); ?>
                                    </div>
                                    <div class="product-description">
                                        <?php echo htmlspecialchars($product['description'] ?? 'Premium Filipino delicacy'); ?>
                                    </div>
                                    <div class="product-footer">
                                        <span class="product-price">
                                            ₱<?php echo number_format($product['price'], 2); ?>
                                        </span>
                                        <button class="add-to-cart-btn" onclick="addToCart(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars($product['name']); ?>')">
                                            Add
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script>
        function addToCart(productId, productName) {
            alert(`Added "${productName}" to cart!`);
            // TODO: Implement cart functionality
            console.log('Added product:', productId, productName);
        }
    </script>
    <script>
    // When the page loads, fade it in
    window.addEventListener("load", () => {
        document.body.classList.add("loaded");
    });

    // When clicking a link, fade out before going to the next page
    document.querySelectorAll("a").forEach(link => {
        const url = link.getAttribute("href");
        if (url && !url.startsWith("#") && !url.startsWith("javascript:")) {
            link.addEventListener("click", e => {
                e.preventDefault();
                document.body.classList.remove("loaded");
                document.body.classList.add("fade-out");
                setTimeout(() => {
                    window.location.href = url;
                }, 400); // matches CSS transition
            });
        }
    });
</script>
</body>
</html>