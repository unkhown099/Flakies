<?php
session_start();
require 'db_connect.php';

// Check if form submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Find user in DB
    $stmt = $conn->prepare("SELECT * FROM staff WHERE username = ? AND status = 'active' LIMIT 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // Verify password
        if (password_verify($password, $user['password'])) {
            // Save user info to session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            // Redirect based on role
            if ($user['role'] === 'admin') {
                header("Location: admin_dashboard.php");
            } elseif ($user['role'] === 'manager') {
                header("Location: manager_dashboard.php");
            } elseif ($user['role'] === 'cashier') {
                header("Location: cashier_dashboard.php");
            } elseif ($user['role'] === 'encoder') {
                header("Location: encoder_dashboard.php");
            } else {
                header("Location: dashboard.php");
            }
            exit;
        } else {
            echo "<script>alert('Invalid password!'); window.location='login.php';</script>";
        }
    } else {
        echo "<script>alert('User not found or inactive.'); window.location='login.php';</script>";
    }
}
?>
