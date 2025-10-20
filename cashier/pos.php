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
$productQuery = "SELECT id, name, price, stock, image, product_status FROM products WHERE category != 'addon' AND category != 'drink'";
$productResult = $conn->query($productQuery);
$products = $productResult->fetch_all(MYSQLI_ASSOC);

$addonQuery = "SELECT id, name, price, stock, image, product_status FROM products WHERE category = 'addon'";
$addonResult = $conn->query($addonQuery);
$addons = $addonResult->fetch_all(MYSQLI_ASSOC);

$drinkQuery = "SELECT id, name, price, stock, image, product_status FROM products WHERE category = 'drink'";
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

<!-- Custom CSS -->
<style>
body { background-color: #f8f9fa; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
.navbar-custom { background-color: #343a40; }
.navbar-custom .navbar-brand, .navbar-custom .navbar-text { color: #fff; }
.card { border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); transition: transform 0.2s, box-shadow 0.2s; cursor:pointer; }
.card:hover { transform: translateY(-3px); box-shadow: 0 8px 12px rgba(0,0,0,0.15); }
.card-img-wrapper { height: 150px; display:flex; justify-content:center; align-items:center; overflow:hidden; border-top-left-radius:12px; border-top-right-radius:12px; background-color:#fff; }
.card-img-top { max-height: 100%; object-fit: contain; }
.disabled-card { opacity: 0.6; pointer-events:none; }
.btn-success { background-color: #28a745; border:none; border-radius:8px; }
.btn-success:hover { background-color: #218838; }
table { background-color: #fff; border-radius:12px; overflow:hidden; box-shadow:0 4px 6px rgba(0,0,0,0.1); }
thead { background-color: #343a40; color:#fff; }
td, th { vertical-align: middle !important; }
.modal-content { border-radius:12px; }
.modal-header { background-color:#343a40; color:#fff; border-top-left-radius:12px; border-top-right-radius:12px; }
#toastContainer { border-radius:8px; }
@media (max-width: 768px) { .card-img-wrapper { height: 120px; } }
/* Responsive cart */
@media (max-width: 576px) {
    table.table td, table.table th { white-space: normal; font-size: 0.9rem; }
    .table-responsive { overflow-x:auto; }
    #checkoutBtn { font-size:1rem; padding:0.5rem; }
    .d-flex.flex-column.flex-sm-row { flex-direction: column !important; align-items:flex-start !important; }
    .d-flex.flex-column.flex-sm-row p { margin-bottom:0.5rem; }
}
</style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-custom">
    <div class="container-fluid">
        <a class="navbar-brand" href="#"><img src="GEPOLEO-LOGO-FLAKIES-CIRCLE.png" alt="Flakies Logo" height="30"> Flakies POS</a>
        <span class="navbar-text text-white mx-3" id="dateTime"></span>
        <span class="navbar-text text-warning mx-3"><i class="fa-solid fa-shopping-cart"></i> Total Orders: <strong><?php echo $totalOrders; ?></strong></span>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"><span class="navbar-toggler-icon"></span></button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link logout-link" href="#" onclick="confirmLogout();"><i class="fa-solid fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
    </div>
</nav>

<script>
function updateDateTime() {
    const now = new Date();
    const options = { weekday:'long', year:'numeric', month:'long', day:'numeric', hour:'2-digit', minute:'2-digit', second:'2-digit' };
    document.getElementById('dateTime').textContent = now.toLocaleDateString('en-US', options);
}
setInterval(updateDateTime, 1000); updateDateTime();
</script>

<div class="container-fluid mt-4">
<div class="row">
<!-- Products/Add-ons/Drinks -->
<div class="col-md-6">
    <h4>Available Products</h4>
    <div class="row g-3" id="productList">
    <?php foreach ($products as $product):
        $isInactive = $product['product_status'] == 1;
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
                <p>₱<?php echo number_format($product['price'],2); ?></p>
                <?php if($isInactive): ?><p><span class="badge bg-danger">Inactive</span></p>
                <?php elseif($isOutOfStock): ?><p><strong>Out of Stock</strong></p>
                <?php else:?><p>Stock: <span class="stock-count"><?php echo $product['stock']; ?></span></p><?php endif;?>
            </div>
        </div>
    </div>
    <?php endforeach;?>
    </div>

    <h4 class="mt-4">Available Add-ons</h4>
    <div class="row g-3" id="addonList">
    <?php foreach ($addons as $addon):
        $isInactive = $addon['product_status'] == 1;
        $isOutOfStock = $addon['stock'] <= 0;
        $disabled = $isInactive || $isOutOfStock;
        $cardClass = $disabled ? 'bg-secondary text-white disabled-card' : 'addon-card';
        $style = $disabled ? 'pointer-events:none; opacity:0.6;' : '';
    ?>
    <div class="col-md-4 mb-3">
        <div class="card h-100 <?php echo $cardClass; ?>" 
             data-id="<?php echo $addon['id']; ?>" 
             data-name="<?php echo htmlspecialchars($addon['name']); ?>" 
             data-price="<?php echo $addon['price']; ?>" 
             data-stock="<?php echo $addon['stock']; ?>" 
             style="<?php echo $style; ?>">
            <div class="card-img-wrapper">
                <img src="images_path/<?php echo !empty($addon['image']) ? $addon['image'] : 'default-addon.png'; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($addon['name']); ?>">
            </div>
            <div class="card-body">
                <h5 class="card-title"><?php echo htmlspecialchars($addon['name']); ?></h5>
                <p>₱<?php echo number_format($addon['price'],2); ?></p>
                <?php if($isInactive): ?><p><span class="badge bg-danger">Inactive</span></p>
                <?php elseif($isOutOfStock): ?><p><strong>Out of Stock</strong></p>
                <?php else:?><p>Stock: <span class="stock-count"><?php echo $addon['stock']; ?></span></p><?php endif;?>
            </div>
        </div>
    </div>
    <?php endforeach;?>
    </div>

    <h4 class="mt-4">Available Drinks</h4>
    <div class="row g-3" id="drinkList">
    <?php foreach ($drinks as $drink):
        $isInactive = $drink['product_status'] == 1;
        $isOutOfStock = $drink['stock'] <= 0;
        $disabled = $isInactive || $isOutOfStock;
        $cardClass = $disabled ? 'bg-secondary text-white disabled-card' : 'drink-card';
        $style = $disabled ? 'pointer-events:none; opacity:0.6;' : '';
    ?>
    <div class="col-md-4 mb-3">
        <div class="card h-100 <?php echo $cardClass; ?>" 
             data-id="<?php echo $drink['id']; ?>" 
             data-name="<?php echo htmlspecialchars($drink['name']); ?>" 
             data-price="<?php echo $drink['price']; ?>" 
             data-stock="<?php echo $drink['stock']; ?>" 
             style="<?php echo $style; ?>">
            <div class="card-img-wrapper">
                <img src="images_path/<?php echo !empty($drink['image']) ? $drink['image'] : 'default-drink.png'; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($drink['name']); ?>">
            </div>
            <div class="card-body">
                <h5 class="card-title"><?php echo htmlspecialchars($drink['name']); ?></h5>
                <p>₱<?php echo number_format($drink['price'],2); ?></p>
                <?php if($isInactive): ?><p><span class="badge bg-danger">Inactive</span></p>
                <?php elseif($isOutOfStock): ?><p><strong>Out of Stock</strong></p>
                <?php else:?><p>Stock: <span class="stock-count"><?php echo $drink['stock']; ?></span></p><?php endif;?>
            </div>
        </div>
    </div>
    <?php endforeach;?>
    </div>
</div>

<!-- Cart -->
<div class="col-md-6">
    <h4 class="mt-4">Cart</h4>
    <div class="table-responsive">
        <table class="table table-bordered mb-2">
            <thead>
                <tr>
                    <th>Product</th><th>Price</th><th>Qty</th><th>Total</th><th>Action</th>
                </tr>
            </thead>
            <tbody id="cartItems"></tbody>
        </table>
    </div>
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mb-3">
        <p class="mb-1"><strong>Total: ₱<span id="cartTotal">0.00</span></strong></p>
        <p class="mb-1"><strong>Items: <span id="totalItems">0</span></strong></p>
    </div>
    <button id="checkoutBtn" class="btn btn-success w-100">Checkout</button>
</div>
</div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
let cart = [];

function renderCart(){
    const cartItems = document.getElementById('cartItems');
    cartItems.innerHTML='';
    let total=0, totalItems=0;
    cart.forEach((item,index)=>{
        const itemTotal=item.price*item.qty;
        total+=itemTotal;
        totalItems+=item.qty;
        const tr=document.createElement('tr');
        tr.innerHTML=`<td>${item.name}</td>
        <td>₱${item.price.toFixed(2)}</td>
        <td><input type="number" min="1" value="${item.qty}" class="form-control form-control-sm qty-input" data-index="${index}"></td>
        <td>₱${itemTotal.toFixed(2)}</td>
        <td><button class="btn btn-danger btn-sm remove-item" data-index="${index}"><i class="fa fa-trash"></i></button></td>`;
        cartItems.appendChild(tr);
    });
    document.getElementById('cartTotal').textContent=total.toFixed(2);
    document.getElementById('totalItems').textContent=totalItems;
    // Quantity change
    document.querySelectorAll('.qty-input').forEach(input=>{
        input.addEventListener('change', function(){
            const idx=parseInt(this.dataset.index);
            let val=parseInt(this.value); if(val<1) val=1;
            cart[idx].qty=val; renderCart();
        });
    });
    // Remove item
    document.querySelectorAll('.remove-item').forEach(btn=>{
        btn.addEventListener('click', function(){
            const idx=parseInt(this.dataset.index);
            cart.splice(idx,1); renderCart();
        });
    });
}

// Add product to cart
function addToCart(id,name,price){
    const existing=cart.find(i=>i.id===id);
    if(existing){ existing.qty+=1; } else { cart.push({id,name,price,qty:1}); }
    renderCart();
}

// Attach click events
document.querySelectorAll('.product-card, .addon-card, .drink-card').forEach(card=>{
    card.addEventListener('click', function(){
        const id=parseInt(this.dataset.id);
        const name=this.dataset.name;
        const price=parseFloat(this.dataset.price);
        const stock=parseInt(this.dataset.stock);
        addToCart(id,name,price);
        if(stock<=5 && stock>0){ Swal.fire({icon:'warning', title:'Low Stock Alert!', text:`"${name}" only has ${stock} left!`, timer:2000, showConfirmButton:false}); }
    });
});

// Logout
function confirmLogout(){
    Swal.fire({
        title:'Are you sure you want to log out?',
        text:'You will be logged out of the system.',
        icon:'warning',
        showCancelButton:true,
        confirmButtonText:'Yes, Log out',
        cancelButtonText:'No, Stay Logged In'
    }).then(result=>{
        if(result.isConfirmed){
            <?php date_default_timezone_set('Asia/Manila'); $currentTime=date('F d, Y h:i A'); ?>
            Swal.fire({title:"Time-out!", text:"Time-out: <?php echo $currentTime; ?>", icon:"success", showConfirmButton:false, timer:1000})
            .then(()=>{window.location.href="logout.php";});
        }
    });
}
</script>
</body>
</html>