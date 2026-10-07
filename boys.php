<?php include 'includes/header.php';
$cat    = $_GET['category'] ?? '';
$search = $_GET['search']   ?? '';
$sort   = $_GET['sort']     ?? 'newest';

$sql   = "SELECT * FROM products WHERE section='boys'";
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

// Standard boys categories, merged with any extra categories used by boys products
$cat_list = ['Bracelet','Chain','Ring','Watch','Pendant','Wallet','Sunglasses','Other'];
$extra = $conn->query("SELECT DISTINCT category FROM products WHERE section='boys' ORDER BY category");
while($x = $extra->fetch_assoc()){
    $found = false;
    foreach($cat_list as $c0){ if(strcasecmp($c0, $x['category']) === 0){ $found = true; break; } }
    if(!$found) $cat_list[] = $x['category'];
}

// Get user wishlist IDs
$wishlist_ids = [];
if(is_logged_in()){
    $uid = (int)$_SESSION['user_id'];
    $wr  = $conn->query("SELECT product_id FROM wishlist WHERE user_id=$uid");
    while($row = $wr->fetch_assoc()) $wishlist_ids[] = $row['product_id'];
}
?>

<section class="boys-hero">
    <div class="boys-hero-grid"></div>
    <div class="boys-hero-glow boys-hero-glow-1"></div>
    <div class="boys-hero-glow boys-hero-glow-2"></div>
    <div class="boys-hero-content">
        <div class="boys-kicker"><span></span> MAYURI / MEN'S EDIT <span></span></div>
        <div class="breadcrumb boys-breadcrumb"><a href="index.php">Home</a> <span>/</span> Boys</div>
        <h1>Built for <em>Him.</em></h1>
        <p>Bold chains, refined bracelets, statement rings, watches and everyday essentials.</p>
        <div class="boys-hero-line"></div>
        <div class="boys-hero-stats">
            <div><b data-count="<?= (int)$count ?>">0</b><small>Styles</small></div>
            <div><b data-count="<?= max(count($cat_list)-1,1) ?>">0</b><small>Categories</small></div>
            <div><b>UPI</b><small>Scan &amp; Pay</small></div>
        </div>
        <a href="#shop" class="boys-cta">Shop Now <span>&darr;</span></a>
    </div>
    <div class="boys-scroll">SCROLL <span></span></div>
</section>
<?php if(isset($_GET['msg'])): ?>
<div class="container" style="padding-top:22px">
    <?php if($_GET['msg']==='waitlisted'): ?><div class="alert success">You are added to the waiting list. Admin can see your request.</div><?php endif; ?>
    <?php if($_GET['msg']==='out_of_stock'): ?><div class="alert error">This product is currently out of stock. Please join waiting list.</div><?php endif; ?>
    <?php if($_GET['msg']==='available'): ?><div class="alert success">Product is available now. You can add it to cart.</div><?php endif; ?>
</div>
<?php endif; ?>

