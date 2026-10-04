<?php
require_once '../config/database.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $id = (int)($_POST['id'] ?? 0);

    if (isset($_POST['mark_notified']) && $id) {
        $stmt = $conn->prepare("
            UPDATE product_waitlist 
            SET status='notified', updated_at=CURRENT_TIMESTAMP 
            WHERE id=?
        ");
        $stmt->bind_param('i', $id);
        $stmt->execute();
    }

    if (isset($_POST['delete']) && $id) {
        $stmt = $conn->prepare("
            DELETE FROM product_waitlist 
            WHERE id=?
        ");
        $stmt->bind_param('i', $id);
        $stmt->execute();
    }

    redirect('waitlist.php');
}

$res = $conn->query("
    SELECT 
        w.*,
        p.name AS product_name,
        p.stock,
        p.status AS product_status
    FROM product_waitlist w
    JOIN products p ON p.id = w.product_id
    ORDER BY w.updated_at DESC, w.id DESC
");

$summary = $conn->query("
    SELECT
        p.id,
        p.name,
        COUNT(w.id) AS total
    FROM products p
    JOIN product_waitlist w
        ON w.product_id = p.id
        AND w.status = 'waiting'
    GROUP BY p.id, p.name
    ORDER BY total DESC
");
?>

<!doctype html>
<html lang="en">

<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Waiting Customers - MAYURI Admin</title>

<link rel="stylesheet" href="../assets/css/style.css">

<style>
body{
    background:#f7f2f0;
}

.admin-wrap{
    display:grid;
    grid-template-columns:220px 1fr;
    min-height:100vh;
}

.admin-sidebar{
    background:var(--dark);
    padding:32px 0;
    position:sticky;
    top:0;
    height:100vh;
    overflow-y:auto;
}

.admin-sidebar .sidebar-brand{
    font-family:'Cormorant Garamond',serif;
    font-size:1.4rem;
    color:var(--gold);
    letter-spacing:.15em;
    padding:0 24px 28px;
    border-bottom:1px solid rgba(255,255,255,.08);
    margin-bottom:16px;
    display:block;
}

.admin-sidebar a{
    display:block;
    padding:11px 24px;
    font-size:.82rem;
    font-weight:500;
    letter-spacing:.1em;
    text-transform:uppercase;
    color:rgba(255,255,255,.5);
    transition:.25s;
    border-left:3px solid transparent;
}

.admin-sidebar a:hover,
.admin-sidebar a.active{
    color:var(--gold);
    background:rgba(255,255,255,.04);
    border-left-color:var(--gold);
}

.admin-main{
    padding:40px 36px;
}

.summary-grid{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
    gap:16px;
    margin:24px 0;
}

.summary-card{
    background:#fff;
    border:1px solid var(--border);
    border-radius:14px;
    padding:18px;
}

.table-wrap{
    background:#fff;
    border:1px solid var(--border);
    border-radius:14px;
    overflow:hidden;
}

.badge{
    padding:6px 10px;
    border-radius:20px;
    font-size:12px;
    font-weight:600;
}

.badge.out{
    background:#fff2f2;
    color:#d9534f;
}

.badge.ok{
    background:#ecfff0;
    color:#198754;
}
</style>

</head>

<body>

<div class="admin-wrap">

    <aside class="admin-sidebar">

        <a class="sidebar-brand" href="../index.php">
            MAYURI
        </a>

        <a href="index.php">Dashboard</a>
        <a href="products.php">Products</a>
        <a href="product_form.php">Add Product</a>
        <a href="orders.php">Orders</a>
        <a href="waitlist.php" class="active">Waiting List</a>
        <a href="../index.php" target="_blank">View Site</a>
        <a href="../logout.php">Logout</a>

    </aside>

    <main class="admin-main">

        <h1>Waiting Customers</h1>

        <p style="color:var(--muted)">
            Out-of-stock products साठी किती customers wait करत आहेत ते येथे दिसेल.
        </p>

        <div class="summary-grid">

            <?php while($s = $summary->fetch_assoc()): ?>

            <div class="summary-card">

                <strong><?= e($s['name']) ?></strong>

                <div style="font-size:2rem;color:var(--gold);margin-top:8px">
                    <?= $s['total'] ?>
                </div>

                <span style="color:var(--muted);font-size:.85rem">
                    Waiting Customer(s)
                </span>

            </div>

            <?php endwhile; ?>

        </div>

        <div class="table-wrap">

            <table class="table">

                <thead>
                <tr>
                    <th>Product</th>
                    <th>Customer</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
                </thead>

                <tbody>

                <?php while($w = $res->fetch_assoc()): ?>

                <tr>

                    <td>
                        <strong><?= e($w['product_name']) ?></strong>
                        <br>
                        <small>
                            Stock: <?= e($w['stock']) ?>
                            /
                            <?= e($w['product_status']) ?>
                        </small>
                    </td>

                    <td><?= e($w['customer_name']) ?></td>

                    <td><?= e($w['email']) ?></td>

                    <td><?= e($w['phone']) ?></td>

                    <td>
                        <span class="badge <?= $w['status'] === 'waiting' ? 'out' : 'ok' ?>">
                            <?= e($w['status']) ?>
                        </span>
                    </td>

                    <td>
                        <?= date('d M Y', strtotime($w['created_at'])) ?>
                    </td>

                    <td>

                        <div style="display:flex;gap:8px;flex-wrap:wrap;">

                            <form method="post">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= $w['id'] ?>">

                                <button
                                    type="submit"
                                    name="mark_notified"
                                    class="btn light"
                                    style="padding:7px 12px;">
                                    Mark Notified
                                </button>
                            </form>

                            <form
                                method="post"
                                onsubmit="return confirm('Remove this entry?')">

                                <?= csrf_field() ?>

                                <input type="hidden" name="id" value="<?= $w['id'] ?>">

                                <button
                                    type="submit"
                                    name="delete"
                                    class="btn danger"
                                    style="padding:7px 12px;">
                                    Delete
                                </button>

                            </form>

                        </div>

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