<?php
session_start();
require_once '../config/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'cashier') {
    header("Location: login.php");
    exit;
}

// Count today's orders
$orderQuery = "
    SELECT COUNT(*) AS total_orders 
    FROM orders 
    WHERE DATE(CONVERT_TZ(order_date, @@session.time_zone, '+08:00')) = CURDATE()
";
$orderResult = $conn->query($orderQuery);
$orderData = $orderResult->fetch_assoc();
$totalOrders = $orderData['total_orders'] ?? 0;

// Fetch products, add-ons, drinks
$productQuery = "SELECT id, name, price, stock, image, status FROM products WHERE category != 'addon' AND category != 'drink'";
$productResult = $conn->query($productQuery);
$products = $productResult->fetch_all(MYSQLI_ASSOC);

$addonQuery = "SELECT id, name, price, stock, image, status FROM products WHERE category = 'addon'";
$addonResult = $conn->query($addonQuery);
$addons = $addonResult->fetch_all(MYSQLI_ASSOC);

$drinkQuery = "SELECT id, name, price, stock, image, status FROM products WHERE category = 'drink'";
$drinkResult = $conn->query($drinkQuery);
$drinks = $drinkResult->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>POS System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Bootstrap & Font Awesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: #fff;
            background-color: #f4e04d;
            overflow-x: hidden;
        }

        .navbar-custom {
            background-color: #2d2d2d;
        }

        .navbar-custom .navbar-brand,
        .navbar-custom .navbar-text {
            color: #fff;
        }

        .card {
            background: #2d2d2d;
            color: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s, box-shadow 0.2s, background-color 0.2s;
            cursor: pointer;
        }

        .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 12px rgba(0, 0, 0, 0.15);
        }

        .card.selected {
            background-color: #28a745 !important;
        }

        .card-img-wrapper {
            height: 150px;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
            border-top-left-radius: 12px;
            border-top-right-radius: 12px;
            background-color: #fff;
        }

        .card-img-top {
            max-height: 100%;
            object-fit: contain;
        }

        .disabled-card {
            opacity: 0.6;
            pointer-events: none;
        }

        .btn-success {
            background-color: #28a745;
            border: none;
            border-radius: 8px;
        }

        .btn-success:hover {
            background-color: #218838;
        }

        .btn-danger {
            border-radius: 8px;
        }

        table {
            background-color: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            color: #000;
        }

        thead {
            background-color: #343a40;
            color: #fff;
        }

        td,
        th {
            vertical-align: middle !important;
        }

        .cart-summary {
            color: black;
        }

        /* Make total/items text black */

        #toastContainer {
            border-radius: 8px;
        }

        .toast-container {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1050;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .toast {
            transform: translateX(150%);
            opacity: 0;
            transition: transform 0.5s ease, opacity 0.5s ease;
        }

        .toast.show-toast {
            transform: translateX(0);
            opacity: 1;
        }

        .toast.hide-toast {
            transform: translateX(150%);
            opacity: 0;
        }

        .qty-btn {
            cursor: pointer;
            padding: 2px 6px;
            margin: 0 2px;
        }

        .nav-links {
            display: flex;
            gap: 2rem;
            list-style: none;
            padding-top: 10px;
            margin-left: auto;
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

        @media (max-width: 768px) {
            .card-img-wrapper {
                height: 120px;
            }
        }
    </style>
</head>

<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-custom">
        <div class="container-fluid">
            <a class="navbar-brand" href="#"><img src="../assets/pictures/45b0e7c9-8bc1-4ef3-bac2-cfc07174d613.png" alt="Flakies Logo" height="30"> Flakies POS</a>
            <span class="navbar-text text-white mx-3" id="dateTime"></span>
            <span class="navbar-text text-warning mx-3"><i class="fa-solid fa-shopping-cart"></i> Total Orders: <strong><?php echo $totalOrders; ?></strong></span>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"><span class="navbar-toggler-icon"></span></button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="nav-links">
                    <li><a href="pos.php"><i class="fa fa-cash-register"></i> POS</a></li>
                    <li><a href="products.php"><i class="fa fa-box"></i> Products</a></li>
                    <li><a href="orders.php"><i class="fa fa-receipt"></i> Orders</a></li>
                    <li><a onclick="confirmLogout()" class="cart-btn"><i class="fa fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <script>
        function updateDateTime() {
            const now = new Date();
            const options = {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            };
            document.getElementById('dateTime').textContent = now.toLocaleDateString('en-US', options);
        }
        setInterval(updateDateTime, 1000);
        updateDateTime();
    </script>

    <div class="container-fluid mt-4">
        <div class="row">

            <!-- Products/Add-ons/Drinks -->
            <div class="col-md-6">
                <h4>Available Products</h4>
                <div class="row g-3" id="productList">
                    <?php foreach ($products as $product):
                        $isInactive = $product['status'] == 1;
                        $isOutOfStock = $product['stock'] <= 0;
                        $disabled = $isInactive || $isOutOfStock;
                        $cardClass = $disabled ? 'bg-secondary text-white disabled-card' : 'product-card';
                        $style = $disabled ? 'pointer-events:none; opacity:0.6;' : '';
                    ?>
                        <div class="col-md-4 mb-3">
                            <div class="card h-100 <?php echo $cardClass; ?>"
                                data-id="<?php echo $product['id']; ?>"
                                data-name="<?php echo htmlspecialchars($product['name']); ?>"
                                data-price="<?php echo $product['price']; ?>"
                                data-stock="<?php echo $product['stock']; ?>"
                                style="<?php echo $style; ?>">
                                <div class="card-img-wrapper">
                                    <img src="images_path/<?php echo !empty($product['image']) ? $product['image'] : 'default-product.png'; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                </div>
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                                    <p>₱<?php echo number_format($product['price'], 2); ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

            

                <h4 class="mt-4">Available Drinks</h4>
                <div class="row g-3" id="drinkList">
                    <?php foreach ($drinks as $drink): ?>
                        <div class="col-md-4 mb-3">
                            <div class="card h-100 drink-card" data-id="<?php echo $drink['id']; ?>" data-name="<?php echo htmlspecialchars($drink['name']); ?>" data-price="<?php echo $drink['price']; ?>" data-stock="<?php echo $drink['stock']; ?>">
                                <div class="card-img-wrapper">
                                    <img src="images_path/<?php echo !empty($drink['image']) ? $drink['image'] : 'default-drink.png'; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($drink['name']); ?>">
                                </div>
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($drink['name']); ?></h5>
                                    <p>₱<?php echo number_format($drink['price'], 2); ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Cart -->
            <div class="col-md-6">
                <h4 class="mt-4">Cart</h4>
                <div class="table-responsive">
                    <table class="table table-bordered mb-2">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Qty</th>
                                <th>Total</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="cartItems"></tbody>
                    </table>
                </div>
                <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-3 cart-summary">
                    <p class="mb-1"><strong>Total: ₱<span id="cartTotal">0.00</span></strong></p>
                    <p class="mb-1"><strong>Items: <span id="totalItems">0</span></strong></p>
                </div>
                <div class="d-flex justify-content-between mt-2">
                    <button id="cancelBtn" class="btn btn-danger w-48">Cancel</button>
                    <button id="checkoutBtn" class="btn btn-success w-48">Checkout</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div id="orderNotification" class="toast-container">
        <div class="toast align-items-center text-white bg-primary border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body"><i class="fa fa-bell"></i> New Customer Order!</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let cart = [];

        function renderCart() {
            const cartItems = document.getElementById('cartItems');
            cartItems.innerHTML = '';
            let total = 0,
                totalItems = 0;
            cart.forEach((item, index) => {
                const itemTotal = item.price * item.qty;
                total += itemTotal;
                totalItems += item.qty;
                const tr = document.createElement('tr');
                tr.innerHTML = `<td>${item.name}</td>
        <td>₱${item.price.toFixed(2)}</td>
        <td>
            <button class="qty-btn btn btn-sm btn-secondary" data-action="minus" data-index="${index}">-</button>
            <span class="mx-1">${item.qty}</span>
            <button class="qty-btn btn btn-sm btn-secondary" data-action="plus" data-index="${index}">+</button>
        </td>
        <td>₱${itemTotal.toFixed(2)}</td>
        <td><button class="btn btn-danger btn-sm remove-item" data-index="${index}"><i class="fa fa-trash"></i></button></td>`;
                cartItems.appendChild(tr);
            });
            document.getElementById('cartTotal').textContent = total.toFixed(2);
            document.getElementById('totalItems').textContent = totalItems;

            document.querySelectorAll('.qty-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const idx = parseInt(this.dataset.index);
                    if (this.dataset.action === 'plus') cart[idx].qty++;
                    else cart[idx].qty = Math.max(1, cart[idx].qty - 1);
                    renderCart();
                });
            });

            document.querySelectorAll('.remove-item').forEach(btn => {
                btn.addEventListener('click', function() {
                    const idx = parseInt(this.dataset.index);
                    const removedId = cart[idx].id;
                    cart.splice(idx, 1);
                    renderCart();
                    document.querySelectorAll(`[data-id='${removedId}']`).forEach(c => c.classList.remove('selected'));
                });
            });
        }

        // Card selection
        document.querySelectorAll('.product-card, .addon-card, .drink-card').forEach(card => {
            card.addEventListener('click', function() {
                const id = parseInt(this.dataset.id);
                const name = this.dataset.name;
                const price = parseFloat(this.dataset.price);
                const stock = parseInt(this.dataset.stock);

                if (this.classList.contains('selected')) {
                    this.classList.remove('selected');
                    cart = cart.filter(item => item.id !== id);
                } else {
                    this.classList.add('selected');
                    cart.push({
                        id,
                        name,
                        price,
                        qty: 1
                    });
                    showOrderNotification(`"${name}" added to cart!`);
                }
                renderCart();
            });
        });

        // Checkout
        document.getElementById('checkoutBtn').addEventListener('click', function() {
            if (cart.length === 0) {
                Swal.fire('Cart is empty', 'Please add items to cart before checkout', 'warning');
                return;
            }

            fetch('checkout.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        cart
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        Swal.fire('Success', data.message, 'success');
                        cart = [];
                        renderCart();
                        document.querySelectorAll('.product-card.selected, .addon-card.selected, .drink-card.selected').forEach(c => c.classList.remove('selected'));
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                })
                .catch(err => {
                    Swal.fire('Error', 'Something went wrong', 'error');
                    console.error(err);
                });
        });

        // Cancel Cart
        document.getElementById('cancelBtn').addEventListener('click', function() {
            if (cart.length === 0) {
                Swal.fire('Cart is already empty', '', 'info');
                return;
            }

            Swal.fire({
                title: 'Are you sure?',
                text: 'This will remove all items from the cart.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, clear cart',
                cancelButtonText: 'No, keep items'
            }).then(result => {
                if (result.isConfirmed) {
                    cart = [];
                    renderCart();
                    document.querySelectorAll('.product-card.selected, .addon-card.selected, .drink-card.selected')
                        .forEach(c => c.classList.remove('selected'));
                    Swal.fire('Cart cleared', '', 'success');
                }
            });
        });

        // Toast Notification
        function showOrderNotification(message) {
            const notification = document.getElementById('orderNotification');
            const toastEl = notification.querySelector('.toast');
            toastEl.querySelector('.toast-body').innerHTML = `<i class="fa fa-bell"></i> ${message}`;

            toastEl.classList.add('show-toast');
            toastEl.classList.remove('hide-toast');

            setTimeout(() => {
                toastEl.classList.remove('show-toast');
                toastEl.classList.add('hide-toast');
            }, 5000);
        }

        // Logout confirmation
        function confirmLogout() {
            Swal.fire({
                title: 'Are you sure you want to log out?',
                text: 'You will be logged out of the system.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, Log out',
                cancelButtonText: 'No, Stay Logged In'
            }).then(result => {
                if (result.isConfirmed) {
                    window.location.href = "../login/logout.php";
                }
            });
        }
    </script>
</body>

</html>