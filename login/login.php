<?php
session_start();
require "../config/db_connect.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Fetch staff by username
    $sql = "SELECT * FROM staff WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();

        // Verify hashed password
        if (password_verify($password, $row['password'])) {
            $_SESSION['staff_id'] = $row['id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['role'] = $row['role'];

            // Redirect based on role
            if ($row['role'] === 'admin') {
                header("Location: ../manager/dashboard.php");
            } elseif ($row['role'] === 'cashier') {
                header("Location: pos.php");
            } elseif ($row['role'] === 'encoder') {
                header("Location: inventory.php");
            } elseif ($row['role'] === 'manager') {
                header("Location: reports.php");
            } else {
                header("Location: dashboard.php");
            }
            exit();
        } else {
            $error = "❌ Invalid password.";
        }
    } else {
        $error = "❌ No user found with that username.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Flakies | Login</title>
    <link rel="stylesheet" href="../assets/loginStyle.css">
    <style>
        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            font-size: 14px;
            color: #666;
        }
        .input-group {
            position: relative;
        }
    </style>
</head>
<body>
    <div class="login-hero">
        <div class="login-card">
            <!-- Placeholder for logo -->
            <div class="logo-container">
                <img src="assets/logo-placeholder.png" alt="Flakies Logo" class="logo-img">
            </div>

            <h1 class="logo-text">Flakies</h1>
            <p class="welcome">Welcome back! Please login to your account.</p>

            <?php if ($error): ?>
                <p class="error"><?php echo $error; ?></p>
            <?php endif; ?>

            <form action="login.php" method="POST" class="login-form">
                <div class="input-group">
                    <input type="text" name="username" placeholder="Username" required>
                </div>
                <div class="input-group">
                    <input type="password" name="password" id="password" placeholder="Password" required>
                    <span class="toggle-password" onclick="togglePassword()">👁️</span>
                </div>
                <button type="submit" class="btn-login">Login</button>
            </form>

            <div class="links">
                <a href="register.php">Create Account</a>
                <a href="forgot_password.php">Forgot Password?</a>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordField = document.getElementById("password");
            const toggle = document.querySelector(".toggle-password");
            if (passwordField.type === "password") {
                passwordField.type = "text";
                toggle.textContent = "🙈"; // change icon
            } else {
                passwordField.type = "password";
                toggle.textContent = "👁️";
            }
        }
    </script>
</body>
</html>
