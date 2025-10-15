<?php
// admin/delete_user.php
session_start();
require __DIR__ . '/../config/db_connect.php';
header('Content-Type: application/json');

if (!isset($_SESSION['staff_id']) || $_SESSION['role'] !== 'admin') { echo json_encode(['status'=>'error','message'=>'Unauthorized']); exit; }
$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
if (!$id) { echo json_encode(['status'=>'error','message'=>'Missing id']); exit; }

$stmt = $conn->prepare("DELETE FROM staff WHERE id = ?");
$stmt->bind_param("i",$id);
$ok = $stmt->execute();
echo json_encode(['status'=>$ok ? 'success':'error','message'=>$ok ? 'User deleted':'Failed']);
