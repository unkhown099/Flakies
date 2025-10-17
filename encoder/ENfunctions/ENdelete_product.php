<?php
    include("../../config/db_connect.php");

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $id = intval($_POST['id']); // simple sanitization

        $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
        $stmt->bind_param("i", $id);
        $success = $stmt->execute();

        if ($success) {
            echo json_encode(['status' => 'success']);
        } 
        else {
            echo json_encode(['status' => 'error', 'message' => $stmt->error]);
        }

        $stmt->close();
        $conn->close();
    }
?>
