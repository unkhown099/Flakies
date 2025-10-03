<?php
session_start();
require "db_connect.php";

// Only admin can access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$result = $conn->query("SELECT * FROM staff ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Users</title>
    <link rel="stylesheet" href="assets/dashboard.css">
</head>
<body>
    <div class="sidebar">
        <h2>Flakies Admin</h2>
        <ul>
            <li><a href="dashboard.php">🏠 Dashboard</a></li>
            <li><a href="users.php" class="active">👥 Manage Users</a></li>
            <li><a href="#">📦 Inventory</a></li>
            <li><a href="#">💰 Sales</a></li>
            <li><a href="logout.php">🚪 Logout</a></li>
        </ul>
    </div>

    <div class="main-content">
        <h1>Manage Users</h1>
        <a href="add_user.php" class="btn">➕ Add User</a>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Name</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php while($row = $result->fetch_assoc()) { ?>
                <tr>
                    <td><?= $row['id'] ?></td>
                    <td><?= $row['username'] ?></td>
                    <td><?= $row['name'] ?></td>
                    <td><?= ucfirst($row['role']) ?></td>
                    <td><?= $row['status'] ?></td>
                    <td><?= $row['created_at'] ?></td>
                    <td>
                        <a href="edit_user.php?id=<?= $row['id'] ?>">✏️ Edit</a> | 
                        <a href="delete_user.php?id=<?= $row['id'] ?>" onclick="return confirm('Are you sure?')">🗑 Deactivate</a>
                    </td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
</body>
</html>
