<?php
include 'includes/header.php';
require_login();

if (empty($_SESSION['cart'])) { redirect('shopping_cart.php'); }
if (empty($_SESSION['checkout_token'])) { $_SESSION['checkout_token'] = bin2hex(random_bytes(16)); }

// Server-side cart pricing. Prices/stock always come from the database, never from the browser.
function mayuri_load_cart($conn, $cart, $lock = false) {
    $items = []; $subtotal = 0; $error = '';
    foreach ($cart as $pid => $qty) {
        $pid = (int)$pid; $qty = max(1, (int)$qty);
        $stmt = $conn->prepare("SELECT * FROM products WHERE id=?" . ($lock ? " FOR UPDATE" : ""));
        $stmt->bind_param('i', $pid); $stmt->execute();
        $p = $stmt->get_result()->fetch_assoc();
        if (!$p || in_array($p['status'], ['out_of_stock','inactive','hidden','draft'], true)) {
            $error = 'An item in your cart is no longer available. Please review your cart.'; continue;
        }
        if ((int)$p['stock'] < $qty) { $error = '"' . $p['name'] . '" has only ' . (int)$p['stock'] . ' left in stock.'; }
        $sub = round($qty * (float)$p['price'], 2);
        $subtotal += $sub; $items[] = [$p, $qty, $sub];
    }
    return [$items, round($subtotal, 2), $error];
}

[$items, $subtotal, $error] = mayuri_load_cart($conn, $_SESSION['cart']);
$couponMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_coupon'])) {
    verify_csrf();
    $_SESSION['coupon_code'] = strtoupper(trim($_POST['coupon_code'] ?? ''));
}
$couponCode = $_SESSION['coupon_code'] ?? '';
[$discount, $couponMsg] = apply_coupon_amount($conn, $couponCode, $subtotal);
$discount = round(min(max(0, $discount), $subtotal), 2);
if ($discount == 0) { $couponCode = ''; }
$delivery = delivery_charge($subtotal - $discount);
$total = round(max(0, $subtotal - $discount + $delivery), 2);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order']) && !$error) {
    verify_csrf();
    $name = trim($_POST['name'] ?? ''); $email = trim($_POST['email'] ?? '');
    $phone = preg_replace('/\D/', '', $_POST['phone'] ?? '');
    $addr1 = trim($_POST['address_line1'] ?? ''); $addr2 = trim($_POST['address_line2'] ?? '');
    $city = trim($_POST['city'] ?? ''); $state = trim($_POST['state'] ?? ''); $pincode = trim($_POST['pincode'] ?? '');
    $payChoice = $_POST['payment_method'] ?? '';
    $token = $_POST['checkout_token'] ?? '';
    $full_address = $addr1 . ($addr2 ? ', ' . $addr2 : '') . ', ' . $city . ', ' . $state . ' - ' . $pincode;

    if (!$name || !$phone || !$addr1 || !$city || !$state || !$pincode || $payChoice !== 'SCAN_PAY') {
        $error = 'Please fill in all required details.';
    } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (!preg_match('/^[6-9]\d{9}$/', $phone)) {
        $error = 'Please enter a valid 10-digit Indian mobile number.';
    } elseif (!preg_match('/^\d{6}$/', $pincode)) {
        $error = 'Please enter a valid 6-digit PIN code.';
    } elseif (!hash_equals($_SESSION['checkout_token'], $token)) {
        $error = 'This checkout session has expired. Please check My Orders before trying again.';
    } else {
        $uid = (int)$_SESSION['user_id'];
        try {
            $conn->begin_transaction();
            // Re-read everything under row locks and recompute the total from scratch.
            [$litems, $lsub, $lerr] = mayuri_load_cart($conn, $_SESSION['cart'], true);
            if ($lerr || !$litems) { throw new RuntimeException($lerr ?: 'Your cart is empty.'); }
            [$ldisc] = apply_coupon_amount($conn, $couponCode, $lsub);
            $ldisc = round(min(max(0, $ldisc), $lsub), 2);
            $ldel = delivery_charge($lsub - $ldisc);
            $ltotal = round(max(0, $lsub - $ldisc + $ldel), 2);
            $pay = 'UPI / Scan & Pay'; $payStatus = 'Pending Verification'; $orderStatus = 'Placed';
            $stmt = $conn->prepare("INSERT INTO orders(user_id,customer_name,email,phone,address_line1,address_line2,city,state,pincode,address,payment_method,payment_status,order_status,total,delivery_charge,coupon_code,discount,checkout_token) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param('issssssssssssddsds', $uid,$name,$email,$phone,$addr1,$addr2,$city,$state,$pincode,$full_address,$pay,$payStatus,$orderStatus,$ltotal,$ldel,$couponCode,$ldisc,$token);
            $stmt->execute();
            $oid = $conn->insert_id;
            $onum = 'MAY' . date('ymd') . str_pad((string)$oid, 5, '0', STR_PAD_LEFT);
            $u = $conn->prepare("UPDATE orders SET order_number=? WHERE id=?");
            $u->bind_param('si', $onum, $oid); $u->execute();
            foreach ($litems as [$p, $qty, $sub]) {
                $si = $conn->prepare("INSERT INTO order_items(order_id,product_id,product_name,price,quantity,subtotal) VALUES (?,?,?,?,?,?)");
                $si->bind_param('iisdid', $oid, $p['id'], $p['name'], $p['price'], $qty, $sub); $si->execute();
                $newStock = (int)$p['stock'] - $qty;
                $newStatus = $newStock <= 0 ? 'out_of_stock' : 'in_stock';
                $su = $conn->prepare("UPDATE products SET stock=?, status=? WHERE id=? AND stock>=?");
                $su->bind_param('isii', $newStock, $newStatus, $p['id'], $qty); $su->execute();
                if ($su->affected_rows < 1) { throw new RuntimeException('Stock changed for "' . $p['name'] . '". Please try again.'); }
            }
            $conn->commit();
            unset($_SESSION['cart'], $_SESSION['coupon_code'], $_SESSION['checkout_token']);
            redirect('success_page.php?order_id=' . $oid);
        } catch (Throwable $ex) {
            try { $conn->rollback(); } catch (Throwable $x) {}
            if (stripos($ex->getMessage(), 'Duplicate entry') !== false) {
                unset($_SESSION['cart'], $_SESSION['coupon_code'], $_SESSION['checkout_token']);
                redirect('my_orders.php');
            }
            error_log('MAYURI checkout error: ' . $ex->getMessage());
            $error = $ex instanceof RuntimeException ? $ex->getMessage() : 'We could not place your order. Please try again.';
        }
    }
}

