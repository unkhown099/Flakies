<?php
    include("../../config/db_connect.php"); // Adjust path as needed

    $searchTerm = '';
    if (isset($_GET['search'])) {
        $searchTerm = mysqli_real_escape_string($conn, $_GET['search']);
        $sql2 = "SELECT * FROM products 
                WHERE name LIKE '%$searchTerm%'
                OR category LIKE '%$searchTerm%'
                OR price LIKE '%$searchTerm%'
                OR stock LIKE '%$searchTerm%'
                OR created_at LIKE '%$searchTerm%'";
    } 
    else {
        $sql2 = "SELECT * FROM products";
    }

    $query2 = mysqli_query($conn, $sql2);

    if (mysqli_num_rows($query2) > 0) {
        while ($row = mysqli_fetch_assoc($query2)) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['price']) . "₱</td>";
            echo "<td>" . htmlspecialchars($row['stock']) . "</td>";
            echo "<td>" . htmlspecialchars($row['created_at']) . "</td>";
            echo "</tr>";
        }
    } 
    else {
        echo "<tr><td colspan='4' style='text-align:center;'>No matching products found.</td></tr>";
    }

    mysqli_close($conn);
?>