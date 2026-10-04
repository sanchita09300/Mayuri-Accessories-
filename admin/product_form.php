<?php
require_once '../config/database.php';
require_admin();

$id  = (int)($_GET['id'] ?? 0);
$p   = null;
$err = '';

if($id){
    $stmt = $conn->prepare("SELECT * FROM products WHERE id=?");
    $stmt->bind_param('i',$id);
    $stmt->execute();
    $p = $stmt->get_result()->fetch_assoc();
}

// Allowed product media types
$allowed_images = ['jpg','jpeg','png','gif','webp','avif','bmp','svg','jfif'];
$allowed_videos = ['mp4','webm','ogg','mov','avi','mkv','m4v'];
$max_image_mb   = 10;
$max_video_mb   = 100;

function upload_product_media_files($files, $allowed_images, $allowed_videos, $max_image_mb, $max_video_mb, &$err){
    $uploaded = [];
    $dest_dir = '../uploads/products/';
    if(!is_dir($dest_dir)) mkdir($dest_dir, 0755, true);

    if(empty($files['name'])) return $uploaded;

    $names = is_array($files['name']) ? $files['name'] : [$files['name']];
    $tmp   = is_array($files['tmp_name']) ? $files['tmp_name'] : [$files['tmp_name']];
    $sizes = is_array($files['size']) ? $files['size'] : [$files['size']];
    $errs  = is_array($files['error']) ? $files['error'] : [$files['error']];

    for($i=0; $i<count($names); $i++){
        if(empty($names[$i])) continue;
        if($errs[$i] !== UPLOAD_ERR_OK){
            $err = 'Upload failed for: '.$names[$i];
            break;
        }

        $ext  = strtolower(pathinfo($names[$i], PATHINFO_EXTENSION));
        $size = (int)$sizes[$i];
        $type = '';

        if(in_array($ext, $allowed_images, true)){
            if($size > $max_image_mb * 1024 * 1024){
                $err = "Image too large: {$names[$i]}. Max {$max_image_mb}MB allowed.";
                break;
            }
            $type = 'image';
            $fname = uniqid('img_', true).'.'.$ext;
        } elseif(in_array($ext, $allowed_videos, true)){
            if($size > $max_video_mb * 1024 * 1024){
                $err = "Video too large: {$names[$i]}. Max {$max_video_mb}MB allowed.";
                break;
            }
            $type = 'video';
            $fname = uniqid('vid_', true).'.'.$ext;
        } else {
            $err = 'Unsupported file type ('.$ext.'). Allowed images: '.implode(', ',$allowed_images).'. Allowed videos: '.implode(', ',$allowed_videos).'.';
            break;
        }

        if(move_uploaded_file($tmp[$i], $dest_dir.$fname)){
            $uploaded[] = ['file_name'=>$fname, 'media_type'=>$type];
        } else {
            $err = 'Upload failed. Check uploads/products folder permission.';
            break;
        }
    }
    return $uploaded;
}

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    verify_csrf();
    $name     = trim($_POST['name']);
    $category = trim($_POST['category']);
    $price    = (float)$_POST['price'];
    $stock    = (int)$_POST['stock'];
    $desc     = trim($_POST['description'] ?? '');
    $status   = $_POST['status'];
    $section  = (($_POST['section'] ?? 'main') === 'boys') ? 'boys' : 'main';
    $mrp      = (($_POST['mrp'] ?? '') !== '') ? (float)$_POST['mrp'] : null;

    $image      = $p['image']      ?? '';
    $media_type = $p['media_type'] ?? 'image';

    $uploadedMedia = upload_product_media_files($_FILES['media'] ?? [], $allowed_images, $allowed_videos, $max_image_mb, $max_video_mb, $err);

    if(!$err && !empty($uploadedMedia)){
        // First uploaded media becomes product cover image/video.
        $image      = $uploadedMedia[0]['file_name'];
        $media_type = $uploadedMedia[0]['media_type'];
    }

    if(!$err){
        if($id){
            $stmt = $conn->prepare("UPDATE products SET name=?,category=?,price=?,stock=?,description=?,status=?,image=?,media_type=?,section=?,mrp=? WHERE id=?");
            $stmt->bind_param('ssdisssssdi',$name,$category,$price,$stock,$desc,$status,$image,$media_type,$section,$mrp,$id);
            $stmt->execute();
            $product_id = $id;
        } else {
            $stmt = $conn->prepare("INSERT INTO products(name,category,price,stock,description,status,image,media_type,section,mrp) VALUES(?,?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param('ssdisssssd',$name,$category,$price,$stock,$desc,$status,$image,$media_type,$section,$mrp);
            $stmt->execute();
            $product_id = $conn->insert_id;
        }

        // Store every uploaded file in separate gallery table.
        if(!empty($uploadedMedia)){
            $sort = 0;
            $maxSort = $conn->query("SELECT COALESCE(MAX(sort_order),0) AS mx FROM product_images WHERE product_id=".(int)$product_id)->fetch_assoc();
            if($maxSort) $sort = (int)$maxSort['mx'];

            $ins = $conn->prepare("INSERT INTO product_images(product_id,file_name,media_type,sort_order) VALUES(?,?,?,?)");
            foreach($uploadedMedia as $m){
                $sort++;
                $ins->bind_param('issi',$product_id,$m['file_name'],$m['media_type'],$sort);
                $ins->execute();
            }
        }
        redirect('products.php');
    }
}

