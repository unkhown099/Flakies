<?php
session_start();
include '../config/db_connect.php';

if (!isset($_SESSION['customer_id'])) {
    header("Location: ../login/login.php");
    exit();
}

$customer_id = $_SESSION['customer_id'];

if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = '../assets/pictures/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $fileName = uniqid() . '_' . basename($_FILES['avatar']['name']);
    $filePath = $uploadDir . $fileName;

    if (move_uploaded_file($_FILES['avatar']['tmp_name'], $filePath)) {
        // Save file name to database
        $stmt = $conn->prepare("UPDATE customers SET profile_picture = ? WHERE id = ?");
        $stmt->bind_param("si", $fileName, $customer_id);
        $stmt->execute();

        header("Location: profile.php?upload=success");
        exit();
    } else {
        echo "Error uploading file.";
    }
} else {
    echo "No file uploaded or upload error.";
}
?>
