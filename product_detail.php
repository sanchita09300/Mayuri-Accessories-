<?php
include 'includes/header.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    redirect('collection.php');
}

$stmt = $conn->prepare("SELECT * FROM products WHERE id=? LIMIT 1");
$stmt->bind_param('i', $id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    redirect('collection.php?msg=not_found');
}

$gallery = [];

// Always show cover image first, then extra uploaded images/videos.
// This fixes the issue where only one image was visible/clickable.
if (!empty($product['image'])) {
    $gallery[] = [
        'file_name' => $product['image'],
        'media_type' => $product['media_type'] ?? 'image'
    ];
}

try {
    $stmt = $conn->prepare("SELECT file_name, media_type FROM product_images WHERE product_id=? ORDER BY sort_order ASC, id ASC");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $gr = $stmt->get_result();
    while ($g = $gr->fetch_assoc()) {
        // Avoid duplicate cover image in thumbnail gallery.
        $exists = false;
        foreach ($gallery as $oldMedia) {
            if ($oldMedia['file_name'] === $g['file_name']) {
                $exists = true;
                break;
            }
        }
        if (!$exists) {
            $gallery[] = $g;
        }
    }
} catch (Exception $ex) {
    // Keep cover image if gallery table is missing or empty.
}

$isOut = ($product['status'] === 'out_of_stock' || (int)$product['stock'] <= 0);
?>

<?php if(($product['section'] ?? 'main')==='boys'): ?><i class="boys-product" hidden></i><?php endif; ?>
<div class="page-banner">
    <div class="container">
        <div class="breadcrumb">
            <a href="index.php">Home</a> &rsaquo;
            <?php $backUrl = (($product['section'] ?? 'main') === 'boys') ? 'boys.php' : 'collection.php'; ?>
            <a href="<?= $backUrl ?>"><?= $backUrl === 'boys.php' ? 'Boys' : 'Collection' ?></a> &rsaquo;
            <?= e($product['name']) ?>
        </div>
        <h1><?= e($product['name']) ?></h1>
        <p>Only selected product details are shown here</p>
    </div>
</div>

<?php if(isset($_GET['msg'])): ?>
<div class="container" style="padding-top:22px">
    <?php if($_GET['msg']==='waitlisted'): ?>
        <div class="alert success">You are added to the waiting list. Admin can see your request.</div>
    <?php endif; ?>
    <?php if($_GET['msg']==='out_of_stock'): ?>
        <div class="alert error">This product is currently out of stock. Please join waiting list.</div>
    <?php endif; ?>
    <?php if($_GET['msg']==='available'): ?>
        <div class="alert success">Product is available now. You can add it to cart.</div>
    <?php endif; ?>
</div>
<?php endif; ?>

