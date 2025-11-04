<?php
session_start();
require_once '../config/db_connect.php';

// Ensure cashier is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'cashier') {
    header("Location: ../login.php");
    exit;
}

// Fetch products grouped by category
$query = "SELECT * FROM products WHERE deleted = 0 ORDER BY category, name";
$productsResult = $conn->query($query);

$categories = [];
while ($product = $productsResult->fetch_assoc()) {
    $category = $product['category'] ?? 'Other';
    $categories[$category][] = $product;
}

// Total number of orders
$totalOrders = $conn->query("SELECT COUNT(*) as total FROM orders")->fetch_assoc()['total'];

// Render the page
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
        /* Same styles as before */
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

        /* Styles for POS */
        .card {
            background: #2d2d2d;
            color: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s, box-shadow 0.2s, background-color 0.2s;
            cursor: pointer;
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
            width: auto;
            object-fit: contain;
        }

        .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 12px rgba(0, 0, 0, 0.15);
        }

        .card.selected {
            background-color: #28a745 !important;
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
    </style>
</head>

<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-custom py-2">
        <div class="container-fluid d-flex align-items-center justify-content-between">
            <a class="navbar-brand d-flex align-items-center" href="#">
                <img src="../assets/pictures/45b0e7c9-8bc1-4ef3-bac2-cfc07174d613.png" alt="Flakies Logo" height="30" class="me-2">
                <span>Flakies POS</span>
            </a>

            <div class="d-flex align-items-center text-white gap-3">
                <span class="navbar-text" id="dateTime"></span>
                <span class="navbar-text text-warning">
                    <i class="fa-solid fa-shopping-cart"></i> Total Orders:
                    <strong><?php echo $totalOrders; ?></strong>
                </span>
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
            <!-- Product display section -->
            <div class="col-md-8">
                <?php foreach ($categories as $category => $products): ?>
                    <h4 class="mt-4"><?php echo ucfirst($category); ?></h4>
                    <div class="row g-3" id="<?php echo strtolower($category); ?>List">
                        <?php foreach ($products as $product):
                            $isOutOfStock = $product['stock'] <= 0;
                            $disabled = $isOutOfStock;
                            $cardClass = $disabled ? 'bg-secondary text-white disabled-card' : 'card product-card';
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
                <?php endforeach; ?>
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
            let total = 0, totalItems = 0;

            cart.forEach((item, index) => {
                const itemTotal = item.price * item.qty;
                total += itemTotal;
                totalItems += item.qty;

                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${item.name}</td>
                    <td>₱${item.price.toFixed(2)}</td>
                    <td>
                        <button class="qty-btn btn btn-sm btn-secondary" data-action="minus" data-index="${index}">-</button>
                        <span class="mx-1">${item.qty}</span>
                        <button class="qty-btn btn btn-sm btn-secondary" data-action="plus" data-index="${index}">+</button>
                    </td>
                    <td>₱${itemTotal.toFixed(2)}</td>
                    <td>
                        <button class="btn btn-danger btn-sm remove-item" data-index="${index}">
                            <i class="fa fa-trash"></i>
                        </button>
                    </td>
                `;
                cartItems.appendChild(tr);
            });

            document.getElementById('cartTotal').textContent = total.toFixed(2);
            document.getElementById('totalItems').textContent = totalItems;

            // Add event listeners for quantity buttons
            document.querySelectorAll('.qty-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const idx = parseInt(this.dataset.index);
                    if (this.dataset.action === 'plus') {
                        cart[idx].qty++;
                    } else {
                        cart[idx].qty = Math.max(1, cart[idx].qty - 1);
                    }
                    renderCart();
                });
            });

            // Add event listeners for remove buttons
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

        // Product selection
        document.querySelectorAll('.card').forEach(card => {
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
                    cart.push({ id, name, price, qty: 1 });
                }
                renderCart();
            });
        });

        // Checkout button
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
                body: JSON.stringify({ cart })
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire('Success', data.message, 'success');
                    cart = [];
                    renderCart();
                    document.querySelectorAll('.card.selected').forEach(c => c.classList.remove('selected'));
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            })
            .catch(err => {
                Swal.fire('Error', 'Something went wrong', 'error');
                console.error(err);
            });
        });

        // Cancel button
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
                    document.querySelectorAll('.card.selected').forEach(c => c.classList.remove('selected'));
                    Swal.fire('Cart cleared', '', 'success');
                }
            });
        });
    </script>
</body>

</html>