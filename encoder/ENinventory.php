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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

</head>

<body>
    <div class="ENsidebar">
        <div class="logo">
            <img src="..\assets\pictures\45b0e7c9-8bc1-4ef3-bac2-cfc07174d613.png" alt="Flakies Logo">
            <span>Flakies</span>
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
                <a href="../login/logout.php" class="logout-link">Logout</a>
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
                        <th class="table-header">Category</th>
                        <th class="table-header">Description</th>
                        <th class="table-header">Price</th>
                        <th class="table-header">Stock</th>
                        <th class="table-header">Image</th>
                        <th class="table-header ">Created at</th>
                        <th class="table-header ">Updated at</th>
                        <th class="table-header ">Actions</th>
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
                            <td class="table-data"><?php echo ($row['category']); ?></td>
                            <td class="table-data"><?php echo ($row['description']); ?></td>
                            <td class="table-data"><?php echo ($row['price']); ?>₱</td>
                            <td class="table-data"><?php echo ($row['stock']); ?></td>
                            <td class="table-data"><?php echo ($row['image']); ?></td>
                            <td class="table-data"><?php echo ($row['created_at']); ?></td>
                            <td class="table-data"><?php echo ($row['updated_at']); ?></td>
                            <td class="INENtable-btn-container">
                                <button class="INENdel-btn" data-id="<?php echo $row['id']; ?>">Delete</button>
                                <button 
                                    class="INENedit-btn"
                                    data-id="<?php echo ($row['id']); ?>"
                                    data-name="<?php echo ($row['name']); ?>"
                                    data-category="<?php echo ($row['category']); ?>"
                                    data-description="<?php echo ($row['description']); ?>"
                                    data-price="<?php echo ($row['price']); ?>"
                                    data-stock="<?php echo ($row['stock']); ?>"
                                >Edit</button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <div class="INENpagination-container">
            <button id="prev-btn" class="page-btn">Previous</button>
            <span id="page-info"></span>
            <button id="next-btn" class="page-btn">Next</button>
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

                <label>Category:</label>
                <input type="text" name="category" required><br>

                <label>Description:</label>
                <textarea type="text" name="description" rows="8" style="height: 100%; resize: none; overflow-y: auto"></textarea><br>

                <label>Price:</label>
                <input type="number" step="0.01" name="price" required><br>

                <label>Stock Quantity:</label>
                <input type="number" name="stock" required><br>

                <label>Image:</label>
                <input type="file" name="image" accept="image/*" required><br>

                <button type="submit">Add Product</button>
            </form>
        </div>
    </div>

    <div id="INENeditModal" class="INENedit-modal" style="display: none;">
        <div class="INENeditmodal-content">
            <span class="INENedit-close-btn">&times;</span>
            <h2>Edit product</h2>
            <form id="editForm" method="POST" action="../encoder/ENfunctions/ENupdate_product.php">
                <input type="hidden" name="id" id="edit-id">

                <label>Product Name:</label>
                <input type="text" name="name" id="edit-name"><br>

                <label>Category:</label>
                <input type="text" name="category" id="edit-category"><br>

                <label>Description:</label>
                <textarea name="description" id="edit-description" rows="8" style="height: 100%; resize: none; overflow-y: auto"></textarea><br>

                <label>Price:</label>
                <input type="number" name="price" id="edit-price" step="0.01"><br>

                <label>Stock Quantity:</label>
                <input type="number" name="stock" id="edit-stock"><br>

                <button type="submit">Save Changes</button>
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

    document.addEventListener("DOMContentLoaded", function() {
        const searchInput = document.querySelector('.INENSearch-Text');

        searchInput.addEventListener('keyup', function() {
            let searchValue = this.value;

            let xhr = new XMLHttpRequest();
            xhr.open("GET", "../encoder/ENfunctions/ENsearch_products.php?search=" + encodeURIComponent(searchValue), true);

            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    document.getElementById('productTableBody').innerHTML = xhr.responseText;
                }
            };

            xhr.send();
        });
    });
    document.addEventListener('click', function(e) {
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
                        } else {
                            alert("Error deleting product: " + data.message);
                        }
                    })
                    .catch(error => {
                        console.error("Fetch error:", error);
                    });
            }
        }
    });

    document.querySelectorAll('.INENedit-btn').forEach(button => {
        button.addEventListener('click', () => {
            const id = button.dataset.id;
            const name = button.dataset.name;
            const category = button.dataset.category;
            const description = button.dataset.description;
            const price = button.dataset.price;
            const stock = button.dataset.stock;

            document.getElementById('edit-id').value = id;
            document.getElementById('edit-name').value = name;
            document.getElementById('edit-category').value = category;
            document.getElementById('edit-description').value = description;
            document.getElementById('edit-price').value = price;
            document.getElementById('edit-stock').value = stock;

            document.getElementById('INENeditModal').style.display = 'block';
        });
    });
    document.querySelector('.INENedit-close-btn').addEventListener('click', () => {
        document.getElementById('INENeditModal').style.display = 'none';
    });

    document.addEventListener('DOMContentLoaded', function () {
    const tableBody = document.getElementById('productTableBody');
    const rows = Array.from(tableBody.getElementsByTagName('tr'));
    const itemsPerPage = 10; // 👈 adjust number of rows per page
    let currentPage = 1;

    const prevBtn = document.getElementById('prev-btn');
    const nextBtn = document.getElementById('next-btn');
    const pageInfo = document.getElementById('page-info');

    function renderPage(page) {
      const totalPages = Math.ceil(rows.length / itemsPerPage);

      // Hide all rows
      rows.forEach(row => row.style.display = 'none');

      // Show only the current page’s rows
      const start = (page - 1) * itemsPerPage;
      const end = start + itemsPerPage;
      rows.slice(start, end).forEach(row => row.style.display = '');

      // Update pagination info
      pageInfo.textContent = `Page ${page} of ${totalPages}`;

      // Enable/disable buttons
      prevBtn.disabled = page === 1;
      nextBtn.disabled = page === totalPages;
    }

    // Button listeners
    prevBtn.addEventListener('click', () => {
      if (currentPage > 1) {
        currentPage--;
        renderPage(currentPage);
      }
    });

    nextBtn.addEventListener('click', () => {
      if (currentPage < Math.ceil(rows.length / itemsPerPage)) {
        currentPage++;
        renderPage(currentPage);
      }
    });

    // Initialize
    renderPage(currentPage);
  });
</script>
</html>