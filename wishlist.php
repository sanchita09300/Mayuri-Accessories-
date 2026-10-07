<?php include 'includes/header.php';
if(!is_logged_in()) redirect('login.php');

// Handle AJAX toggle (from collection page wishlist button)
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['product_id'])){
    $pid = (int)$_POST['product_id'];
    $uid = (int)$_SESSION['user_id'];
    $chk = $conn->prepare("SELECT id FROM wishlist WHERE user_id=? AND product_id=?");
    $chk->bind_param('ii',$uid,$pid); $chk->execute();
    if($chk->get_result()->num_rows > 0){
        $del = $conn->prepare("DELETE FROM wishlist WHERE user_id=? AND product_id=?");
        $del->bind_param('ii',$uid,$pid); $del->execute();
        echo json_encode(['status'=>'removed']);
    } else {
        $ins = $conn->prepare("INSERT IGNORE INTO wishlist(user_id,product_id) VALUES(?,?)");
        $ins->bind_param('ii',$uid,$pid); $ins->execute();
        echo json_encode(['status'=>'added']);
    }
    exit;
}

$uid = (int)$_SESSION['user_id'];
$items = $conn->query("
    SELECT p.*, w.id as wid FROM wishlist w
    JOIN products p ON p.id = w.product_id
    WHERE w.user_id = $uid ORDER BY w.id DESC
");
?>
<div class="container" style="padding-top:48px;padding-bottom:64px">
  <h1 style="font-family:'Cormorant Garamond',serif;font-size:2.2rem;margin-bottom:8px">My Wishlist</h1>
  <p style="color:var(--muted);margin-bottom:36px">Items you've saved for later</p>

  <?php if($items->num_rows === 0): ?>
    <div style="text-align:center;padding:80px 0">
      <div style="font-size:3.5rem;margin-bottom:16px">&#9825;</div>
      <h2 style="font-family:'Cormorant Garamond',serif;font-size:1.8rem;margin-bottom:10px">Your wishlist is empty</h2>
      <p style="color:var(--muted);margin-bottom:28px">Browse our collection and save items you love</p>
      <a href="collection.php" class="btn gold">Explore Collection &rarr;</a>
    </div>
  <?php else: ?>
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:28px">
    <?php while($p = $items->fetch_assoc()): ?>
    <div class="reveal wishlist-card" style="background:#fff;border-radius:16px;overflow:hidden;box-shadow:var(--shadow-sm);transition:transform 0.3s,box-shadow 0.3s" onmouseover="this.style.transform='translateY(-4px)';this.style.boxShadow='var(--shadow-md)'" onmouseout="this.style.transform='none';this.style.boxShadow='var(--shadow-sm)'">
      <div style="position:relative;height:220px;background:var(--cream);overflow:hidden">
        <?php if($p['image']): ?>
          <?php if(($p['media_type']??'image')==='video'): ?>
          <video src="uploads/products/<?=e($p['image'])?>" style="width:100%;height:100%;object-fit:cover" muted autoplay loop playsinline></video>
          <?php else: ?>
          <img src="uploads/products/<?=e($p['image'])?>" alt="<?=e($p['name'])?>" style="width:100%;height:100%;object-fit:cover">
          <?php endif; ?>
        <?php else: ?>
          <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:var(--muted);font-size:2rem">&#9728;</div>
        <?php endif; ?>
        <button onclick="removeWishlistItem(this,<?=$p['id']?>)" style="position:absolute;top:10px;right:10px;background:#fff;border:none;border-radius:50%;width:34px;height:34px;cursor:pointer;font-size:1.1rem;box-shadow:var(--shadow-sm)" title="Remove from wishlist">&#10084;&#65039;</button>
      </div>
      <div style="padding:18px">
        <div style="font-size:0.72rem;letter-spacing:0.12em;text-transform:uppercase;color:var(--muted);margin-bottom:4px"><?=e($p['category'])?></div>
        <h3 style="font-family:'Cormorant Garamond',serif;font-size:1.15rem;margin-bottom:8px"><?=e($p['name'])?></h3>
        <div style="display:flex;justify-content:space-between;align-items:center">
          <span style="font-size:1.05rem;font-weight:600;color:var(--primary)">&#8377;<?=number_format($p['price'],2)?></span>
          <a href="product_detail.php?id=<?=$p['id']?>" class="btn gold" style="padding:7px 14px;font-size:0.75rem">View &rarr;</a>
        </div>
      </div>
    </div>
    <?php endwhile; ?>
  </div>
  <?php endif; ?>
</div>
<script>
function removeWishlistItem(btn, pid){
  fetch('wishlist.php', {
    method:'POST',
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:'product_id='+pid
  })
  .then(r=>r.json()).then(d=>{
    if(d.status==='removed'){
      var card = btn.closest('.wishlist-card');
      if(card){ card.style.opacity='0'; card.style.transform='scale(0.9)'; card.style.transition='all 0.3s'; setTimeout(function(){ card.remove(); },300); }
    }
  });
}
</script>
<?php include 'includes/footer.php'; ?>