<div class="boys-marquee" aria-hidden="true"><div class="boys-marquee-track">
<?php for($k=0;$k<2;$k++): foreach(['CHAINS','BRACELETS','RINGS','WATCHES','PENDANTS','WALLETS','SUNGLASSES','CAP'] as $w): ?><span><?=$w?></span><i>&#9670;</i><?php endforeach; endfor; ?>
</div></div>
<main class="boys-page">
<div class="container section" id="shop">
    <div class="boys-section-heading reveal">
        <div>
            <span class="boys-eyebrow">THE MEN'S EDIT</span>
            <h2>Find Your Signature</h2>
        </div>
        <p>Minimal. Masculine. Made to stand out.</p>
    </div>

    <div class="boys-chips reveal">
        <a href="boys.php" class="<?= $cat==='' ? 'active' : '' ?>">All</a>
        <?php foreach($cat_list as $c): ?><a href="boys.php?category=<?=urlencode($c)?>" class="<?= strcasecmp($cat,$c)===0 ? 'active' : '' ?>"><?=e($c)?></a><?php endforeach; ?>
    </div>

    <form class="filter-bar boys-filter" method="get">
        <div style="flex:1;min-width:200px">
            <label>Search</label>
            <input name="search" placeholder="Search boys accessories&hellip;" value="<?=e($search)?>">
        </div>
        <div>
            <label>Category</label>
            <select name="category">
                <option value="">All Categories</option>
                <?php foreach($cat_list as $c): ?>
                <option value="<?=e($c)?>" <?= strcasecmp($cat,$c)===0 ? 'selected' : '' ?>><?=e($c)?></option>
                <?php endforeach; ?>
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
            <a class="btn secondary" href="boys.php">Clear</a>
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
        <p style="font-size:1.2rem;font-family:'Cormorant Garamond',serif;color:var(--dark)">No products found</p>
        <a class="btn" href="boys.php" style="margin-top:20px;display:inline-flex">Browse All Boys Accessories</a>
    </div>
    <?php else: ?>
    <div class="grid">
        <?php $i=0; while($p = $products->fetch_assoc()): $i++;
            $inWishlist = in_array($p['id'], $wishlist_ids);
            $isOut      = ($p['status']==='out_of_stock' || $p['stock']<=0);
            $mrp        = (float)($p['mrp'] ?? 0);
            $discount   = ($mrp > (float)$p['price']) ? (int)round(($mrp - $p['price']) / $mrp * 100) : 0;
        ?>
        <div class="card reveal boys-card" style="transition-delay:<?=min($i*0.07,0.56)?>s;cursor:pointer" onclick="window.location.href='product_detail.php?id=<?=$p['id']?>'">
            <?php if($discount > 0): ?><div class="card-badge"><?=$discount?>% OFF</div><?php endif; ?>
            <div class="card-img-wrap" style="position:relative"><span class="boys-shine"></span>
                <?php if(($p['media_type']??'image')==='video' && $p['image']): ?>
                <video src="uploads/products/<?=e($p['image'])?>" style="width:100%;height:100%;object-fit:cover" muted autoplay loop playsinline></video>
                <?php else: ?>
                <img class="product-img"
                    src="<?= $p['image'] ? 'uploads/products/'.e($p['image']) : 'https://images.unsplash.com/photo-1611652022419-a9419f74343d?q=80&w=800' ?>"
                    alt="<?=e($p['name'])?>" loading="lazy">
                <?php endif; ?>
                <button onclick="event.stopPropagation(); toggleWishlist(this,<?=$p['id']?>)"
                    class="boys-wishlist" style="position:absolute;top:12px;right:12px"
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
                <p class="price">&#8377;<?=number_format($p['price'],2)?>
                    <?php if($discount > 0): ?><span style="font-family:'Jost',sans-serif;font-size:0.85rem;font-weight:400;color:var(--muted);text-decoration:line-through;margin-left:6px">&#8377;<?=number_format($mrp,2)?></span><?php endif; ?>
                </p>
                <div class="card-footer" onclick="event.stopPropagation()">
                    <span class="badge <?=$isOut?'out':'ok'?>"><?=$isOut?'Out of Stock':'In Stock'?></span>
                    <?php if($isOut): ?>
                    <form method="post" action="waitlist.php" style="display:inline"><?=csrf_field()?><input type="hidden" name="product_id" value="<?=$p['id']?>"><input type="hidden" name="return_to" value="boys.php"><button class="btn light" style="padding:8px 14px">Waiting</button></form>
                    <?php else: ?>
                    <form method="post" action="add_to_cart.php" style="display:inline"><?=csrf_field()?><input type="hidden" name="product_id" value="<?=$p['id']?>"><input type="hidden" name="qty" value="1"><input type="hidden" name="return_to" value="boys.php"><button class="btn gold" style="padding:8px 14px">Add to Cart</button></form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
    <?php endif; ?>
</div>
<section class="boys-perks container reveal">
    <div><span></span><b>Fast Delivery</b><small>Across India</small></div>
    <div><span></span><b>Secure Checkout</b><small>Safe payments</small></div>
    <div><span></span><b>Easy Support</b><small>We are here to help</small></div>
    <div><span></span><b>Quality Checked</b><small>Every piece</small></div>
</section>
</main>
<?php include 'includes/footer.php'; ?>
