<?php
require_once '../config/database.php';
require_admin();

$products = $conn->query("SELECT COUNT(*) c FROM products")->fetch_assoc()['c'];
$orders   = $conn->query("SELECT COUNT(*) c FROM orders")->fetch_assoc()['c'];
$revenue  = $conn->query("SELECT COALESCE(SUM(total),0) t FROM orders")->fetch_assoc()['t'];
$pending  = $conn->query("SELECT COUNT(*) c FROM orders WHERE payment_status LIKE 'Pending%'")->fetch_assoc()['c'];
$waiting  = $conn->query("SELECT COUNT(*) c FROM product_waitlist WHERE status='waiting'")->fetch_assoc()['c'];
$recent   = $conn->query("SELECT * FROM orders ORDER BY id DESC LIMIT 5");
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Admin &mdash; MAYURI</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { background: #f7f2f0; }
        .admin-wrap { display: grid; grid-template-columns: 220px 1fr; min-height: 100vh; }
        .admin-sidebar {
            background: var(--dark); padding: 32px 0;
            position: sticky; top: 0; height: 100vh; overflow-y: auto;
        }
        .admin-sidebar .sidebar-brand {
            font-family: 'Cormorant Garamond', serif; font-size: 1.4rem;
            color: var(--gold); letter-spacing: 0.15em; padding: 0 24px 28px;
            border-bottom: 1px solid rgba(255,255,255,0.08); margin-bottom: 16px;
            display: block;
        }
        .admin-sidebar a {
            display: block; padding: 11px 24px;
            font-size: 0.82rem; font-weight: 500; letter-spacing: 0.1em;
            text-transform: uppercase; color: rgba(255,255,255,0.5);
            transition: all 0.25s; border-left: 3px solid transparent;
        }
        .admin-sidebar a:hover, .admin-sidebar a.active {
            color: var(--gold); background: rgba(255,255,255,0.04);
            border-left-color: var(--gold);
        }
        .admin-main { padding: 40px 36px; }
        .admin-header { margin-bottom: 32px; }
        .admin-header h1 { font-size: 2rem; margin-bottom: 4px; }
        .admin-header p { color: var(--muted); font-size: 0.9rem; }
        .recent-table { background: #fff; border: 1px solid var(--border); border-radius: var(--radius-lg); overflow: hidden; margin-top: 32px; }
        .recent-table h3 { padding: 20px 24px 14px; border-bottom: 1px solid var(--border); font-size: 1.1rem; }
    </style>
</head>
<body>
<div class="admin-wrap">
    <aside class="admin-sidebar">
        <a class="sidebar-brand" href="../index.php">MAYURI ÃƒÂ¢Ãƒâ€¦Ã¢â‚¬Å“Ã‚Â¦</a>
        <a href="index.php" class="active">Dashboard</a>
        <a href="products.php">Products</a>
        <a href="product_form.php">Add Product</a>
        <a href="orders.php">Orders</a>
        <a href="waitlist.php">Waiting List</a>
        <a href="../index.php" target="_blank">View Site</a>
        <a href="../logout.php">Logout</a>
    </aside>

    <main class="admin-main">
        <div class="admin-header">
            <h1>Dashboard</h1>
            <p>Welcome back! Here's an overview of your store.</p>
        </div>

        <div class="admin-grid">
            <div class="stat-card animate-fade-up">
                <div class="stat-label">Total Products</div>
                <div class="stat-value"><?=$products?></div>
            </div>
            <div class="stat-card animate-fade-up delay-1">
                <div class="stat-label">Total Orders</div>
                <div class="stat-value"><?=$orders?></div>
            </div>
            <div class="stat-card animate-fade-up delay-2">
                <div class="stat-label">Total Revenue</div>
                <div class="stat-value" style="font-size:1.6rem">&#8377;<?=number_format($revenue,0)?></div>
            </div>
            <div class="stat-card animate-fade-up delay-3">
                <div class="stat-label">Pending Orders</div>
                <div class="stat-value" style="color:<?=$pending>0?'var(--danger)':'var(--ok)'?>"><?=$pending?></div>
            </div>
            <div class="stat-card animate-fade-up delay-3">
                <div class="stat-label">Waiting Customers</div>
                <div class="stat-value" style="color:<?=$waiting>0?'var(--danger)':'var(--ok)'?>"><?=$waiting?></div>
            </div>
        </div>

        <div class="admin-nav" style="margin-top:28px">
            <a class="btn gold" href="product_form.php">+ Add New Product</a>
            <a class="btn secondary" href="orders.php">View Orders</a>
            <a class="btn light" href="products.php">Manage Products</a>
            <a class="btn secondary" href="waitlist.php">Waiting List</a>
        </div>

        <div class="recent-table">
            <h3>Recent Orders</h3>
            <table class="table">
                <thead>
                    <tr><th>#</th><th>Customer</th><th>Total</th><th>Payment</th><th>Status</th></tr>
                </thead>
                <tbody>
                    <?php while($o = $recent->fetch_assoc()): ?>
                    <tr>
                        <td>#<?=$o['id']?></td>
                        <td><?=e($o['customer_name'])?></td>
                        <td>&#8377;<?=number_format($o['total'],2)?></td>
                        <td><?=e($o['payment_method'])?></td>
                        <td><span class="badge <?=$o['payment_status']=='Paid'?'ok':'out'?>"><?=e($o['payment_status'])?></span></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>
</body>
</html>
