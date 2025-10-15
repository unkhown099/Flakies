<?php
require __DIR__ . '/../config/db_connect.php';
header('Content-Type: application/json');

if (isset($_POST['bulk']) && isset($_POST['ids'])) {
    $ids = explode(',', $_POST['ids']);
    $ids = array_map('intval', $ids);
    if (count($ids) > 0) {
        $idList = implode(',', $ids);
        $sql = "DELETE FROM account_actions WHERE id IN ($idList)";
        if ($conn->query($sql)) {
            echo json_encode(['success' => true]);
            exit;
        } else {
            echo json_encode(['success' => false, 'message' => $conn->error]);
            exit;
        }
    }
}

echo json_encode(['success' => false, 'message' => 'Invalid request']);
