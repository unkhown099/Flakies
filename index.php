<?php
session_start();
include("config/db_connect.php");

// Fetch products dynamically
$sql = "
    SELECT 
        p.id,
        p.name,
        p.description,
        p.price,
        p.stock,
        p.image,
        SUM(c.quantity) AS total_sold
    FROM products p
    LEFT JOIN cart c ON p.id = c.product_id   -- or use order_items if you track completed sales
    GROUP BY p.id
    ORDER BY total_sold DESC
    LIMIT 3
";
$result = $conn->query($sql);

// Get customer ID if logged in
$customer_id = $_SESSION['customer_id'] ?? null;

// Fetch number of items in cart if logged in
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
    <title>Flakies - Authentic Filipino Delights</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            line-height: 1.6;
            color: #333;
            overflow-x: hidden;
        }

        .hero {
            background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
            color: #2d3748;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '🍧 🍚 🥥 🍨 🌺 ✨';
            position: absolute;
            font-size: 5rem;
            opacity: 0.15;
            animation: float 25s infinite ease-in-out;
            white-space: nowrap;
        }

        @keyframes float {

            0%,
            100% {
                transform: translate(0, 0) rotate(0deg);
            }

            25% {
                transform: translate(50px, -30px) rotate(5deg);
            }

            50% {
                transform: translate(0, -50px) rotate(-5deg);
            }

            75% {
                transform: translate(-50px, -30px) rotate(5deg);
            }
        }

        nav {
            background: #2d2d2d;
            color: #f4e04d;
            padding: 1.2rem 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 20px rgba(0,0,0,0.3);
            position: sticky;
            top: 0;
            z-index: 100;
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
            color: #667eea;
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


        .hero {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-direction: row-reverse;
            /* 👈 swaps sides */
            padding: 6rem 8%;
            background: linear-gradient(135deg, #d9ed42 0%, #d39e2a 60%, #e0d979ff 100%);
            min-height: 100vh;
            color: #2d2d2d;
            overflow: hidden;
        }


        .hero-content {
            flex: 1;
            width: 600px;
            animation: fadeInLeft 1.2s ease-out;
            justify-content: end;
        }

        .hero-content h1 {
            font-size: 7rem;
            font-weight: 800;
            line-height: 1.1;
            color: #2d2d2d;
            margin-bottom: 1.5rem;
        }

        .hero-content span {
            color: #fff;
            background: linear-gradient(135deg, #2d2d2d, #444);
            padding: 0.3rem 0.8rem;
            border-radius: 8px;
        }

        .hero-content p {
            font-size: 2.0rem;
            color: #4a4a4a;
            margin-bottom: 2rem;
        }

        .hero-buttons {
            display: flex;
            gap: 1rem;
        }

        .hero-buttons .btn {
            padding: 0.9rem 2.2rem;
            border: none;
            border-radius: 50px;
            font-size: 1rem;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .hero-buttons .btn-primary {
            background: #2d2d2d;
            color: white;
        }

        .hero-buttons .btn-primary:hover {
            background: #444;
            transform: translateY(-3px);
        }

        .hero-buttons .btn-secondary {
            background: white;
            color: #2d2d2d;
            border: 2px solid #2d2d2d;
        }

        .hero-buttons .btn-secondary:hover {
            background: #2d2d2d;
            color: white;
        }

        .hero-image {
            flex: 1;
            display: flex;

            animation: fadeInRight 1.2s ease-out;
            margin-left: 80px;
        }

        .hero-image img {
            width: 90%;
            max-width: 600px;
            border-radius: 50%;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
            border: 10px solid #fff;
            object-fit: cover;

        }

        @keyframes fadeInLeft {
            from {
                opacity: 0;
                transform: translateX(-50px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes fadeInRight {
            from {
                opacity: 0;
                transform: translateX(50px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @media (max-width: 900px) {
            .hero {
                flex-direction: column-reverse;
                text-align: center;
                padding: 4rem 5%;
            }

            .hero-content {
                max-width: 100%;
            }

            .hero-image img {
                max-width: 320px;
                margin-bottom: 2rem;
            }

            .hero-content h1 {
                font-size: 3rem;
            }
        }

        .cta-buttons {
            display: flex;
            gap: 1.5rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            padding: 1rem 2.5rem;
            font-size: 1.1rem;
            font-weight: 600;
            border: none;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: white;
            color: #667eea;
            border: 2px solid #667eea;
        }

        .btn-secondary:hover {
            background: #667eea;
            color: white;
        }

        .products {
            padding: 6rem 5%;
            background: #fff;
        }

        .products h2 {
            text-align: center;
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #2d3748;
        }

        .products-subtitle {
            text-align: center;
            font-size: 1.2rem;
            color: #718096;
            margin-bottom: 4rem;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 3rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .product-card {
            background: white;
            border-radius: 25px;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
        }

        .product-card:hover {
            transform: translateY(-15px);
            box-shadow: 0 20px 60px rgba(102, 126, 234, 0.15);
        }

        .product-image {
            width: 100%;
            height: 250px;
            background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 6rem;
        }

        .product-info {
            padding: 2rem;
        }

        .product-info h3 {
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
            color: #2d3748;
        }

        .product-info p {
            color: #718096;
            margin-bottom: 1.5rem;
            line-height: 1.7;
        }

        .product-price {
            font-size: 1.5rem;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 1rem;
        }

        .order-btn {
            width: 100%;
            background: #f4e04d;
            color: #2d2d2d;
            padding: 0.9rem;
            border: none;
            border-radius: 15px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .order-btn:hover {
            transform: scale(1.02);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.3);
        }

        .features {
            padding: 6rem 5%;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        }

        .features h2 {
            text-align: center;
            font-size: 3rem;
            margin-bottom: 4rem;
            color: #2d3748;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2.5rem;
            max-width: 1000px;
            margin: 0 auto;
        }

        .feature-item {
            text-align: center;
            padding: 2rem;
        }

        .feature-icon {
            font-size: 3.5rem;
            margin-bottom: 1rem;
        }

        .feature-item h3 {
            font-size: 1.3rem;
            margin-bottom: 0.8rem;
            color: #2d3748;
        }

        .feature-item p {
            color: #718096;
            line-height: 1.6;
        }

        footer {
            background: #2d2d2d;
            color: #f4e04d;
            padding: 3rem 5%;
            text-align: center;
        }

        footer p {
            opacity: 0.9;
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
            border-color: #2d2d2d;
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

        @media (max-width: 768px) {
            h1 {
                font-size: 2.8rem;
            }

            .hero p {
                font-size: 1.1rem;
            }

            .nav-links {
                gap: 1rem;
                font-size: 0.9rem;
            }

            .products h2,
            .features h2 {
                font-size: 2.2rem;
            }

            .products-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <nav>
    <div class="logo">
        <img src="assets/pictures/45b0e7c9-8bc1-4ef3-bac2-cfc07174d613.png" alt="Flakies Logo">
        <span>Flakies</span>
    </div>
    <ul class="nav-links">
        <li><a href="pages/menu.php">Menu</a></li>
        <li><a href="pages/about.php">About</a></li>
        <li><a href="pages/contact.php">Contact</a></li>

        <?php if (isset($_SESSION['customer_id'])): ?>
            <li>
                <a href="pages/cart.php" class="auth-btn cart-btn">
                    🛒 Cart (<?php echo $cartCount; ?>)
                </a>
            </li>
            <li><a href="login/logout.php" class="auth-btn login-btn">Logout</a></li>
            <li>
                <a href="pages/profile.php" class="profile-link">
                    <img src="<?php echo $_SESSION['profile_picture'] ?? 'assets/pictures/default-profile.png'; ?>" 
                         alt="Profile" class="profile-pic">
                </a>
            </li>
        <?php else: ?>
            <li><a href="login/login.php" class="auth-btn login-btn">Login</a></li>
            <li><a href="login/register.php" class="auth-btn register-btn">Register</a></li>
        <?php endif; ?>
    </ul>
</nav>

    <section class="hero">
        <div class="hero-content">
            <h1><span>Flakies</span> — Sarap ng Pilipinas! 🇵🇭</h1>
            <p>Bringing the best of Filipino street and home delicacies to your doorstep. Taste nostalgia, love, and warmth in every bite.</p>
            <div class="hero-buttons">
                <a href="./pages/menu.php">
                <button class="btn btn-secondary" >View Menu</button>
                </a>
            </div>
        </div>
        <div class="hero-image">
            <img src="assets/pictures/45b0e7c9-8bc1-4ef3-bac2-cfc07174d613.png" alt="Flakies Featured Dish">
        </div>
        </section>

        <section class="products" id="products">
            <h2>Our Specialties</h2>
            <p class="products-subtitle">Handcrafted with love, delivered with care</p>

            <div class="products-grid">
                <?php
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo "<div class='product-card'>
                                <div class='product-image'>";
                        if (!empty($row['image'])) {
                            echo "<img src='cashier/images_path/" . $row['image'] . "' 
                                    alt='" . htmlspecialchars($row['name'], ENT_QUOTES) . "' 
                                    style='width:100%;height:250px;object-fit:cover;border-radius:10px;'>";
                        } else {
                            echo "🍴"; // fallback emoji
                        }
                        echo "</div>
                            <div class='product-info'>
                                <h3>" . htmlspecialchars($row['name']) . "</h3>
                                <p>" . htmlspecialchars($row['description']) . "</p>
                                <div class='product-price'>₱" . $row['price'] . "</div>
                                <p style='color:gray;font-size:0.9rem;'>Stock: " . $row['stock'] . "</p>
                                <a href='pages/menu.php'><button class='order-btn'>View on menu</button></a>
                            </div>
                            </div>";
                    }
                } else {
                    echo "<p style='text-align:center;'>No products available yet.</p>";
                }
                ?>
                </div>
        </section>

        <section class="features" id="features">
            <h2>Why Choose Flakies?</h2>

            <div class="features-grid">
                <div class="feature-item">
                    <div class="feature-icon">✨</div>
                    <h3>Authentic Flavors</h3>
                    <p>Traditional recipes passed down through generations</p>
                </div>

                <div class="feature-item">
                    <div class="feature-icon">🚚</div>
                    <h3>Fresh Delivery</h3>
                    <p>Same-day delivery to keep everything fresh and delicious</p>
                </div>

                <div class="feature-item">
                    <div class="feature-icon">💯</div>
                    <h3>Quality Assured</h3>
                    <p>Premium ingredients and strict hygiene standards</p>
                </div>

                <div class="feature-item">
                    <div class="feature-icon">💝</div>
                    <h3>Made with Love</h3>
                    <p>Every order is prepared with care and attention</p>
                </div>
            </div>
        </section>

        <footer>
            <p>&copy; 2025 Flakies. All rights reserved. Bringing authentic Filipino flavors to your table. 🇵🇭</p>
        </footer>
</body>

</html>