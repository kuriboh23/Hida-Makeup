<?php
/**
 * HIDA.MAKEUP - Global Reusable Footer
 */
require_once __DIR__ . '/config.php';
?>
    </main>

    <!-- FLOATING WHATSAPP BUTTON -->
    <a href="<?= htmlspecialchars(STORE_WHATSAPP_LINK) ?>" target="_blank" class="whatsapp-float" aria-label="Contactez-nous sur WhatsApp">
        <i class="fa-brands fa-whatsapp"></i>
    </a>

    <!-- GLOBAL FOOTER -->
    <footer id="contact">
        <div class="container">
            <div class="footer-grid">
                <!-- Column 1: Brand Info -->
                <div>
                    <div class="footer-logo"><?= htmlspecialchars(SITE_NAME) ?></div>
                    <p class="footer-about">
                        <?= htmlspecialchars(SITE_FOOTER_ABOUT) ?>
                    </p>
                    <div class="socials">
                        <a href="<?= htmlspecialchars(SOCIAL_INSTAGRAM) ?>" target="_blank" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
                        <a href="<?= htmlspecialchars(SOCIAL_FACEBOOK) ?>" target="_blank" aria-label="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
                        <a href="<?= htmlspecialchars(STORE_WHATSAPP_LINK) ?>" target="_blank" aria-label="WhatsApp"><i class="fa-brands fa-whatsapp"></i></a>
                        <a href="<?= htmlspecialchars(SOCIAL_TIKTOK) ?>" target="_blank" aria-label="TikTok"><i class="fa-brands fa-tiktok"></i></a>
                    </div>
                </div>

                <!-- Column 2: Shop Links -->
                <div>
                    <h4>Boutique</h4>
                    <div class="footer-links">
                        <a href="<?= BASE_PATH ?>products.php">Tous les produits</a>
                        <a href="<?= BASE_PATH ?>category.php?slug=levres">Lèvres & Gloss</a>
                        <a href="<?= BASE_PATH ?>category.php?slug=teint">Teint & Fonds de teint</a>
                        <a href="<?= BASE_PATH ?>category.php?slug=soins">Soins du Visage</a>
                        <a href="<?= BASE_PATH ?>category.php?slug=yeux">Maquillage des Yeux</a>
                        <a href="<?= BASE_PATH ?>category.php?slug=parfums">Parfums & Brumes</a>
                    </div>
                </div>

                <!-- Column 3: Information Links -->
                <div id="about">
                    <h4>Informations</h4>
                    <div class="footer-links">
                        <a href="<?= BASE_PATH ?>index.php#about">À propos de Hida</a>
                        <a href="<?= BASE_PATH ?>index.php#contact">Contactez-nous</a>
                        <a href="<?= BASE_PATH ?>products.php?filter=nouveau">Nouveautés</a>
                        <a href="<?= BASE_PATH ?>products.php?filter=bestseller">Meilleures Ventes</a>
                        <a href="<?= BASE_PATH ?>favorites.php">Mes Favoris</a>
                        <a href="<?= BASE_PATH ?>cart.php">Mon Panier</a>
                    </div>
                </div>

                <!-- Column 4: Contact & Store Info -->
                <div>
                    <h4>Contact & Commandes</h4>
                    <div class="footer-contact">
                        <div><i class="fa-solid fa-location-dot" style="margin-right: 6px;"></i> <?= htmlspecialchars(STORE_ADDRESS) ?></div>
                        <div><i class="fa-solid fa-phone" style="margin-right: 6px;"></i> <?= htmlspecialchars(STORE_PHONE) ?></div>
                        <div><i class="fa-solid fa-envelope" style="margin-right: 6px;"></i> <?= htmlspecialchars(STORE_EMAIL) ?></div>
                        <div style="color: var(--gold); margin-top: 5px;"><i class="fa-solid fa-clock" style="margin-right: 6px;"></i> <?= htmlspecialchars(STORE_HOURS) ?></div>
                    </div>
                </div>
            </div>

            <div class="footer-bottom">
                <span>© <?= date('Y') ?> <?= htmlspecialchars(SITE_NAME) ?> — Tous droits réservés.</span>
                <span>Fait avec ♥ au Maroc</span>
            </div>
        </div>
    </footer>

    <!-- MAIN JAVASCRIPT -->
    <script src="<?= BASE_PATH ?>assets/js/main.js"></script>
    <?php if (isset($extra_js)): ?>
        <script><?= $extra_js ?></script>
    <?php endif; ?>
</body>
</html>
