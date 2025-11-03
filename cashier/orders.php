<?php
session_start();
require_once '../config/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'cashier') {
  header("Location: login.php");
  exit;
}

// Get filter parameters
$filter_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$filter_status = isset($_GET['status']) ? $_GET['status'] : 'all';

// Fetch orders with filters
$ordersQuery = "
    SELECT o.id, o.total_amount, o.payment_method, o.status,
           DATE_FORMAT(CONVERT_TZ(o.order_date, @@session.time_zone, '+08:00'), '%Y-%m-%d %h:%i %p') as order_date,
           DATE_FORMAT(CONVERT_TZ(o.order_date, @@session.time_zone, '+08:00'), '%Y-%m-%d') as order_day
    FROM orders o
    WHERE 1=1
";

if ($filter_date !== 'all') {
  $ordersQuery .= " AND DATE(CONVERT_TZ(o.order_date, @@session.time_zone, '+08:00')) = '$filter_date'";
}

if ($filter_status !== 'all') {
  $ordersQuery .= " AND o.status = '$filter_status'";
}

$ordersQuery .= " ORDER BY o.order_date DESC LIMIT 100";

$ordersResult = $conn->query($ordersQuery);
$orders = $ordersResult->fetch_all(MYSQLI_ASSOC);

// Get statistics
$statsQuery = "
    SELECT 
        COUNT(*) as total_orders,
        SUM(total_amount) as total_sales,
        COUNT(CASE WHEN DATE(CONVERT_TZ(order_date, @@session.time_zone, '+08:00')) = CURDATE() THEN 1 END) as today_orders,
        SUM(CASE WHEN DATE(CONVERT_TZ(order_date, @@session.time_zone, '+08:00')) = CURDATE() THEN total_amount ELSE 0 END) as today_sales
    FROM orders
