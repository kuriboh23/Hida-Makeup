<?php
/**
 * HIDA.MAKEUP - Homepage
 * Matches homepage.html template exactly — fully dynamic PHP version.
 */

require_once __DIR__ . '/includes/db.php';

// Fetch all active categories (ordered by display_order)
$stmt = $pdo->query("SELECT * FROM categories ORDER BY display_order ASC");
$categories = $stmt->fetchAll();

// Fetch Best-Sellers & Featured products (limit 8, same logic as template shows 4+)
$stmt = $pdo->query("
    SELECT p.*, c.name AS category_name, c.slug AS category_slug
    FROM products p
    JOIN categories c ON p.category_id = c.id
    WHERE p.best_seller = 1 OR p.featured = 1
    ORDER BY p.best_seller DESC, p.id DESC
    LIMIT 8
");
$best_sellers = $stmt->fetchAll();

$page_title = "Accueil";
$page_description = SITE_DESCRIPTION;

require_once __DIR__ . '/includes/header.php';
?>

    <!-- ================================
         HERO SECTION
    ================================ -->
    <section class="hero">
        <div class="container hero-grid">
            <div class="hero-content">
                <div class="eyebrow">HIDA.MAKEUP · BEAUTY SHOP</div>
                <h1>
                    Votre beauté,<br>
                    <em>votre signature.</em>
                </h1>
                <p class="hero-text">
                    Maquillage, skincare, parfums et essentiels beauté
                    soigneusement sélectionnés pour vous accompagner
                    chaque jour. Livraison partout au Maroc.
                </p>
                <div class="hero-buttons">
                    <a href="<?= BASE_PATH ?>products.php" class="btn btn-primary">
                        Découvrir la boutique
                        <i class="fa-solid fa-arrow-right"></i>
                    </a>
                    <a href="#best-sellers" class="btn btn-outline">
                        Nos best-sellers
                    </a>
                </div>
                <div class="hero-note">
                    <span>
                        <i class="fa-solid fa-truck-fast"></i>
                        Livraison Maroc
                    </span>
                    <span>
                        <i class="fa-solid fa-money-bill-wave"></i>
                        Paiement à la livraison
                    </span>
                    <span>
                        <i class="fa-solid fa-shield-halved"></i>
                        100% Authentique
                    </span>
                </div>
            </div>
        </div>
    </section>

    <!-- ================================
         PROMO STRIP
    ================================ -->
    <div class="promo-strip">
        <div class="container promo-inner">
            <strong>✨ Vos essentiels beauté, réunis au même endroit.</strong>
            <span>Livraison gratuite dès <?= format_price(FREE_SHIPPING_THRESHOLD) ?> d'achat !</span>
            <a href="<?= BASE_PATH ?>products.php">SHOP NOW →</a>
        </div>
    </div>

    <!-- ================================
         CATEGORIES SECTION
    ================================ -->
    <section class="section">
        <div class="container">
            <div class="section-head">
                <div>
                    <span class="section-tag">COLLECTION</span>
                    <h2 class="section-title">Shoppez par catégorie</h2>
                    <p class="section-subtitle">Trouvez rapidement ce qui vous fait envie.</p>
                </div>
                <a href="<?= BASE_PATH ?>category.php" class="see-all">
                    Voir tout <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>

            <div class="category-grid">
                <?php foreach ($categories as $cat): ?>
                    <a href="<?= BASE_PATH ?>category.php?slug=<?= urlencode($cat['slug']) ?>" class="category">
                        <div class="category-image">
                            <img src="<?= htmlspecialchars($cat['image']) ?>"
                                 alt="<?= htmlspecialchars($cat['name']) ?>"
                                 loading="lazy">
                        </div>
                        <div class="category-name"><?= htmlspecialchars($cat['name']) ?></div>
                        <div class="category-count">Découvrir</div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- ================================
         BEST SELLERS SECTION
    ================================ -->
    <section class="section products-section" id="best-sellers" style="background: #fff;">
        <div class="container">
            <div class="section-head">
                <div>
                    <span class="section-tag">COUP DE CŒUR</span>
                    <h2 class="section-title">Les favoris HIDA</h2>
                    <p class="section-subtitle">Les produits que vous allez vouloir essayer.</p>
                </div>
                <a href="<?= BASE_PATH ?>products.php" class="see-all">
                    Tout voir <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>

            <div class="product-grid">
                <?php foreach ($best_sellers as $product):
                    $is_fav = is_in_wishlist($product['id']);
                ?>
                    <article class="product-card">
                        <div class="product-image">
                            <?php if (!empty($product['badge'])): ?>
                                <span class="product-tag"><?= htmlspecialchars($product['badge']) ?></span>
                            <?php endif; ?>

                            <button class="product-fav-btn heart <?= $is_fav ? 'active' : '' ?>"
                                    data-id="<?= $product['id'] ?>"
                                    aria-label="Ajouter aux favoris">
                                <i class="fa-<?= $is_fav ? 'solid' : 'regular' ?> fa-heart"></i>
                            </button>

                            <a href="<?= BASE_PATH ?>product.php?slug=<?= urlencode($product['slug']) ?>">
                                <img src="<?= htmlspecialchars($product['main_image']) ?>"
                                     alt="<?= htmlspecialchars($product['name']) ?>"
                                     loading="lazy">
                            </a>

                            <a href="<?= BASE_PATH ?>cart.php?action=add&id=<?= $product['id'] ?>"
                               class="add-to-cart-panel"
                               title="Ajouter au panier">
                                <i class="fa-solid fa-plus"></i>
                            </a>
                        </div>

                        <div class="product-info">
                            <div class="product-brand"><?= htmlspecialchars($product['category_name']) ?></div>
                            <a href="<?= BASE_PATH ?>product.php?slug=<?= urlencode($product['slug']) ?>"
                               class="product-name">
                                <?= htmlspecialchars($product['name']) ?>
                            </a>
                            <div class="product-price">
                                <?= format_price($product['price']) ?>
                                <?php if (!empty($product['original_price']) && $product['original_price'] > $product['price']): ?>
                                    <span class="old-price"><?= format_price($product['original_price']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <div style="text-align:center; margin-top: 40px;">
                <a href="<?= BASE_PATH ?>products.php" class="btn btn-outline">
                    Voir toute la boutique <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>
        </div>
    </section>

    <!-- ================================
         BEAUTY ROUTINE SECTION
    ================================ -->
    <section class="section routine">
        <div class="container routine-grid">
            <div class="routine-image">
                <img src="https://images.unsplash.com/photo-1512496015851-a90fb38ba796?auto=format&fit=crop&w=900&q=80"
                     alt="Routine Beauté HIDA" loading="lazy">
            </div>
            <div class="routine-content">
                <div class="eyebrow">VOTRE ROUTINE PERSONNALISÉE</div>
                <h2>
                    Créez votre routine.
                    <em style="color:var(--burgundy); font-style: normal;">À votre façon.</em>
                </h2>
                <p>
                    Pas besoin de compliquer votre quotidien. Commencez par les essentiels
                    et ajoutez ce qui correspond vraiment à votre style et à vos besoins.
                </p>
                <ul class="routine-list">
                    <li><i class="fa-solid fa-check"></i> Préparez et hydratez votre peau</li>
                    <li><i class="fa-solid fa-check"></i> Unifiez votre teint avec nos fonds de teint</li>
                    <li><i class="fa-solid fa-check"></i> Sublimez votre regard et vos lèvres</li>
                    <li><i class="fa-solid fa-check"></i> Fixez pour une tenue impeccable</li>
                </ul>
                <a href="<?= BASE_PATH ?>products.php" class="btn btn-primary">
                    Explorer les essentiels <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>
        </div>
    </section>

    <!-- ================================
         TRUST & SERVICES SECTION
    ================================ -->
    <section class="trust-section">
        <div class="container trust-container">
            <span class="trust-badge">ENGAGEMENT EXCELLENCE</span>
            <h2>Nos services exclusifs</h2>

            <div class="trust-grid-vertical">
                <div class="trust-card">
                    <div class="trust-icon-box blob-icon">
                        <i class="fa-solid fa-truck-fast"></i>
                    </div>
                    <div class="trust-text">
                        <strong>Livraison rapide partout au Maroc</strong>
                        <span>Recevez votre commande en toute sécurité chez vous, expédiée sous 24h à 48h.</span>
                    </div>
                </div>

                <div class="trust-card">
                    <div class="trust-icon-box blob-icon">
                        <i class="fa-solid fa-money-bill-wave"></i>
                    </div>
                    <div class="trust-text">
                        <strong>Paiement à la livraison 100% sécurisé</strong>
                        <span>Réglez uniquement à la réception de votre colis entre les mains du livreur.</span>
                    </div>
                </div>

                <div class="trust-card">
                    <div class="trust-icon-box circle-icon">
                        <i class="fa-solid fa-medal"></i>
                    </div>
                    <div class="trust-text">
                        <strong>Qualité certifiée & Produits authentiques</strong>
                        <span>Sélection rigoureuse des meilleurs cosmétiques et essentiels beauté.</span>
                    </div>
                </div>

                <div class="trust-card">
                    <div class="trust-icon-box blob-icon">
                        <i class="fa-brands fa-whatsapp"></i>
                    </div>
                    <div class="trust-text">
                        <strong>Support & conseils 7j/7</strong>
                        <span>Notre équipe est disponible chaque jour sur WhatsApp pour vous conseiller.</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ================================
         WHATSAPP FAST ORDER CTA
    ================================ -->
    <section class="whatsapp-section">
        <div class="container">
            <div class="whatsapp-box">
                <div>
                    <h2>Besoin d'aide pour choisir ?</h2>
                    <p>Écrivez-nous sur WhatsApp et notre équipe vous aidera à trouver le produit parfait.</p>
                </div>
                <a href="<?= htmlspecialchars(STORE_WHATSAPP_LINK) ?>" target="_blank" class="btn btn-whatsapp btn-lg">
                    <i class="fa-brands fa-whatsapp"></i> Parler sur WhatsApp
                </a>
            </div>
        </div>
    </section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