$pre_name = e($_SESSION['name'] ?? '');
$pre_email = e($_SESSION['email'] ?? '');
?>

<div class="page-banner co-banner-anim">
    <div class="container">
        <div class="breadcrumb">
            <a href="index.php">Home</a> /
            <a href="shopping_cart.php">Cart</a> /
            Checkout
        </div>
        <h1>Checkout</h1>
        <p>Estimated delivery: <?= estimate_delivery_text() ?></p>
    </div>
</div>

<!-- Checkout Progress Steps -->
<div class="co-steps-bar">
    <div class="container">
        <div class="co-steps">
            <div class="co-step done"><span class="co-step-num">✓</span><span class="co-step-label">Cart</span></div>
            <div class="co-step-line done"></div>
            <div class="co-step active"><span class="co-step-num">2</span><span class="co-step-label">Checkout</span></div>
            <div class="co-step-line"></div>
            <div class="co-step"><span class="co-step-num">3</span><span class="co-step-label">Confirm</span></div>
        </div>
    </div>
</div>

<div class="container co-container">
    <div class="checkout-layout">

        <form class="co-form form" method="post">
            <?= csrf_field() ?>

            <div class="co-section-head co-anim" style="animation-delay:0.05s">
                <div class="co-section-icon">📦</div>
                <div>
                    <h2 style="font-size:1.3rem;margin:0">Delivery Details</h2>
                    <p class="form-subtitle" style="margin:4px 0 0">Where should we send your order?</p>
                </div>
            </div>

            <?php
            if ($error) {
                echo '<div class="alert error co-anim" style="animation-delay:0.1s">' . e($error) . '</div>';
            }
            ?>

            <div class="co-grid-2 co-anim" style="animation-delay:0.12s">
                <div class="form-group" style="margin:0">
                    <label>Full Name *</label>
                    <input name="name" value="<?= $pre_name ?>" required placeholder="Your full name">
                </div>

                <div class="form-group" style="margin:0">
                    <label>Phone *</label>
                    <input name="phone" value="<?= e($_POST['phone'] ?? '') ?>" maxlength="10" required placeholder="10-digit mobile number">
                </div>
            </div>

            <div class="form-group co-anim" style="animation-delay:0.18s">
                <label>Email (optional)</label>
                <input type="email" name="email" value="<?= $pre_email ?>" placeholder="For order updates">
            </div>

            <div class="co-address-block co-anim" style="animation-delay:0.22s">
                <h3 class="co-address-label">🏠 Delivery Address</h3>

                <div class="form-group">
                    <label>Flat / House No., Building, Street *</label>
                    <input name="address_line1" value="<?= e($_POST['address_line1'] ?? '') ?>" required placeholder="e.g. 12A, Rose Apartments, MG Road">
                </div>

                <div class="form-group">
                    <label>Area / Landmark</label>
                    <input name="address_line2" value="<?= e($_POST['address_line2'] ?? '') ?>" placeholder="Nearby landmark (optional)">
                </div>

                <div class="co-grid-2">
                    <div class="form-group" style="margin:0">
                        <label>City *</label>
                        <input name="city" value="<?= e($_POST['city'] ?? '') ?>" required placeholder="City">
                    </div>

                    <div class="form-group" style="margin:0">
                        <label>PIN Code *</label>
                        <input name="pincode" maxlength="6" pattern="\d{6}" value="<?= e($_POST['pincode'] ?? '') ?>" required placeholder="6-digit PIN">
                    </div>
                </div>

                <div class="form-group">
                    <label>State *</label>
                    <select name="state" required>
                        <option value="">-- Select State --</option>

                        <?php
                        $states = [
                            'Maharashtra',
                            'Delhi',
                            'Karnataka',
                            'Gujarat',
                            'Rajasthan',
                            'Tamil Nadu',
                            'Telangana',
                            'Uttar Pradesh',
                            'West Bengal',
                            'Madhya Pradesh',
                            'Goa',
                            'Kerala',
                            'Punjab',
                            'Haryana',
                            'Bihar',
                            'Assam',
                            'Odisha',
                            'Jharkhand',
                            'Chhattisgarh',
                            'Andhra Pradesh'
                        ];

                        $sel = $_POST['state'] ?? 'Maharashtra';

                        foreach ($states as $s) {
                            echo '<option value="' . e($s) . '"' . ($sel === $s ? ' selected' : '') . '>' . e($s) . '</option>';
                        }
                        ?>
                    </select>
                </div>
            </div>

            <div class="co-payment-label co-anim" style="animation-delay:0.28s">
                <span>💳 Payment Method</span>
            </div>

            <input type="hidden" name="payment_method" value="SCAN_PAY">
            <input type="hidden" name="checkout_token" value="<?= e($_SESSION['checkout_token']) ?>">

            <div class="scanpay-card co-anim" style="animation-delay:0.34s">
                <h3 class="scanpay-title">📱 Scan &amp; Pay</h3>

                <div class="scanpay-qr">
                    <div id="upiQr" style="display:inline-block;background:#fff;padding:12px;border-radius:8px"></div>
                </div>

                <p class="scanpay-amount">Amount to Pay: <strong>&#8377;<?= number_format($total, 2) ?></strong></p>
                <a class="btn secondary co-upi-btn" href="<?= e(upi_uri($total, 'MAYURI Order')) ?>">Open in UPI App</a>
                <p class="scanpay-hint">Scan using any UPI app (PhonePe, GPay, Paytm…)</p>

                <div class="scanpay-steps">
                    <div class="co-step-item"><span class="co-step-badge">1</span> Scan QR using any UPI app and complete payment</div>
                    <div class="co-step-item"><span class="co-step-badge">2</span> Click <strong>I Have Paid</strong> to place your order</div>
                </div>
            </div>

            <div class="alert co-anim" style="animation-delay:0.4s">
                🔒 Scan the QR code and complete your UPI payment. Payment will be verified before order processing.
            </div>

            <button type="submit" name="place_order" id="co-submit-btn" class="btn gold co-submit co-anim" style="animation-delay:0.46s">
                ✅ I Have Paid — Place Order
            </button>
        </form>

        <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
        <script>new QRCode(document.getElementById("upiQr"),{text:<?= json_encode(upi_uri($total, "MAYURI Order")) ?>,width:220,height:220,correctLevel:QRCode.CorrectLevel.M});</script>
        <script>
        // Prevent double-click from placing the order twice
        (function () {
            var f = document.querySelector('button[name="place_order"]').form;
            f.addEventListener('submit', function (ev) {
                if (f.dataset.sent) { ev.preventDefault(); return; }
                f.dataset.sent = '1';
                var btn = document.getElementById('co-submit-btn');
                if(btn){ btn.textContent = '⏳ Placing Order…'; btn.disabled = true; }
            });
        })();
        </script>

        <!-- Right: Order Summary -->
        <div class="co-right-col">
            <div class="order-summary co-summary-anim">
                <h3 class="co-summary-title">
                    <span>🛍️ Order Summary</span>
                    <span class="co-item-count"><?= count($items) ?> item<?= count($items) !== 1 ? 's' : '' ?></span>
                </h3>
                <div class="divider"></div>

                <?php foreach ($items as $i => [$p, $qty, $sub]): ?>
                    <div class="order-item co-order-item-anim" style="animation-delay:<?= $i * 0.07 ?>s">
                        <span><?= e($p['name']) ?> × <?= $qty ?></span>
                        <strong>₹<?= number_format($sub, 2) ?></strong>
                    </div>
                <?php endforeach; ?>

                <div class="divider"></div>

                <form method="post" class="co-coupon-form">
                    <?= csrf_field() ?>
                    <input name="coupon_code" placeholder="🏷️ Coupon code" value="<?= e($couponCode) ?>" class="co-coupon-input">
                    <button name="apply_coupon" class="btn secondary co-coupon-btn">Apply</button>
                </form>

                <?php if ($couponMsg): ?>
                    <p class="co-coupon-msg"><?= e($couponMsg) ?></p>
                <?php endif; ?>

                <div class="co-price-lines">
                    <div class="co-price-line">
                        <span>Subtotal</span>
                        <span>₹<?= number_format($subtotal, 2) ?></span>
                    </div>
                    <div class="co-price-line discount">
                        <span>Discount</span>
                        <span>− ₹<?= number_format($discount, 2) ?></span>
                    </div>
                    <div class="co-price-line">
                        <span>Delivery</span>
                        <span><?= $delivery == 0 ? '<span class="co-free">FREE</span>' : '₹' . number_format($delivery, 2) ?></span>
                    </div>
                </div>

                <div class="order-total co-grand-total">
                    <span>Grand Total</span>
                    <span class="co-total-amount">₹<?= number_format($total, 2) ?></span>
                </div>

                <p class="co-coupon-hint">Try coupons: <code>MAYURI10</code> or <code>WELCOME50</code></p>
            </div>

            <div class="trust-box co-trust-anim">
                🔒 Secure checkout<br>
                🚚 Est. delivery: <?= estimate_delivery_text() ?><br>
                🔄 7-day return support
            </div>
        </div>

    </div>
