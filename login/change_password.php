<?php
session_start();
require '../config/db_connect.php';

if (!isset($_SESSION['reset_email'], $_SESSION['verified_code'])) {
  header("Location: login.php");
  exit;
}

$email = $_SESSION['reset_email'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $newPassword = trim($_POST['new_password']);
  $confirmPassword = trim($_POST['confirm_password']);

  echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>';

  if (strlen($newPassword) < 8) {
    echo "<script>
            Swal.fire({
                icon: 'warning',
                title: 'Weak Password',
                text: 'Password must be at least 8 characters long.',
                confirmButtonText: 'OK'
            });
        </script>";
  } elseif ($newPassword !== $confirmPassword) {
    echo "<script>
            Swal.fire({
                icon: 'error',
                title: 'Passwords Don\\'t Match',
                text: 'New password and confirm password do not match.',
                confirmButtonText: 'OK'
            });
        </script>";
  } else {
    $hashedNew = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE customers SET password = ? WHERE email = ?");
    $stmt->bind_param("ss", $hashedNew, $email);

    if ($stmt->execute()) {
      unset($_SESSION['reset_email'], $_SESSION['verified_code']);
      echo "<script>
                Swal.fire({
                    icon: 'success',
                    title: 'Password Changed!',
                    text: 'Your password has been successfully changed.',
                    confirmButtonText: 'OK'
                }).then(() => {
                    window.location = 'login.php';
                });
            </script>";
    } else {
      echo "<script>
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to update password. Please try again.',
                    confirmButtonText: 'OK'
                });
            </script>";
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Flakies | Reset Password</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    body {
      font-family: Poppins, sans-serif;
      margin: 0;
      min-height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      background: linear-gradient(135deg, #fff9e6 0%, #f6e27a 40%, #d9ed42 100%);
      padding: 20px;
    }

    .change-password-card {
      background: #fff;
      width: 450px;
      padding: 40px 35px;
      border-radius: 18px;
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
      text-align: center;
    }

    .logo-container img {
      width: 75px;
      height: 75px;
      border-radius: 50%;
      object-fit: cover;
    }

    .logo-text {
      font-size: 28px;
      font-weight: 800;
      background: linear-gradient(90deg, #d39e2a, #e0d979);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }

    .input-group {
      position: relative;
      margin-bottom: 18px;
      text-align: left;
    }

    .input-label {
      font-size: 13px;
      font-weight: 600;
      margin-bottom: 5px;
      display: block;
    }

    .input-wrapper {
      position: relative;
    }

    input[type="password"] {
      width: calc(100% - 30px);
      padding: 12px 40px 12px 15px;
      border: 1px solid #ccc;
      border-radius: 10px;
      font-size: 15px;
      outline: none;
    }

    .toggle-password {
      position: absolute;
      right: 15px;
      top: 50%;
      transform: translateY(-50%);
      cursor: pointer;
      color: #999;
    }

    .password-strength {
      height: 4px;
      background: #e0e0e0;
      border-radius: 2px;
      margin-top: 8px;
      overflow: hidden;
    }

    .password-strength-bar {
      height: 100%;
      width: 0%;
      border-radius: 2px;
      transition: all 0.3s ease;
    }

    .strength-weak {
      width: 33%;
      background: #dc3545;
    }

    .strength-medium {
      width: 66%;
      background: #ffc107;
    }

    .strength-strong {
      width: 100%;
      background: #28a745;
    }

    .strength-text {
      font-size: 12px;
      margin-top: 5px;
      font-weight: 600;
    }

    .password-requirements {
      text-align: left;
      font-size: 12px;
      color: #666;
      margin-top: 10px;
      padding: 10px;
      background: #f8f9fa;
      border-radius: 8px;
    }

    .requirement {
      margin: 5px 0;
    }

    .requirement.met {
      color: #28a745;
    }

    .requirement::before {
      content: "✗ ";
      color: #dc3545;
      font-weight: bold;
    }

    .requirement.met::before {
      content: "✓ ";
      color: #28a745;
    }

    .btn-change {
      width: 100%;
      background: linear-gradient(135deg, #d9ed42, #d39e2a);
      border: none;
      border-radius: 10px;
      padding: 12px;
      color: #000;
      font-weight: 700;
      font-size: 16px;
      cursor: pointer;
    }
  </style>
</head>

<body>
  <div class="change-password-card">
    <div class="logo-container">
      <img src="../assets/pictures/45b0e7c9-8bc1-4ef3-bac2-cfc07174d613.png" alt="Flakies Logo">
    </div>
    <h1 class="logo-text">Flakies</h1>
    <p>Reset Your Password</p>
    <form action="change_password.php" method="POST" id="changePasswordForm">
      <div class="input-group">
        <label class="input-label">New Password</label>
        <div class="input-wrapper">
          <input type="password" name="new_password" id="newPassword" placeholder="Enter new password" required>
          <span class="toggle-password" onclick="togglePassword('newPassword', this)">👁️</span>
        </div>
        <div class="password-strength">
          <div class="password-strength-bar" id="strengthBar"></div>
        </div>
        <div class="strength-text" id="strengthText"></div>
        <div class="password-requirements">
          <div class="requirement" id="req-length">At least 8 characters</div>
          <div class="requirement" id="req-uppercase">Contains uppercase letter</div>
          <div class="requirement" id="req-lowercase">Contains lowercase letter</div>
          <div class="requirement" id="req-number">Contains number</div>
        </div>
      </div>
      <div class="input-group">
        <label class="input-label">Confirm New Password</label>
        <div class="input-wrapper">
          <input type="password" name="confirm_password" id="confirmPassword" placeholder="Confirm new password" required>
          <span class="toggle-password" onclick="togglePassword('confirmPassword', this)">👁️</span>
        </div>
        <div id="matchMessage" style="font-size:12px;margin-top:5px;"></div>
      </div>
      <button type="submit" class="btn-change">Change Password</button>
    </form>
  </div>

  <script>
    function togglePassword(id, icon) {
      const input = document.getElementById(id);
      input.type = input.type === 'password' ? 'text' : 'password';
      icon.textContent = input.type === 'password' ? '👁️' : '🙈';
    }

    const newPasswordInput = document.getElementById('newPassword');
    const confirmPasswordInput = document.getElementById('confirmPassword');
    const strengthBar = document.getElementById('strengthBar');
    const strengthText = document.getElementById('strengthText');
    const matchMessage = document.getElementById('matchMessage');

    newPasswordInput.addEventListener('input', () => {
      const password = newPasswordInput.value;
      let strength = 0;
      const hasLength = password.length >= 8;
      const hasUppercase = /[A-Z]/.test(password);
      const hasLowercase = /[a-z]/.test(password);
      const hasNumber = /[0-9]/.test(password);

      document.getElementById('req-length').classList.toggle('met', hasLength);
      document.getElementById('req-uppercase').classList.toggle('met', hasUppercase);
      document.getElementById('req-lowercase').classList.toggle('met', hasLowercase);
      document.getElementById('req-number').classList.toggle('met', hasNumber);

      if (hasLength) strength++;
      if (hasUppercase) strength++;
      if (hasLowercase) strength++;
      if (hasNumber) strength++;

      strengthBar.className = 'password-strength-bar';
      if (strength <= 1) strengthBar.classList.add('strength-weak'), strengthText.textContent = 'Weak', strengthText.style.color = '#dc3545';
      else if (strength <= 3) strengthBar.classList.add('strength-medium'), strengthText.textContent = 'Medium', strengthText.style.color = '#ffc107';
      else strengthBar.classList.add('strength-strong'), strengthText.textContent = 'Strong', strengthText.style.color = '#28a745';

      checkPasswordMatch();
    });

    confirmPasswordInput.addEventListener('input', checkPasswordMatch);

    function checkPasswordMatch() {
      const newPass = newPasswordInput.value;
      const confirmPass = confirmPasswordInput.value;
      if (confirmPass === '') {
        matchMessage.textContent = '';
        return;
      }
      if (newPass === confirmPass) {
        matchMessage.textContent = '✓ Passwords match';
        matchMessage.style.color = '#28a745';
      } else {
        matchMessage.textContent = '✗ Passwords do not match';
        matchMessage.style.color = '#dc3545';
      }
    }
  </script>
</body>

</html>