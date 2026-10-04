<?php $base = (strpos($_SERVER['PHP_SELF'], '/admin/') !== false) ? '../' : ''; ?>
<footer class="footer">
    <div class="container">
        <div class="footer-inner">
            <div>
                <div class="footer-brand-name">MAYURI</div>
                <p class="footer-tagline">Curated accessories for her and for him, with beauty in every detail. Handpicked with love from Pune, Maharashtra.</p>
                <div style="margin-top:20px;display:flex;gap:12px">
                    <a href="https://instagram.com/" target="_blank" rel="noopener" aria-label="Instagram" style="width:36px;height:36px;border-radius:50%;border:1px solid rgba(255,255,255,0.12);display:flex;align-items:center;justify-content:center;color:rgba(255,255,255,0.5);font-size:0.8rem;transition:all 0.3s" onmouseover="this.style.borderColor='var(--gold)';this.style.color='var(--gold)'" onmouseout="this.style.borderColor='rgba(255,255,255,0.12)';this.style.color='rgba(255,255,255,0.5)'">IG</a>
                    <a href="#" aria-label="Facebook" style="width:36px;height:36px;border-radius:50%;border:1px solid rgba(255,255,255,0.12);display:flex;align-items:center;justify-content:center;color:rgba(255,255,255,0.5);font-size:0.8rem;transition:all 0.3s" onmouseover="this.style.borderColor='var(--gold)';this.style.color='var(--gold)'" onmouseout="this.style.borderColor='rgba(255,255,255,0.12)';this.style.color='rgba(255,255,255,0.5)'">FB</a>
                    <a href="https://wa.me/918669619138?text=Hello%20MAYURI%2C%20I%20need%20help%20with%20my%20order." target="_blank" rel="noopener" aria-label="WhatsApp" style="width:36px;height:36px;border-radius:50%;border:1px solid rgba(255,255,255,0.12);display:flex;align-items:center;justify-content:center;color:rgba(255,255,255,0.5);font-size:0.8rem;transition:all 0.3s" onmouseover="this.style.borderColor='var(--gold)';this.style.color='var(--gold)'" onmouseout="this.style.borderColor='rgba(255,255,255,0.12)';this.style.color='rgba(255,255,255,0.5)'">WA</a>
                </div>
            </div>
            <div class="footer-col">
                <h4>Shop</h4>
                <a href="<?=$base?>collection.php">All Collection</a>
                <a href="<?=$base?>collection.php?category=Necklace">Necklaces</a>
                <a href="<?=$base?>collection.php?category=Bracelet">Bracelets</a>
                <a href="<?=$base?>collection.php?category=Earrings">Earrings</a>
                <a href="<?=$base?>collection.php?category=Ring">Rings</a>
                <a href="<?=$base?>boys.php">Boys Collection</a>
            </div>
            <div class="footer-col">
                <h4>Account</h4>
                <?php if(is_logged_in()): ?>
                <a href="<?=$base?>wishlist.php">My Wishlist</a>
                <a href="<?=$base?>shopping_cart.php">My Cart</a>
                <a href="<?=$base?>logout.php">Logout</a>
                <?php else: ?>
                <a href="<?=$base?>login.php">Login</a>
                <a href="<?=$base?>register.php">Register</a>
                <?php endif; ?>
                <a href="<?=$base?>policies.php">Policies</a>
                <h4 style="margin-top:20px">Contact</h4>
                <span style="font-size:0.88rem;color:rgba(255,255,255,0.4)">Pune, Maharashtra</span>
            </div>
        </div>
        <div class="footer-bottom">
            <span>&copy; <?=date('Y')?> Mayuri Accessories. All rights reserved.</span>
            <span style="color:var(--gold);font-family:'Cormorant Garamond',serif;font-size:1.15rem">Made with Love by Sanchita</span>
        </div>
    </div>
</footer>
<a href="https://wa.me/918669619138?text=Hello%20MAYURI%2C%20I%20need%20help%20with%20my%20order." target="_blank" rel="noopener" class="wa-float" aria-label="Chat on WhatsApp">WhatsApp</a>
<button type="button" class="back-top" id="backToTop" aria-label="Back to top" onclick="window.scrollTo({top:0,behavior:'smooth'})">&uarr;</button>
</body>
</html>
