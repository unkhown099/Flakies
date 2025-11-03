<?php
    include("../../config/db_connect.php"); // Adjust path as needed

    $searchTerm = '';
    if (isset($_GET['search'])) {
        $searchTerm = mysqli_real_escape_string($conn, $_GET['search']);
        $sql2 = "SELECT * FROM products 
                WHERE name LIKE '%$searchTerm%'
                OR category LIKE '%$searchTerm%'
                OR description LIKE '%$searchTerm%'
                OR price LIKE '%$searchTerm%'
                OR stock LIKE '%$searchTerm%'
                OR image LIKE '%$searchTerm%'
                OR created_at LIKE '%$searchTerm%'
                OR updated_at LIKE '%$searchTerm%'";
    } 
    else {
        $sql2 = "SELECT * FROM products";
    }

    $query2 = mysqli_query($conn, $sql2);

    if (mysqli_num_rows($query2) > 0) {
        while ($row = mysqli_fetch_assoc($query2)) {
            echo "<tr>";
            echo '<td class="table-data">' . htmlspecialchars($row['name']) . "</td>";
            echo '<td class="table-data">' . htmlspecialchars($row['category']) . "</td>";
            echo '<td class="table-data">' . htmlspecialchars($row['description']) . "</td>";
            echo '<td class="table-data">' . htmlspecialchars($row['price']) . "₱</td>";
            echo '<td class="table-data">' . htmlspecialchars($row['stock']) . "</td>";
            echo '<td class="table-data">' . htmlspecialchars($row['image']) . "</td>";
            echo '<td class="table-data">' . htmlspecialchars($row['created_at']) . "</td>";
            echo '<td class="table-data">' . htmlspecialchars($row['updated_at']) . "</td>";
            echo '<td class="INENtable-btn-container">
                    <button class="INENdel-btn" data-id="<?php echo'. $row['id'] .'?>">Delete</button>
                        <button class="INENedit-btn"
                            data-id="<?php echo'. $row['id'] .'?>"
                            data-name="<?php echo'. ($row['name']) .'?>"
                            data-category="<?php echo'. ($row['category']) .'?>"
                            data-description="<?php echo'. ($row['description']) .'?>"
                            data-price="<?php echo'. $row['price'] .'?>"
                            data-stock="<?php echo'. $row['stock'] .'?>"
                        >Edit</button>
                </td>';
            echo "</tr>";
        }
    } 
    else {
        echo "<tr><td colspan='4' style='text-align:center;'>No matching products found.</td></tr>";
    }

    mysqli_close($conn);
?>