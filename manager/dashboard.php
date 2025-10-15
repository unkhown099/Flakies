<?php
session_start();

// if not logged in, redirect to login
if (!isset($_SESSION['staff_id'])) {
    header("Location: login.php");
    exit();
}

$role = $_SESSION['role'];
$username = $_SESSION['username'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Flakies | Dashboard</title>
    <link rel="stylesheet" href="../assets/dashboard.css">
</head>

<body>
    <div class="sidebar">
        <h2 class="logo">Flakies</h2>
        <p class="welcome">Welcome, <?php echo htmlspecialchars($username); ?>!</p>

        <ul class="menu">
            <li><a href="dashboard.php">🏠 Dashboard</a></li>

            <?php if ($role === 'admin') : ?>
                <li><a href="manage_users.php">👤 Manage Users</a></li>
                <li><a href="manage_products.php">📦 Manage Products</a></li>
                <li><a href="reports.php">📊 Reports</a></li>
            <?php endif; ?>

            <?php if ($role === 'manager') : ?>
                <li><a href="reports.php">📊 Reports</a></li>
                <li><a href="inventory.php">📦 Inventory</a></li>
            <?php endif; ?>

            <?php if ($role === 'cashier') : ?>
                <li><a href="pos.php">💵 Point of Sale</a></li>
            <?php endif; ?>
        </ul>

        <a href="logout.php" class="btn-logout">🚪 Logout</a>
    </div>

    <div class="main-content">
        <h1>Dashboard</h1>
        <p>Your role: <strong><?php echo ucfirst($role); ?></strong></p>
    </div>
</body>

</html>