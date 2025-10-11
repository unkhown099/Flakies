<?php
    $servername = "127.0.0.1"; // safer than 'localhost' for custom ports
    $username   = "root";
    $password   = "";
    $dbname     = "flakies";
    $port       = 3307; // XAMPP MySQL port

    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname, $port);

    // Check connection
    if ($conn->connect_errno) {
        die("Connection failed: " . $conn->connect_error);
    }
?>
