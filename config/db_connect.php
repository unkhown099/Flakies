<?php 
    $servername = "127.0.0.1";
    $username = "xampp";
    $password = "";
    $database = "flakies";
    $serverport = 3306;

    // Create connection
    $conn = new mysqli($servername, $username, $password, $database, $serverport);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
?>