$gallery = [];
if($id){
    $g = $conn->query("SELECT * FROM product_images WHERE product_id=".(int)$id." ORDER BY sort_order ASC, id ASC");
    while($row = $g->fetch_assoc()) $gallery[] = $row;
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= $id ? 'Edit' : 'Add' ?> Product - MAYURI Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600&family=Jost:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { background: #f7f2f0; }
        .admin-wrap { display: grid; grid-template-columns: 220px 1fr; min-height: 100vh; }
        .admin-sidebar { background: var(--dark); padding: 32px 0; position: sticky; top: 0; height: 100vh; overflow-y:auto; }
        .admin-sidebar .sidebar-brand { font-family: 'Cormorant Garamond', serif; font-size: 1.4rem; color: var(--gold); letter-spacing: 0.15em; padding: 0 24px 28px; border-bottom: 1px solid rgba(255,255,255,0.08); margin-bottom: 16px; display: block; }
        .admin-sidebar a { display: block; padding: 11px 24px; font-size: 0.82rem; font-weight: 500; letter-spacing: 0.1em; text-transform: uppercase; color: rgba(255,255,255,0.5); transition: all 0.25s; border-left: 3px solid transparent; }
        .admin-sidebar a:hover, .admin-sidebar a.active { color: var(--gold); background: rgba(255,255,255,0.04); border-left-color: var(--gold); }
        .admin-main { padding: 40px 36px; }
        .upload-zone { border: 2px dashed var(--border); border-radius: 12px; padding: 32px; text-align: center; cursor: pointer; transition: all 0.3s; background: var(--surface); }
        .upload-zone:hover, .upload-zone.dragover { border-color: var(--gold); background: rgba(196,160,100,0.06); }
        .upload-zone .upload-icon { font-size: 2.5rem; margin-bottom: 10px; }
        .upload-zone p { color: var(--muted); font-size: 0.88rem; margin: 4px 0; }
        .media-preview { margin-top: 12px; display:grid; grid-template-columns:repeat(auto-fill,minmax(120px,1fr)); gap:10px; }
        .media-preview img, .media-preview video { width:100%; height:120px; object-fit:cover; border-radius:10px; background:#000; border:1px solid var(--border); }
        .gallery-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(110px,1fr)); gap:10px; margin-bottom:12px; }
        .gallery-grid img, .gallery-grid video { width:100%; height:110px; object-fit:cover; border-radius:8px; border:1px solid var(--border); background:#000; }
        .file-badge { display: inline-block; background: var(--gold); color: var(--dark); font-size: 0.72rem; font-weight: 700; letter-spacing: 0.08em; padding: 3px 8px; border-radius: 4px; text-transform: uppercase; margin-top: 8px; }
    </style>
</head>
<body>
<div class="admin-wrap">
    <aside class="admin-sidebar">
        <a class="sidebar-brand" href="../index.php">MAYURI</a>
        <a href="index.php">Dashboard</a>
        <a href="products.php">Products</a>
        <a href="product_form.php" class="active">Add Product</a>
        <a href="orders.php">Orders</a>
        <a href="waitlist.php">Waiting List</a>
        <a href="../index.php" target="_blank">View Site</a>
        <a href="../logout.php">Logout</a>
    </aside>
    <main class="admin-main">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:28px">
            <h1><?= $id ? 'Edit Product' : 'Add New Product' ?></h1>
            <a class="btn secondary" href="products.php">Back to Products</a>
        </div>

        <div style="max-width:720px">
        <form class="form" method="post" enctype="multipart/form-data" style="margin:0">
            <?=csrf_field()?>
            <?php if($err) echo '<div class="alert error">'.e($err).'</div>'; ?>

            <div class="form-group">
                <label>Product Name *</label>
                <input name="name" value="<?=e($p['name']??'')?>" placeholder="e.g. Tulip Bracelet" required>
            </div>
            <div class="form-group">
                <label>Product Section *</label>
                <select name="section" required>
                    <option value="main" <?=($p['section']??'main')!=='boys'?'selected':''?>>Main Collection</option>
                    <option value="boys" <?=($p['section']??'')==='boys'?'selected':''?>>Boys Section â€” Boys Accessories</option>
                </select>
            </div>
            <div class="form-group">
                <label>Category *</label>
                <input name="category" value="<?=e($p['category']??'')?>" placeholder="e.g. Necklace, Earrings, Bracelet" required>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                <div class="form-group">
                    <label>Price (Rs.) *</label>
                    <input type="number" name="price" step="0.01" min="0" value="<?=e($p['price']??'')?>" placeholder="499.00" required>
                </div>
                <div class="form-group">
                    <label>Original Price / MRP (optional, for discount)</label>
                    <input type="number" name="mrp" step="0.01" min="0" value="<?=e($p['mrp']??'')?>" placeholder="699.00">
                </div>
                <div class="form-group">
                    <label>Stock Quantity</label>
                    <input type="number" name="stock" min="0" value="<?=e($p['stock']??0)?>">
                </div>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" rows="4" placeholder="Describe this product..."> <?=e($p['description']??'')?></textarea>
            </div>

            <div class="form-group">
                <label>Status</label>
                <select name="status">
                    <option value="in_stock" <?=($p['status']??'in_stock')==='in_stock'?'selected':''?>>In Stock</option>
                    <option value="out_of_stock" <?=($p['status']??'')==='out_of_stock'?'selected':''?>>Out of Stock</option>
                </select>
            </div>

            <div class="form-group">
                <label>Product Images / Video</label>

                <?php if(!empty($gallery) || !empty($p['image'])): ?>
                    <p style="font-size:0.82rem;color:var(--muted);margin-bottom:8px">Current media:</p>
                    <div class="gallery-grid">
                        <?php if(!empty($gallery)): foreach($gallery as $m): ?>
                            <?php if($m['media_type']==='video'): ?>
                                <video src="../uploads/products/<?=e($m['file_name'])?>" controls muted></video>
                            <?php else: ?>
                                <img src="../uploads/products/<?=e($m['file_name'])?>" alt="Product image">
                            <?php endif; ?>
                        <?php endforeach; elseif(!empty($p['image'])): ?>
                            <?php if(($p['media_type']??'image')==='video'): ?>
                                <video src="../uploads/products/<?=e($p['image'])?>" controls muted></video>
                            <?php else: ?>
                                <img src="../uploads/products/<?=e($p['image'])?>" alt="Product image">
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <div class="upload-zone" id="uploadZone" onclick="document.getElementById('mediaInput').click()">
                    <div class="upload-icon">+</div>
                    <p><strong>Click to upload multiple files</strong> or drag & drop</p>
                    <p>Images: JPG, JPEG, PNG, JFIF, GIF, WEBP, AVIF, BMP, SVG (max <?=$max_image_mb?>MB each)</p>
                    <p>Videos: MP4, WEBM, OGG, MOV, AVI, MKV, M4V (max <?=$max_video_mb?>MB each)</p>
                    <div id="fileInfo" style="margin-top:10px;font-size:0.85rem;color:var(--gold);font-weight:600"></div>
                </div>
                <input type="file" name="media[]" id="mediaInput" multiple accept="image/*,video/*,.jpg,.jpeg,.jfif,.png,.gif,.webp,.avif,.bmp,.svg,.mp4,.webm,.ogg,.mov,.avi,.mkv,.m4v" style="display:none">
                <div class="media-preview" id="mediaPreview" style="display:none"></div>
            </div>

            <button type="submit" class="btn gold" style="width:100%;justify-content:center;padding:13px">
                <?= $id ? 'Update Product' : 'Add Product' ?>
            </button>
        </form>
        </div>
    </main>
</div>

<script>
var input = document.getElementById('mediaInput');
var zone  = document.getElementById('uploadZone');
var info  = document.getElementById('fileInfo');
var prev  = document.getElementById('mediaPreview');

zone.addEventListener('dragover', function(e){ e.preventDefault(); zone.classList.add('dragover'); });
zone.addEventListener('dragleave', function(){ zone.classList.remove('dragover'); });
zone.addEventListener('drop', function(e){
    e.preventDefault(); zone.classList.remove('dragover');
    if(e.dataTransfer.files.length){ input.files = e.dataTransfer.files; handleFiles(e.dataTransfer.files); }
});
input.addEventListener('change', function(){ handleFiles(this.files); });

function handleFiles(files){
    prev.innerHTML = '';
    if(!files || !files.length){ prev.style.display='none'; return; }
    info.textContent = files.length + ' file(s) selected';
    zone.querySelector('.upload-icon').textContent = 'OK';

    Array.from(files).forEach(function(file){
        if(file.type.startsWith('image/') || file.name.toLowerCase().endsWith('.jfif')){
            var img = document.createElement('img');
            img.src = URL.createObjectURL(file);
            prev.appendChild(img);
        } else if(file.type.startsWith('video/')){
            var vid = document.createElement('video');
            vid.src = URL.createObjectURL(file);
            vid.controls = true; vid.muted = true;
            prev.appendChild(vid);
        }
    });
    prev.style.display = 'grid';
}
</script>
</body>
</html>
