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
    <link rel="stylesheet" href="../assets/css/sidebar.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --gold1: #d9ed42;
            --gold2: #d39e2a;
            --cream: #e0d979ff;
            --dark: #000;
            --light: #fafafa;
            --muted: #f7f8fa;
        }

        /* Reset + Base */
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: "Poppins", sans-serif;
            display: flex;
            min-height: 100vh;
            background: var(--muted);
            color: var(--dark);
        }

        /* SIDEBAR */
        .sidebar {
            width: 260px;
            background: linear-gradient(180deg, var(--gold1) 0%, var(--gold2) 60%, var(--cream) 100%);
            color: #000;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            padding: 24px 20px;
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.1);
            border-top-right-radius: 20px;
            position: fixed;
            top: 0;
            bottom: 0;
        }

        .sidebar h2 {
            font-size: 26px;
            font-weight: 800;
            margin: 0 0 8px;
        }

        .sidebar .welcome {
            font-size: 14px;
            color: rgba(0, 0, 0, 0.7);
            margin-bottom: 25px;
            font-weight: 500;
        }

        .menu {
            list-style: none;
            width: 100%;
            padding: 0;
            margin: 0;
            display: flex;
            flex-direction: column;
            gap: 6px;
            /* consistent even spacing between each item */
        }

        .menu li {
            margin: 0;
        }

        .menu a {
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            color: #000;
            font-weight: 600;
            padding: 12px 18px;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .menu a:hover,
        .menu a.active {
            background: rgba(0, 0, 0, 0.1);
            color: #000;
            transform: translateX(4px);
        }


        .sidebar .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 26px;
            font-weight: 800;
            margin-bottom: 8px;
        }

        .sidebar .logo img {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            object-fit: cover;
        }


        /* LOGOUT BUTTON — Professional Version */
        .btn-logout {
            margin-top: auto;
            width: 100%;
            background: linear-gradient(135deg, #000 0%, #222 100%);
            color: #fff;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 15px;
            padding: 12px 0;
            text-align: center;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.25);
            position: relative;
            overflow: hidden;
        }

        /* Gold accent glow when hovered */
        .btn-logout::before {
            content: "";
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(120deg, rgba(255, 255, 255, 0.15), rgba(255, 255, 255, 0));
            transition: all 0.5s ease;
        }

        .btn-logout:hover::before {
            left: 100%;
        }

        .btn-logout:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            background: linear-gradient(135deg, #111 0%, #000 100%);
        }

        /* Optional small gold outline on hover */
        .btn-logout:hover {
            border: 1px solid #e0c65a;
        }


        /* MAIN CONTENT */
        .main-content {
            flex: 1;
            margin-left: 260px;
            padding: 40px 50px;
            background: var(--light);
        }

        /* === GLOBAL HEADER STYLING === */
        h1,
        h2,
        h3 {
            font-family: "Poppins", sans-serif;
            font-weight: 700;
            color: #000;
            letter-spacing: 0.5px;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
            position: relative;
        }

        /* Subtle underline accent for top-level headers */
        h1::after,
        h2::after,
        h3::after {
            content: "";
            flex-grow: 1;
            height: 3px;
            border-radius: 10px;
            background: linear-gradient(90deg, #d9ed42, #d39e2a);
            margin-left: 12px;
            opacity: 0.4;
        }

        /* Sizes for hierarchy */
        h1 {
            font-size: 28px;
        }

        h2 {
            font-size: 22px;
        }

        h3 {
            font-size: 18px;
            font-weight: 600;
            color: #333;
        }

        /* Optional: give icons or emojis inside headers consistent look */
        h1 span.icon,
        h2 span.icon,
        h3 span.icon {
            font-size: 24px;
        }


        /* BUTTONS */
        .btn-primary {
            background: linear-gradient(135deg, var(--gold1), var(--gold2));
            color: #000;
            font-weight: 700;
            padding: 10px 16px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            transition: 0.3s ease;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, var(--gold2), var(--gold1));
            transform: translateY(-2px);
        }

        .btn-danger {
            background: #e74c3c;
            color: #fff;
            border: none;
            padding: 10px 16px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            transition: 0.3s;
        }

        .btn-danger:hover {
            background: #c0392b;
            transform: translateY(-2px);
        }

        /* TABLE WRAPPER */
        .table-container {
            background: #fff;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.08);
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
        }

        th,
        td {
            padding: 12px 14px;
            text-align: left;
            border-bottom: 1px solid #eee;
            font-size: 14px;
        }

        th {
            background: #fafafa;
            font-weight: 700;
            color: #000;
        }

        tbody tr:hover {
            background: #f9f9f9;
        }

        /* BUTTONS INSIDE TABLE */
        .active-btn {
            border: none;
            padding: 6px 10px;
            border-radius: 6px;
            color: #fff;
            cursor: pointer;
            transition: 0.2s;
            font-weight: 600;
        }

        .active-btn.active {
            background: #27ae60;
        }

        .active-btn.inactive {
            background: #e74c3c;
        }

        .active-btn:hover {
            opacity: 0.85;
        }

        .delete-btn {
            background: #e74c3c;
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 6px 10px;
            cursor: pointer;
            transition: 0.2s;
        }

        .delete-btn:hover {
            background: #c0392b;
        }

        /* PAGINATION */
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
            color: #000;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.2s;
        }

        .pagination a:hover {
            background: linear-gradient(135deg, var(--gold1), var(--gold2));
            color: #000;
        }

        .pagination a.active {
            background: linear-gradient(135deg, var(--gold2), var(--gold1));
            color: #000;
            border-color: transparent;
        }

        /* CHECKBOX AREA + ACTIONS */
        .bulk-actions {
            display: flex;
            gap: 10px;
            margin-bottom: 16px;
        }

        .bulk-actions button {
            border: none;
            padding: 10px 14px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: 0.3s ease;
        }

        .bulk-actions button:first-child {
            background: linear-gradient(135deg, var(--gold1), var(--gold2));
            color: #000;
        }

        .bulk-actions button:first-child:hover {
            background: linear-gradient(135deg, var(--gold2), var(--gold1));
        }

        .bulk-actions button:last-child {
            background: #e74c3c;
            color: #fff;
        }

        .bulk-actions button:last-child:hover {
            background: #c0392b;
        }
    </style>
</head>

<body>

    <aside class="sidebar">
        <div class="logo">
            <img src="../assets/pictures/45b0e7c9-8bc1-4ef3-bac2-cfc07174d613.png" alt="Flakies Logo">
            <span>Flakies</span>
        </div>

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
        <h1><span class="icon">📦</span> Product Inventory</h1>
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
                body: new URLSearchParams({
                    ids: ids.join(','),
                    bulk: true
                })
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
                        body: new URLSearchParams({
                            ids: ids.join(','),
                            bulk: true
                        })
                    }).then(() => location.reload());
                }
            });
        }
    </script>

</body>

</html>