</div>

<!-- Checkout Animations & Mobile Styles -->
<style>
/* ===== CHECKOUT LAYOUT ===== */
.co-container { padding-top: 0; }
.checkout-layout {
    display: grid;
    grid-template-columns: 1.4fr 1fr;
    gap: 44px;
    padding: 44px 0 70px;
    align-items: start;
}

/* ===== PROGRESS STEPS ===== */
.co-steps-bar {
    background: var(--white);
    border-bottom: 1px solid var(--border);
    padding: 14px 0;
    animation: coFadeDown 0.5s ease both;
}
.co-steps {
    display: flex;
    align-items: center;
    gap: 0;
    justify-content: center;
}
.co-step {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
    min-width: 64px;
}
.co-step-num {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: var(--border);
    color: var(--muted);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    font-weight: 700;
    transition: all 0.3s;
}
.co-step.active .co-step-num {
    background: var(--primary);
    color: #fff;
    box-shadow: 0 0 0 4px rgba(107,76,82,0.15);
}
.co-step.done .co-step-num {
    background: var(--gold);
    color: var(--dark);
}
.co-step-label {
    font-size: 0.62rem;
    font-weight: 600;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: var(--muted);
}
.co-step.active .co-step-label { color: var(--primary); }
.co-step.done .co-step-label { color: var(--gold); }
.co-step-line {
    flex: 1;
    height: 2px;
    background: var(--border);
    margin: 0 4px;
    margin-bottom: 12px;
    max-width: 80px;
    border-radius: 2px;
    transition: background 0.3s;
}
.co-step-line.done { background: var(--gold); }

