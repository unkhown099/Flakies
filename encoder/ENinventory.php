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
            <li>
                <a href="logout.php" class="ENbtn-logout">🚪 Logout</a>
            </li>
        </ul>
    </div>

    <section class="ENmain-content">
        <h1>INVENTORY</h1>
        <p>Your role: <strong><?php echo ucfirst($role); ?></strong></p>
        <?php
            $sql1 = "SELECT * FROM products";
            $query1 = mysqli_query($conn, $sql1);
        ?>
        <div class="ENbtn-container">
            <input class="ENSearch-Text" type="text" name="search" placeholder="Search">
            <div class="ENadd-btn">
                <p>Add Product</p>
            </div>
        </div>

        <table class="INtable">
            <thead>
                <tr>
                    <th>Product Name</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Created at</th>
                </tr>
            </thead>
            <tbody id="productTableBody">
                <?php while ($row = mysqli_fetch_assoc($query1)) : ?>
                <tr>
                    <td><?php echo ($row['name']); ?></td>
                    <td><?php echo ($row['price']); ?>₱</td>
                    <td><?php echo ($row['stock']); ?></td>
                    <td><?php echo ($row['created_at']); ?></td>
                    <td>
                        delete
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php
            mysqli_close($conn);
        ?>
    </section>

    <!-- Modal Structure -->
    <div id="addProductModal" class="ENmodal">
        <div class="ENmodal-content">
            <span class="close-btn">&times;</span>
            <h2>Add New Product</h2>
            <form action="../encoder/ENfunctions/ENadd_product.php" method="post">
                <label>Product Name:</label>
                <input type="text" name="name" required><br>

                <label>Price:</label>
                <input type="number" step="0.01" name="price" required><br>

                <label>Stock Quantity:</label>
                <input type="number" name="stock" required><br>

                <button type="submit">Add Product</button>
            </form>
        </div>
    </div>
</body>
<script>
    const modal = document.getElementById("addProductModal");
    const openBtn = document.querySelector(".ENadd-btn"); // First "Add Product" button
    const closeBtn = document.querySelector(".close-btn");

    openBtn.addEventListener("click", () => {
        modal.style.display = "block";
    });

    closeBtn.addEventListener("click", () => {
        modal.style.display = "none";
    });

    window.addEventListener("click", (e) => {
        if (e.target == modal) {
            modal.style.display = "none";
        }
    });

    document.addEventListener("DOMContentLoaded", function () {
        const searchInput = document.querySelector('.ENSearch-Text');

        searchInput.addEventListener('keyup', function () {
            let searchValue = this.value;

            // Create an AJAX request
            let xhr = new XMLHttpRequest();
            xhr.open("GET", "../encoder/ENfunctions/ENsearch_products.php?search=" + encodeURIComponent(searchValue), true);

            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    document.getElementById('productTableBody').innerHTML = xhr.responseText;
                }
            };

            xhr.send();
            });
    });
</script>
</html>