<?php
    include("../../config/db_connect.php");

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $id = $_POST['id'];
        $name = $_POST['name'];
        $category = $_POST['category'];
        $description = $_POST['description'];
        $price = $_POST['price'];
        $stock = $_POST['stock'];

        $stmt = $conn->prepare("UPDATE products SET name=?, category=?, description=?, price=?, stock=? WHERE id=?");
        $stmt->bind_param("sssdii", $name, $category, $description, $price, $stock, $id);
        $stmt->execute();

        header("Location: ../ENinventory.php");
        exit();
    }
?>