/* ===== FORM STYLES ===== */
.co-form {
    max-width: none;
    margin: 0;
    animation: coSlideInLeft 0.55s ease both;
}
.co-section-head {
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: 22px;
    padding: 16px 18px;
    background: linear-gradient(135deg, rgba(201,169,110,0.06), transparent);
    border-radius: 12px;
    border-left: 3px solid var(--gold);
}
.co-section-icon { font-size: 1.5rem; }

.co-grid-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
    margin-bottom: 20px;
}

.co-address-block {
    border-top: 1px solid var(--border);
    margin: 18px 0 16px;
    padding-top: 18px;
}
.co-address-label {
    font-size: 0.88rem;
    margin-bottom: 14px;
    color: var(--muted);
    font-weight: 600;
}

.co-payment-label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.68rem;
    font-weight: 700;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    color: var(--muted);
    margin-bottom: 12px;
}

/* ===== SCANPAY CARD ===== */
.scanpay-card {
    background: linear-gradient(135deg, var(--cream) 0%, #fff 100%);
    border: 1px solid var(--gold-light);
    border-radius: 16px;
    padding: 24px;
    text-align: center;
    margin-bottom: 18px;
    box-shadow: 0 4px 24px rgba(201,169,110,0.1);
    position: relative;
    overflow: hidden;
}
.scanpay-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0; height: 3px;
    background: linear-gradient(90deg, var(--gold), #fff5d0, var(--gold));
    background-size: 200% auto;
    animation: shimmer 2.5s linear infinite;
}
.scanpay-title { margin-bottom: 14px; font-size: 1.05rem; }
.scanpay-qr {
    display: flex;
    justify-content: center;
    padding: 8px 0 12px;
    animation: coQrPop 0.6s ease 0.5s both;
}
.scanpay-amount { font-size: 1rem; margin: 8px 0 10px; }
.scanpay-amount strong {
    font-size: 1.2rem;
    font-family: 'Cormorant Garamond', serif;
    color: var(--primary);
}
.co-upi-btn { display: inline-flex; margin: 6px auto; }
.scanpay-hint { font-size: 0.78rem; color: var(--muted); margin-top: 8px; }
.scanpay-steps { margin-top: 14px; text-align: left; }
.co-step-item {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    font-size: 0.82rem;
    color: var(--muted);
    padding: 5px 0;
}
.co-step-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 22px;
    height: 22px;
    min-width: 22px;
    border-radius: 50%;
    background: var(--gold);
    color: var(--dark);
    font-size: 0.7rem;
    font-weight: 700;
}

