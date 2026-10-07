<?php include 'includes/header.php';
if(!isset($_SESSION['user_id'])) redirect('login.php');

$uid = (int)$_SESSION['user_id'];

// Fetch all orders for this user
$orders_res = $conn->prepare("SELECT * FROM orders WHERE user_id=? ORDER BY created_at DESC");
$orders_res->bind_param('i', $uid);
$orders_res->execute();
$orders = $orders_res->get_result()->fetch_all(MYSQLI_ASSOC);

// For each order, fetch items
$order_items_map = [];
if($orders){
    $oids = implode(',', array_column($orders, 'id'));
    $ir = $conn->query("SELECT * FROM order_items WHERE order_id IN ($oids)");
    while($row = $ir->fetch_assoc()){
        $order_items_map[$row['order_id']][] = $row;
    }
}

function statusColor($s){
    return ['Pending'=>'#f59e0b','Processing'=>'#3b82f6','Completed'=>'#10b981','Cancelled'=>'#ef4444'][$s] ?? '#888';
}
function payColor($s){
    return $s==='Paid' ? '#10b981' : '#f59e0b';
}
?>

<div class="page-banner">
    <div class="container">
        <div class="breadcrumb"><a href="index.php">Home</a> / My Orders</div>
        <h1>My Orders</h1>
        <p>Track all your purchases and delivery status</p>
    </div>
</div>

<div class="container" style="max-width:800px;padding-top:32px;padding-bottom:60px">

<?php if(empty($orders)): ?>
    <div style="text-align:center;padding:60px 20px">
        <div style="font-size:3rem;margin-bottom:16px">ÃƒÆ’Ã‚Â°Ãƒâ€¦Ã‚Â¸ÃƒÂ¢Ã¢â€šÂ¬Ã‚ÂºÃƒâ€šÃ‚ÂÃƒÆ’Ã‚Â¯Ãƒâ€šÃ‚Â¸Ãƒâ€šÃ‚Â</div>
        <h2 style="font-weight:600;margin-bottom:8px">No orders yet</h2>
        <p style="color:var(--muted);margin-bottom:24px">You haven't placed any orders. Start shopping!</p>
        <a class="btn gold" href="collection.php">Browse Collection &rarr;</a>
    </div>
