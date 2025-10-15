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
            <li>
                <a href="logout.php" class="ENbtn-logout">🚪 Logout</a>
            </li>
        </ul>
    </div>

    <section class="ENmain-content">
        <h1>DASHBOARD</h1>
        <p>Your role: <strong><?php echo ucfirst($role); ?></strong></p>
    </section>
</body>
</html>