/* ===== SUBMIT BUTTON ===== */
.co-submit {
    width: 100%;
    justify-content: center;
    padding: 16px;
    font-size: 0.95rem;
    letter-spacing: 0.06em;
    position: relative;
    overflow: hidden;
    transition: transform 0.2s, box-shadow 0.2s;
}
.co-submit:hover { transform: translateY(-2px); box-shadow: 0 8px 28px rgba(201,169,110,0.4); }
.co-submit:active { transform: translateY(0); }

/* ===== ORDER SUMMARY (RIGHT COLUMN) ===== */
.co-right-col { display: flex; flex-direction: column; gap: 16px; }
.co-summary-anim { animation: coSlideInRight 0.55s ease 0.2s both; }
.order-summary {
    background: var(--white);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    padding: 28px;
    box-shadow: var(--shadow-sm);
    position: sticky;
    top: 90px;
}
.co-summary-title {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
    font-size: 1rem;
}
.co-item-count {
    font-size: 0.72rem;
    font-weight: 600;
    background: rgba(201,169,110,0.1);
    color: var(--gold);
    padding: 3px 10px;
    border-radius: 20px;
    letter-spacing: 0.06em;
}
.order-item {
    display: flex;
    justify-content: space-between;
    font-size: 0.86rem;
    padding: 7px 0;
    color: var(--muted);
    border-bottom: 1px solid rgba(232,221,216,0.5);
    animation: coOrderItemIn 0.4s ease both;
}
.order-item:last-child { border-bottom: none; }
.co-order-item-anim { animation: coOrderItemIn 0.4s ease both; }

