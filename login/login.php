<?php
session_start();
require "../config/db_connect.php";

$error = "";
$successRedirect = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Check staff table first
    $sql = "SELECT * FROM staff WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            $_SESSION['staff_id'] = $row['id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['role'] = $row['role'];

            if ($row['role'] === 'admin') $successRedirect = '../admin/dashboard.php';
            elseif ($row['role'] === 'cashier') $successRedirect = 'pos.php';
            elseif ($row['role'] === 'encoder') $successRedirect = '../encoder/ENdashboard.php';
            elseif ($row['role'] === 'manager') $successRedirect = '../manager/dashboard.php';
            else $successRedirect = 'dashboard.php';

        } else {
            $error = "❌ Invalid password.";
        }
    } else {
        // Check customer table
        $sql2 = "SELECT * FROM customers WHERE username = ?";
        $stmt2 = $conn->prepare($sql2);
        $stmt2->bind_param("s", $username);
        $stmt2->execute();
        $result2 = $stmt2->get_result();

        if ($result2->num_rows === 1) {
            $row2 = $result2->fetch_assoc();
            if (password_verify($password, $row2['password'])) {
                $_SESSION['customer_id'] = $row2['id'];
                $_SESSION['customer_username'] = $row2['username'];
                $successRedirect = '../index.php';
            } else {
                $error = "❌ Invalid password.";
            }
        } else {
            $error = "❌ No user found with that username.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Flakies | Login</title>
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

        .error {
            color: red;
            font-size: 14px;
            background: #ffe6e6;
            border-radius: 6px;
            padding: 8px;
            margin-bottom: 15px;
        }

        .input-group {
            position: relative;
            margin-bottom: 18px;
        }

        input[type="text"],
        input[type="password"] {
            width: 85%;
            padding: 12px 40px 12px 15px;
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

        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            font-size: 14px;
            color: #777;
            transition: 0.2s;
        }

        .toggle-password:hover {
            color: #000;
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
            justify-content: space-between;
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

        .home-link {
            display: block;
            margin-top: 25px;
            text-align: center;
            font-weight: 600;
            text-decoration: none;
            color: #000;
            transition: color 0.3s ease;
        }

        .home-link:hover {
            color: #d39e2a;
        }
        .required-field.error-border {
            border-color: red;
            box-shadow: 0 0 6px rgba(255, 0, 0, 0.5);
        }
        .required-message {
            color: red;
            font-size: 12px;
            margin-top: 5px;
            display: none;
        }

    </style>
</head>

<body>
    <div class="login-card">
        <div class="logo-container">
            <img src="../assets/pictures/45b0e7c9-8bc1-4ef3-bac2-cfc07174d613.png" alt="Flakies Logo">
        </div>

        <h1 class="logo-text">Flakies</h1>
        <p class="welcome">Welcome back! Please log in to continue.</p>

        <?php if ($error): ?>
            <p class="error"><?= $error ?></p>
        <?php endif; ?>

        <form action="login.php" method="POST" class="login-form">
            <div class="input-group">
                <input type="text" name="username" placeholder="Username" class="required-field">
                <span class="required-message">*Required</span>
            </div>
            <div class="input-group">
                <input type="password" name="password" id="password" placeholder="Password" class="required-field">
                <span class="required-message">*Required</span>
                <span class="toggle-password" onclick="togglePassword()">👁️</span>
            </div>
            <button type="submit" class="btn-login">Login</button>
        </form>

        <div class="links">
            <a href="register.php">Create Account</a>
            <a href="forgot_password.php">Forgot Password?</a>
        </div>

        <a href="../index.php" class="home-link">🏠 Back to Home</a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    function togglePassword() {
        const passwordField = document.getElementById("password");
        const toggle = document.querySelector(".toggle-password");
        if (passwordField.type === "password") {
            passwordField.type = "text";
            toggle.textContent = "🙈";
        } else {
            passwordField.type = "password";
            toggle.textContent = "👁️";
        }
    }

    <?php if ($error): ?>
    Swal.fire({
        icon: 'error',
        title: 'Oops!',
        text: '<?= $error ?>'
    });
    <?php endif; ?>

    <?php if ($successRedirect): ?>
    Swal.fire({
        icon: 'success',
        title: 'Login Successful!',
        text: 'Redirecting...',
        timer: 1500,
        showConfirmButton: false
    }).then(() => {
        window.location.href = '<?= $successRedirect ?>';
    });
    <?php endif; ?>

    const form = document.querySelector('.login-form');
        form.addEventListener('submit', function(e) {
            let hasError = false;
            document.querySelectorAll('.required-field').forEach((input, index) => {
                const message = input.nextElementSibling; // the span
                if (input.value.trim() === '') {
                    input.classList.add('error-border');
                    message.style.display = 'block';
                    hasError = true;
                } else {
                    input.classList.remove('error-border');
                    message.style.display = 'none';
                }
            });
            if (hasError) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Oops!',
                    text: 'Please fill in all required fields.'
                });
            }
        });
    </script>
</body>
</html>
