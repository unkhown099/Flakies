<?php
session_start();
require_once '../config/db_connect.php';

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
    <title>Flakies - About Us</title>
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

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 3rem 2rem;
        }

        .hero-section {
            background: white;
            border-radius: 20px;
            padding: 3rem;
            margin-bottom: 3rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            text-align: center;
            color: #2d2d2d;
        }

        .hero-section h1 {
            font-size: 3rem;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, #f4e04d 0%, #d4a942 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-section p {
            font-size: 1.2rem;
            color: #666;
            line-height: 1.8;
        }

        .content-section {
            background: white;
            border-radius: 20px;
            padding: 2.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            color: #2d2d2d;
        }

        .content-section h2 {
            font-size: 2rem;
            color: #2d2d2d;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 3px solid #f4e04d;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .content-section p {
            font-size: 1.1rem;
            line-height: 1.8;
            color: #555;
            margin-bottom: 1rem;
        }

        .content-section ul {
            list-style: none;
            padding-left: 0;
        }

        .content-section li {
            font-size: 1.1rem;
            padding: 0.8rem 0;
            padding-left: 2rem;
            position: relative;
            color: #555;
            line-height: 1.6;
        }

        .content-section li::before {
            content: '✓';
            position: absolute;
            left: 0;
            color: #d4a942;
            font-weight: bold;
            font-size: 1.3rem;
        }

        .values-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-top: 1.5rem;
        }

        .value-card {
            background: linear-gradient(135deg, #f4e04d 0%, #d4a942 100%);
            padding: 2rem;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }

        .value-card:hover {
            transform: translateY(-5px);
        }

        .value-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .value-card h3 {
            font-size: 1.3rem;
            color: #2d2d2d;
            margin-bottom: 0.5rem;
        }

        .value-card p {
            color: #2d2d2d;
            font-size: 0.95rem;
            line-height: 1.6;
        }

        .team-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }

        .team-member {
            background: linear-gradient(135deg, #f4e04d 0%, #d4a942 100%);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }

        .team-member:hover {
            transform: translateY(-10px);
        }

        .member-avatar {
            width: 100%;
            height: 150px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 4rem;
            background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
        }

        .member-info {
            padding: 1.5rem;
            text-align: center;
            background: white;
        }

        .member-name {
            font-size: 1.1rem;
            font-weight: 800;
            color: #2d2d2d;
            margin-bottom: 0.3rem;
        }

        .member-role {
            font-size: 0.9rem;
            color: #d4a942;
            font-weight: 600;
        }

        .cta-section {
            background: white;
            border-radius: 20px;
            padding: 2.5rem;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            color: #2d2d2d;
        }

        .cta-section h2 {
            font-size: 2rem;
            margin-bottom: 1rem;
        }

        .cta-section p {
            font-size: 1.1rem;
            margin-bottom: 2rem;
            color: #555;
        }

        .cta-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .cta-btn {
            padding: 1rem 2rem;
            font-size: 1rem;
            font-weight: 700;
            border: none;
            border-radius: 15px;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .cta-btn-primary {
            background: linear-gradient(135deg, #f4e04d 0%, #d4a942 100%);
            color: #2d2d2d;
        }

        .cta-btn-primary:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(212, 169, 66, 0.4);
        }

        .cta-btn-secondary {
            background: #2d2d2d;
            color: #f4e04d;
            border: 2px solid #f4e04d;
        }

        .cta-btn-secondary:hover {
            background: #1a1a1a;
        }

        @media (max-width: 768px) {
            .hero-section h1 {
                font-size: 2rem;
            }

            .content-section h2 {
                font-size: 1.5rem;
            }

            .nav-links {
                gap: 1rem;
                font-size: 0.9rem;
            }

            .cta-buttons {
                flex-direction: column;
            }

            .cta-btn {
                width: 100%;
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
        </div>
        <ul class="nav-links">
            <li><a href="../index.php">Home</a></li>
            <li><a href="./menu.php">Menu</a></li>
            <li><a href="./contact.php">Contact</a></li>

            <?php if (isset($_SESSION['customer_id'])): ?>
                <li>
                    <a href="./cart.php" class="auth-btn cart-btn">
                        🛒 Cart (<?php echo $cartCount; ?>)
                    </a>
                </li>
                <li><a href="/login/logout.php" class="auth-btn login-btn">Logout</a></li>
            <?php else: ?>
                <li><a href="../login/login.php" class="auth-btn login-btn">Login</a></li>
                <li><a href="../login/register.php" class="auth-btn register-btn">Register</a></li>
            <?php endif; ?>
        </ul>
    </nav>

    <div class="container">
        <div class="hero-section">
            <h1>About Flakies 🍚</h1>
            <p>Bringing the authentic taste of the Philippines straight to your doorstep since day one.</p>
        </div>

        <div class="content-section">
            <h2>📖 Our Story</h2>
            <p>
                Flakies was born from a passion for authentic Filipino cuisine and a dream to share the flavors of home with everyone. We believe that food is more than just sustenance—it's a connection to culture, family, and tradition.
            </p>
            <p>
                Starting as a small venture with handcrafted pastils and refreshing halo-halo, we've grown into a trusted name in Filipino delicacies. Every product we create carries the love, dedication, and authenticity that our family has instilled in us for generations.
            </p>
            <p>
                Today, Flakies stands as a celebration of Filipino culinary heritage, delivering quality, tradition, and taste with every order.
            </p>
        </div>

        <div class="content-section">
            <h2>🎯 Our Mission</h2>
            <p>
                To celebrate and share authentic Filipino flavors with communities everywhere. We're committed to:
            </p>
            <ul>
                <li>Providing premium quality ingredients in every product</li>
                <li>Preserving traditional Filipino recipes and cooking methods</li>
                <li>Ensuring fresh, timely delivery to our valued customers</li>
                <li>Supporting local farmers and suppliers</li>
                <li>Creating memorable experiences through authentic Filipino food</li>
            </ul>
        </div>

        <div class="content-section">
            <h2>💎 Our Values</h2>
            <div class="values-grid">
                <div class="value-card">
                    <div class="value-icon">🌟</div>
                    <h3>Authenticity</h3>
                    <p>We honor traditional recipes and never compromise on the authentic Filipino taste.</p>
                </div>
                <div class="value-card">
                    <div class="value-icon">❤️</div>
                    <h3>Quality</h3>
                    <p>Premium ingredients and meticulous preparation ensure every bite is perfect.</p>
                </div>
                <div class="value-card">
                    <div class="value-icon">⚡</div>
                    <h3>Speed</h3>
                    <p>Fresh, quick delivery because great food should arrive at its best.</p>
                </div>
                <div class="value-card">
                    <div class="value-icon">🤝</div>
                    <h3>Community</h3>
                    <p>We're proud to serve our community and support local businesses.</p>
                </div>
            </div>
        </div>

        <div class="content-section">
            <h2>👥 Meet Our Team</h2>
            <p>Behind every delicious Flakies product is a dedicated team passionate about Filipino cuisine.</p>
            <div class="team-grid">
                <div class="team-member">
                    <div class="member-avatar">👨‍🍳</div>
                    <div class="member-info">
                        <div class="member-name">Juan Santos</div>
                        <div class="member-role">Head Chef</div>
                    </div>
                </div>
                <div class="team-member">
                    <div class="member-avatar">👩‍💼</div>
                    <div class="member-info">
                        <div class="member-name">Maria Lopez</div>
                        <div class="member-role">Operations Manager</div>
                    </div>
                </div>
                <div class="team-member">
                    <div class="member-avatar">👨‍💻</div>
                    <div class="member-info">
                        <div class="member-name">Carlos Reyes</div>
                        <div class="member-role">Delivery Manager</div>
                    </div>
                </div>
                <div class="team-member">
                    <div class="member-avatar">👩‍🍳</div>
                    <div class="member-info">
                        <div class="member-name">Ana Garcia</div>
                        <div class="member-role">Product Specialist</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="cta-section">
            <h2>Ready to Taste Authenticity? 🇵🇭</h2>
            <p>Experience the best of Filipino cuisine delivered fresh to your door.</p>
            <div class="cta-buttons">
                <a href="menu.php" class="cta-btn cta-btn-primary">Browse Menu</a>
                <a href="#" class="cta-btn cta-btn-secondary">Contact Us</a>
            </div>
        </div>
    </div>
</body>
</html>