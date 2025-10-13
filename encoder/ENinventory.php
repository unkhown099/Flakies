<?php
    include("../config/db_connect.php");
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
    <link rel="stylesheet" href="../assets/ENcss/EN.css">
</head>
<body>
    <div class="ENsidebar">
        <div class="logo">
            <img src="..\assets\pictures\45b0e7c9-8bc1-4ef3-bac2-cfc07174d613.png" alt="Flakies Logo" >
        </div>
        <p class="welcome">Welcome, <?php echo htmlspecialchars($username); ?>!</p>

        <ul class="ENmenu">
            <li>
                <a href="ENdashboard.php">🏠 Dashboard</a>
            </li>
            <?php if ($role === 'encoder') : ?>
            <li>
                <a href="ENinventory.php">📦 Inventory</a>
            </li>            
            <?php endif; ?>
        </ul>

        <a href="logout.php" class="ENbtn-logout">🚪 Logout</a>
    </div>

    <section class="ENmain-content">
        <h1>INVENTORY</h1>
        <p>Your role: <strong><?php echo ucfirst($role); ?></strong></p>
        <?php
            $sql = "SELECT * FROM products";
            $query = mysqli_query($conn, $sql);
        ?>
        <table class="INtable">
            <tr>
                <th>Item ID</th>
                <th>Product Name</th>
                <th>Category</th>
                <th>Price</th>
                <th>Stock</th>
                <th>Created at</th>
            </tr>
            <tr>
                <?php while ($row = mysqli_fetch_assoc($query)) : ?>
                <td><?php echo htmlspecialchars($row['id']); ?></td>
                <td><?php echo htmlspecialchars($row['name']); ?></td>
                <td><?php echo htmlspecialchars($row['category']); ?></td>
                <td><?php echo htmlspecialchars($row['price']); ?></td>
                <td><?php echo htmlspecialchars($row['stock']); ?></td>
                <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                <?php endwhile; ?>
            </tr>
        </table>
        <?php
            mysqli_close($conn);
        ?>
    </section>
</body>
</html>