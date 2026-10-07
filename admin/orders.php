<?php
require_once '../config/database.php';
require_admin();

if(isset($_POST['order_id'])){
    verify_csrf();
    $id = (int)$_POST['order_id'];
    $status = in_array($_POST['order_status'] ?? '', order_statuses(), true) ? $_POST['order_status'] : 'Placed';
    $pst = in_array($_POST['payment_status'] ?? '', payment_statuses(), true) ? $_POST['payment_status'] : 'Pending Verification';
    $tracking = substr(trim($_POST['tracking_number'] ?? ''), 0, 80);
    try {
        $conn->begin_transaction();
        $stmt = $conn->prepare("SELECT stock_restored FROM orders WHERE id=? FOR UPDATE");
        $stmt->bind_param('i',$id); $stmt->execute();
        $cur = $stmt->get_result()->fetch_assoc();
        if($cur){
            // Cancelling an order puts its stock back exactly once.
            if($status==='Cancelled' && !(int)$cur['stock_restored']){
                $conn->query("UPDATE products p JOIN order_items oi ON oi.product_id=p.id SET p.stock=p.stock+oi.quantity, p.status='in_stock' WHERE oi.order_id=".$id);
                $conn->query("UPDATE orders SET stock_restored=1 WHERE id=".$id);
            }
            $stmt = $conn->prepare("UPDATE orders SET order_status=?, payment_status=?, tracking_number=? WHERE id=?");
            $stmt->bind_param('sssi',$status,$pst,$tracking,$id); $stmt->execute();
        }
        $conn->commit();
    } catch(Throwable $ex){ try{$conn->rollback();}catch(Throwable $x){} error_log('MAYURI admin order update: '.$ex->getMessage()); }
    redirect('orders.php');
}
$q = trim($_GET['q'] ?? ''); $fs = $_GET['status'] ?? ''; $fp = $_GET['pay'] ?? '';
$sql = "SELECT * FROM orders WHERE 1"; $types=''; $args=[];
if($q!==''){ $sql.=" AND (order_number LIKE ? OR customer_name LIKE ? OR phone LIKE ? OR id=?)"; $like='%'.$q.'%'; $types.='sssi'; array_push($args,$like,$like,$like,(int)$q); }
if(in_array($fs,order_statuses(),true)){ $sql.=" AND order_status=?"; $types.='s'; $args[]=$fs; }
if(in_array($fp,payment_statuses(),true)){ $sql.=" AND payment_status=?"; $types.='s'; $args[]=$fp; }
$stmt=$conn->prepare($sql." ORDER BY id DESC"); if($args) $stmt->bind_param($types,...$args); $stmt->execute();
$orders=$stmt->get_result();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Orders &mdash; MAYURI Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600&family=Jost:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { background: #f7f2f0; }
        .admin-wrap { display: grid; grid-template-columns: 220px 1fr; min-height: 100vh; }
        .admin-sidebar { background: var(--dark); padding: 32px 0; position: sticky; top: 0; height: 100vh; overflow-y: auto; }
        .admin-sidebar .sidebar-brand { font-family: 'Cormorant Garamond', serif; font-size: 1.4rem; color: var(--gold); letter-spacing: 0.15em; padding: 0 24px 28px; border-bottom: 1px solid rgba(255,255,255,0.08); margin-bottom: 16px; display: block; }
        .admin-sidebar a { display: block; padding: 11px 24px; font-size: 0.82rem; font-weight: 500; letter-spacing: 0.1em; text-transform: uppercase; color: rgba(255,255,255,0.5); transition: all 0.25s; border-left: 3px solid transparent; }
        .admin-sidebar a:hover, .admin-sidebar a.active { color: var(--gold); background: rgba(255,255,255,0.04); border-left-color: var(--gold); }
        .admin-main { padding: 40px 36px; }
    </style>
</head>
<body>
<div class="admin-wrap">
    <aside class="admin-sidebar">
        <a class="sidebar-brand" href="../index.php">MAYURI Ã¢Å“Â¦</a>
        <a href="index.php">Dashboard</a>
        <a href="products.php">Products</a>
        <a href="product_form.php">Add Product</a>
        <a href="orders.php" class="active">Orders</a>
        <a href="../index.php" target="_blank">View Site</a>
        <a href="../logout.php">Logout</a>
    </aside>
    <main class="admin-main">
        <h1 style="margin-bottom:28px">Orders</h1>
        <div class="table-wrap" style="background:#fff;border:1px solid var(--border);border-radius:var(--radius-lg);overflow:hidden">
        <form method="get" style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:14px">
            <input name="q" value="<?=e($q)?>" placeholder="Order no. / customer / phone" style="margin:0;max-width:240px">
            <select name="status" style="margin:0;width:auto"><option value="">All order status</option><?php foreach(order_statuses() as $x) echo '<option'.($fs===$x?' selected':'').'>'.e($x).'</option>'; ?></select>
            <select name="pay" style="margin:0;width:auto"><option value="">All payments</option><?php foreach(payment_statuses() as $x) echo '<option'.($fp===$x?' selected':'').'>'.e($x).'</option>'; ?></select>
            <button class="btn secondary">Filter</button> <a class="btn light" href="orders.php">Reset</a>
        </form>
        <table class="table">
            <thead>
                <tr><th>#</th><th>Customer</th><th>Phone</th><th>Payment</th><th>Total</th><th>Status</th><th>Update</th></tr>
            </thead>
            <tbody>
            <?php while($o = $orders->fetch_assoc()): ?>
            <tr>
                <td style="font-weight:600;color:var(--primary)"><?=e($o['order_number'] ?: '#'.$o['id'])?> <a href="../invoice.php?order_id=<?=$o['id']?>" target="_blank" style="font-size:.7rem">Invoice</a></td>
                <td>
                    <strong><?=e($o['customer_name'])?></strong>
                    <div style="font-size:0.78rem;color:var(--muted);max-width:160px"><?=e($o['address'])?></div>
                </td>
                <td><?=e($o['phone'])?></td>
                <td>
                    <span style="font-size:0.8rem"><?=e($o['payment_method'])?></span><br>
                    <span class="badge <?=$o['payment_status']=='Paid'?'ok':'out'?>"><?=e($o['payment_status'])?></span>
                </td>
                <td style="font-weight:700;color:var(--primary)">&#8377;<?=number_format($o['total'],2)?></td>
                <td><?=e($o['order_status']??'Placed')?></td>
                <td>
                    <form method="post" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap"><?=csrf_field()?>
                        <input type="hidden" name="order_id" value="<?=$o['id']?>">
                        <select name="order_status" style="margin-bottom:0;width:auto;font-size:0.82rem;padding:7px 10px;border:1px solid var(--border);border-radius:var(--radius);background:var(--bg)">
                            <?php foreach(order_statuses() as $s): ?>
                                <option <?=($o['order_status']??'')==$s?'selected':''?>><?=$s?></option>
                            <?php endforeach; ?>
                        </select>
                        <select name="payment_status" style="margin-bottom:0;width:auto;font-size:0.82rem;padding:7px 10px">
                            <?php foreach(payment_statuses() as $ps): ?><option <?=($o['payment_status']??'')==$ps?'selected':''?>><?=e($ps)?></option><?php endforeach; ?>
                        </select>
                        <input name="tracking_number" value="<?=e($o['tracking_number']??'')?>" placeholder="Tracking no." style="width:130px;margin-bottom:0;font-size:.78rem;padding:7px 10px">
                        <button style="padding:8px 14px;font-size:0.78rem">Save</button>
                    </form>
                </td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
        </div>
    </main>
</div>
</body>
</html>
