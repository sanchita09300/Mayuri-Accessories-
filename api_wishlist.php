<?php
require_once 'config/database.php';
header('Content-Type: application/json');
if(!is_logged_in()){ echo json_encode(['error'=>'not_logged_in']); exit; }
$pid = (int)($_POST['product_id'] ?? 0);
$uid = $_SESSION['user_id'];
if(!$pid){ echo json_encode(['error'=>'invalid']); exit; }
$chk = $conn->prepare("SELECT id FROM wishlist WHERE user_id=? AND product_id=?");
$chk->bind_param('ii',$uid,$pid); $chk->execute();
if($chk->get_result()->num_rows > 0){
    $conn->query("DELETE FROM wishlist WHERE user_id=$uid AND product_id=$pid");
    echo json_encode(['status'=>'removed']);
} else {
    $conn->query("INSERT INTO wishlist(user_id,product_id) VALUES($uid,$pid)");
    echo json_encode(['status'=>'added']);
}
