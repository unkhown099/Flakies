<?php
// admin/toggle_status.php
session_start();
require __DIR__ . '/../config/db_connect.php';
header('Content-Type: application/json');

if (!isset($_SESSION['staff_id']) || $_SESSION['role'] !== 'admin') { echo json_encode(['status'=>'error','message'=>'Unauthorized']); exit; }
$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
if (!$id) { echo json_encode(['status'=>'error','message'=>'Missing id']); exit; }

$stmt = $conn->prepare("SELECT status FROM staff WHERE id=?");
$stmt->bind_param("i",$id); $stmt->execute(); $r = $stmt->get_result();
if ($r->num_rows !== 1) { echo json_encode(['status'=>'error','message'=>'User not found']); exit; }
$row = $r->fetch_assoc();
$new = $row['status'] === 'active' ? 'inactive' : 'active';
$u = $conn->prepare("UPDATE staff SET status=? WHERE id=?");
$u->bind_param("si",$new,$id);
$ok = $u->execute();
echo json_encode(['status'=>$ok ? 'success':'error','message'=>$ok ? "Status set to $new" : 'Failed']);
