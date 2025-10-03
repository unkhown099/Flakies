<?php
require 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $name = trim($_POST['name']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = 'cashier'; // default role, can be changed by admin

    // Insert new user
    $stmt = $conn->prepare("INSERT INTO staff (username, password, name, role) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $username, $password, $name, $role);

    if ($stmt->execute()) {
        echo "<script>alert('Account created successfully! Please login.'); window.location='login.php';</script>";
    } else {
        echo "<script>alert('Error: Username may already exist.'); window.location='register.php';</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Account | Flakies</title>
    <link rel="stylesheet" href="assets/login.css">
</head>
<body>
    <div class="login-hero">
        <div class="login-card">
            <h1 class="logo">Flakies</h1>
            <p class="welcome">Create a new account</p>

            <form action="register.php" method="POST" class="login-form">
                <div class="input-group">
                    <input type="text" name="name" placeholder="Full Name" required>
                </div>
                <div class="input-group">
                    <input type="text" name="username" placeholder="Username" required>
                </div>
                <div class="input-group">
                    <input type="password" name="password" placeholder="Password" required>
                </div>
                <button type="submit" class="btn-login">Register</button>
            </form>

            <div class="links">
                <a href="login.php">Already have an account? Login</a>
            </div>
        </div>
    </div>
</body>
</html>