<?php else: ?>
    <div style="font-size:0.85rem;color:var(--muted);margin-bottom:20px"><?=count($orders)?> order<?=count($orders)>1?'s':''?> found</div>

    <?php foreach($orders as $o): ?>
    <?php $items = $order_items_map[$o['id']] ?? []; ?>
    <div style="background:#fff;border:1px solid var(--border,#e5e5e5);border-radius:14px;overflow:hidden;margin-bottom:24px">

        <!-- Order Header -->
        <div style="background:var(--cream,#faf8f5);padding:14px 20px;border-bottom:1px solid var(--border,#e5e5e5);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px">
            <div style="display:flex;gap:20px;flex-wrap:wrap;align-items:center">
                <div>
                    <span style="font-size:0.72rem;color:var(--muted)">ORDER</span>
                    <div style="font-weight:700;font-size:0.95rem">&nbsp;<?=e($o['order_number'] ?: '#'.$o['id'])?></div>
                </div>
                <div>
                    <span style="font-size:0.72rem;color:var(--muted)">PLACED ON</span>
                    <div style="font-weight:600;font-size:0.82rem"><?=date('d M Y', strtotime($o['created_at']))?></div>
                </div>
                <div>
                    <span style="font-size:0.72rem;color:var(--muted)">TOTAL</span>
                    <div style="font-weight:700;color:var(--primary,#8b6914);font-size:0.95rem">&#8377;<?=number_format($o['total'],2)?></div>
                </div>
            </div>
            <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
                <span style="background:<?=payColor($o['payment_status'])?>22;color:<?=payColor($o['payment_status'])?>;padding:3px 10px;border-radius:20px;font-size:0.72rem;font-weight:600">
                    <?=e($o['payment_status'])?>
                </span>
                <span style="background:<?=statusColor($o['order_status'])?>22;color:<?=statusColor($o['order_status'])?>;padding:3px 10px;border-radius:20px;font-size:0.72rem;font-weight:600">
                    <?=e($o['order_status'])?>
                </span>
            </div>
        </div>

        <!-- Items List -->
        <div style="padding:14px 20px">
            <?php foreach($items as $it): ?>
            <div style="display:flex;justify-content:space-between;align-items:center;padding:7px 0;border-bottom:1px dashed #f0f0f0">
                <div>
                    <div style="font-size:0.88rem;font-weight:500"><?=e($it['product_name'])?></div>
                    <div style="font-size:0.75rem;color:var(--muted)">Qty <?=$it['quantity']?> &times; &#8377;<?=number_format($it['price'],2)?></div>
                </div>
                <div style="font-weight:600;font-size:0.88rem">&#8377;<?=number_format($it['subtotal'],2)?></div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Delivery Address -->
        <div style="padding:12px 20px;border-top:1px solid var(--border,#e5e5e5);background:#fdfcfb;display:flex;gap:32px;flex-wrap:wrap">
            <div style="flex:1;min-width:200px">
                <div style="font-size:0.72rem;color:var(--muted);font-weight:600;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px">ÃƒÆ’Ã‚Â°Ãƒâ€¦Ã‚Â¸ÃƒÂ¢Ã¢â€šÂ¬Ã…â€œÃƒâ€šÃ‚Â Delivery Address</div>
                <div style="font-size:0.82rem;line-height:1.7;color:var(--dark,#1a1a1a)">
                    <?php if(!empty($o['address_line1'])): ?>
                        <?=e($o['address_line1'])?><?=!empty($o['address_line2'])?', '.e($o['address_line2']):'';?><br>
                        <?=e($o['city'])?>, <?=e($o['state'])?> &ndash; <?=e($o['pincode'])?>
                    <?php else: ?>
                        <?=nl2br(e($o['address']))?>
                    <?php endif; ?>
                    <br><span style="color:var(--muted)">ÃƒÂ°Ã…Â¸Ã¢â‚¬Å“Ã…Â¾ <?=e($o['phone'])?></span>
                </div>
            </div>
            <div>
                <div style="font-size:0.72rem;color:var(--muted);font-weight:600;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px">Payment</div>
                <div style="font-size:0.82rem;font-weight:500"><?=e($o['payment_method'])?></div>
            </div>
        </div>

        <!-- Progress Bar -->
        <div style="margin:12px 0;display:flex;gap:10px;flex-wrap:wrap">
                    <a class="btn secondary" style="padding:7px 14px;font-size:.78rem" href="invoice.php?order_id=<?=$o['id']?>" target="_blank">Invoice / PDF</a>
                    <?php if(!empty($o['tracking_number'])): ?><span class="badge ok">Tracking: <?=e($o['tracking_number'])?></span><?php endif; ?>
                </div>
                <?php if($o['order_status'] !== 'Cancelled'): ?>
        <?php
        $stepMap = ['Pending'=>0,'Processing'=>1,'Completed'=>2];
        $curr = $stepMap[$o['order_status']] ?? 0;
        $labels = ['Confirmed','Processing','Delivered'];
        ?>
        <div style="padding:14px 20px;border-top:1px solid var(--border,#e5e5e5)">
            <div style="display:flex;align-items:center">
                <?php foreach($labels as $i=>$lbl): ?>
                <?php $done = $i<=$curr; $isLast=$i===count($labels)-1; ?>
                <div style="display:flex;align-items:center;flex:<?=$isLast?'0':'1'?>">
                    <div style="display:flex;flex-direction:column;align-items:center">
                        <div style="width:22px;height:22px;border-radius:50%;background:<?=$done?'var(--primary,#8b6914)':'#e0e0e0'?>;color:#fff;display:flex;align-items:center;justify-content:center;font-size:0.6rem;font-weight:700">
                            <?=$done?'ÃƒÂ¢Ã…â€œÃ¢â‚¬Å“':($i+1)?>
                        </div>
                        <div style="font-size:0.65rem;color:<?=$done?'var(--primary,#8b6914)':'#aaa'?>;margin-top:4px;font-weight:600;white-space:nowrap"><?=$lbl?></div>
                    </div>
                    <?php if(!$isLast): ?>
                    <div style="flex:1;height:2px;background:<?=$i<$curr?'var(--primary,#8b6914)':'#e0e0e0'?>;margin:0 4px;margin-bottom:16px"></div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php else: ?>
        <div style="padding:12px 20px;border-top:1px solid #fee2e2;background:#fff5f5">
            <span style="font-size:0.8rem;color:#ef4444;font-weight:600">ÃƒÆ’Ã‚Â¢Ãƒâ€šÃ‚ÂÃƒÆ’Ã¢â‚¬Â¦ÃƒÂ¢Ã¢â€šÂ¬Ã¢â€žÂ¢ This order was cancelled</span>
        </div>
        <?php endif; ?>

        <!-- Action -->
        <div style="padding:10px 20px;border-top:1px solid var(--border,#e5e5e5);text-align:right">
            <a href="success_page.php?order_id=<?=$o['id']?>" style="font-size:0.8rem;color:var(--primary,#8b6914);font-weight:600;text-decoration:none">View Details &rarr;</a>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>

</div>

<?php include 'includes/footer.php'; ?>
