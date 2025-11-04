<?php
session_start();

if (!isset($_SESSION['staff_id'], $_SESSION['role'])) {
    header("Location: ../login/login.php");
    exit();
}

if ($_SESSION['role'] !== 'admin') {
    echo "Access denied. You must be an admin to view this page.";
    exit();
}

require __DIR__ . '/../config/db_connect.php';

// Handle schedule actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Sanitize inputs
    $staff_id = (int) ($_POST['user_id'] ?? 0);
    $shift = $_POST['shift'] ?? 'morning';
    $shift_date = $_POST['schedule_date'] ?? date('Y-m-d');
    $shift_start = $_POST['shift_start'] ?? '08:00';
    $shift_end = $_POST['shift_end'] ?? '17:00';
    $notes = $_POST['notes'] ?? '';

    // Ensure time format for MySQL
    $shift_start = date('H:i:s', strtotime($shift_start));
    $shift_end   = date('H:i:s', strtotime($shift_end));

    if ($action === 'add') {
        $stmt = $conn->prepare("INSERT INTO schedules (staff_id, shift, shift_date, shift_start, shift_end, notes) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssss", $staff_id, $shift, $shift_date, $shift_start, $shift_end, $notes);
        $stmt->execute();
        $stmt->close();

        echo json_encode(['status' => 'success', 'message' => 'Schedule added successfully']);
        exit();
    }

    if ($action === 'edit') {
        $schedule_id = (int) ($_POST['schedule_id'] ?? 0);
        $stmt = $conn->prepare("UPDATE schedules SET staff_id=?, shift=?, shift_date=?, shift_start=?, shift_end=?, notes=? WHERE id=?");
        $stmt->bind_param("isssssi", $staff_id, $shift, $shift_date, $shift_start, $shift_end, $notes, $schedule_id);
        $stmt->execute();
        $stmt->close();

        header("Location: manage_report.php?success=updated");
        exit();
    }

    if ($action === 'delete') {
        $schedule_id = (int) ($_POST['schedule_id'] ?? 0);
        $stmt = $conn->prepare("DELETE FROM schedules WHERE id=?");
        $stmt->bind_param("i", $schedule_id);
        $stmt->execute();
        $stmt->close();

        header("Location: manage_report.php?success=deleted");
        exit();
    }
}

// Fetch staff members
$staffQuery = "SELECT id, username, role FROM staff WHERE role IN ('cashier', 'admin') ORDER BY username ASC";
$staffResult = $conn->query($staffQuery);
$staffMembers = $staffResult->fetch_all(MYSQLI_ASSOC);

// Filters
$filter_date = $_GET['filter_date'] ?? date('Y-m-d');
$filter_user = $_GET['filter_user'] ?? 'all';

// Fetch schedules
$scheduleQuery = "
    SELECT s.id, s.staff_id, s.shift_date, s.shift, s.shift_start, s.shift_end, s.notes, u.username, u.role
    FROM schedules s
    JOIN staff u ON s.staff_id = u.id
    WHERE 1=1
";

if ($filter_date !== 'all') {
    $scheduleQuery .= " AND s.shift_date = '$filter_date'";
}

if ($filter_user !== 'all') {
    $scheduleQuery .= " AND s.staff_id = '$filter_user'";
}

$scheduleQuery .= " ORDER BY s.shift_date DESC, s.shift_start ASC";

$scheduleResult = $conn->query($scheduleQuery);
$schedules = $scheduleResult->fetch_all(MYSQLI_ASSOC);

// Statistics
$todaySchedules = $conn->query("SELECT COUNT(*) as count FROM schedules WHERE shift_date = CURDATE()")->fetch_assoc()['count'];
$weekSchedules = $conn->query("SELECT COUNT(*) as count FROM schedules WHERE WEEK(shift_date) = WEEK(CURDATE()) AND YEAR(shift_date) = YEAR(CURDATE())")->fetch_assoc()['count'];
$totalStaff = $conn->query("SELECT COUNT(*) as count FROM staff WHERE role IN ('cashier', 'admin')")->fetch_assoc()['count'];

