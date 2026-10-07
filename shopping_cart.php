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

<div class="page-banner cart-banner-anim">
    <div class="container">
        <div class="breadcrumb"><a href="index.php">Home</a> / Cart</div>
        <h1>Shopping Cart</h1>
        <p><?= count($items) ?> item<?= count($items) !== 1 ? 's' : '' ?> in your cart</p>
    </div>
</div>

<div class="container section">
    <?php if(!$items): ?>
        <div class="cart-empty-state animate-fade-up">
            <div class="cart-empty-icon">🛒</div>
            <h2>Your cart is empty</h2>
            <p>Explore our collection and find something you love.</p>
            <a class="btn gold" href="collection.php">Browse Collection</a>
        </div>
    <?php else: ?>
        <div class="cart-page-layout">
            <!-- Left: Cart Items -->
            <form method="post" class="cart-form-col">
                <?= csrf_field() ?>
                <div class="cart-table-header">
                    <span>Product</span>
                    <span>Price</span>
                    <span>Qty</span>
                    <span>Subtotal</span>
                    <span></span>
                </div>
                <div class="cart-items-list">
                    <?php foreach($items as $i => $p): ?>
                    <div class="cart-item-row" style="animation-delay:<?= $i * 0.08 ?>s">
                        <div class="cart-item-product">
                            <img class="cart-item-img" src="<?= $p['image'] ? 'uploads/products/'.e($p['image']) : 'https://images.unsplash.com/photo-1515562141207-7a88fb7ce338?q=80&w=200' ?>" alt="<?=e($p['name'])?>">
                            <div class="cart-item-info">
                                <div class="cart-item-name"><?=e($p['name'])?></div>
                                <div class="cart-item-cat"><?=e($p['category'])?></div>
                            </div>
                        </div>
                        <div class="cart-item-price" data-label="Price">&#8377;<?=number_format($p['price'],2)?></div>
                        <div class="cart-item-qty" data-label="Qty">
                            <input class="qty-input" type="number" min="1" max="<?=e($p['stock'])?>" name="qty[<?=$p['id']?>]" value="<?=e($p['qty'])?>">
                        </div>
                        <div class="cart-item-subtotal" data-label="Subtotal">&#8377;<?=number_format($p['subtotal'],2)?></div>
                        <div class="cart-item-remove">
                            <a class="cart-remove-btn" href="shopping_cart.php?remove=<?=$p['id']?>" title="Remove">&times;</a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="cart-actions">
                    <button class="btn secondary cart-update-btn">&#8635; Update Cart</button>
                    <a class="btn light" href="collection.php">&larr; Continue Shopping</a>
                </div>
            </form>

            <!-- Right: Order Summary -->
            <div class="cart-summary-box cart-summary-anim">
                <h3 class="cart-summary-title">Order Summary</h3>
                <div class="divider" style="margin:0 0 16px"></div>
                <?php foreach($items as $p): ?>
                    <div class="cart-summary-line">
                        <span><?=e($p['name'])?> &times; <?=$p['qty']?></span>
                        <span>&#8377;<?=number_format($p['subtotal'],2)?></span>
                    </div>
                <?php endforeach; ?>
                <div class="divider" style="margin:12px 0"></div>
                <div class="cart-summary-total-row">
                    <span class="cart-summary-total-label">Total</span>
                    <div class="cart-total">&#8377;<?=number_format($total,2)?></div>
                </div>
                <a class="btn gold cart-checkout-btn" href="checkout.php">Proceed to Checkout &rarr;</a>
                <p class="cart-secure-note">🔒 Secure checkout</p>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Cart Page Animations & Mobile Fix CSS -->
<style>
/* ===== CART PAGE LAYOUT ===== */
.cart-page-layout {
    display: grid;
    grid-template-columns: 1.6fr 1fr;
    gap: 32px;
    align-items: start;
}

/* ===== CART EMPTY STATE ===== */
.cart-empty-state {
    text-align: center;
    padding: 80px 20px;
    animation: cartFadeUp 0.6s ease both;
}
.cart-empty-icon {
    font-size: 4rem;
    margin-bottom: 20px;
    display: inline-block;
    animation: cartFloat 3s ease-in-out infinite;
}
.cart-empty-state h2 { margin-bottom: 10px; }
.cart-empty-state p { color: var(--muted); margin-bottom: 32px; }

/* ===== CART TABLE HEADER ===== */
.cart-table-header {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr 1fr 40px;
    gap: 8px;
    padding: 10px 18px;
    font-size: 0.68rem;
    font-weight: 700;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    color: var(--muted);
    border-bottom: 1px solid var(--border);
    background: var(--bg);
    border-radius: 10px 10px 0 0;
}

/* ===== CART ITEMS LIST ===== */
.cart-items-list {
    background: var(--white);
    border: 1px solid var(--border);
    border-radius: 0 0 14px 14px;
    overflow: hidden;
}

/* ===== CART ITEM ROW ===== */
.cart-item-row {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr 1fr 40px;
    gap: 8px;
    padding: 18px;
    align-items: center;
    border-bottom: 1px solid var(--border);
    transition: background 0.2s ease;
    animation: cartItemSlide 0.45s ease both;
}
.cart-item-row:last-child { border-bottom: none; }
.cart-item-row:hover { background: rgba(245,237,232,0.5); }

