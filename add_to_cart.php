<?php
require_once 'config/database.php';
require_login();
verify_csrf();
$id  = (int)($_POST['product_id'] ?? 0);
$qty = max(1, (int)($_POST['qty'] ?? 1));
$return_to = trim($_POST['return_to'] ?? 'shopping_cart.php');
if(strpos($return_to, 'product_detail.php') !== 0 && $return_to !== 'shopping_cart.php' && $return_to !== 'collection.php' && $return_to !== 'boys.php') $return_to = 'shopping_cart.php';
$stmt = $conn->prepare("SELECT id, stock, status FROM products WHERE id=? LIMIT 1");
$stmt->bind_param('i', $id);
$stmt->execute();
$p = $stmt->get_result()->fetch_assoc();
if(!$p){ redirect('collection.php?msg=not_found'); }
if($p['status']==='out_of_stock' || (int)$p['stock'] <= 0){ redirect($return_to.(strpos($return_to,'?')!==false?'&':'?').'msg=out_of_stock'); }
if($qty > (int)$p['stock']) $qty = (int)$p['stock'];
$_SESSION['cart'][$id] = ($_SESSION['cart'][$id] ?? 0) + $qty;
redirect('shopping_cart.php');