";
$statsResult = $conn->query($statsQuery);
$stats = $statsResult->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Orders - Cashier</title>
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

    .filter-box {
      background: #fff;
      border-radius: 25px;
      padding: 1.5rem;
      box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
      margin-bottom: 2rem;
    }

    .filter-input,
    .filter-select {
      border: 2px solid #e0e0e0;
      border-radius: 15px;
      padding: 0.8rem 1.2rem;
      font-size: 1rem;
      transition: all 0.3s;
    }

    .filter-input:focus,
    .filter-select:focus {
      outline: none;
      border-color: #667eea;
      box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    .table-container {
      background: #fff;
      border-radius: 25px;
      padding: 2rem;
      box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
      overflow-x: auto;
    }

    .table {
      margin-bottom: 0;
    }

    .table thead {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
    }

    .table thead th {
      border: none;
      padding: 1rem;
      font-weight: 600;
    }

    .table tbody tr {
      transition: all 0.3s;
    }

    .table tbody tr:hover {
      background-color: #f8f9ff;
      transform: scale(1.01);
    }

    .table tbody td {
      padding: 1rem;
      vertical-align: middle;
    }

    .badge-status {
      padding: 0.5rem 1rem;
      border-radius: 15px;
      font-size: 0.85rem;
      font-weight: 600;
    }

    .status-completed {
      background: #d4edda;
      color: #155724;
    }

    .status-pending {
      background: #fff3cd;
      color: #856404;
    }

    .status-cancelled {
      background: #f8d7da;
      color: #721c24;
    }

    .btn-view {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      border: none;
      padding: 0.5rem 1.5rem;
      border-radius: 15px;
      font-weight: 600;
      transition: all 0.3s;
      cursor: pointer;
    }

    .btn-view:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
    }

    .modal-content {
      border-radius: 25px;
      border: none;
    }

    .modal-header {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      border-radius: 25px 25px 0 0;
      border: none;
    }

    .payment-badge {
      padding: 0.4rem 0.8rem;
      border-radius: 10px;
      font-size: 0.85rem;
      font-weight: 600;
    }

    .payment-cash {
      background: #c3e6cb;
      color: #155724;
    }

    .payment-card {
      background: #d1ecf1;
      color: #0c5460;
    }

    .payment-gcash {
      background: #cce5ff;
      color: #004085;
    }

    @media(max-width:900px) {
      .dashboard-cards {
        flex-direction: column;
      }

      .table-container {
        padding: 1rem;
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
      <li><a href="pos.php"><i class="fa fa-cash-register"></i> POS</a></li>
      <li><a href="products.php"><i class="fa fa-box"></i> Products</a></li>
      <li><a href="orders.php"><i class="fa fa-receipt"></i> Orders</a></li>
      <li><a href="../login/logout.php" class="cart-btn"><i class="fa fa-sign-out-alt"></i> Logout</a></li>
    </ul>
  </nav>

  <div class="container mt-5">

    <!-- Statistics Cards -->
    <div class="dashboard-cards">
      <div class="card flex-fill text-center">
        <h4>Today's Orders</h4>
        <h2><?php echo $stats['today_orders']; ?></h2>
        <i class="fa fa-shopping-cart"></i>
      </div>
      <div class="card flex-fill text-center">
        <h4>Today's Sales</h4>
        <h2>₱<?php echo number_format($stats['today_sales'], 2); ?></h2>
        <i class="fa fa-money-bill-wave"></i>
      </div>
      <div class="card flex-fill text-center">
        <h4>Total Orders</h4>
        <h2><?php echo $stats['total_orders']; ?></h2>
        <i class="fa fa-receipt"></i>
      </div>
      <div class="card flex-fill text-center">
        <h4>Total Sales</h4>
        <h2>₱<?php echo number_format($stats['total_sales'], 2); ?></h2>
        <i class="fa fa-chart-line"></i>
      </div>
    </div>

    <!-- Filter Box -->
    <div class="filter-box">
      <form method="GET" action="" class="row g-3">
        <div class="col-md-5">
          <label class="form-label fw-bold">Filter by Date</label>
          <input type="date" name="date" class="filter-input form-control"
            value="<?php echo $filter_date !== 'all' ? $filter_date : ''; ?>">
        </div>
        <div class="col-md-5">
          <label class="form-label fw-bold">Filter by Status</label>
          <select name="status" class="filter-select form-select">
            <option value="all" <?php echo $filter_status === 'all' ? 'selected' : ''; ?>>All Status</option>
            <option value="completed" <?php echo $filter_status === 'completed' ? 'selected' : ''; ?>>Completed</option>
            <option value="pending" <?php echo $filter_status === 'pending' ? 'selected' : ''; ?>>Pending</option>
            <option value="cancelled" <?php echo $filter_status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
          </select>
        </div>
        <div class="col-md-2 d-flex align-items-end">
          <button type="submit" class="btn btn-view w-100">
            <i class="fa fa-filter"></i> Filter
          </button>
        </div>
      </form>
    </div>

    <!-- Orders Table -->
    <h3 class="mb-4">Order History</h3>
    <div class="table-container">
      <table class="table table-hover">
        <thead>
          <tr>
            <th>Order ID</th>
            <th>Date & Time</th>
            <th>Total Amount</th>
            <th>Payment Method</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($orders)): ?>
            <?php foreach ($orders as $order): ?>
              <tr>
                <td><strong>#<?php echo $order['id']; ?></strong></td>
                <td><?php echo $order['order_date']; ?></td>
                <td><strong>₱<?php echo number_format($order['total_amount'], 2); ?></strong></td>
                <td>
                  <?php
                  $paymentClass = 'payment-cash';
                  if ($order['payment_method'] === 'card') $paymentClass = 'payment-card';
                  if ($order['payment_method'] === 'gcash') $paymentClass = 'payment-gcash';
                  ?>
                  <span class="payment-badge <?php echo $paymentClass; ?>">
                    <?php echo ucfirst($order['payment_method']); ?>
                  </span>
                </td>
                <td>
                  <?php
                  $statusClass = 'status-pending';
                  if ($order['status'] === 'completed') $statusClass = 'status-completed';
                  if ($order['status'] === 'cancelled') $statusClass = 'status-cancelled';
                  ?>
                  <span class="badge-status <?php echo $statusClass; ?>">
                    <?php echo ucfirst($order['status']); ?>
                  </span>
                </td>
                <td>
                  <button class="btn-view" onclick="viewOrder(<?php echo $order['id']; ?>)">
                    <i class="fa fa-eye"></i> View
                  </button>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="6" class="text-center py-5">
                <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
                <h5>No orders found</h5>
                <p class="text-muted">Try adjusting your filters</p>
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

  </div>

  <!-- Order Details Modal -->
  <div class="modal fade" id="orderModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fa fa-receipt"></i> Order Details</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body" id="orderDetailsContent">
          <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    function viewOrder(orderId) {
      const modal = new bootstrap.Modal(document.getElementById('orderModal'));
      modal.show();

      // Fetch order details via AJAX
      fetch(`get_order_details.php?id=${orderId}`)
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            let itemsHtml = '';
            data.items.forEach(item => {
              itemsHtml += `
                                <tr>
                                    <td>${item.product_name}</td>
                                    <td class="text-center">${item.quantity}</td>
                                    <td class="text-end">₱${parseFloat(item.price).toFixed(2)}</td>
                                    <td class="text-end"><strong>₱${parseFloat(item.subtotal).toFixed(2)}</strong></td>
                                </tr>
                            `;
            });

            document.getElementById('orderDetailsContent').innerHTML = `
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <h6 class="text-muted">Order ID</h6>
                                    <p class="fs-5"><strong>#${data.order.id}</strong></p>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="text-muted">Date & Time</h6>
                                    <p class="fs-5">${data.order.order_date}</p>
                                </div>
                            </div>
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <h6 class="text-muted">Payment Method</h6>
                                    <p class="fs-5">${data.order.payment_method}</p>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="text-muted">Status</h6>
                                    <p class="fs-5"><span class="badge-status status-${data.order.status}">${data.order.status}</span></p>
                                </div>
                            </div>
                            <hr>
                            <h5 class="mb-3">Order Items</h5>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead class="table-light text-black">
                                        <tr>
                                            <th>Product</th>
                                            <th class="text-center">Quantity</th>
                                            <th class="text-end">Price</th>
                                            <th class="text-end">Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${itemsHtml}
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="3" class="text-end"><strong>Total:</strong></td>
                                            <td class="text-end"><h5 class="text-success mb-0">₱${parseFloat(data.order.total_amount).toFixed(2)}</h5></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        `;
          } else {
            document.getElementById('orderDetailsContent').innerHTML = `
                            <div class="alert alert-danger">
                                <i class="fa fa-exclamation-circle"></i> Error loading order details
                            </div>
                        `;
          }
        })
        .catch(error => {
          document.getElementById('orderDetailsContent').innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fa fa-exclamation-circle"></i> Error loading order details
                        </div>
                    `;
        });
    }
  </script>
</body>
</html>