<?php
require_once '../config/database.php';
require_admin();

if(isset($_POST['order_id'])){
    verify_csrf();
    $id = (int)$_POST['order_id'];
    $status = $_POST['order_status'];
    $tracking = trim($_POST['tracking_number'] ?? '');
    if(!in_array($status, ['Placed','Processing','Shipped','Delivered','Cancelled'])) $status='Placed';
    $stmt = $conn->prepare("UPDATE orders SET order_status=?, tracking_number=? WHERE id=?");
    $stmt->bind_param('ssi',$status,$tracking,$id);
    $stmt->execute();
    redirect('orders.php');
}
$orders = $conn->query("SELECT * FROM orders ORDER BY id DESC");
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
        <a class="sidebar-brand" href="../index.php">MAYURI âœ¦</a>
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
        <table class="table">
            <thead>
                <tr><th>#</th><th>Customer</th><th>Phone</th><th>Payment</th><th>Total</th><th>Status</th><th>Update</th></tr>
            </thead>
            <tbody>
            <?php while($o = $orders->fetch_assoc()): ?>
            <tr>
                <td style="font-weight:600;color:var(--primary)">#<?=$o['id']?></td>
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
                            <?php foreach(['Placed','Processing','Shipped','Delivered','Cancelled'] as $s): ?>
                                <option <?=($o['order_status']??'')==$s?'selected':''?>><?=$s?></option>
                            <?php endforeach; ?>
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