<div class="container section">
    <div class="product-detail-box">
        <div class="product-detail-media">
            <?php if(!empty($gallery)): ?>
                <?php $main = $gallery[0]; ?>
                <div class="main-product-media" id="mainProductMedia">
                    <?php if(($main['media_type'] ?? 'image') === 'video'): ?>
                        <video src="uploads/products/<?= e($main['file_name']) ?>" controls autoplay muted loop playsinline></video>
                    <?php else: ?>
                        <img src="uploads/products/<?= e($main['file_name']) ?>" alt="<?= e($product['name']) ?>">
                    <?php endif; ?>
                </div>

                <?php if(count($gallery) > 1): ?>
                <div class="product-thumbs">
                    <?php foreach($gallery as $i => $m): ?>
                        <button type="button" class="thumb-item <?= $i === 0 ? 'active' : '' ?>" data-src="uploads/products/<?= e($m['file_name']) ?>" data-type="<?= e($m['media_type'] ?? 'image') ?>" data-alt="<?= e($product['name']) ?>">
                            <?php if(($m['media_type'] ?? 'image') === 'video'): ?>
                                <video src="uploads/products/<?= e($m['file_name']) ?>" muted playsinline></video>
                            <?php else: ?>
                                <img src="uploads/products/<?= e($m['file_name']) ?>" alt="<?= e($product['name']) ?>">
                            <?php endif; ?>
                        </button>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="main-product-media">
                    <img src="https://images.unsplash.com/photo-1515562141207-7a88fb7ce338?q=80&w=900" alt="<?= e($product['name']) ?>">
                </div>
            <?php endif; ?>
        </div>

        <div class="product-detail-info">
            <div class="card-category"><?= e($product['category']) ?></div>
            <h2><?= e($product['name']) ?></h2>
            <p class="price">&#8377;<?= number_format((float)$product['price'], 2) ?></p>

            <span class="badge <?= $isOut ? 'out' : 'ok' ?>">
                <?= $isOut ? 'Out of Stock' : 'In Stock' ?>
            </span>

            <?php if(!empty($product['description'])): ?>
                <p class="product-desc"><?= nl2br(e($product['description'])) ?></p>
            <?php endif; ?>

            <p style="color:var(--muted);margin-top:14px">
                Stock Available: <strong><?= (int)$product['stock'] ?></strong>
            </p>

            <div style="display:flex;gap:12px;flex-wrap:wrap;margin-top:24px">
                <?php if($isOut): ?>
                    <form method="post" action="waitlist.php">
                        <?= csrf_field() ?>
                        <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
                        <input type="hidden" name="return_to" value="product_detail.php?id=<?= (int)$product['id'] ?>">
                        <button class="btn light">Waiting for Product</button>
                    </form>
                <?php else: ?>
                    <form method="post" action="add_to_cart.php">
                        <?= csrf_field() ?>
                        <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
                        <input type="hidden" name="return_to" value="product_detail.php?id=<?= (int)$product['id'] ?>">
                        <label style="display:block;margin-bottom:8px;color:var(--muted);font-size:.9rem">Quantity</label>
                        <input type="number" name="qty" value="1" min="1" max="<?= max(1, (int)$product['stock']) ?>" style="max-width:110px;margin-right:10px">
                        <button class="btn gold">Add to Cart</button>
                    </form>
                <?php endif; ?>
                <a href="<?= $backUrl ?>" class="btn secondary"><?= $backUrl === 'boys.php' ? 'Back to Boys' : 'Back to Collection' ?></a>
            </div>
        </div>
    </div>
</div>

<style>
.product-detail-box{
    display:grid;
    grid-template-columns:minmax(280px, 1fr) minmax(280px, 0.9fr);
    gap:42px;
    align-items:start;
}
.main-product-media{
    background:#fff;
    border:1px solid var(--border);
    border-radius:var(--radius-lg);
    overflow:hidden;
    box-shadow:var(--shadow-sm);
}
.main-product-media img,
.main-product-media video{
    width:100%;
    height:520px;
    object-fit:cover;
    display:block;
}
.product-thumbs{
    display:grid;
    grid-template-columns:repeat(auto-fill,minmax(86px,1fr));
    gap:10px;
    margin-top:12px;
}
.thumb-item{
    border:1px solid var(--border);
    border-radius:12px;
    overflow:hidden;
    background:#fff;
    padding:0;
    cursor:pointer;
    opacity:.78;
    transition:.2s;
}
.thumb-item:hover,
.thumb-item.active{
    opacity:1;
    border-color:var(--gold);
    box-shadow:0 0 0 2px rgba(196,160,100,.16);
}
.thumb-item img,
.thumb-item video{
    width:100%;
    height:86px;
    object-fit:cover;
    display:block;
}
.product-detail-info{
    background:#fff;
    border:1px solid var(--border);
    border-radius:var(--radius-lg);
    padding:34px;
    box-shadow:var(--shadow-sm);
}
.product-detail-info h2{
    font-size:2.1rem;
    margin:10px 0;
}
.product-desc{
    color:var(--muted);
    line-height:1.8;
    margin-top:22px;
}
.product-card-link{
    color:inherit;
    text-decoration:none;
    display:block;
}
@media(max-width:768px){
    .product-detail-box{grid-template-columns:1fr;gap:24px;}
    .main-product-media img,
    .main-product-media video{height:360px;}
    .product-detail-info{padding:24px;}
}
</style>


<script>
document.querySelectorAll('.thumb-item').forEach(function(btn){
    btn.addEventListener('click', function(){
        var main = document.getElementById('mainProductMedia');
        if(!main) return;

        var src  = this.getAttribute('data-src');
        var type = this.getAttribute('data-type');
        var alt  = this.getAttribute('data-alt') || 'Product image';

        if(type === 'video'){
            main.innerHTML = '<video src="' + src + '" controls autoplay muted loop playsinline></video>';
        } else {
            main.innerHTML = '<img src="' + src + '" alt="' + alt.replace(/"/g, '&quot;') + '">';
        }

        document.querySelectorAll('.thumb-item').forEach(function(x){ x.classList.remove('active'); });
        this.classList.add('active');
    });
});
</script>

<?php include 'includes/footer.php'; ?>
