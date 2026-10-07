<?php include 'includes/header.php';
$oid = (int)($_GET['order_id'] ?? 0);
$order = null; $order_items = [];

if($oid){
    if(!is_logged_in()) redirect('login.php');
    $r = $conn->prepare("SELECT * FROM orders WHERE id=? AND (user_id=? OR ?=1)");
    $uidv = (int)$_SESSION['user_id']; $adm = is_admin() ? 1 : 0;
    $r->bind_param('iii', $oid, $uidv, $adm);
    $r->execute();
    $order = $r->get_result()->fetch_assoc();

    if($order){
        $ri = $conn->prepare("SELECT * FROM order_items WHERE order_id=?");
        $ri->bind_param('i', $oid);
        $ri->execute();
        $order_items = $ri->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}

function statusColor($s){
    return ['Pending'=>'#f59e0b','Processing'=>'#3b82f6','Completed'=>'#10b981','Cancelled'=>'#ef4444'][$s] ?? '#888';
}
?>

<div class="container" style="max-width:700px;padding-top:40px;padding-bottom:60px">
    <div class="success-wrap" style="text-align:center;margin-bottom:32px">
        <div class="success-icon">ÃƒÆ’Ã‚Â°Ãƒâ€¦Ã‚Â¸Ãƒâ€¦Ã‚Â½ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â°</div>
        <h1>Order Placed!</h1>
        <p>Thank you for shopping with <strong>MAYURI Accessories</strong>.</p>
        <p style="color:var(--muted);margin-top:6px">We've received your order. It will be processed once your UPI payment is verified.</p>
        <?php if($oid): ?>
            <div class="order-id-tag">Order #<?=$oid?></div>
        <?php endif; ?>
    </div>

    <?php if($order): ?>
    <!-- Order Details Card -->
    <div style="background:#fff;border:1px solid var(--border,#e5e5e5);border-radius:14px;overflow:hidden;margin-bottom:20px">
        <div style="background:var(--cream,#faf8f5);padding:16px 20px;border-bottom:1px solid var(--border,#e5e5e5);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px">
            <div>
                <div style="font-size:0.75rem;color:var(--muted);margin-bottom:2px">Order Placed</div>
                <div style="font-weight:600;font-size:0.9rem"><?=date('d M Y, h:i A', strtotime($order['created_at']))?></div>
            </div>
            <div style="text-align:right">
                <div style="font-size:0.75rem;color:var(--muted);margin-bottom:4px">Order Status</div>
                <span style="background:<?=statusColor($order['order_status'])?>22;color:<?=statusColor($order['order_status'])?>;padding:4px 12px;border-radius:20px;font-size:0.78rem;font-weight:600">
                    <?=e($order['order_status'])?>
                </span>
            </div>
        </div>

        <!-- Items -->
        <div style="padding:16px 20px">
            <div style="font-size:0.8rem;color:var(--muted);font-weight:600;margin-bottom:10px;text-transform:uppercase;letter-spacing:0.05em">Items Ordered</div>
            <?php foreach($order_items as $it): ?>
            <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px dashed var(--border,#eee)">
                <div>
                    <div style="font-size:0.88rem;font-weight:500"><?=e($it['product_name'])?></div>
                    <div style="font-size:0.76rem;color:var(--muted)">Qty: <?=$it['quantity']?> &times; &#8377;<?=number_format($it['price'],2)?></div>
                </div>
                <div style="font-weight:600;font-size:0.9rem">&#8377;<?=number_format($it['subtotal'],2)?></div>
            </div>
            <?php endforeach; ?>
            <div style="display:flex;justify-content:space-between;padding-top:12px;font-weight:700;font-size:1.05rem">
                <span>Total Paid</span>
                <span style="color:var(--primary,#8b6914)">&#8377;<?=number_format($order['total'],2)?></span>
            </div>
        </div>

        <!-- Delivery Address -->
        <div style="padding:16px 20px;border-top:1px solid var(--border,#e5e5e5);background:var(--cream,#faf8f5)">
            <div style="font-size:0.8rem;color:var(--muted);font-weight:600;margin-bottom:8px;text-transform:uppercase;letter-spacing:0.05em">ÃƒÆ’Ã‚Â°Ãƒâ€¦Ã‚Â¸ÃƒÂ¢Ã¢â€šÂ¬Ã…â€œÃƒâ€šÃ‚Â Delivery Address</div>
            <div style="font-size:0.88rem;line-height:1.7">
                <strong><?=e($order['customer_name'])?></strong><br>
                <?php if(!empty($order['address_line1'])): ?>
                    <?=e($order['address_line1'])?><br>
                    <?php if(!empty($order['address_line2'])): ?><?=e($order['address_line2'])?><br><?php endif; ?>
                    <?=e($order['city'])?>, <?=e($order['state'])?> &ndash; <?=e($order['pincode'])?>
                <?php else: ?>
                    <?=nl2br(e($order['address']))?>
                <?php endif; ?>
                <br>ÃƒÆ’Ã‚Â°Ãƒâ€¦Ã‚Â¸ÃƒÂ¢Ã¢â€šÂ¬Ã…â€œÃƒâ€¦Ã‚Â¾ <?=e($order['phone'])?>
            </div>
        </div>

        <!-- Payment Info -->
        <div style="padding:14px 20px;border-top:1px solid var(--border,#e5e5e5);display:flex;gap:24px;flex-wrap:wrap">
            <div>
                <div style="font-size:0.75rem;color:var(--muted)">Payment Method</div>
                <div style="font-weight:600;font-size:0.88rem"><?=e($order['payment_method'])?></div>
            </div>
            <div>
                <div style="font-size:0.75rem;color:var(--muted)">Payment Status</div>
                <div style="font-weight:600;font-size:0.88rem;color:<?=$order['payment_status']==='Paid'?'#10b981':'#f59e0b'?>"><?=e($order['payment_status'])?></div>
            </div>
        </div>
    </div>

    <!-- Tracking Steps -->
    <div style="background:#fff;border:1px solid var(--border,#e5e5e5);border-radius:14px;padding:20px;margin-bottom:24px">
        <div style="font-size:0.8rem;color:var(--muted);font-weight:600;margin-bottom:16px;text-transform:uppercase;letter-spacing:0.05em">Order Progress</div>
        <?php
        $steps = ['Pending'=>0,'Processing'=>1,'Completed'=>2,'Cancelled'=>3];
        $curr  = $order['order_status'];
        $cancelled = $curr === 'Cancelled';
        $labels = $cancelled
            ? [['Order Placed','Your order was received'],['Cancelled','Order has been cancelled']]
            : [['Order Placed','Your order is confirmed'],['Processing','Being prepared for dispatch'],['On the Way','Estimated 3ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Å“5 business days'],['Delivered','Enjoy your purchase!']];
        $currStep = $cancelled ? 1 : $steps[$curr];
        ?>
        <div style="display:flex;align-items:flex-start;gap:0">
            <?php foreach($labels as $i => [$label,$desc]): ?>
            <?php $done = $i <= $currStep; $isLast = $i===count($labels)-1; ?>
            <div style="flex:1;display:flex;flex-direction:column;align-items:center;position:relative">
                <?php if(!$isLast): ?>
                <div style="position:absolute;top:14px;left:50%;width:100%;height:2px;background:<?=$done&&$i<$currStep?'var(--primary,#8b6914)':'#e5e5e5'?>;z-index:0"></div>
                <?php endif; ?>
                <div style="width:28px;height:28px;border-radius:50%;background:<?=$done?'var(--primary,#8b6914)':'#e5e5e5'?>;color:#fff;display:flex;align-items:center;justify-content:center;font-size:0.7rem;font-weight:700;z-index:1;position:relative">
                    <?=$done?'ÃƒÆ’Ã‚Â¢Ãƒâ€¦Ã¢â‚¬Å“ÃƒÂ¢Ã¢â€šÂ¬Ã…â€œ':($i+1)?>
                </div>
                <div style="margin-top:8px;text-align:center;padding:0 4px">
                    <div style="font-size:0.72rem;font-weight:700;color:<?=$done?'var(--primary,#8b6914)':'#aaa'?>"><?=$label?></div>
                    <div style="font-size:0.65rem;color:var(--muted)"><?=$desc?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <div style="text-align:center;display:flex;gap:12px;justify-content:center;flex-wrap:wrap">
        <?php if(isset($_SESSION['user_id'])): ?>
        <a class="btn light" href="my_orders.php">ÃƒÆ’Ã‚Â°Ãƒâ€¦Ã‚Â¸ÃƒÂ¢Ã¢â€šÂ¬Ã…â€œÃƒâ€šÃ‚Â¦ My Orders</a>
        <?php endif; ?>
        <a class="btn gold" href="collection.php">Continue Shopping &rarr;</a>
        <a class="btn light" href="index.php">&larr; Back to Home</a>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
