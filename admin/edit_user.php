<?php
// admin/edit_user.php
session_start();
require __DIR__ . '/../config/db_connect.php';
header('Content-Type: application/json');

if (!isset($_SESSION['staff_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['error'=>'Unauthorized']); exit;
}
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$stmt = $conn->prepare("SELECT id,username,name,role,status FROM staff WHERE id=? LIMIT 1");
$stmt->bind_param("i",$id);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 1) {
    echo json_encode($res->fetch_assoc());
} else echo json_encode(['error'=>'Not found']);
