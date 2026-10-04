<?php
require_once 'config/database.php';
require_login();
verify_csrf();
$product_id = (int)($_POST['product_id'] ?? 0);
$return_to = trim($_POST['return_to'] ?? 'collection.php');
if(strpos($return_to, 'product_detail.php') !== 0 && $return_to !== 'collection.php' && $return_to !== 'boys.php') $return_to = 'collection.php';
$uid = (int)$_SESSION['user_id'];
$stmt = $conn->prepare("SELECT id, name, stock, status FROM products WHERE id=? LIMIT 1");
$stmt->bind_param('i', $product_id);
$stmt->execute();
$p = $stmt->get_result()->fetch_assoc();
if(!$p){ redirect($return_to.(strpos($return_to,'?')!==false?'&':'?').'msg=not_found'); }
if($p['status'] !== 'out_of_stock' && (int)$p['stock'] > 0){ redirect($return_to.(strpos($return_to,'?')!==false?'&':'?').'msg=available'); }
$name  = $_SESSION['name'] ?? 'Customer';
$email = $_SESSION['email'] ?? '';
$phone = $_SESSION['phone'] ?? '';
try {
    $stmt = $conn->prepare("INSERT INTO product_waitlist(product_id,user_id,customer_name,email,phone,status) VALUES(?,?,?,?,?,'waiting') ON DUPLICATE KEY UPDATE status='waiting', updated_at=CURRENT_TIMESTAMP");
    $stmt->bind_param('iisss', $product_id, $uid, $name, $email, $phone);
    $stmt->execute();
} catch(Exception $ex) { error_log('Waitlist error: '.$ex->getMessage()); }
redirect($return_to.(strpos($return_to,'?')!==false?'&':'?').'msg=waitlisted');
