<?php
require ('../config/db_connect.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name']);
    $middle_name = trim($_POST['middle_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $errors = [];

    if ($first_name === '') $errors[] = 'First name is required';
    if ($last_name === '') $errors[] = 'Last name is required';
    if ($email === '') $errors[] = 'Email is required';
    if ($username === '') $errors[] = 'Username is required';
    if ($password === '') $errors[] = 'Password is required';

    echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>';

    if (count($errors) === 0) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO customers (first_name, middle_name, last_name, email, phone, username, password) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssss", $first_name, $middle_name, $last_name, $email, $phone, $username, $password_hash);

        if ($stmt->execute()) {
            echo "<script>
            Swal.fire({
                icon: 'success',
                title: 'Account Created!',
                text: 'Your registration was successful. Please login.',
                confirmButtonText: 'OK'
            }).then(() => {
                window.location = 'login.php';
            });
            </script>";
        } else {
            echo "<script>
            Swal.fire({
                icon: 'error',
                title: 'Registration Failed',
                text: 'Username or Email may already exist.',
                confirmButtonText: 'OK'
            }).then(() => {
                window.location = 'register.php';
            });
            </script>";
        }
    } else {
        echo "<script>
        Swal.fire({
            icon: 'warning',
            title: 'Incomplete Form',
            html: '".implode("<br>", $errors)."',
            confirmButtonText: 'OK'
        });
        </script>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Flakies | Register</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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

        input[type="text"],
        input[type="password"],
        input[type="email"] {
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
        <p class="welcome">Create a new account</p>

        <form id="registerForm" action="register.php" method="POST">
            <div class="input-group">
                <input type="text" name="first_name" placeholder="First Name">
                <span class="error-msg"></span>
            </div>
            <div class="input-group">
                <input type="text" name="middle_name" placeholder="Middle Name (Optional)">
                <span class="error-msg"></span>
            </div>
            <div class="input-group">
                <input type="text" name="last_name" placeholder="Last Name">
                <span class="error-msg"></span>
            </div>
            <div class="input-group">
                <input type="email" name="email" placeholder="Email">
                <span class="error-msg"></span>
            </div>
            <div class="input-group">
                <input type="text" name="phone" placeholder="Phone Number (Optional)">
                <span class="error-msg"></span>
            </div>
            <div class="input-group">
                <input type="text" name="username" placeholder="Username">
                <span class="error-msg"></span>
            </div>
            <div class="input-group">
                <input type="password" name="password" placeholder="Password">
                <span class="error-msg"></span>
            </div>

            <button type="submit" class="btn-login">Register</button>
        </form>

        <div class="links">
            <a href="login.php">Already have an account? Login</a>
        </div>
        <div class="links">
            <a href="../index.php" class="home-link">🏠 Back to Home</a>
        </div>
    </div>

        <script>
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            let isValid = true;
            const requiredFields = ['first_name', 'last_name', 'email', 'username', 'password']; // required field names

            requiredFields.forEach(name => {
                const input = this.querySelector(`input[name="${name}"]`);
                const errorMsg = input.nextElementSibling;

                if (input.value.trim() === '') {
                    input.style.borderColor = 'red';
                    errorMsg.textContent = '*Required';
                    errorMsg.style.color = 'red';
                    errorMsg.style.fontSize = '12px';
                    isValid = false;
                } else {
                    input.style.borderColor = '#ccc';
                    errorMsg.textContent = '';
                }
            });

            if (!isValid) {
                e.preventDefault(); // Stop form from submitting
            }
        });
        </script>

</body>
</html>