$conn->close();
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Flakies | Staff Schedule</title>
    <link rel="icon" type="image/x-icon" href="../assets/pictures/45b0e7c9-8bc1-4ef3-bac2-cfc07174d613.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            display: flex;
            height: 100vh;
            margin: 0;
            font-family: "Poppins", sans-serif;
            background: #f7f8fa;
            color: #222;
        }

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
            margin: 0;
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
            border: 1px solid #e0c65a;
        }

        /* MAIN CONTENT */
        .main-content {
            flex-grow: 1;
            margin-left: 260px;
            padding: 40px 50px;
            background: #fafafa;
            overflow-y: auto;
        }

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

        /* DASHBOARD CARDS */
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .card {
            background: white;
            border-radius: 12px;
            padding: 20px 25px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card:hover {
            transform: translateY(-4px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.12);
        }

        .card h3 {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 5px;
            color: #333;
        }

        .card h3::after {
            display: none;
        }

        .card p {
            font-size: 26px;
            font-weight: 700;
            color: #000;
            margin: 0;
        }

        .card small {
            color: gray;
        }

        /* FILTER AND ACTION BAR */
        .action-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            flex-wrap: wrap;
            gap: 15px;
        }

        .filter-group {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }

        .filter-group label {
            font-size: 15px;
            font-weight: 600;
            color: #333;
        }

        .filter-group input,
        .filter-group select {
            padding: 10px 15px;
            border-radius: 8px;
            border: 1px solid #ccc;
            font-size: 14px;
            font-family: "Poppins", sans-serif;
        }

        .btn-add {
            background: linear-gradient(135deg, #d9ed42, #d39e2a);
            color: #000;
            border: none;
            border-radius: 10px;
            padding: 12px 25px;
            font-weight: 700;
            font-size: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.15);
        }

        .btn-add:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.25);
        }

        /* TABLE */
        .table-container {
            background: #fff;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: linear-gradient(135deg, #d9ed42, #d39e2a);
        }

        thead th {
            padding: 15px;
            text-align: left;
            font-weight: 700;
            color: #000;
            font-size: 15px;
        }

        tbody tr {
            border-bottom: 1px solid #eee;
            transition: background 0.3s ease;
        }

        tbody tr:hover {
            background: #f9f9f9;
        }

        tbody td {
            padding: 15px;
            font-size: 14px;
            color: #333;
        }

        .badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-cashier {
            background: #d1ecf1;
            color: #0c5460;
        }

        .badge-admin {
            background: #f8d7da;
            color: #721c24;
        }

        .btn-edit,
        .btn-delete {
            padding: 8px 15px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 13px;
            margin-right: 5px;
        }

        .btn-edit {
            background: #ffc107;
            color: #000;
        }

        .btn-edit:hover {
            background: #e0a800;
            transform: translateY(-2px);
        }

        .btn-delete {
            background: #dc3545;
            color: #fff;
        }

        .btn-delete:hover {
            background: #c82333;
            transform: translateY(-2px);
        }

        /* MODAL */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: #fff;
            padding: 30px;
            border-radius: 15px;
            width: 500px;
            max-width: 90%;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        .modal-header {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 20px;
            color: #000;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #333;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 14px;
            font-family: "Poppins", sans-serif;
            box-sizing: border-box;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #d39e2a;
            box-shadow: 0 0 0 3px rgba(211, 158, 42, 0.1);
        }

        .form-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 20px;
        }

        .btn-submit {
            background: linear-gradient(135deg, #d9ed42, #d39e2a);
            color: #000;
            border: none;
            padding: 10px 25px;
            border-radius: 8px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .btn-cancel {
            background: #6c757d;
            color: #fff;
            border: none;
            padding: 10px 25px;
            border-radius: 8px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-cancel:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }
    </style>
</head>

<body>
    <div class="sidebar">
        <div class="logo">
            <img src="../assets/pictures/45b0e7c9-8bc1-4ef3-bac2-cfc07174d613.png" alt="Flakies Logo">
            <span>Flakies</span>
        </div>
        <div class="welcome"><?= ucfirst($_SESSION['role']) ?> Panel</div>
        <ul class="menu">
            <li><a href="dashboard.php">🏠 Dashboard</a></li>
            <li><a href="manage_users.php">👥 Manage Users</a></li>
            <li><a href="manage_products.php">📦 Manage Products</a></li>
            <li><a href="manage_report.php" class="active">📅 Staff Schedule</a></li>
            <li><a href="manage_pages.php">📝 Manage Pages</a></li>
        </ul>
        <a href="../login/logout.php" class="btn-logout">🚪 Logout</a>
    </div>

    <div class="main-content">
        <h1>📅 Staff Schedule Management</h1>

        <!-- Statistics Cards -->
        <div class="dashboard-cards">
            <div class="card">
                <h3>Today's Shifts</h3>
                <p><?= $todaySchedules; ?></p>
                <small><?= date("F d, Y"); ?></small>
            </div>
            <div class="card">
                <h3>This Week</h3>
                <p><?= $weekSchedules; ?></p>
                <small>Total scheduled shifts</small>
            </div>
            <div class="card">
                <h3>Total Staff</h3>
                <p><?= $totalStaff; ?></p>
                <small>Active staff members</small>
            </div>
        </div>

        <!-- Action Bar -->
        <div class="action-bar">
            <form method="GET" class="filter-group">
                <label>Date:</label>
                <input type="date" name="filter_date" value="<?= $filter_date; ?>" min="<?= date('Y-m-d'); ?>">


                <label>Staff:</label>
                <select name="filter_user">
                    <option value="all" <?= $filter_user === 'all' ? 'selected' : ''; ?>>All Staff</option>
                    <?php foreach ($staffMembers as $staff): ?>
                        <option value="<?= $staff['id']; ?>" <?= $filter_user == $staff['id'] ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($staff['username']); ?> (<?= ucfirst($staff['role']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>

                <button type="submit" class="btn-add">🔍 Filter</button>
            </form>

            <button class="btn-add" onclick="openAddModal()">➕ Add Schedule</button>
        </div>

        <!-- Schedules Table -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Staff Member</th>
                        <th>Role</th>
                        <th>Date</th>
                        <th>Shift Start</th>
                        <th>Shift End</th>
                        <th>Notes</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($schedules)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 40px; color: #999;">
                                No schedules found. Click "Add Schedule" to create one.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($schedules as $schedule): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($schedule['username']); ?></strong></td>
                                <td><span class="badge badge-<?= $schedule['role']; ?>"><?= ucfirst($schedule['role']); ?></span></td>
                                <td><?= date("M d, Y", strtotime($schedule['shift_date'])); ?></td>
                                <td><?= date("h:i A", strtotime($schedule['shift_start'])); ?></td>
                                <td><?= date("h:i A", strtotime($schedule['shift_end'])); ?></td>
                                <td><?= htmlspecialchars($schedule['notes'] ?: '-'); ?></td>
                                <td>
                                    <button class="btn-edit" onclick='openEditModal(<?= json_encode($schedule); ?>)'>✏️ Edit</button>
                                    <button class="btn-delete" onclick="deleteSchedule(<?= $schedule['id']; ?>)">🗑️ Delete</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add/Edit Modal -->
    <div id="scheduleModal" class="modal">
        <div class="modal-content">
            <div class="modal-header" id="modalTitle">Add New Schedule</div>
            <form method="POST" id="scheduleForm">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="schedule_id" id="scheduleId">

                <div class="form-group">
                    <label>Staff Member</label>
                    <select name="user_id" id="userId" required>
                        <option value="">Select Staff</option>
                        <?php foreach ($staffMembers as $staff): ?>
                            <option value="<?= $staff['id']; ?>">
                                <?= htmlspecialchars($staff['username']); ?> (<?= ucfirst($staff['role']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Date</label>
                    <input type="date" name="schedule_date" id="scheduleDate" required>
                </div>

                <div class="form-group">
                    <label>Shift Start</label>
                    <input type="time" name="shift_start" id="shiftStart" required>
                </div>

                <div class="form-group">
                    <label>Shift End</label>
                    <input type="time" name="shift_end" id="shiftEnd" required>
                </div>

                <div class="form-group">
                    <label>Notes (Optional)</label>
                    <textarea name="notes" id="notes" rows="3" placeholder="Any additional notes..."></textarea>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn-submit">Save Schedule</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        <?php if (isset($_GET['success'])): ?>
            const successMsg = '<?= $_GET['success']; ?>';
            if (successMsg === 'added') {
                Swal.fire('Success!', 'Schedule added successfully', 'success');
            } else if (successMsg === 'updated') {
                Swal.fire('Success!', 'Schedule updated successfully', 'success');
            } else if (successMsg === 'deleted') {
                Swal.fire('Success!', 'Schedule deleted successfully', 'success');
            }
        <?php endif; ?>

        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Add New Schedule';
            document.getElementById('formAction').value = 'add';
            document.getElementById('scheduleForm').reset();
            document.getElementById('scheduleModal').style.display = 'flex';
        }

        function openEditModal(schedule) {
            document.getElementById('modalTitle').textContent = 'Edit Schedule';
            document.getElementById('formAction').value = 'edit';
            document.getElementById('scheduleId').value = schedule.id;
            document.getElementById('userId').value = schedule.staff_id;
            document.getElementById('scheduleDate').value = schedule.shift_date;
            document.getElementById('shiftStart').value = schedule.shift_start;
            document.getElementById('shiftEnd').value = schedule.shift_end;
            document.getElementById('notes').value = schedule.notes || '';
            document.getElementById('scheduleModal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('scheduleModal').style.display = 'none';
        }

        function deleteSchedule(id) {
            Swal.fire({
                title: 'Are you sure?',
                text: "This schedule will be permanently deleted!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = `
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="schedule_id" value="${id}">
                    `;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }

        window.onclick = function(event) {
            const modal = document.getElementById('scheduleModal');
            if (event.target === modal) closeModal();
        }
    </script>
    <script>
        document.getElementById('scheduleForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);

            fetch('manage_schedule.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        Swal.fire('Success!', data.message, 'success').then(() => {
                            closeModal();
                            location.reload(); // optional: refresh table
                        });
                    } else {
                        Swal.fire('Error', data.message || 'Something went wrong', 'error');
                    }
                })
                .catch(err => {
                    Swal.fire('Error', 'Could not submit form', 'error');
                    console.error(err);
                });
        });
    </script>
</body>

</html>