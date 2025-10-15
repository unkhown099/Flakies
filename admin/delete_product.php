<?php
require __DIR__ . '/../config/db_connect.php';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $conn->query("DELETE FROM products WHERE id = $id");
}

if (isset($_POST['bulk'])) {
    $ids = explode(',', $_POST['ids']);
    foreach ($ids as $id) {
        $conn->query("DELETE FROM products WHERE id = " . intval($id));
    }
}
?>
