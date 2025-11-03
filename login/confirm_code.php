<?php
session_start();

// Ensure email and reset code exist
if (!isset($_SESSION['reset_email'], $_SESSION['reset_code'], $_SESSION['reset_code_time'])) {
  header("Location: forgot_password.php");
  exit;
}

$alert = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $code = trim($_POST['code']);
  $currentTime = time();

  // Expiration check (5 minutes)
  if ($currentTime - $_SESSION['reset_code_time'] > 300) {
    unset($_SESSION['reset_code'], $_SESSION['reset_code_time']);
    $alert = "
        <script>
        Swal.fire({
            icon: 'error',
            title: 'Code Expired',
            text: 'Your verification code has expired. Please request a new one.',
            confirmButtonText: 'OK'
        }).then(() => window.location = 'forgot_password.php');
        </script>";
  }
  // Code match check
elseif ((string)$code === (string)$_SESSION['reset_code']) {
    $_SESSION['verified_code'] = true; // mark as verified
    // do NOT unset reset_code yet
    $alert = "
    <script>
    Swal.fire({
        icon: 'success',
        title: 'Verified!',
        text: 'Code verified successfully. You can now reset your password.',
        confirmButtonText: 'Continue'
    }).then(() => {
        window.location = 'change_password.php';
    });
    </script>";
  } else {
    $alert = "
        <script>
        Swal.fire({
            icon: 'error',
            title: 'Invalid Code',
            text: 'The verification code is incorrect. Please try again.',
            confirmButtonText: 'Try Again'
        });
        </script>";
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Flakies | Verify Code</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    body {
      font-family: Poppins, sans-serif;
      margin: 0;
      height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      background: linear-gradient(135deg, #fff9e6 0%, #f6e27a 40%, #d9ed42 100%);
    }

    .verify-card {
      background: #fff;
      width: 420px;
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
      margin-bottom: 15px;
    }

    .logo-text {
      font-size: 28px;
      font-weight: 800;
      color: #000;
      letter-spacing: 1px;
      background: linear-gradient(90deg, #d39e2a, #e0d979);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      margin-bottom: 10px;
    }

    .welcome {
      font-size: 14px;
      color: #555;
      margin-bottom: 10px;
    }

    .email-display {
      font-size: 13px;
      color: #d39e2a;
      font-weight: 600;
      margin-bottom: 25px;
    }

    .code-input-group {
      display: flex;
      justify-content: center;
      gap: 10px;
      margin-bottom: 25px;
    }

    .code-input {
      width: 50px;
      height: 55px;
      text-align: center;
      font-size: 24px;
      font-weight: 700;
      border: 2px solid #ccc;
      border-radius: 10px;
      outline: none;
    }

    .code-input:focus {
      border-color: #d39e2a;
      box-shadow: 0 0 8px rgba(211, 158, 42, 0.4);
      transform: scale(1.05);
    }

    .btn-verify {
      width: 100%;
      background: linear-gradient(135deg, #d9ed42, #d39e2a);
      border: none;
      border-radius: 10px;
      padding: 12px;
      color: #000;
      font-weight: 700;
      font-size: 16px;
      cursor: pointer;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
    }

    .btn-verify:disabled {
      background: #ccc;
      cursor: not-allowed;
    }

    .btn-secondary {
      background: transparent;
      border: 2px solid #d39e2a;
      border-radius: 10px;
      padding: 8px 15px;
      color: #d39e2a;
      font-weight: 600;
      font-size: 14px;
      cursor: pointer;
      text-decoration: none;
      transition: all 0.3s ease;
    }

    .btn-secondary:hover {
      background: #d39e2a;
      color: #fff;
    }
  </style>
</head>

<body>
  <div class="verify-card">
    <div class="logo-container"><img src="../assets/pictures/45b0e7c9-8bc1-4ef3-bac2-cfc07174d613.png" alt="Flakies Logo"></div>
    <h1 class="logo-text">Flakies</h1>
    <p class="welcome">Enter Verification Code</p>
    <p class="email-display">Sent to: <?php echo htmlspecialchars($_SESSION['reset_email']); ?></p>

    <form action="confirm_code.php" method="POST" id="verifyForm">
      <div class="code-input-group">
        <input type="text" class="code-input" maxlength="1" pattern="[0-9]" required autocomplete="off" id="code1">
        <input type="text" class="code-input" maxlength="1" pattern="[0-9]" required autocomplete="off" id="code2">
        <input type="text" class="code-input" maxlength="1" pattern="[0-9]" required autocomplete="off" id="code3">
        <input type="text" class="code-input" maxlength="1" pattern="[0-9]" required autocomplete="off" id="code4">
        <input type="text" class="code-input" maxlength="1" pattern="[0-9]" required autocomplete="off" id="code5">
        <input type="text" class="code-input" maxlength="1" pattern="[0-9]" required autocomplete="off" id="code6">
      </div>
      <input type="hidden" name="code" id="fullCode">
      <button type="submit" class="btn-verify" id="verifyBtn" disabled>Verify Code</button>
    </form>

    <div style="margin-top:20px; display:flex; justify-content:center; gap:15px;">
      <a href="forgot_password.php" class="btn-secondary">← Back</a>
      <button id="resendCode" class="btn-secondary">Resend Code</button>
    </div>
  </div>

  <script>
    const inputs = document.querySelectorAll('.code-input');
    const verifyBtn = document.getElementById('verifyBtn');
    const fullCodeInput = document.getElementById('fullCode');

    inputs[0].focus();

    inputs.forEach((input, index) => {
      input.addEventListener('input', () => {
        const value = input.value;
        if (!/^[0-9]$/.test(value)) {
          input.value = '';
          return;
        }
        if (value !== '' && index < inputs.length - 1) inputs[index + 1].focus();
        const code = Array.from(inputs).map(i => i.value).join('');
        fullCodeInput.value = code;
        verifyBtn.disabled = code.length !== 6;
      });

      input.addEventListener('keydown', e => {
        if (e.key === 'Backspace' && input.value === '' && index > 0) inputs[index - 1].focus();
      });

      input.addEventListener('paste', e => {
        e.preventDefault();
        const paste = e.clipboardData.getData('text').trim().slice(0, 6);
        if (/^\d{6}$/.test(paste)) {
          paste.split('').forEach((c, i) => {
            if (inputs[i]) inputs[i].value = c;
          });
          inputs[5].focus();
          fullCodeInput.value = paste;
          verifyBtn.disabled = false;
        }
      });
    });

    // Resend Code
    document.getElementById('resendCode').addEventListener('click', () => {
      window.location.href = 'forgot_password.php?resend=true';
    });
  </script>

  <?php if ($alert) echo $alert; ?>
</body>

</html>