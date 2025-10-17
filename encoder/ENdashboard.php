<?php
    session_start();

    // if not logged in, redirect to login
    if (!isset($_SESSION['staff_id'])) {
        header("Location: login.php");
        exit();
    }

    $role = $_SESSION['role'];
    $username2 = $_SESSION['username'];

    include("../config/db_connect.php");

    // Query to count products
    $result = $conn->query("SELECT COUNT(*) AS totalprod FROM products");

    // Fetch count
    $productCount = 0;
    if ($result && $row = $result->fetch_assoc()) {
        $productCount = $row['totalprod'];
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Flakies | Dashboard</title>
    <link rel="stylesheet" href="../assets/ENcss/EN.css">
</head>
<body>
    <div class="ENsidebar">
        <div class="logo">
            <img src="..\assets\pictures\45b0e7c9-8bc1-4ef3-bac2-cfc07174d613.png" alt="Flakies Logo" >
        </div>
        <p class="welcome">Welcome, <?php echo htmlspecialchars($username2); ?>!</p>

        <ul class="ENmenu">
            <li>
                <a href="ENdashboard.php">🏠 Dashboard</a>
            </li>
            <?php if ($role === 'encoder') : ?>
            <li>
                <a href="ENinventory.php">📦 Inventory</a>
            </li>            
            <?php endif; ?>
            <li>
                <a href="logout.php" class="ENbtn-logout">🚪 Logout</a>
            </li>
        </ul>
    </div>

    <section class="ENmain-content">
        <h1>DASHBOARD</h1>
        <p>Your role: <strong><?php echo ucfirst($role); ?></strong></p>

        <div class="DENpad-container">
            <div class="DENcards">
                <h2>Total Products:</h2>
                <h1><?php echo $productCount;?></h1>
            </div>
        </div>
        
        <h3 class="DENtable-wrapper-title">LOW STOCK PRODUCTS</h3>

        <div class="DENtable-wrapper">
            <table class="DENtable">
                <thead>
                    <tr>
                        <th class="DENtable-header">Product Name</th>
                        <th class="DENtable-header">Price</th>
                        <th class="DENtable-header">Stock</th>
                        <th class="DENtable-header">Created at</th>
                    </tr>
                </thead>

                <tbody id="productTableBody">
                    <?php
                        $sql1 = "SELECT * FROM products WHERE stock <= 10";
                        $query1 = mysqli_query($conn, $sql1);
                    ?>
                    <?php while ($row = mysqli_fetch_assoc($query1)) : ?>
                    <tr>
                        <td class="DENtable-data"><?php echo ($row['name']); ?></td>
                        <td class="DENtable-data"><?php echo ($row['price']); ?>₱</td>
                        <td class="DENtable-data"><?php echo ($row['stock']); ?></td>
                        <td class="DENtable-data"><?php echo ($row['created_at']); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <h3 class="DENtable-wrapper-title">10 NEW PRODUCTS INSERTED</h3>

        <div class="DENtable-wrapper">
            <table class="DENtable">
                <thead>
                    <tr>
                        <th class="DENtable-header">Product Name</th>
                        <th class="DENtable-header">Price</th>
                        <th class="DENtable-header">Stock</th>
                        <th class="DENtable-header">Created at</th>
                    </tr>
                </thead>

                <tbody id="productTableBody">
                    <?php
                        $sql2 = "SELECT * FROM products LIMIT 10";
                        $query2 = mysqli_query($conn, $sql2);
                    ?>
                    <?php while ($row = mysqli_fetch_assoc($query2)) : ?>
                    <tr>
                        <td class="DENtable-data"><?php echo ($row['name']); ?></td>
                        <td class="DENtable-data"><?php echo ($row['price']); ?>₱</td>
                        <td class="DENtable-data"><?php echo ($row['stock']); ?></td>
                        <td class="DENtable-data"><?php echo ($row['created_at']); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </section>
</body>
</html>