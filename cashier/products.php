<?php
session_start();
require_once '../config/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'cashier') {
  header("Location: ../login.php");
  exit;
}

// ✅ Fetch all products (no categories)
$productsQuery = "
    SELECT 
        id, 
        name,
        category,
        description, 
        price, 
        stock, 
        image
    FROM products
    ORDER BY name ASC
";
$productsResult = $conn->query($productsQuery);
$products = [];

if ($productsResult && $productsResult->num_rows > 0) {
  while ($row = $productsResult->fetch_assoc()) {
    $products[] = $row;
  }
}

// ✅ Product statistics (totals, stock levels)
$statsQuery = "
    SELECT 
        COUNT(*) AS total_products,
        COALESCE(SUM(stock), 0) AS total_stock,
        COUNT(CASE WHEN stock < 10 THEN 1 END) AS low_stock_count
    FROM products
";
$statsResult = $conn->query($statsQuery);
$stats = $statsResult->fetch_assoc() ?? [
  'total_products' => 0,
  'total_stock' => 0,
  'low_stock_count' => 0
];
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Products - Cashier</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- Bootstrap & Font Awesome -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

  <style>
    /* ===== Modern Dashboard CSS ===== */
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
      background-color: #f4e04d;
    }

    nav {
      background: #2d2d2d;
      color: #f4e04d;
      padding: 1.2rem 5%;
      display: flex;
      justify-content: space-between;
      align-items: center;
      box-shadow: 0 2px 20px rgba(0, 0, 0, 0.3);
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

    .dashboard-cards {
      display: flex;
      gap: 2rem;
      margin-bottom: 3rem;
      flex-wrap: wrap;
    }

    .card {
      border-radius: 25px;
      padding: 2rem;
      box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
      transition: 0.3s;
      background: #fff;
      color: #333;
    }

    .card:hover {
      transform: translateY(-10px);
      box-shadow: 0 20px 60px rgba(102, 126, 234, 0.15);
    }

    .card i {
      font-size: 3rem;
      margin-top: 10px;
    }

    .products-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
      gap: 2rem;
      margin-top: 2rem;
    }

    .product-card {
      background: #fff;
      border-radius: 25px;
      padding: 1.5rem;
      box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
      transition: all 0.3s ease;
      display: flex;
      flex-direction: column;
    }

    .product-card:hover {
      transform: translateY(-10px);
      box-shadow: 0 20px 60px rgba(102, 126, 234, 0.15);
    }

    .product-image {
      width: 100%;
      height: 200px;
      object-fit: cover;
      border-radius: 15px;
      margin-bottom: 1rem;
      background: #f0f0f0;
    }

    .product-name {
      font-size: 1.2rem;
      font-weight: 600;
      margin-bottom: 0.5rem;
      color: #2d2d2d;
    }

    .product-category {
      font-size: 0.9rem;
      color: #667eea;
      margin-bottom: 0.5rem;
    }

    .product-description {
      font-size: 0.9rem;
      color: #666;
      margin-bottom: 1rem;
      flex-grow: 1;
    }

    .product-price {
      font-size: 1.5rem;
      font-weight: 700;
      color: #667eea;
      margin-bottom: 0.5rem;
    }

    .product-stock {
      font-size: 0.9rem;
      margin-bottom: 1rem;
    }

    .stock-high {
      color: #28a745;
    }

    .stock-low {
      color: #dc3545;
    }

    .stock-medium {
      color: #ffc107;
    }

    .search-box {
      background: #fff;
      border-radius: 25px;
      padding: 1.5rem;
      box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
      margin-bottom: 2rem;
    }

    .search-input {
      border: 2px solid #e0e0e0;
      border-radius: 15px;
      padding: 0.8rem 1.2rem;
      width: 100%;
      font-size: 1rem;
      transition: all 0.3s;
    }

    .search-input:focus {
      outline: none;
      border-color: #667eea;
      box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    .badge-stock {
      padding: 0.4rem 0.8rem;
      border-radius: 10px;
      font-size: 0.85rem;
      font-weight: 600;
    }

    @media(max-width:900px) {
      .dashboard-cards {
        flex-direction: column;
      }

      .products-grid {
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
      }
    }
  </style>
</head>

<body>

  <!-- Navbar -->
  <nav>
    <a href="dashboard.php" class="logo">
      <img src="../assets/pictures/45b0e7c9-8bc1-4ef3-bac2-cfc07174d613.png" alt="Logo">
      <span>Flakies POS</span>
    </a>
    <ul class="nav-links">
      <li><a href="cashierdashboard.php"><i class="fa fa-box"></i> Dashboard</a></li>
      <li><a href="pos.php"><i class="fa fa-cash-register"></i> POS</a></li>
      <li><a href="orders.php"><i class="fa fa-receipt"></i> Orders</a></li>
      <li><a href="../login/logout.php" class="cart-btn"><i class="fa fa-sign-out-alt"></i> Logout</a></li>
    </ul>
  </nav>

  <div class="container mt-5">

    <!-- Statistics Cards -->
    <div class="dashboard-cards">
      <div class="card flex-fill text-center">
        <h4>Total Products</h4>
        <h2><?php echo $stats['total_products']; ?></h2>
        <i class="fa fa-box"></i>
      </div>
      <div class="card flex-fill text-center">
        <h4>Total Stock</h4>
        <h2><?php echo number_format($stats['total_stock']); ?></h2>
        <i class="fa fa-cubes"></i>
      </div>
      <div class="card flex-fill text-center">
        <h4>Low Stock Items</h4>
        <h2><?php echo $stats['low_stock_count']; ?></h2>
        <i class="fa fa-exclamation-triangle"></i>
      </div>
    </div>

    <!-- Search Box -->
    <div class="search-box">
      <input type="text" id="searchInput" class="search-input" placeholder="🔍 Search products by name...">
    </div>

    <!-- Products Grid -->
    <h3 class="mb-4">All Products</h3>
    <div class="products-grid" id="productsGrid">
      <?php foreach ($products as $product): ?>
        <div class="product-card" data-name="<?php echo strtolower($product['name']); ?>">
          <?php if (!empty($product['image'])): ?>
            <img src="./images_path/<?php echo htmlspecialchars($product['image']); ?>"
              alt="<?php echo htmlspecialchars($product['name']); ?>"
              class="product-image">
          <?php else: ?>
            <div class="product-image d-flex align-items-center justify-content-center">
              <i class="fas fa-image fa-3x text-muted"></i>
            </div>
          <?php endif; ?>

          <div class="product-category">
            <i class="fas fa-tag"></i> <?php echo htmlspecialchars($product['category'] ?? 'Uncategorized'); ?>
          </div>

          <div class="product-name"><?php echo htmlspecialchars($product['name']); ?></div>

          <div class="product-description">
            <?php echo htmlspecialchars($product['description'] ?? 'No description available'); ?>
          </div>

          <div class="product-price">₱<?php echo number_format($product['price'], 2); ?></div>

          <div class="product-stock">
            Stock:
            <?php
            $stock = $product['stock'];
            $stockClass = $stock < 10 ? 'stock-low' : ($stock < 50 ? 'stock-medium' : 'stock-high');
            ?>
            <span class="badge-stock <?php echo $stockClass; ?>">
              <?php echo $stock; ?> units
            </span>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <?php if (empty($products)): ?>
      <div class="text-center mt-5">
        <i class="fas fa-box-open fa-5x text-muted mb-3"></i>
        <h4>No products available</h4>
      </div>
    <?php endif; ?>

  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Search functionality
    document.getElementById('searchInput').addEventListener('input', function(e) {
      const searchTerm = e.target.value.toLowerCase();
      const productCards = document.querySelectorAll('.product-card');

      productCards.forEach(card => {
        const productName = card.getAttribute('data-name');
        if (productName.includes(searchTerm)) {
          card.style.display = 'flex';
        } else {
          card.style.display = 'none';
        }
      });
    });
  </script>
</body>

</html>