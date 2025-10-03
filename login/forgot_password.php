<?php
require 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);

    // Find user
    $stmt = $conn->prepare("SELECT * FROM staff WHERE username = ? LIMIT 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $newPassword = substr(str_shuffle("abcdefghijklmnopqrstuvwxyz0123456789"), 0, 8);
        $hashed = password_hash($newPassword, PASSWORD_DEFAULT);

        $update = $conn->prepare("UPDATE staff SET password=? WHERE username=?");
        $update->bind_param("ss", $hashed, $username);
        $update->execute();

        echo "<script>alert('Your password has been reset. New password: $newPassword'); window.location='login.php';</script>";
    } else {
        echo "<script>alert('User not found.'); window.location='forgot_password.php';</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password | Flakies</title>
    <link rel="stylesheet" href="assets/login.css">
</head>
<body>
    <div class="login-hero">
        <div class="login-card">
            <h1 class="logo">Flakies</h1>
            <p class="welcome">Reset your password</p>

            <form action="forgot_password.php" method="POST" class="login-form">
                <div class="input-group">
                    <input type="text" name="username" placeholder="Enter your username" required>
                </div>
                <button type="submit" class="btn-login">Reset Password</button>
            </form>

            <div class="links">
                <a href="login.php">Back to Login</a>
            </div>
        </div>
    </div>
</body>
</html>
