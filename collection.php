<?php include 'includes/header.php';
$cat    = $_GET['category'] ?? '';
$search = $_GET['search']   ?? '';
$sort   = $_GET['sort']     ?? 'newest';

$sql   = "SELECT * FROM products WHERE section<>'boys'";
$types = ''; $vals = [];
if($cat)   { $sql .= " AND category=?"; $types .= 's'; $vals[] = $cat; }
if($search){ $sql .= " AND (name LIKE ? OR description LIKE ?)"; $types .= 'ss'; $vals[] = "%$search%"; $vals[] = "%$search%"; }

switch($sort){
    case 'price_asc':  $sql .= " ORDER BY price ASC"; break;
    case 'price_desc': $sql .= " ORDER BY price DESC"; break;
    case 'name':       $sql .= " ORDER BY name ASC"; break;
    default:           $sql .= " ORDER BY id DESC";
}

$stmt = $conn->prepare($sql);
if($vals) $stmt->bind_param($types, ...$vals);
$stmt->execute();
$products = $stmt->get_result();
$count    = $products->num_rows;

$cats = $conn->query("SELECT DISTINCT category FROM products WHERE section<>'boys' ORDER BY category");

// Get user wishlist IDs
$wishlist_ids = [];
if(is_logged_in()){
    $uid = (int)$_SESSION['user_id'];
    $wr  = $conn->query("SELECT product_id FROM wishlist WHERE user_id=$uid");
    while($row = $wr->fetch_assoc()) $wishlist_ids[] = $row['product_id'];
}
?>

<div class="page-banner">
    <div class="container">
        <div class="breadcrumb"><a href="index.php">Home</a> &rsaquo; Collection</div>
        <h1>Our Collection</h1>
        <p>Discover pieces that speak to your style</p>
    </div>
</div>
<?php if(isset($_GET['msg'])): ?>
<div class="container" style="padding-top:22px">
    <?php if($_GET['msg']==='waitlisted'): ?><div class="alert success">You are added to the waiting list. Admin can see your request.</div><?php endif; ?>
    <?php if($_GET['msg']==='out_of_stock'): ?><div class="alert error">This product is currently out of stock. Please join waiting list.</div><?php endif; ?>
    <?php if($_GET['msg']==='available'): ?><div class="alert success">Product is available now. You can add it to cart.</div><?php endif; ?>
</div>
<?php endif; ?>

<div class="container section">
    <form class="filter-bar" method="get">
        <div style="flex:1;min-width:200px">
            <label>Search</label>
            <input name="search" placeholder="Search accessories..." value="<?=e($search)?>">
        </div>
        <div>
            <label>Category</label>
            <select name="category">
                <option value="">All Categories</option>
                <?php while($c = $cats->fetch_assoc()): ?>
                <option value="<?=e($c['category'])?>" <?= $cat===$c['category']?'selected':'' ?>><?=e($c['category'])?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div>
            <label>Sort By</label>
            <select name="sort">
                <option value="newest"     <?=$sort==='newest'    ?'selected':''?>>Newest First</option>
                <option value="price_asc"  <?=$sort==='price_asc' ?'selected':''?>>Price: Low &rarr; High</option>
                <option value="price_desc" <?=$sort==='price_desc'?'selected':''?>>Price: High &rarr; Low</option>
                <option value="name"       <?=$sort==='name'      ?'selected':''?>>Name A&ndash;Z</option>
            </select>
        </div>
        <div style="display:flex;gap:10px;align-items:flex-end">
            <button type="submit" class="btn">Filter</button>
            <?php if($cat || $search): ?>
            <a class="btn secondary" href="collection.php">Clear</a>
            <?php endif; ?>
        </div>
    </form>

    <p class="collection-meta">
        Showing <strong><?= $count ?></strong> product<?= $count !== 1 ? 's' : '' ?>
        <?= $cat    ? ' &middot; Category: <strong>'.e($cat).'</strong>' : '' ?>
        <?= $search ? ' &middot; Search: <strong>'.e($search).'</strong>' : '' ?>
    </p>

    <?php if($count === 0): ?>
    <div class="alert" style="text-align:center;padding:52px 24px">
        <div style="font-size:2.5rem;margin-bottom:16px">&#128270;</div>
        <p style="font-size:1.2rem;font-family:'Cormorant Garamond',serif;color:var(--dark)">No products found</p>
        <a class="btn" href="collection.php" style="margin-top:20px;display:inline-flex">Browse All</a>
    </div>
    <?php else: ?>
    <div class="grid">
        <?php $i=0; while($p = $products->fetch_assoc()): $i++;
            $inWishlist = in_array($p['id'], $wishlist_ids);
        ?>
        <div class="card reveal" style="animation-delay:<?=min($i*0.06,0.5)?>s;cursor:pointer" onclick="window.location.href='product_detail.php?id=<?=$p['id']?>'">
            <div class="card-img-wrap" style="position:relative">
                <?php if(($p['media_type']??'image')==='video' && $p['image']): ?>
                <video src="uploads/products/<?=e($p['image'])?>" style="width:100%;height:100%;object-fit:cover" muted autoplay loop playsinline></video>
                <?php else: ?>
                <img class="product-img"
                    src="<?= $p['image'] ? 'uploads/products/'.e($p['image']) : 'https://images.unsplash.com/photo-1515562141207-7a88fb7ce338?q=80&w=800' ?>"
                    alt="<?=e($p['name'])?>" loading="lazy">
                <?php endif; ?>
                <!-- Wishlist button -->
                <button onclick="event.stopPropagation(); toggleWishlist(this,<?=$p['id']?>)"
                    style="position:absolute;top:10px;right:10px;background:rgba(255,255,255,0.92);border:none;border-radius:50%;width:34px;height:34px;cursor:pointer;font-size:1rem;box-shadow:0 2px 8px rgba(0,0,0,0.12);transition:transform 0.2s"
                    onmouseover="this.style.transform='scale(1.15)'" onmouseout="this.style.transform='scale(1)'"
                    title="<?=$inWishlist?'Remove from':'Add to'?> wishlist">
                    <?=$inWishlist?'&#10084;&#65039;':'&#9825;'?>
                </button>
            </div>
            <div class="card-body">
                <div class="card-category"><?=e($p['category'])?></div>
                <h3><?=e($p['name'])?></h3>
                <?php if($p['description']): ?>
                <p style="font-size:0.84rem;color:var(--muted);margin-top:5px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden"><?=e($p['description'])?></p>
                <?php endif; ?>
                <p class="price">&#8377;<?=number_format($p['price'],2)?></p>
                <div class="card-footer" onclick="event.stopPropagation()">
                    <span class="badge <?=($p['status']==='out_of_stock'||$p['stock']<=0)?'out':'ok'?>">
                        <?=($p['status']==='out_of_stock'||$p['stock']<=0)?'Out of Stock':'In Stock'?>
                    </span>
                    <?php if($p['status']==='out_of_stock'||$p['stock']<=0): ?><form method="post" action="waitlist.php" style="display:inline"><?=csrf_field()?><input type="hidden" name="product_id" value="<?=$p['id']?>"><button class="btn light" style="padding:8px 14px">Notify Me</button></form><?php else: ?><form method="post" action="add_to_cart.php" style="display:inline"><?=csrf_field()?><input type="hidden" name="product_id" value="<?=$p['id']?>"><input type="hidden" name="qty" value="1"><button class="btn gold" style="padding:8px 14px">Add to Cart</button></form><?php endif; ?>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
    <?php endif; ?>
</div>
<?php include 'includes/footer.php'; ?>
