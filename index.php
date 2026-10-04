<?php include 'includes/header.php'; ?>

<?php if(!is_logged_in()): ?>
<!--  LANDING PAGE (not logged in)  -->
<section class="hero-landing">
    <div class="hero-marquee-line"></div>

    <!-- Floating sparkles -->
    <div class="sparkles" aria-hidden="true">
        <span style="top:10%;left:7%;font-size:1rem;animation-delay:0s"></span>
        <span style="top:22%;left:90%;font-size:0.7rem;animation-delay:0.8s"></span>
        <span style="top:60%;left:4%;font-size:0.5rem;animation-delay:1.6s"></span>
        <span style="top:75%;left:93%;font-size:0.8rem;animation-delay:0.4s"></span>
        <span style="top:40%;left:48%;font-size:0.45rem;animation-delay:2.2s"></span>
        <span style="top:85%;left:18%;font-size:0.9rem;animation-delay:1.1s"></span>
        <span style="top:15%;left:38%;font-size:0.5rem;animation-delay:2.5s"></span>
        <span style="top:50%;left:80%;font-size:0.6rem;animation-delay:0.6s"></span>
    </div>

    <div class="eyebrow-pill">
        <span></span>
        Est. 2026 &nbsp;&nbsp; Pune, India
        <span></span>
    </div>

    <h1>MAYURI
        <span class="gold-word">Fine Accessories</span>
    </h1>

    <p class="tagline">Handcrafted &nbsp;&nbsp; Curated &nbsp;&nbsp; Timeless</p>

    <div class="divider-gold"><i></i></div>

    <p class="sub-text">Discover jewelry and accessories crafted for women who appreciate beauty in every detail from everyday grace to unforgettable moments.</p>

    <div class="cta-group">
        <a class="btn-hero-primary" href="login.php">Sign In to Shop</a>
        <a class="btn-hero-outline" href="register.php">Create Account</a>
    </div>

    <div class="features-row">
        <div class="feat-item">
            <div class="feat-icon"></div>
            <div class="feat-label">Premium Quality</div>
        </div>
        <div class="feat-item">
            <div class="feat-icon"></div>
            <div class="feat-label">Fast Delivery</div>
        </div>
        <div class="feat-item">
            <div class="feat-icon"></div>
            <div class="feat-label">Gift Packaging</div>
        </div>
        <div class="feat-item">
            <div class="feat-icon"></div>
            <div class="feat-label">Secure Checkout</div>
        </div>
    </div>

    <div class="scroll-hint">
        <span>Scroll</span>
        <div class="arrow"></div>
    </div>
</section>

<!-- Brand Story Section -->
<section class="brand-story">
    <div class="container brand-story-inner">
        <p class="hero-eyebrow reveal">Our Story</p>
        <h2 class="reveal delay-1" style="margin:18px 0 20px">Where Every Piece Tells a Story</h2>
        <p class="reveal delay-2" style="font-size:1rem;color:var(--muted);line-height:2;max-width:580px;margin:0 auto 36px">
            Born in Pune, MAYURI was created with one belief that every woman deserves accessories that make her feel extraordinary. We handpick each piece for its craftsmanship, quality, and timeless elegance.
        </p>
        
        <div class="reveal delay-4">
            <a class="btn gold" href="register.php">Join MAYURI Today</a>
        </div>
    </div>
</section>

<!-- Categories teaser -->
<div class="container section" style="text-align:center">
    <p class="hero-eyebrow reveal">What We Offer</p>
    <h2 class="reveal delay-1" style="margin-bottom:44px">Explore Our Categories</h2>
    <div class="cat-teaser-grid reveal delay-2">
        <?php
        $cats = [
            ['Necklaces','https://images.unsplash.com/photo-1599643478518-a784e5dc4c8f?q=80&w=800'],
            ['Earrings','https://images.unsplash.com/photo-1630019852942-f89202989a59?q=80&w=800'],
            ['Bracelets','https://images.unsplash.com/photo-1611591437281-460bfbe1220a?q=80&w=800'],
            ['Rings','https://images.unsplash.com/photo-1605100804763-247f67b3557e?q=80&w=800'],
        ];
        foreach($cats as $c): ?>
        <div class="cat-teaser-item" onclick="window.location='login.php'">
            <img src="<?=$c[1]?>" alt="<?=$c[0]?>">
            <div class="cat-teaser-overlay">
                <div class="cat-teaser-name"><?=$c[0]?></div>
                <div class="cat-teaser-btn">Login to Shop</div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <p style="margin-top:24px;color:var(--muted);font-size:0.88rem" class="reveal delay-3">
        <a href="login.php" style="color:var(--primary);font-weight:600">Login</a> or
        <a href="register.php" style="color:var(--primary);font-weight:600">register</a> to see all products and prices.
    </p>
