<?php
require __DIR__ . '/../config/db_connect.php';

// Filters
$filter_staff = $_GET['staff'] ?? '';
$filter_role = $_GET['role'] ?? '';
$filter_from = $_GET['from'] ?? '';
$filter_to = $_GET['to'] ?? '';

// Pagination setup
$limit = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

// Build WHERE clause
$whereClauses = [];
if ($filter_staff) $whereClauses[] = "staff.name LIKE '%" . $conn->real_escape_string($filter_staff) . "%'";
if ($filter_role) $whereClauses[] = "staff.role = '" . $conn->real_escape_string($filter_role) . "'";
if ($filter_from && $filter_to) $whereClauses[] = "DATE(account_actions.action_time) BETWEEN '$filter_from' AND '$filter_to'";
$whereSQL = $whereClauses ? "WHERE " . implode(' AND ', $whereClauses) : "";

// Count total for pagination
$totalQuery = "SELECT COUNT(*) as total 
               FROM account_actions 
               JOIN staff ON account_actions.staff_id = staff.id 
               $whereSQL";

$totalResult = $conn->query($totalQuery);
if (!$totalResult) {
    die("Query Error in totalQuery: " . $conn->error . "<br>SQL: " . $totalQuery);
}
$totalRow = $totalResult->fetch_assoc();
$total = $totalRow ? $totalRow['total'] : 0;
$totalPages = ceil($total / $limit);

// Fetch actions
$sql = "SELECT account_actions.*, staff.name, staff.role 
        FROM account_actions 
        JOIN staff ON account_actions.staff_id = staff.id 
        $whereSQL
        ORDER BY account_actions.action_time DESC 
        LIMIT $limit OFFSET $offset";

