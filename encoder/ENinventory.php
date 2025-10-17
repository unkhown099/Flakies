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
        <div class="INENbtn-container">
            <input class="INENSearch-Text" type="text" name="search" placeholder="Search">
            <div class="INENadd-btn">
                <p>Add Product</p>
            </div>
        </div>

        <div class="INENtable-wrapper">
            <table class="INENtable">
                <thead>
                    <tr>
                        <th class="table-header">Product Name</th>
                        <th class="table-header">Price</th>
                        <th class="table-header">Stock</th>
                        <th class="table-header ">Created at</th>
                    </tr>
                </thead>

                <tbody id="productTableBody">
                    <?php
                        $sql1 = "SELECT * FROM products";
                        $query1 = mysqli_query($conn, $sql1);
                    ?>
                    <?php while ($row = mysqli_fetch_assoc($query1)) : ?>
                    <tr>
                        <td class="table-data"><?php echo ($row['name']); ?></td>
                        <td class="table-data"><?php echo ($row['price']); ?>₱</td>
                        <td class="table-data"><?php echo ($row['stock']); ?></td>
                        <td class="table-data"><?php echo ($row['created_at']); ?></td>
                        <td class="INENdel-btn-container">
                            <button class="INENdel-btn" data-id="<?php echo $row['id'];?>">Delete</button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php
            mysqli_close($conn);
        ?>
    </section>

    <!-- Modal Structure -->
    <div id="addProductModal" class="INENmodal">
        <div class="INENmodal-content">
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
    const openBtn = document.querySelector(".INENadd-btn");
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
        const searchInput = document.querySelector('.INENSearch-Text');

        searchInput.addEventListener('keyup', function () {
            let searchValue = this.value;

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
    document.addEventListener('click', function (e) {
        if (e.target && e.target.classList.contains('INENdel-btn')) {
            const button = e.target;
            const productId = button.getAttribute('data-id');

            if (confirm("Are you sure you want to delete this product?")) {
                fetch('../encoder/ENfunctions/ENdelete_product.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'id=' + encodeURIComponent(productId)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        // Remove the row from the table
                        const row = button.closest('tr');
                        row.remove();
                    } 
                    else {
                        alert("Error deleting product: " + data.message);
                    }
                })
                .catch(error => {
                    console.error("Fetch error:", error);
                });
            }
        }
    });
</script>
</html>