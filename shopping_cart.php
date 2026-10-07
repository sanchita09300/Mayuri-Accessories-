<?php include 'includes/header.php';
if(isset($_GET['remove'])){
    unset($_SESSION['cart'][(int)$_GET['remove']]);
    redirect('shopping_cart.php');
}
if($_SERVER['REQUEST_METHOD']=='POST'){
    verify_csrf();
    foreach($_POST['qty']??[] as $id => $q){
        $st = $conn->prepare("SELECT stock FROM products WHERE id=?"); $pid=(int)$id; $st->bind_param('i',$pid); $st->execute();
        $row = $st->get_result()->fetch_assoc();
        if(!$row || (int)$row['stock'] < 1){ unset($_SESSION['cart'][$pid]); continue; }
        $_SESSION['cart'][$pid] = min(max(1, (int)$q), (int)$row['stock']);
    }
    redirect('shopping_cart.php');
}
$items = []; $total = 0;
if(!empty($_SESSION['cart'])){
    $ids = implode(',', array_map('intval', array_keys($_SESSION['cart'])));
    $res = $conn->query("SELECT * FROM products WHERE id IN ($ids)");
    $found = [];
    while($p = $res->fetch_assoc()){
        if($p['status']==='out_of_stock' || (int)$p['stock'] < 1){ unset($_SESSION['cart'][$p['id']]); continue; }
        $found[] = (int)$p['id'];
        $_SESSION['cart'][$p['id']] = min((int)$_SESSION['cart'][$p['id']], (int)$p['stock']);
        $p['qty'] = $_SESSION['cart'][$p['id']];
        $p['subtotal'] = $p['qty'] * $p['price'];
        $total += $p['subtotal'];
        $items[] = $p;
    }
    foreach(array_keys($_SESSION['cart']) as $cid){ if(!in_array((int)$cid,$found,true)) unset($_SESSION['cart'][$cid]); }
}
?>

<div class="page-banner">
    <div class="container">
        <div class="breadcrumb"><a href="index.php">Home</a> / Cart</div>
        <h1>Shopping Cart</h1>
        <p><?= count($items) ?> item<?= count($items) !== 1 ? 's' : '' ?> in your cart</p>
    </div>
</div>

<div class="container section">
    <?php if(!$items): ?>
        <div style="text-align:center;padding:60px 0" class="animate-fade-up">
            <div style="font-size:3.5rem;margin-bottom:20px">ÃƒÂ°Ã…Â¸Ã¢â‚¬ÂºÃ‚ÂÃƒÂ¯Ã‚Â¸Ã‚Â</div>
            <h2 style="margin-bottom:10px">Your cart is empty</h2>
            <p style="color:var(--muted);margin-bottom:32px">Explore our collection and find something you love.</p>
            <a class="btn gold" href="collection.php">Browse Collection</a>
        </div>
    <?php else: ?>
        <div style="display:grid;grid-template-columns:1.6fr 1fr;gap:32px;align-items:start">
            <form method="post">
                <?= csrf_field() ?>
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Qty</th>
                            <th>Subtotal</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($items as $p): ?>
                        <tr>
                            <td>
                                <div style="display:flex;align-items:center;gap:12px">
                                    <img class="small-img" src="<?= $p['image'] ? 'uploads/products/'.e($p['image']) : 'https://images.unsplash.com/photo-1515562141207-7a88fb7ce338?q=80&w=200' ?>" alt="">
                                    <div>
                                        <div style="font-weight:600"><?=e($p['name'])?></div>
                                        <div style="font-size:0.78rem;color:var(--gold);letter-spacing:0.08em"><?=e($p['category'])?></div>
                                    </div>
                                </div>
                            </td>
                            <td>&#8377;<?=number_format($p['price'],2)?></td>
                            <td><input style="width:70px;margin-bottom:0" type="number" min="1" max="<?=e($p['stock'])?>" name="qty[<?=$p['id']?>]" value="<?=e($p['qty'])?>"></td>
                            <td style="font-weight:600;color:var(--primary)">&#8377;<?=number_format($p['subtotal'],2)?></td>
                            <td><a class="btn danger" style="padding:7px 12px" href="shopping_cart.php?remove=<?=$p['id']?>">&times;</a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="cart-actions">
                    <button class="btn secondary">Update Cart</button>
                    <a class="btn light" href="collection.php">&larr; Continue Shopping</a>
                </div>
            </form>

            <div class="cart-summary-box">
                <h3 style="margin-bottom:16px">Order Summary</h3>
                <div class="divider" style="margin:0 0 16px"></div>
                <?php foreach($items as $p): ?>
                    <div style="display:flex;justify-content:space-between;font-size:0.88rem;padding:5px 0;color:var(--muted)">
                        <span><?=e($p['name'])?> &times; <?=$p['qty']?></span>
                        <span>&#8377;<?=number_format($p['subtotal'],2)?></span>
                    </div>
                <?php endforeach; ?>
                <div class="divider" style="margin:12px 0"></div>
                <div style="display:flex;justify-content:space-between;align-items:center">
                    <span style="font-size:0.82rem;text-transform:uppercase;letter-spacing:0.1em;color:var(--muted)">Total</span>
                    <div class="cart-total">&#8377;<?=number_format($total,2)?></div>
                </div>
                <a class="btn gold" style="width:100%;justify-content:center;margin-top:20px;padding:14px" href="checkout.php">Proceed to Checkout &rarr;</a>
                <p style="font-size:0.75rem;color:var(--muted);text-align:center;margin-top:10px">ÃƒÂ°Ã…Â¸Ã¢â‚¬ÂÃ¢â‚¬â„¢ Secure checkout</p>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
