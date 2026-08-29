</main>

<footer class="footer">
    <div class="shell footer__row">
        <div>
            <p class="footer__mark"><?= e(SHOP_NAME) ?></p>
            <p><?= e(SHOP_TAGLINE) ?>. Every piece is thrown, hammered or
               block-printed by hand, so no two are identical.</p>
        </div>
        <div>
            <h3>Shop</h3>
            <ul>
                <li><a href="<?= e(url('index.php')) ?>">All products</a></li>
                <li><a href="<?= e(url('cart.php')) ?>">Your cart</a></li>
                <li><a href="<?= e(url('checkout.php')) ?>">Checkout</a></li>
            </ul>
        </div>
        <div>
            <h3>Ordering</h3>
            <ul>
                <li>Cash on delivery only</li>
                <li>Dispatch within 2 working days</li>
                <li>Call 042-3577-1180, 10am&ndash;7pm</li>
            </ul>
        </div>
        <div>
            <h3>Shopkeeper</h3>
            <ul>
                <li><a href="<?= e(url('admin/login.php')) ?>">Staff login</a></li>
            </ul>
        </div>
    </div>
</footer>

<script src="<?= e(url('assets/js/main.js')) ?>" defer></script>
</body>
</html>
