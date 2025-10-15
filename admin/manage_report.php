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
      <a href="manage_products.php">📦 Manage Products</a>
      <a class="active" href="manage_report.php">📊 Reports</a>
    </nav>
    <a class="btn-logout" href="../login/logout.php">🚪 Logout</a>
</aside>

    <div class="main-content">
        <div class="header">Reports</div>

        <!-- Filters -->
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
            <a href="manage_report.php" style="color:white;text-decoration:none;margin-left:10px;">Reset</a>
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
                            <tr><td colspan="5" style="text-align:center;">No records found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </form>

            <!-- Pagination -->
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
            const ids = Array.from(document.querySelectorAll("input[name='action_ids[]']:checked"))
                .map(cb => cb.value);
            if (ids.length === 0) {
                return Swal.fire("No selection", "Please select actions to delete.", "info");
            }
            Swal.fire({
                title: "Delete Selected?",
                text: `You are about to delete ${ids.length} record(s).`,
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#d33",
                cancelButtonColor: "#3085d6",
                confirmButtonText: "Yes, delete"
            }).then(result => {
                if (result.isConfirmed) {
                    fetch("delete_action.php", {
                        method: "POST",
                        headers: {"Content-Type": "application/x-www-form-urlencoded"},
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