/* ===== COUPON FORM ===== */
.co-coupon-form {
    display: flex;
    gap: 8px;
    margin: 14px 0 10px;
    flex-wrap: wrap;
}
.co-coupon-input { flex: 1; min-width: 0; }
.co-coupon-btn { white-space: nowrap; flex-shrink: 0; }
.co-coupon-msg { font-size: 0.78rem; color: var(--muted); margin-bottom: 8px; }
.co-coupon-hint { font-size: 0.72rem; color: var(--muted); margin-top: 8px; }
.co-coupon-hint code {
    background: rgba(201,169,110,0.1);
    color: var(--gold);
    padding: 1px 5px;
    border-radius: 4px;
    font-size: 0.72rem;
}

/* ===== PRICE LINES ===== */
.co-price-lines { margin: 4px 0 4px; }
.co-price-line {
    display: flex;
    justify-content: space-between;
    font-size: 0.86rem;
    padding: 7px 0;
    color: var(--muted);
    border-bottom: 1px dashed var(--border);
}
.co-price-line:last-child { border-bottom: none; }
.co-price-line.discount strong, .co-price-line.discount span:last-child { color: #2e9e5b; font-weight: 600; }
.co-free { color: #2e9e5b; font-weight: 700; font-size: 0.82rem; letter-spacing: 0.06em; }
.co-grand-total {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 14px;
    padding-top: 14px;
    border-top: 2px solid var(--border);
}
.co-total-amount {
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.8rem;
    color: var(--primary);
    font-weight: 700;
}

/* ===== TRUST BOX ===== */
.trust-box {
    margin-top: 0;
    padding: 16px 18px;
    background: var(--cream);
    border-radius: 10px;
    font-size: 0.85rem;
    color: var(--muted);
    line-height: 2;
    border: 1px solid var(--border);
}
.co-trust-anim { animation: coFadeIn 0.6s ease 0.4s both; }

/* ===== ANIMATIONS ===== */
.co-anim { animation: coFadeUp 0.45s ease both; }

@keyframes coFadeUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}
@keyframes coFadeDown {
    from { opacity: 0; transform: translateY(-14px); }
    to { opacity: 1; transform: translateY(0); }
}
@keyframes coSlideInLeft {
    from { opacity: 0; transform: translateX(-22px); }
    to { opacity: 1; transform: translateX(0); }
}
@keyframes coSlideInRight {
    from { opacity: 0; transform: translateX(22px); }
    to { opacity: 1; transform: translateX(0); }
}
@keyframes coOrderItemIn {
    from { opacity: 0; transform: translateX(10px); }
    to { opacity: 1; transform: translateX(0); }
}
@keyframes coQrPop {
    from { opacity: 0; transform: scale(0.85); }
    to { opacity: 1; transform: scale(1); }
}
@keyframes coFadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

/* ===== MOBILE RESPONSIVE ===== */
@media (max-width: 900px) {
    .checkout-layout {
        grid-template-columns: 1fr;
        gap: 24px;
        padding: 28px 0 50px;
    }
    /* Order summary goes to top on mobile for quick reference */
    .co-right-col { order: -1; }
    .order-summary { position: static; padding: 22px; }

    .co-form { animation: coFadeUp 0.5s ease both; }
    .co-summary-anim { animation: coFadeUp 0.4s ease both; }
}

@media (max-width: 600px) {
    .co-grid-2 { grid-template-columns: 1fr; gap: 0; }
    .co-grid-2 .form-group { margin-bottom: 16px; }
    .scanpay-card { padding: 18px 14px; }
    .co-coupon-form { flex-direction: column; }
    .co-coupon-input, .co-coupon-btn { width: 100%; }
    .co-total-amount { font-size: 1.5rem; }
    .co-steps-bar { padding: 10px 0; }
    .co-step-label { display: none; }
    .co-step-line { max-width: 40px; }
}

@media (max-width: 400px) {
    .scanpay-card { padding: 14px 10px; }
    .co-submit { font-size: 0.85rem; padding: 14px; }
}
</style>

<?php include 'includes/footer.php'; ?>