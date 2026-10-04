<?php require_once __DIR__ . '/../config/database.php'; ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>MAYURI Fine Accessories</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;1,400;1,500&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= (strpos($_SERVER['PHP_SELF'], '/admin/') !== false) ? '../' : '' ?>assets/css/style.css?v=11">
</head>
<body>

<!-- Page Loader -->
<div class="page-loader" id="pageLoader">
    <div class="loader-inner">
        <div class="loader-brand">MAYURI</div>
        <div class="loader-tagline">Fine Accessories</div>
        <div class="loader-spin"></div>
    </div>
</div>

<?php $base = (strpos($_SERVER['PHP_SELF'], '/admin/') !== false) ? '../' : ''; ?>
<div class="scroll-progress" id="scrollProgress"></div>
<nav class="nav" id="mainNav">
    <div class="container">
        <a class="brand" href="<?=$base?>index.php">MAYURI<span class="brand-dot"></span></a>
        <button class="menu-toggle" id="menuToggle" type="button" aria-label="Open menu">&#9776;</button>
        <div class="menu" id="mainMenu">
            <a href="<?=$base?>index.php">Home</a>
            <a href="<?=$base?>collection.php">Collection</a>
            <a href="<?=$base?>boys.php">Boys</a>
            <?php if(is_logged_in()): ?>
            <a href="<?=$base?>wishlist.php" title="Wishlist" style="position:relative">&#9825; Wishlist</a>
            <a href="<?=$base?>shopping_cart.php">Cart
                <?php $c = cart_count(); if($c > 0): ?><span style="background:var(--gold);color:var(--dark);border-radius:50%;width:18px;height:18px;display:inline-flex;align-items:center;justify-content:center;font-size:0.6rem;font-weight:700;margin-left:2px"><?=$c?></span><?php endif; ?>
            </a>
            <a href="<?=$base?>my_orders.php" style="font-size:0.82rem">&#128230; My Orders</a>
            <span style="font-size:0.82rem;color:var(--muted)">Hi, <?=e($_SESSION['name'])?></span>
            <a href="<?=$base?>logout.php" class="btn light" style="padding:7px 16px;font-size:0.72rem">Logout</a>
            <?php else: ?>
            <a href="<?=$base?>login.php">Login</a>
            <a href="<?=$base?>register.php" class="btn gold" style="padding:8px 18px;font-size:0.72rem">Join Now</a>
            <?php endif; ?>
            <?php if(is_admin()): ?>
            <a class="btn secondary" style="padding:7px 16px;font-size:0.72rem" href="<?= (strpos($_SERVER['PHP_SELF'], '/admin/') !== false) ? '' : 'admin/' ?>index.php">Admin</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<script>
window.addEventListener('load', function(){
    var l = document.getElementById('pageLoader');
    if(l) { setTimeout(function(){ l.classList.add('hidden'); }, 400); }
});
document.addEventListener('DOMContentLoaded', function(){
    var els = document.querySelectorAll('.reveal');
    if(els.length) {
        var io = new IntersectionObserver(function(entries){
            entries.forEach(function(e){ if(e.isIntersecting){ e.target.classList.add('visible'); io.unobserve(e.target); } });
        }, { threshold: 0.1 });
        els.forEach(function(el){ io.observe(el); });
    }
    var nav = document.getElementById('mainNav');
    if(nav) {
        window.addEventListener('scroll', function(){
            nav.classList.toggle('scrolled', window.scrollY > 30);
        });
    }
    var sp = document.getElementById('scrollProgress'), btt = document.getElementById('backToTop');
    window.addEventListener('scroll', function(){
        var max = document.documentElement.scrollHeight - innerHeight;
        if(sp) sp.style.width = (max > 0 ? scrollY / max * 100 : 0) + '%';
        if(btt) btt.classList.toggle('show', scrollY > 500);
    }, {passive:true});
    document.querySelectorAll('[data-count]').forEach(function(el){
        var to = +el.dataset.count, t0 = null;
        (function step(t){ t0 = t0 || t; var p = Math.min((t - t0) / 900, 1); el.textContent = Math.round(to * (1 - Math.pow(1 - p, 3))); if(p < 1) requestAnimationFrame(step); })(performance.now());
    });
    var menuToggle = document.getElementById('menuToggle');
    var mainMenu = document.getElementById('mainMenu');
    if(menuToggle && mainMenu) {
        menuToggle.addEventListener('click', function(){
            mainMenu.classList.toggle('open');
            menuToggle.textContent = mainMenu.classList.contains('open') ? '\u2715' : '\u2630';
        });
        mainMenu.querySelectorAll('a').forEach(function(link){
            link.addEventListener('click', function(){
                mainMenu.classList.remove('open');
                menuToggle.textContent = '\u2630';
            });
        });
    }
});
function showToast(msg) {
    var t = document.createElement('div');
    t.className = 'toast'; t.textContent = msg;
    document.body.appendChild(t);
    setTimeout(function(){ t.classList.add('show'); }, 10);
    setTimeout(function(){ t.classList.remove('show'); setTimeout(function(){ t.remove(); }, 400); }, 3000);
}
// Wishlist toggle helper (call from product cards)
function toggleWishlist(btn, pid) {
    fetch('<?=$base?>api_wishlist.php', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'product_id='+pid})
    .then(r=>r.json()).then(d=>{
        if(d.error === 'not_logged_in'){ window.location.href='<?=$base?>login.php'; return; }
        if(d.status==='added'){ btn.textContent='\u2764\uFE0F'; showToast('Added to wishlist'); }
        else { btn.textContent='\u2661'; showToast('Removed from wishlist'); }
    });
}
</script>