<?php
    include("../../config/db_connect.php");

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $name = $_POST["name"];
        $price = $_POST["price"];
        $stock = $_POST["stock"];

        $sql = "INSERT INTO products (name, category, price, stock, description) 
                VALUES ('$name', '$category', '$price', '$stock', '$description')";

        if (mysqli_query($conn, $sql)) {
            header("Location: ../ENinventory.php");
        } else {
            echo "Error: " . mysqli_error($conn);
        }

        mysqli_close($conn);
    }
?>