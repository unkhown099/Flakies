<?php
session_start();
require_once '../config/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'cashier') {
    header("Location: login.php");
    exit;
}

// Count today's orders and total sales
$orderQuery = "
    SELECT COUNT(*) AS total_orders, 
           SUM(total_amount) AS total_sales
    FROM orders
    WHERE DATE(CONVERT_TZ(order_date, @@session.time_zone, '+08:00')) = CURDATE()
";
$orderResult = $conn->query($orderQuery);
$orderData = $orderResult->fetch_assoc();

$totalOrders = $orderData['total_orders'] ?? 0;
$totalSales = $orderData['total_sales'] ?? 0;

// Fetch latest 5 orders
$recentOrdersQuery = "
    SELECT id, total_amount, 
           DATE_FORMAT(CONVERT_TZ(order_date, @@session.time_zone, '+08:00'), '%Y-%m-%d %h:%i %p') as order_date
    FROM orders
    ORDER BY order_date DESC
    LIMIT 5
";
$recentOrdersResult = $conn->query($recentOrdersQuery);
$recentOrders = $recentOrdersResult->fetch_all(MYSQLI_ASSOC);

// Fetch products count
$productQuery = "SELECT COUNT(*) AS total_products FROM products";
$productResult = $conn->query($productQuery);
$productData = $productResult->fetch_assoc();
$totalProducts = $productData['total_products'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Cashier Dashboard</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<!-- Bootstrap & Font Awesome -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

<style>
/* ===== Modern Dashboard CSS ===== */
*{margin:0;padding:0;box-sizing:border-box;}
body{
    font-family:-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif; 
    line-height:1.6; 
    color:#333; 
    overflow-x:hidden;
    background-color:#f4e04d; /* main background color */
}
nav{
    background:#2d2d2d;
    color:#f4e04d;
    padding:1.2rem 5%; 
    display:flex; 
    justify-content:space-between; 
    align-items:center; 
    box-shadow:0 2px 20px rgba(0,0,0,0.3); 
    position:sticky; 
    top:0; 
    z-index:100;
}
.logo{display:flex;align-items:center;gap:10px;text-decoration:none;}
.logo img{height:45px;width:45px;border-radius:50%; object-fit:cover;}
.logo span{font-size:1.6rem;font-weight:700;color:#f4e04d;}
.nav-links{display:flex;gap:2rem;list-style:none;align-items:center;}
.nav-links a{color:#f4e04d;text-decoration:none;font-weight:500;transition:opacity 0.3s;}
.nav-links a:hover{color:#667eea;}
.auth-btn{display:inline-block;padding:0.5rem 1.2rem;border-radius:50px;font-weight:600;text-decoration:none;transition:all 0.3s ease;border:2px solid #f4e04d;}
.cart-btn{background:#2d2d2d;color:#f4e04d;padding:0.6rem 1.5rem;border-radius:50px;font-weight:600;text-decoration:none;transition:transform 0.3s;border:2px solid #f4e04d;cursor:pointer;}
.cart-btn:hover{transform:scale(1.05);background:#f4e04d;color:#2d2d2d;}
.dashboard-cards{display:flex;gap:2rem;margin-bottom:4rem;flex-wrap:wrap;}
.card{
    border-radius:25px; 
    padding:2rem; 
    box-shadow:0 10px 40px rgba(0,0,0,0.08); 
    transition:0.3s; 
    background:#fff; /* Card background white for readability */
    color:#333;
}
.card:hover{transform:translateY(-10px); box-shadow:0 20px 60px rgba(102,126,234,0.15);}
.card i{font-size:3rem;margin-top:10px;}
.table-responsive{background:#fff;border-radius:25px;padding:20px; box-shadow:0 10px 40px rgba(0,0,0,0.08);}
.btn-primary{background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:white;box-shadow:0 10px 30px rgba(102,126,234,0.3);}
.btn-primary:hover{transform:translateY(-3px);box-shadow:0 15px 40px rgba(102,126,234,0.4);}
.btn-secondary{background:white;color:#667eea;border:2px solid #667eea;}
.btn-secondary:hover{background:#667eea;color:white;}
@media(max-width:900px){.dashboard-cards{flex-direction:column;}}
</style>
</head>
<body>

<!-- Navbar -->
<nav>
    <a href="#" class="logo"><img src="GEPOLEO-LOGO-FLAKIES-CIRCLE.png" alt="Logo"><span>Flakies POS</span></a>
    <ul class="nav-links">
        <li><a href="pos.php"><i class="fa fa-cash-register"></i> POS</a></li>
        <li><a href="products.php"><i class="fa fa-box"></i> Products</a></li>
        <li><a href="orders.php"><i class="fa fa-receipt"></i> Orders</a></li>
        <li><a href="logout.php" class="cart-btn"><i class="fa fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</nav>

<div class="container mt-5">

    <!-- Dashboard Cards -->
    <div class="dashboard-cards">
        <div class="card flex-fill text-center">
            <h4>Total Orders Today</h4>
            <h2><?php echo $totalOrders; ?></h2>
            <i class="fa fa-shopping-cart"></i>
        </div>
        <div class="card flex-fill text-center">
            <h4>Total Sales Today</h4>
            <h2>₱<?php echo number_format($totalSales,2); ?></h2>
            <i class="fa fa-money-bill-wave"></i>
        </div>
        <div class="card flex-fill text-center">
            <h4>Total Products</h4>
            <h2><?php echo $totalProducts; ?></h2>
            <i class="fa fa-tags"></i>
        </div>
    </div>

    <!-- Recent Orders Table -->
    <div class="mt-5">
        <h3>Recent Orders</h3>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Order ID</th>
                        <th>Total Amount</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($recentOrders as $order): ?>
                    <tr>
                        <td><?php echo $order['id']; ?></td>
                        <td>₱<?php echo number_format($order['total_amount'],2); ?></td>
                        <td><?php echo $order['order_date']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
