<?php
session_start();

// ✅ Block unauthorized users
if (!isset($_SESSION['staff_id'])) {
    header("Location: ../login.php");
    exit();
}

if ($_SESSION['role'] !== 'admin') {
    header("Location: ../admin_dashboard.php");
    exit();
}

require __DIR__ . '/../config/db_connect.php';

// Pagination setup
$limit = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

$totalQuery = "SELECT COUNT(*) as total FROM products";
$totalResult = $conn->query($totalQuery);
$total = $totalResult->fetch_assoc()['total'] ?? 0;
$totalPages = ceil($total / $limit);

$sql = "SELECT * FROM products ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
$result = $conn->query($sql);
$products = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Products | Admin</title>
    <link rel="icon" href="../assets/logo-placeholder.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
    :root {
        --accent1: #6b4226;
        --accent2: #4e2d12;
        --muted: #f6f7fb;
        --text-dark: #2c2c2c;
        --text-light: #666;
    }
    body {
        margin: 0;
        font-family: "Poppins", Arial, sans-serif;
        background: var(--muted);
        display: flex;
        min-height: 100vh;
        color: var(--text-dark);
    }
    /* --- Sidebar stays unchanged --- */
    .sidebar {
        width: 250px;
        background: linear-gradient(180deg, var(--accent1), var(--accent2));
        color: #fff;
        padding: 24px;
        display: flex;
        flex-direction: column;
        align-items: center;
        border-top-right-radius: 18px;
    }
    .sidebar h2 {
        margin: 0 0 6px;
        font-size: 20px;
    }
    .sidebar .welcome {
        color: #e5d1b8;
        font-size: 13px;
        margin-bottom: 18px;
    }
    .sidebar .menu {
        list-style: none;
        padding: 0;
        width: 100%;
    }
    .sidebar .menu a {
        display: block;
        padding: 10px 14px;
        color: #fff;
        text-decoration: none;
        border-radius: 8px;
        margin: 8px 0;
        font-weight: 600;
        transition: background 0.2s;
    }
    .sidebar .menu a.active, .sidebar .menu a:hover {
        background: rgba(255, 255, 255, 0.1);
    }
    .btn-logout {
        margin-top: auto;
        background: #fff;
        color: var(--accent2);
        padding: 10px 14px;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 700;
    }

    /* --- Main Content Styling --- */
    .main-content {
        flex: 1;
        padding: 32px 40px;
        display: flex;
        flex-direction: column;
    }

    .header {
        font-size: 24px;
        font-weight: 700;
        margin-bottom: 20px;
        color: var(--accent2);
        border-bottom: 2px solid #ddd;
        padding-bottom: 10px;
    }

    .table-container {
        background: #fff;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 4px 14px rgba(0,0,0,0.08);
        flex: 1;
        overflow-x: auto;
    }

    .bulk-actions {
        display: flex;
        gap: 10px;
        margin-bottom: 16px;
    }

    .bulk-actions button {
        padding: 10px 14px;
        border-radius: 8px;
        border: none;
        cursor: pointer;
        font-weight: 600;
        transition: 0.2s;
    }

    .bulk-actions button:first-child {
        background: var(--accent1);
        color: #fff;
    }

    .bulk-actions button:first-child:hover {
        background: var(--accent2);
    }

    .bulk-actions button:last-child {
        background: #dc3545;
        color: #fff;
    }

    .bulk-actions button:last-child:hover {
        background: #bb2d3b;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        border-radius: 10px;
        overflow: hidden;
    }

    thead {
        background: #fafafa;
    }

    th, td {
        text-align: left;
        padding: 12px 14px;
        border-bottom: 1px solid #eee;
        font-size: 14px;
    }

    th {
        font-weight: 700;
        color: var(--text-dark);
    }

    tbody tr:hover {
        background: #f9f9f9;
    }

    .active-btn {
        border: none;
        padding: 6px 10px;
        border-radius: 6px;
        color: #fff;
        cursor: pointer;
        transition: 0.2s;
    }
    .active-btn.active { background: #28a745; }
    .active-btn.inactive { background: #dc3545; }
    .active-btn:hover { opacity: 0.85; }

    .delete-btn {
        background: #ff4757;
        color: #fff;
        border: none;
        border-radius: 6px;
        padding: 6px 10px;
        cursor: pointer;
        transition: 0.2s;
    }
    .delete-btn:hover { background: #e84118; }

    /* --- Pagination --- */
    .pagination {
        margin-top: 20px;
        text-align: center;
    }
    .pagination a {
        display: inline-block;
        padding: 8px 12px;
        margin: 0 4px;
        background: #fff;
        border: 1px solid #ddd;
        border-radius: 6px;
        color: var(--accent1);
        text-decoration: none;
        font-weight: 600;
        transition: all 0.2s;
    }
    .pagination a:hover {
        background: var(--accent1);
        color: #fff;
    }
    .pagination a.active {
        background: var(--accent2);
        color: #fff;
        border-color: var(--accent2);
    }
    </style>
</head>
<body>

<aside class="sidebar">
    <h2>Flakies</h2>
    <div class="welcome">Admin Panel</div>
    <nav class="menu">
      <a href="dashboard.php">🏠 Dashboard</a>
      <a href="manage_users.php">👥 Manage Users</a>
      <a class="active" href="manage_products.php">📦 Manage Products</a>
      <a href="manage_report.php">📊 Reports</a>
    </nav>
    <a class="btn-logout" href="../login/logout.php">🚪 Logout</a>
</aside>

<div class="main-content">
    <div class="header">📦 Product Inventory</div>
    <div class="table-container">
        <form id="productForm">
            <div class="bulk-actions">
                <button type="button" onclick="bulkToggle()">Toggle Active</button>
                <button type="button" onclick="bulkDelete()">Delete Selected</button>
            </div>

            <table>
                <thead>
                    <tr>
                        <th><input type="checkbox" id="selectAll" onclick="toggleAll(this)"></th>
                        <th>Name</th>
                        <th>Stock</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td><input type="checkbox" name="product_ids[]" value="<?= $product['id'] ?>"></td>
                            <td><?= htmlspecialchars($product['name']) ?></td>
                            <td><?= htmlspecialchars($product['stock']) ?></td>
                            <td>₱<?= number_format($product['price'], 2) ?></td>
                            <td>
                                <button type="button"
                                    class="active-btn <?= $product['product_status'] == 1 ? 'active' : 'inactive' ?>"
                                    onclick="toggleStatus(<?= $product['id'] ?>, <?= $product['product_status'] ?>)">
                                    <?= $product['product_status'] == 1 ? 'Active' : 'Inactive' ?>
                                </button>
                            </td>
                            <td>
                                <button type="button" class="delete-btn" onclick="confirmDelete(<?= $product['id'] ?>)">Delete</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </form>

        <div class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?= $i ?>" class="<?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
    </div>
</div>

<script>
function toggleStatus(id, currentStatus) {
    const newStatus = currentStatus === 1 ? 0 : 1;
    fetch(`toggle_status.php?id=${id}&status=${newStatus}`)
        .then(() => location.reload());
}

function confirmDelete(id) {
    Swal.fire({
        title: "Delete Product?",
        text: "This action cannot be undone.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#3085d6",
        confirmButtonText: "Yes, delete it!"
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(`delete_product.php?id=${id}`)
                .then(() => Swal.fire("Deleted!", "Product deleted.", "success"))
                .then(() => location.reload());
        }
    });
}

function toggleAll(source) {
    const checkboxes = document.querySelectorAll("input[name='product_ids[]']");
    checkboxes.forEach(cb => cb.checked = source.checked);
}

function getSelectedIds() {
    return Array.from(document.querySelectorAll("input[name='product_ids[]']:checked"))
        .map(cb => cb.value);
}

function bulkToggle() {
    const ids = getSelectedIds();
    if (ids.length === 0) return Swal.fire("No selection", "Select products first.", "info");

    fetch("toggle_status.php", {
        method: "POST",
        body: new URLSearchParams({ ids: ids.join(','), bulk: true })
    }).then(() => location.reload());
}

function bulkDelete() {
    const ids = getSelectedIds();
    if (ids.length === 0) return Swal.fire("No selection", "Select products to delete.", "info");

    Swal.fire({
        title: "Delete Selected?",
        text: `You are about to delete ${ids.length} product(s).`,
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#3085d6",
        confirmButtonText: "Yes, delete"
    }).then(result => {
        if (result.isConfirmed) {
            fetch("delete_product.php", {
                method: "POST",
                body: new URLSearchParams({ ids: ids.join(','), bulk: true })
            }).then(() => location.reload());
        }
    });
}
</script>

</body>
</html>