.cart-item-product {
    display: flex;
    align-items: center;
    gap: 14px;
}
.cart-item-img {
    width: 64px;
    height: 64px;
    object-fit: cover;
    border-radius: 10px;
    transition: transform 0.3s ease;
    flex-shrink: 0;
}
.cart-item-row:hover .cart-item-img { transform: scale(1.06); }
.cart-item-name { font-weight: 600; font-size: 0.92rem; line-height: 1.3; }
.cart-item-cat { font-size: 0.72rem; color: var(--gold); letter-spacing: 0.08em; margin-top: 3px; }

.cart-item-price { font-size: 0.9rem; color: var(--text); }
.cart-item-subtotal { font-weight: 700; color: var(--primary); font-size: 0.95rem; }

.qty-input {
    width: 68px !important;
    margin-bottom: 0 !important;
    padding: 8px 10px;
    border: 1.5px solid var(--border);
    border-radius: 8px;
    font-size: 0.9rem;
    text-align: center;
    transition: border-color 0.25s, box-shadow 0.25s;
    background: var(--bg);
}
.qty-input:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(107,76,82,0.08);
    outline: none;
}

.cart-remove-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: rgba(220,53,69,0.08);
    color: var(--danger);
    font-size: 1.1rem;
    font-weight: 700;
    text-decoration: none;
    transition: background 0.25s, transform 0.2s;
}
.cart-remove-btn:hover {
    background: var(--danger);
    color: #fff;
    transform: rotate(90deg) scale(1.1);
}

/* ===== CART ACTIONS ===== */
.cart-actions {
    display: flex;
    gap: 12px;
    margin-top: 20px;
    flex-wrap: wrap;
}
.cart-update-btn { animation: cartFadeUp 0.5s ease 0.3s both; }

/* ===== CART SUMMARY BOX ===== */
.cart-summary-box {
    background: var(--white);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    padding: 28px;
    box-shadow: var(--shadow-sm);
    position: sticky;
    top: 90px;
}
.cart-summary-anim { animation: cartSlideInRight 0.55s ease 0.15s both; }
.cart-summary-title {
    font-size: 1.1rem;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.cart-summary-title::before {
    content: '';
    width: 3px;
    height: 18px;
    background: var(--gold);
    border-radius: 2px;
    display: inline-block;
}
.cart-summary-line {
    display: flex;
    justify-content: space-between;
    font-size: 0.86rem;
    padding: 6px 0;
    color: var(--muted);
}
.cart-summary-total-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 4px;
}
.cart-summary-total-label {
    font-size: 0.78rem;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: var(--muted);
}
.cart-total {
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.9rem;
    color: var(--primary);
    font-weight: 600;
}
.cart-checkout-btn {
    width: 100%;
    justify-content: center;
    margin-top: 20px;
    padding: 14px;
    position: relative;
    overflow: hidden;
}
.cart-checkout-btn::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 0;
    height: 0;
    background: rgba(255,255,255,0.25);
    border-radius: 50%;
    transform: translate(-50%, -50%);
    transition: width 0.5s ease, height 0.5s ease;
}
.cart-checkout-btn:hover::after { width: 300px; height: 300px; }
.cart-secure-note {
    font-size: 0.75rem;
    color: var(--muted);
    text-align: center;
    margin-top: 10px;
}

/* ===== CART ANIMATIONS ===== */
@keyframes cartFadeUp {
    from { opacity: 0; transform: translateY(24px); }
    to { opacity: 1; transform: translateY(0); }
}
@keyframes cartSlideInRight {
    from { opacity: 0; transform: translateX(28px); }
    to { opacity: 1; transform: translateX(0); }
}
@keyframes cartItemSlide {
    from { opacity: 0; transform: translateX(-16px); }
    to { opacity: 1; transform: translateX(0); }
}
@keyframes cartFloat {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-12px); }
}
.cart-banner-anim .page-banner { animation: none; }

/* ===== MOBILE RESPONSIVE ===== */
@media (max-width: 768px) {
    .cart-page-layout {
        grid-template-columns: 1fr;
        gap: 24px;
    }
    .cart-summary-box {
        position: static;
        padding: 22px;
    }

    /* Hide desktop header on mobile */
    .cart-table-header { display: none; }

    /* Mobile card-style rows */
    .cart-item-row {
        grid-template-columns: 1fr auto;
        grid-template-areas:
            "product remove"
            "price   qty"
            "subtotal subtotal";
        gap: 10px;
        padding: 16px;
    }
    .cart-item-product { grid-area: product; }
    .cart-item-price   { grid-area: price; font-size: 0.82rem; }
    .cart-item-price::before  { content: attr(data-label) ': '; font-size: 0.65rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: var(--muted); }
    .cart-item-qty    { grid-area: qty; }
    .cart-item-subtotal { grid-area: subtotal; font-size: 1rem; }
    .cart-item-subtotal::before { content: 'Subtotal: '; font-size: 0.65rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: var(--muted); }
    .cart-item-remove  { grid-area: remove; }

    .cart-item-img { width: 54px; height: 54px; }

    .cart-actions { flex-direction: column; }
    .cart-actions .btn { width: 100%; justify-content: center; }
}

@media (max-width: 480px) {
    .cart-item-row {
        padding: 14px 12px;
    }
    .cart-item-name { font-size: 0.85rem; }
    .cart-total { font-size: 1.6rem; }
    .cart-summary-box { padding: 18px; }
}
</style>

<?php include 'includes/footer.php'; ?>
