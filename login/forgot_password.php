<?php
require '../config/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);

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

        echo "<script>alert('✅ Your password has been reset. New password: $newPassword'); window.location='login.php';</script>";
    } else {
        echo "<script>alert('❌ User not found.'); window.location='forgot_password.php';</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Flakies | Forgot Password</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: "Poppins", sans-serif;
            margin: 0;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: linear-gradient(135deg, #fff9e6 0%, #f6e27a 40%, #d9ed42 100%);
            background-attachment: fixed;
        }

        .login-card {
            background: #fff;
            width: 380px;
            padding: 40px 35px;
            border-radius: 18px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            text-align: center;
            position: relative;
            transition: all 0.3s ease;
        }

        .login-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 35px rgba(0, 0, 0, 0.25);
        }

        .logo-container {
            margin-bottom: 15px;
        }

        .logo-container img {
            width: 75px;
            height: 75px;
            border-radius: 50%;
            object-fit: cover;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }

        .logo-text {
            font-size: 28px;
            font-weight: 800;
            color: #000;
            letter-spacing: 1px;
            background: linear-gradient(90deg, #d39e2a, #e0d979);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .welcome {
            font-size: 14px;
            color: #555;
            margin-bottom: 25px;
        }

        .input-group {
            position: relative;
            margin-bottom: 18px;
        }

        input[type="text"] {
            width: 85%;
            padding: 12px 15px;
            border: 1px solid #ccc;
            border-radius: 10px;
            font-size: 15px;
            outline: none;
            transition: all 0.3s ease;
        }

        input:focus {
            border-color: #d39e2a;
            box-shadow: 0 0 6px rgba(211, 158, 42, 0.4);
        }

        .btn-login {
            width: 100%;
            background: linear-gradient(135deg, #d9ed42, #d39e2a);
            border: none;
            border-radius: 10px;
            padding: 12px;
            color: #000;
            font-weight: 700;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
        }

        .btn-login:hover {
            transform: translateY(-2px);
            background: linear-gradient(135deg, #e0d979, #d39e2a);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.25);
        }

        .links {
            margin-top: 20px;
            display: flex;
            justify-content: center;
            font-size: 14px;
        }

        .links a {
            text-decoration: none;
            color: #000;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .links a:hover {
            color: #d39e2a;
        }
    </style>
</head>

<body>
    <div class="login-card">
        <div class="logo-container">
            <img src="../assets/pictures/45b0e7c9-8bc1-4ef3-bac2-cfc07174d613.png" alt="Flakies Logo">
        </div>

        <h1 class="logo-text">Flakies</h1>
        <p class="welcome">Reset your password</p>

        <form action="forgot_password.php" method="POST">
            <div class="input-group">
                <input type="text" name="username" placeholder="Enter your username" required>
            </div>
            <button type="submit" class="btn-login">Reset Password</button>
        </form>

        <div class="links">
            <a href="login.php">← Back to Login</a>
        </div>
    </div>
</body>
</html>
