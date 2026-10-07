<?php
require_once 'config/database.php';
require_login();

$id = (int) ($_GET['order_id'] ?? 0);
$uid = (int) $_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT *
    FROM orders
    WHERE id = ?
    AND (
        user_id = ?
        OR ? IN (
            SELECT id FROM users WHERE role = 'admin'
        )
    )
");

$stmt->bind_param('iii', $id, $uid, $uid);
$stmt->execute();

$o = $stmt->get_result()->fetch_assoc();

if (!$o) {
    die('Invoice not found.');
}

$items = $conn->query("SELECT * FROM order_items WHERE order_id = " . (int) $id);
?>

<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice #<?= $id ?></title>

    <style>
        body {
            font-family: Arial, sans-serif;
            color: #222;
            padding: 30px;
        }

        .box {
            max-width: 800px;
            margin: auto;
            border: 1px solid #ddd;
            padding: 28px;
        }

        h1 {
            letter-spacing: 4px;
        }

        .row {
            display: flex;
            justify-content: space-between;
            gap: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            border-bottom: 1px solid #eee;
            padding: 10px;
            text-align: left;
        }

        .total {
            text-align: right;
            font-size: 20px;
            font-weight: bold;
            margin-top: 20px;
        }

        button {
            padding: 10px 16px;
            border: none;
            background: #b8893b;
            color: white;
            border-radius: 6px;
            cursor: pointer;
        }

        @media print {
            button {
                display: none;
            }

            body {
                padding: 0;
            }

            .box {
                border: none;
            }
        }
    </style>
</head>

<body>

<div class="box">
    <button onclick="window.print()">Print / Save PDF</button>

    <h1>MAYURI</h1>

    <div class="row">
        <div>
            <strong>Invoice #<?= e((isset($o['order_number']) && $o['order_number'] !== '') ? $o['order_number'] : $id) ?></strong><br>
            Date: <?= date('d M Y', strtotime($o['created_at'])) ?><br>
            Status: <?= e($o['order_status']) ?>
        </div>

        <div>
            <strong>Bill To</strong><br>
            <?= e($o['customer_name']) ?><br>
            <?= e($o['phone']) ?><br>
            <?= e($o['address']) ?>
        </div>
    </div>

    <table>
        <tr>
            <th>Product</th>
            <th>Qty</th>
            <th>Price</th>
            <th>Subtotal</th>
        </tr>

        <?php while ($it = $items->fetch_assoc()): ?>
            <tr>
                <td><?= e($it['product_name']) ?></td>
                <td><?= e($it['quantity']) ?></td>
                <td>â‚¹<?= number_format($it['price'], 2) ?></td>
                <td>â‚¹<?= number_format($it['subtotal'], 2) ?></td>
            </tr>
        <?php endwhile; ?>
    </table>

    <p class="total">
        Total: â‚¹<?= number_format($o['total'], 2) ?>
    </p>
</div>

</body>
</html>