$result = $conn->query($sql);
if (!$result) {
    die("Query Error in data fetch: " . $conn->error . "<br>SQL: " . $sql);
}
$actions = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Manage Reports</title>
    <link rel="icon" type="image/x-icon" href="GEPOLEO-LOGO-FLAKIES-CIRCLE.png">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        :root {
            --primary: #d9ed42;
            --secondary: #d39e2a;
            --light: #e0d979ff;
            --dark: #000;
            --bg: #fefefe;
        }

        body {
            margin: 0;
            font-family: "Poppins", Arial, sans-serif;
            background: var(--light);
            display: flex;
            min-height: 100vh;
            color: var(--dark);
        }

        /* --- Sidebar --- */
        /* SIDEBAR */
        .sidebar {
            width: 260px;
            flex-shrink: 0;
            background: linear-gradient(180deg, #d9ed42 0%, #d39e2a 60%, #e0d979ff 100%);
            color: #000;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            align-items: flex-start;
            padding: 25px 20px;
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.1);
            border-top-right-radius: 20px;
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            margin: 0 !important;
            box-sizing: border-box !important;
        }

        main.container,
        .main-content {
            margin-left: 260px !important;
            padding: 40px 50px;
            background: #fafafa;
            min-height: 100vh;
            box-sizing: border-box;
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


        /* --- Main Content --- */
        .main-content {
            flex: 1;
            padding: 32px 40px;
            display: flex;
            flex-direction: column;
            background: var(--bg);
        }

        .header {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 20px;
            color: var(--dark);
            border-bottom: 3px solid var(--secondary);
            padding-bottom: 10px;
        }

        .table-container {
            background: #fff;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.08);
            flex: 1;
            overflow-x: auto;
        }

        /* --- Filters --- */
        .filter-form {
            display: flex;
            gap: 10px;
            margin-bottom: 18px;
            flex-wrap: wrap;
        }

        .filter-form input,
        .filter-form select,
        .filter-form button,
        .filter-form a {
            padding: 8px 12px;
            border-radius: 6px;
            border: 1px solid #ccc;
            font-size: 14px;
            font-weight: 500;
        }

        .filter-form button {
            background: var(--primary);
            color: #000;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: 0.3s;
        }

        .filter-form button:hover {
            background: var(--secondary);
            color: #fff;
        }

        .filter-form a {
            background: #000;
            color: #fff;
            text-decoration: none;
            font-weight: 600;
            transition: 0.3s;
        }

        .filter-form a:hover {
            background: #222;
        }

        /* --- Table --- */
        table {
            width: 100%;
            border-collapse: collapse;
            border-radius: 10px;
            overflow: hidden;
        }

        thead {
            background: var(--primary);
            color: #000;
        }

        th,
        td {
            text-align: left;
            padding: 12px 14px;
            border-bottom: 1px solid #eee;
            font-size: 14px;
        }

        th {
            font-weight: 700;
        }

        tbody tr:hover {
            background: var(--light);
        }

        /* --- Bulk buttons --- */
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
            transition: 0.3s;
        }

        .bulk-actions button:first-child {
            background: var(--secondary);
            color: #fff;
        }

        .bulk-actions button:first-child:hover {
            background: #000;
        }

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
            color: #000;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }

        .pagination a:hover {
            background: var(--primary);
        }

        .pagination a.active {
            background: var(--secondary);
            color: #fff;
            border-color: var(--secondary);
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
            <a href="manage_products.php">📦 Manage Products</a>
            <a class="active" href="manage_report.php">📊 Reports</a>
        </nav>
        <a class="btn-logout" href="../login/logout.php">🚪 Logout</a>
    </aside>

    <div class="main-content">
        <div class="header">📊 Reports</div>

        <form method="GET" class="filter-form">
            <input type="text" name="staff" placeholder="Search Staff..." value="<?= htmlspecialchars($filter_staff) ?>">
            <select name="role">
                <option value="">All Roles</option>
                <option value="cashier" <?= $filter_role == 'cashier' ? 'selected' : '' ?>>Cashier</option>
                <option value="encoder" <?= $filter_role == 'encoder' ? 'selected' : '' ?>>Encoder</option>
                <option value="admin" <?= $filter_role == 'admin' ? 'selected' : '' ?>>Admin</option>
                <option value="manager" <?= $filter_role == 'manager' ? 'selected' : '' ?>>Manager</option>
            </select>
            <input type="date" name="from" value="<?= htmlspecialchars($filter_from) ?>">
            <input type="date" name="to" value="<?= htmlspecialchars($filter_to) ?>">
            <button type="submit">Filter</button>
            <a href="manage_report.php">Reset</a>
        </form>

        <div class="table-container">
            <form id="reportForm">
                <div class="bulk-actions">
                    <button type="button" onclick="bulkDelete()">Delete Selected</button>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="selectAll" onclick="toggleAll(this)"></th>
                            <th>Staff (Role)</th>
                            <th>Action Type</th>
                            <th>Details</th>
                            <th>Action Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($actions) > 0): ?>
                            <?php foreach ($actions as $action): ?>
                                <tr>
                                    <td><input type="checkbox" name="action_ids[]" value="<?= $action['id']; ?>"></td>
                                    <td><?= htmlspecialchars($action['name']) ?> (<?= htmlspecialchars($action['role']) ?>)</td>
                                    <td><?= htmlspecialchars($action['action_type']); ?></td>
                                    <td><?= htmlspecialchars($action['action_detail']); ?></td>
                                    <td><?= htmlspecialchars($action['action_time']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" style="text-align:center;">No records found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </form>

            <div class="pagination">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?page=<?= $i; ?>&staff=<?= urlencode($filter_staff) ?>&role=<?= urlencode($filter_role) ?>&from=<?= urlencode($filter_from) ?>&to=<?= urlencode($filter_to) ?>"
                        class="<?= ($i == $page) ? 'active' : ''; ?>"><?= $i; ?></a>
                <?php endfor; ?>
            </div>
        </div>
    </div>

    <script>
        function toggleAll(source) {
            const checkboxes = document.querySelectorAll("input[name='action_ids[]']");
            checkboxes.forEach(cb => cb.checked = source.checked);
        }

        function bulkDelete() {
            const ids = Array.from(document.querySelectorAll("input[name='action_ids[]']:checked")).map(cb => cb.value);
            if (ids.length === 0) {
                return Swal.fire("No selection", "Please select actions to delete.", "info");
            }
            Swal.fire({
                title: "Delete Selected?",
                text: `You are about to delete ${ids.length} record(s).`,
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#000",
                cancelButtonColor: "#d39e2a",
                confirmButtonText: "Yes, delete"
            }).then(result => {
                if (result.isConfirmed) {
                    fetch("delete_action.php", {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/x-www-form-urlencoded"
                            },
                            body: "ids=" + encodeURIComponent(ids.join(',')) + "&bulk=true"
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire("Deleted!", "Selected actions have been deleted.", "success")
                                    .then(() => location.reload());
                            } else {
                                Swal.fire("Error!", data.message, "error");
                            }
                        })
                        .catch(() => Swal.fire("Error!", "Failed to delete records.", "error"));
                }
            });
        }
    </script>
</body>

</html>