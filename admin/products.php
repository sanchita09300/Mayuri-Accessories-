<?php
require_once '../config/database.php';
require_admin();

if($_SERVER['REQUEST_METHOD']==='POST'){
    verify_csrf();
    $id = (int)($_POST['product_id'] ?? 0);
    if(isset($_POST['delete']) && $id){
        $stmt = $conn->prepare("DELETE FROM products WHERE id=?");
        $stmt->bind_param('i',$id); $stmt->execute();
    }
    if(isset($_POST['toggle']) && $id){
        $stmt = $conn->prepare("SELECT status FROM products WHERE id=?");
        $stmt->bind_param('i',$id); $stmt->execute();
        $p = $stmt->get_result()->fetch_assoc();
        $new = ($p && $p['status']=='in_stock') ? 'out_of_stock' : 'in_stock';
        $u = $conn->prepare("UPDATE products SET status=? WHERE id=?");
        $u->bind_param('si',$new,$id); $u->execute();
    }
    redirect('products.php');
}
$section_filter = $_GET['section'] ?? '';
if(!in_array($section_filter, ['main','boys'], true)) $section_filter = '';
$where = $section_filter ? " WHERE p.section='". $conn->real_escape_string($section_filter) . "'" : '';
$res = $conn->query("SELECT p.*, COUNT(w.id) AS waiting_count FROM products p LEFT JOIN product_waitlist w ON w.product_id=p.id AND w.status='waiting'".$where." GROUP BY p.id ORDER BY p.id DESC");
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Products &mdash; MAYURI Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600&family=Jost:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { background: #f7f2f0; }
        .admin-wrap { display: grid; grid-template-columns: 220px 1fr; min-height: 100vh; }
        .admin-sidebar { background: var(--dark); padding: 32px 0; position: sticky; top: 0; height: 100vh; overflow-y: auto; }
        .admin-sidebar .sidebar-brand { font-family: 'Cormorant Garamond', serif; font-size: 1.4rem; color: var(--gold); letter-spacing: 0.15em; padding: 0 24px 28px; border-bottom: 1px solid rgba(255,255,255,0.08); margin-bottom: 16px; display: block; }
        .admin-sidebar a { display: block; padding: 11px 24px; font-size: 0.82rem; font-weight: 500; letter-spacing: 0.1em; text-transform: uppercase; color: rgba(255,255,255,0.5); transition: all 0.25s; border-left: 3px solid transparent; }
        .admin-sidebar a:hover, .admin-sidebar a.active { color: var(--gold); background: rgba(255,255,255,0.04); border-left-color: var(--gold); }
        .admin-main { padding: 40px 36px; }
    </style>
</head>
<body>
<div class="admin-wrap">
    <aside class="admin-sidebar">
        <a class="sidebar-brand" href="../index.php">MAYURI ÃƒÂ¢Ãƒâ€¦Ã¢â‚¬Å“Ã‚Â¦</a>
        <a href="index.php">Dashboard</a>
        <a href="products.php" class="active">Products</a>
        <a href="product_form.php">Add Product</a>
        <a href="orders.php">Orders</a>
        <a href="waitlist.php">Waiting List</a>
        <a href="../index.php" target="_blank">View Site</a>
        <a href="../logout.php">Logout</a>
    </aside>
    <main class="admin-main">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:28px">
            <h1>Manage Products</h1>
            <a class="btn gold" href="product_form.php">+ Add Product</a>
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:18px">
            <a class="btn <?= $section_filter==='' ? 'gold' : 'secondary' ?>" href="products.php">All Products</a>
            <a class="btn <?= $section_filter==='main' ? 'gold' : 'secondary' ?>" href="products.php?section=main">Main Collection</a>
            <a class="btn <?= $section_filter==='boys' ? 'gold' : 'secondary' ?>" href="products.php?section=boys">Boys Accessories</a>
        </div>
        <div class="table-wrap" style="background:#fff;border:1px solid var(--border);border-radius:var(--radius-lg);overflow:hidden">
        <table class="table">
            <thead>
                <tr><th>Image</th><th>Name</th><th>Section</th><th>Category</th><th>Price</th><th>Stock</th><th>Waiting</th><th>Status</th><th>Actions</th></tr>
            </thead>
            <tbody>
            <?php while($p = $res->fetch_assoc()): ?>
            <tr>
                <td>
                    <?php if(!empty($p['image']) && ($p['media_type']??'image')==='video'): ?>
                        <video class="small-img" src="../uploads/products/<?=e($p['image'])?>" muted></video>
                    <?php else: ?>
                        <img class="small-img" src="<?= $p['image'] ? '../uploads/products/'.e($p['image']) : 'https://images.unsplash.com/photo-1515562141207-7a88fb7ce338?q=80&w=200' ?>" alt="">
                    <?php endif; ?>
                </td>
                <td><strong><?=e($p['name'])?></strong></td>
                <td><span class="badge <?=($p['section']??'main')==='boys'?'ok':'light'?>"><?=($p['section']??'main')==='boys'?'Boys':'Main'?></span></td>
                <td><span style="font-size:0.75rem;color:var(--gold);letter-spacing:0.1em;text-transform:uppercase"><?=e($p['category'])?></span></td>
                <td>&#8377;<?=number_format($p['price'],2)?></td>
                <td><?=e($p['stock'])?></td>
                <td><a href="waitlist.php" class="badge <?=((int)$p['waiting_count']>0)?'out':'ok'?>"><?=(int)$p['waiting_count']?></a></td>
                <td><span class="badge <?=$p['status']=='out_of_stock'?'out':'ok'?>"><?=$p['status']=='out_of_stock'?'Out of Stock':'In Stock'?></span></td>
                <td>
                    <div style="display:flex;gap:8px">
                        <a class="btn light" style="padding:7px 14px" href="product_form.php?id=<?=$p['id']?>">Edit</a>
                        <form method="post" style="display:inline"><?=csrf_field()?><input type="hidden" name="product_id" value="<?=$p['id']?>"><button name="toggle" class="btn secondary" style="padding:7px 14px">Toggle</button></form>
                        <form method="post" style="display:inline" onsubmit="return confirm('Delete this product?')"><?=csrf_field()?><input type="hidden" name="product_id" value="<?=$p['id']?>"><button name="delete" class="btn danger" style="padding:7px 14px">Delete</button></form>
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