</div>



<?php else: ?>
<!--  HOMEPAGE (logged in)  -->
<?php $featured = $conn->query("SELECT * FROM products WHERE section<>'boys' ORDER BY id DESC LIMIT 8"); ?>

<section class="hero">
    <div class="hero-dots" aria-hidden="true">
        <span style="top:20%;left:15%;animation-delay:0s"></span>
        <span style="top:60%;left:80%;animation-delay:1s"></span>
        <span style="top:40%;left:55%;animation-delay:2s"></span>
        <span style="top:75%;left:30%;animation-delay:1.5s"></span>
    </div>
    <div class="hero-eyebrow">New Arrivals &middot; Handpicked for You</div>
    <h1>Adorn Yourself<br>with <em>Elegance</em></h1>
    <p>Premium accessories crafted for every occasion &mdash; from everyday grace to unforgettable moments.</p>
    <div class="hero-actions">
        <a class="btn gold gold-pulse" href="collection.php">Shop Collection</a>
        <a class="btn secondary" href="collection.php?category=Necklace">Explore Necklaces</a>
    </div>
    <div class="hero-decor"></div>
</section>

<!-- Value strip above products -->
<div class="value-strip">
    <div class="container">
        <div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:28px;text-align:center">
            <div class="value-item reveal">
                <div class="value-icon"></div>
                <strong>Handpicked Quality</strong>
                <p>Every piece carefully selected</p>
            </div>
            <div class="value-item reveal delay-1">
                <div class="value-icon"></div>
                <strong>Fast Delivery</strong>
                <p>Across Maharashtra &amp; beyond</p>
            </div>
            <div class="value-item reveal delay-2">
                <div class="value-icon"></div>
                <strong>Premium Packaging</strong>
                <p>Gift-ready, every time</p>
            </div>
            <div class="value-item reveal delay-3">
                <div class="value-icon"></div>
                <strong>Secure Checkout</strong>
                <p>Safe &amp; trusted payments</p>
            </div>
        </div>
    </div>
</div>

<div class="container section">
    <div class="section-head reveal">
        <h2>Featured Pieces</h2>
        <span class="eyebrow">Just In</span>
    </div>
    <div class="grid">
        <?php $i=0; while($p = $featured->fetch_assoc()): $i++; ?>
        <div class="card reveal" style="animation-delay:<?=($i*0.07)?>s;cursor:pointer" onclick="window.location.href='product_detail.php?id=<?=$p['id']?>'">
            <?php if($i <= 3): ?><div class="card-badge">New</div><?php endif; ?>
            <div class="card-img-wrap">
                <img class="product-img"
                    src="<?= $p['image'] ? 'uploads/products/'.e($p['image']) : 'https://images.unsplash.com/photo-1515562141207-7a88fb7ce338?q=80&w=800' ?>"
                    alt="<?=e($p['name'])?>" loading="lazy">
                
            </div>
            <div class="card-body">
                <div class="card-category"><?=e($p['category'])?></div>
                <h3><?=e($p['name'])?></h3>
                <p class="price">&#8377;<?=number_format($p['price'],2)?></p>
                <div class="card-footer" onclick="event.stopPropagation()">
                    <span class="badge <?=($p['status']=='out_of_stock'||$p['stock']<=0)?'out':'ok'?>">
                        <?=($p['status']=='out_of_stock'||$p['stock']<=0)?'Out of Stock':'In Stock'?>
                    </span>
                    <?php if($p['status']==='out_of_stock'||$p['stock']<=0): ?><form method="post" action="waitlist.php" style="display:inline"><?=csrf_field()?><input type="hidden" name="product_id" value="<?=$p['id']?>"><button class="btn light" style="padding:8px 14px">Waiting</button></form><?php else: ?><form method="post" action="add_to_cart.php" style="display:inline"><?=csrf_field()?><input type="hidden" name="product_id" value="<?=$p['id']?>"><input type="hidden" name="qty" value="1"><button class="btn gold" style="padding:8px 14px">Add Cart</button></form><?php endif; ?>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
    <div style="text-align:center;margin-top:48px" class="reveal">
        <a class="btn secondary" href="collection.php">View All Products &rarr;</a>
    </div>
</div>

<?php endif; ?>

<?php include 'includes/footer.php'; ?>
