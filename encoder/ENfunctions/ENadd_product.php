<?php
    include("../../config/db_connect.php");

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $name = $_POST["name"];
        $category = $_POST["category"];
        $description = $_POST["description"];
        $price = $_POST["price"];
        $stock = $_POST["stock"];
        $image = $_POST["image"];

        $sql = "INSERT INTO products (name, category, description, price, stock, image) 
                VALUES ('$name', '$category', '$description', '$price', '$stock', '$image')";

        if (mysqli_query($conn, $sql)) {
            header("Location: ../ENinventory.php");
        } else {
            echo "Error: " . mysqli_error($conn);
        }

        mysqli_close($conn);
    }
?>