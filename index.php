<?php
include("db_connect.php");

// Fetch products dynamically
$sql = "SELECT name, description, price, stock, image FROM products";
$result = $conn->query($sql);
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
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            25% { transform: translate(50px, -30px) rotate(5deg); }
            50% { transform: translate(0, -50px) rotate(-5deg); }
            75% { transform: translate(-50px, -30px) rotate(5deg); }
        }

        nav {
            position: fixed;
            top: 0;
            width: 100%;
            padding: 1.5rem 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 1000;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
        }

        .logo {
            font-size: 1.8rem;
            font-weight: 800;
            letter-spacing: -1px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .nav-links {
            display: flex;
            gap: 2rem;
            list-style: none;
            align-items: center;
        }

        .nav-links a {
            color: #333;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }

        .nav-links a:hover {
            color: #667eea;
        }

        .cart-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0.6rem 1.5rem;
            border-radius: 50px;
            font-weight: 600;
            transition: transform 0.3s;
        }

        .cart-btn:hover {
            transform: scale(1.05);
            color: white;
        }

        .hero-content {
            text-align: center;
            max-width: 900px;
            padding: 2rem;
            position: relative;
            z-index: 1;
            animation: fadeInUp 1s ease;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        h1 {
            font-size: 4.5rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
            line-height: 1.1;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero p {
            font-size: 1.4rem;
            margin-bottom: 2.5rem;
            color: #4a5568;
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
            box-shadow: 0 10px 40px rgba(0,0,0,0.08);
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 3rem 5%;
            text-align: center;
        }

        footer p {
            opacity: 0.9;
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

            .products h2, .features h2 {
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
        <div class="logo">🌺 Flakies</div>
        <ul class="nav-links">
            <li><a href="#products">Menu</a></li>
            <li><a href="#features">About</a></li>
            <li><a href="#contact">Contact</a></li>
            <li><a href="#" class="cart-btn">🛒 Cart</a></li>
        </ul>
    </nav>

    <section class="hero">
        <div class="hero-content">
            <h1>Sarap ng Pilipinas! 🇵🇭</h1>
            <p>Authentic Filipino delicacies delivered fresh to your door. From savory pastils to refreshing halo-halo, experience the taste of home.</p>
            <div class="cta-buttons">
                <button class="btn btn-primary">Order Now</button>
                <button class="btn btn-secondary">View Full Menu</button>
            </div>
        </div>
    </section>

    <section class="products" id="products">
    <h2>Our Specialties</h2>
    <p class="products-subtitle">Handcrafted with love, delivered with care</p>

    <div class="products-grid">
        <?php
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                echo "
                <div class='product-card'>
                    <div class='product-image'>";
                        if (!empty($row['image'])) {
                            echo "<img src='uploads/{$row['image']}' alt='{$row['name']}' style='width:100%;height:250px;object-fit:cover;border-radius:10px;'>";
                        } else {
                            echo "🍴"; // fallback emoji
                        }
                echo "</div>
                    <div class='product-info'>
                        <h3>{$row['name']}</h3>
                        <p>{$row['description']}</p>
                        <div class='product-price'>₱{$row['price']}</div>
                        <p style='color:gray;font-size:0.9rem;'>Stock: {$row['stock']}</p>
                        <button class='order-btn'>Add to Cart</button>
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