<?php
    $servername = "127.0.0.1";
    $connport = "3306";
    $username = "xampp";
    $password = "";
    $database = "flakies";

    // Create connection
    $conn = mysqli_connect($servername, $username, $password, $database, $connport);

    // Check connection
    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }
?>