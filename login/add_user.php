<?php
session_start();
require "db_connect.php";

// Admin check
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $name = trim($_POST['name']);
    $role = $_POST['role'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $sql = "INSERT INTO staff (username, password, name, role) VALUES (?,?,?,?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $username, $password, $name, $role);

    if ($stmt->execute()) {
        header("Location: users.php");
        exit();
    } else {
        $error = "Error adding user.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add User</title>
    <link rel="stylesheet" href="assets/dashboard.css">
</head>
<body>
    <div class="main-content">
        <h1>Add New User</h1>
        <?php if (!empty($error)) echo "<p style='color:red;'>$error</p>"; ?>
        <form method="POST">
            <input type="text" name="username" placeholder="Username" required><br>
            <input type="text" name="name" placeholder="Full Name" required><br>
            <input type="password" name="password" placeholder="Password" required><br>
            <select name="role">
                <option value="admin">Admin</option>
                <option value="manager">Manager</option>
                <option value="cashier">Cashier</option>
                <option value="encoder">Encoder</option>
                <option value="inventory">Inventory Clerk</option>
            </select><br>
            <button type="submit">Add User</button>
        </form>
    </div>
</body>
</html>
