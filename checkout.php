<?php
include 'includes/header.php';

if (empty($_SESSION['cart'])) {
    redirect('shopping_cart.php');
}

$cart = $_SESSION['cart'];
$ids = implode(',', array_map('intval', array_keys($cart)));

$res = $conn->query("SELECT * FROM products WHERE id IN ($ids)");

$items = [];
$subtotal = 0;
$error = '';
$couponMsg = '';
$couponCode = strtoupper(trim($_POST['coupon_code'] ?? $_SESSION['coupon_code'] ?? ''));

while ($p = $res->fetch_assoc()) {
    $qty = $cart[$p['id']];

    if ($p['status'] == 'out_of_stock' || $p['stock'] < $qty) {
        $error = '"' . $p['name'] . '" is no longer available in the requested quantity.';
    }

    $sub = $qty * $p['price'];
    $subtotal += $sub;
    $items[] = [$p, $qty, $sub];
}

[$discount, $couponMsg] = apply_coupon_amount($conn, $couponCode, $subtotal);

$delivery = delivery_charge($subtotal - $discount);
$total = max(0, $subtotal - $discount + $delivery);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_coupon'])) {
    verify_csrf();
    $_SESSION['coupon_code'] = $couponCode;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order']) && !$error) {
    verify_csrf();

    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = preg_replace('/\D/', '', $_POST['phone']);
    $addr1 = trim($_POST['address_line1']);
    $addr2 = trim($_POST['address_line2']);
    $city = trim($_POST['city']);
    $state = trim($_POST['state']);
    $pincode = trim($_POST['pincode']);
    $payChoice = $_POST['payment_method'] ?? '';

    $full_address = $addr1 . ($addr2 ? ', ' . $addr2 : '') . ', ' . $city . ', ' . $state . ' - ' . $pincode;

    // Only the manual UPI "Scan & Pay" flow is accepted. Anything else (including COD) is rejected.
    if (!$name || !$phone || !$addr1 || !$city || !$state || !$pincode || $payChoice !== 'SCAN_PAY') {
        $error = 'Please fill in all required details.';
    } elseif (!preg_match('/^[6-9]\d{9}$/', $phone)) {
        $error = 'Please enter a valid 10-digit Indian mobile number.';
    } elseif (!preg_match('/^\d{6}$/', $pincode)) {
        $error = 'Please enter a valid 6-digit PIN code.';
    } else {
        // Set on the server so the stored values can never be tampered with from the form.
        // The transaction is not auto-verified, so it is NOT marked "Paid".
        $pay = 'UPI / Scan & Pay';
        $payStatus = 'Pending Verification';
        $uid = $_SESSION['user_id'] ?? null;
        $orderStatus = 'Placed';

        $stmt = $conn->prepare("INSERT INTO orders(
            user_id,
            customer_name,
            email,
            phone,
            address_line1,
            address_line2,
            city,
            state,
            pincode,
            address,
            payment_method,
            payment_status,
            order_status,
            total,
            delivery_charge,
            coupon_code,
            discount
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $stmt->bind_param(
            'issssssssssssddsd',
            $uid,
            $name,
            $email,
            $phone,
            $addr1,
            $addr2,
            $city,
            $state,
            $pincode,
            $full_address,
            $pay,
            $payStatus,
            $orderStatus,
            $total,
            $delivery,
            $couponCode,
            $discount
        );

        $stmt->execute();
        $oid = $conn->insert_id;

        foreach ($items as [$p, $qty, $sub]) {
            $stmt2 = $conn->prepare("INSERT INTO order_items(
                order_id,
                product_id,
                product_name,
                price,
                quantity,
                subtotal
            ) VALUES (?, ?, ?, ?, ?, ?)");

            $stmt2->bind_param(
                'iisdid',
                $oid,
                $p['id'],
                $p['name'],
                $p['price'],
                $qty,
                $sub
            );

            $stmt2->execute();

            $newStock = max(0, $p['stock'] - $qty);
            $newStatus = $newStock <= 0 ? 'out_of_stock' : 'in_stock';

            $u = $conn->prepare("UPDATE products SET stock = ?, status = ? WHERE id = ?");
            $u->bind_param('isi', $newStock, $newStatus, $p['id']);
            $u->execute();
        }

        unset($_SESSION['cart'], $_SESSION['coupon_code']);

        redirect('success_page.php?order_id=' . $oid);
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
                    ðŸ“ Delivery Address
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

            <div class="scanpay-card">
                <h3 class="scanpay-title">Scan &amp; Pay</h3>

                <div class="scanpay-qr">
                    <img src="assets/images/upi-qr.png" alt="UPI QR code - scan with any UPI app to pay" width="300" height="300">
                </div>

                <p class="scanpay-amount">Amount to pay: <strong>â‚¹<?= number_format($total, 2) ?></strong></p>
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
                I Have Paid â€” Place Order
            </button>
        </form>

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
                        <span><?= e($p['name']) ?> Ã— <?= $qty ?></span>
                        <strong>â‚¹<?= number_format($sub, 2) ?></strong>
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
                    <strong>â‚¹<?= number_format($subtotal, 2) ?></strong>
                </div>

                <div class="order-item">
                    <span>Discount</span>
                    <strong>- â‚¹<?= number_format($discount, 2) ?></strong>
                </div>

                <div class="order-item">
                    <span>Delivery</span>
                    <strong>
                        <?= $delivery == 0 ? 'FREE' : 'â‚¹' . number_format($delivery, 2) ?>
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