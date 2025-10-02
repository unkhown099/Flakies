<?php
$host = "127.0.0.1";   // localhost
$port = 3307;          // your MySQL Workbench/XAMPP port
$user = "root";        // default in XAMPP
$pass = "";            // default empty in XAMPP
$db   = "flakies";     // your existing database

$conn = new mysqli($host, $user, $pass, $db, $port);

if ($conn->connect_error) {
    die("❌ Connection failed: " . $conn->connect_error);
}
// echo "✅ Connected successfully"; // uncomment to test
?>