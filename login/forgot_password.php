<?php
session_start();
require '../config/db_connect.php';
require '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$alert = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    $stmt = $conn->prepare("SELECT * FROM customers WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        // Generate and store verification code
        $verificationCode = rand(100000, 999999);
        $_SESSION['reset_email'] = $email;
        $_SESSION['reset_code'] = $verificationCode;
        $_SESSION['reset_code_time'] = time();

        $mail = new PHPMailer(true);
        // $mail->SMTPDebug = 2;
        // $mail->Debugoutput = 'html';

        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'flakies050@gmail.com';
            $mail->Password = 'mpqw ieiz exil xnob'; // Gmail app password
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            $mail->setFrom('flakies050@gmail.com', 'Flakies Support');
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = 'Flakies Password Reset Code';
            $mail->Body = "
                <div style='font-family:Poppins,sans-serif;'>
                    <h2>Flakies Password Reset</h2>
                    <p>Your verification code is:</p>
                    <h1 style='color:#d39e2a;'>$verificationCode</h1>
                    <p>This code will expire in 5 minutes.</p>
                </div>
            ";

            // TEMPORARY SSL bypass (local test only)
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ];

            $mail->send();

            $alert = "
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'success',
                        title: 'Verification Code Sent!',
                        text: 'A 6-digit code has been sent to your email.',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        window.location = 'confirm_code.php';
                    });
                });
            </script>";
        } catch (Exception $e) {
            $alert = "
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Email Sending Failed',
                        text: 'Unable to send verification code. Please try again later.',
                        confirmButtonText: 'OK'
                    });
                });
            </script>";
        }
    } else {
        $alert = "
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Email Not Found',
                    text: 'No account exists with that email address.',
                    confirmButtonText: 'OK'
                });
            });
        </script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Flakies | Forgot Password</title>
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
        <p class="welcome">Reset your password</p>

        <form action="forgot_password.php" method="POST">
            <div class="input-group">
                <input type="email" name="email" placeholder="Enter your email" required>
            </div>
            <button type="submit" class="btn-login">Send Code</button>
        </form>

        <div class="links">
            <a href="login.php">← Back to Login</a>
        </div>
    </div>

    <?php if (!empty($alert)) echo $alert; ?>
</body>

</html>