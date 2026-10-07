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

<div class="page-banner">
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

<div class="container">
    <div class="checkout-layout">

        <form class="form" method="post" style="margin:0">
            <?= csrf_field() ?>

            <h2>Delivery Details</h2>
            <p class="form-subtitle">Where should we send your order?</p>

            <?php
            if ($error) {
                echo '<div class="alert error">' . e($error) . '</div>';
            }
            ?>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                <div class="form-group" style="margin:0">
                    <label>Full Name *</label>
                    <input name="name" value="<?= $pre_name ?>" required>
                </div>

                <div class="form-group" style="margin:0">
                    <label>Phone *</label>
                    <input name="phone" value="<?= e($_POST['phone'] ?? '') ?>" maxlength="10" required>
                </div>
            </div>

            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" value="<?= $pre_email ?>">
            </div>

            <div style="border-top:1px solid var(--border);margin:18px 0 16px;padding-top:16px">
                <h3 style="font-size:0.9rem;margin-bottom:14px;color:var(--muted)">
                    Ã°Å¸â€œÂ Delivery Address
                </h3>

                <div class="form-group">
                    <label>Flat / House No., Building, Street *</label>
                    <input name="address_line1" value="<?= e($_POST['address_line1'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label>Area / Landmark</label>
                    <input name="address_line2" value="<?= e($_POST['address_line2'] ?? '') ?>">
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                    <div class="form-group" style="margin:0">
                        <label>City *</label>
                        <input name="city" value="<?= e($_POST['city'] ?? '') ?>" required>
                    </div>

                    <div class="form-group" style="margin:0">
                        <label>PIN Code *</label>
                        <input name="pincode" maxlength="6" pattern="\d{6}" value="<?= e($_POST['pincode'] ?? '') ?>" required>
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

            <div class="form-group" style="margin-bottom:12px">
                <label>Payment Method</label>
            </div>

            <input type="hidden" name="payment_method" value="SCAN_PAY">
            <input type="hidden" name="checkout_token" value="<?= e($_SESSION['checkout_token']) ?>">

            <div class="scanpay-card">
                <h3 class="scanpay-title">Scan &amp; Pay</h3>

                <div class="scanpay-qr">
                    <div id="upiQr" style="display:inline-block;background:#fff;padding:12px;border-radius:8px"></div>
                </div>

                <p class="scanpay-amount">Amount to Pay: <strong>&#8377;<?= number_format($total, 2) ?></strong></p>
                <a class="btn secondary" style="margin:6px 0" href="<?= e(upi_uri($total, 'MAYURI Order')) ?>">Open in UPI app (mobile)</a>
                <p class="scanpay-hint">Scan this QR code using any UPI payment app</p>

                <div class="scanpay-steps">
                    <p>Scan the QR code using any UPI app and complete the payment.</p>
                    <p>After successful payment, click <strong>I Have Paid</strong> to place your order.</p>
                </div>
            </div>

            <div class="alert">
                Scan the QR code and complete your UPI payment. Payment will be verified before order processing.
            </div>

            <button type="submit" name="place_order" class="btn gold" style="width:100%;justify-content:center;padding:14px">
                I Have Paid Ã¢â‚¬â€ Place Order
            </button>
        </form>

        <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
        <script>new QRCode(document.getElementById("upiQr"),{text:<?= json_encode(upi_uri($total, "MAYURI Order")) ?>,width:240,height:240,correctLevel:QRCode.CorrectLevel.M});</script>
        <script>
        // Prevent a double-click from placing the order twice.
        (function () {
            var f = document.querySelector('button[name="place_order"]').form;
            f.addEventListener('submit', function (ev) {
                if (f.dataset.sent) { ev.preventDefault(); return; }
                f.dataset.sent = '1';
            });
        })();
        </script>

        <div>
            <div class="order-summary">
                <h3>Order Summary</h3>
                <div class="divider"></div>

                <?php foreach ($items as [$p, $qty, $sub]): ?>
                    <div class="order-item">
                        <span><?= e($p['name']) ?> Ãƒâ€” <?= $qty ?></span>
                        <strong>Ã¢â€šÂ¹<?= number_format($sub, 2) ?></strong>
                    </div>
                <?php endforeach; ?>

                <div class="divider"></div>

                <form method="post" style="display:flex;gap:8px;margin:12px 0">
                    <?= csrf_field() ?>
                    <input name="coupon_code" placeholder="Coupon code" value="<?= e($couponCode) ?>">
                    <button name="apply_coupon" class="btn secondary" style="white-space:nowrap">Apply</button>
                </form>

                <?php if ($couponMsg): ?>
                    <p style="font-size:.8rem;color:var(--muted)">
                        <?= e($couponMsg) ?>
                    </p>
                <?php endif; ?>

                <div class="order-item">
                    <span>Subtotal</span>
                    <strong>Ã¢â€šÂ¹<?= number_format($subtotal, 2) ?></strong>
                </div>

                <div class="order-item">
                    <span>Discount</span>
                    <strong>- Ã¢â€šÂ¹<?= number_format($discount, 2) ?></strong>
                </div>

                <div class="order-item">
                    <span>Delivery</span>
                    <strong>
                        <?= $delivery == 0 ? 'FREE' : 'Ã¢â€šÂ¹' . number_format($delivery, 2) ?>
                    </strong>
                </div>

                <div class="order-total">
                    <span>Grand Total</span>
                    <span><?= number_format($total, 2) ?></span>
                </div>

                <p style="font-size:.78rem;color:var(--muted)">
                    Try coupons: MAYURI10 or WELCOME50
                </p>
            </div>

            <div class="trust-box">
                 Secure checkout<br>
                Estimated delivery: <?= estimate_delivery_text() ?><br>
                 7-day return support
            </div>
        </div>

    </div>
</div>

<?php include 'includes/footer.php'; ?>