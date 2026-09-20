<?php
/**
 * HIDA.MAKEUP - Single Product Page
 * Dynamic product details, image gallery, quantity selector, add to cart, and WhatsApp order.
 */

require_once __DIR__ . '/includes/db.php';

$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';
$id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;

$product = null;
if ($slug !== '') {
    $stmt = $pdo->prepare("
        SELECT p.*, c.name AS category_name, c.slug AS category_slug 
        FROM products p 
        JOIN categories c ON p.category_id = c.id 
        WHERE p.slug = :slug LIMIT 1
    ");
    $stmt->execute([':slug' => $slug]);
    $product = $stmt->fetch();
} elseif ($id > 0) {
    $stmt = $pdo->prepare("
        SELECT p.*, c.name AS category_name, c.slug AS category_slug 
        FROM products p 
        JOIN categories c ON p.category_id = c.id 
        WHERE p.id = :id LIMIT 1
    ");
    $stmt->execute([':id' => $id]);
    $product = $stmt->fetch();
}

// If product not found, redirect to shop
if (!$product) {
    header("Location: products.php");
    exit;
}

// Gallery: local photos in assets/images/products/ win, then the stored URLs
$gallery = product_gallery_urls($product);

// Fetch related products in the same category
$rel_stmt = $pdo->prepare("
    SELECT p.*, c.name AS category_name 
    FROM products p 
    JOIN categories c ON p.category_id = c.id 
    WHERE p.category_id = :cat_id AND p.id != :prod_id 
    ORDER BY p.featured DESC, p.id DESC 
    LIMIT 4
");
$rel_stmt->execute([
    ':cat_id' => $product['category_id'],
    ':prod_id' => $product['id']
]);
$related_products = $rel_stmt->fetchAll();

$is_fav = is_in_wishlist($product['id']);
$page_title = $product['name'];
$page_description = !empty($product['short_description']) ? $product['short_description'] : SITE_DESCRIPTION;

// Current URL for WhatsApp sharing
$host_name = $_SERVER['HTTP_HOST'] ?? 'localhost';
$req_uri = $_SERVER['REQUEST_URI'] ?? ('product.php?slug=' . urlencode($product['slug']));
$protocol_name = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
$current_product_url = $protocol_name . "://" . $host_name . $req_uri;
$whatsapp_inquiry_url = whatsapp_product_url($product['name'], $product['price'], $current_product_url);

require_once __DIR__ . '/includes/header.php';
?>

<!-- BREADCRUMB -->
<div class="page-hero breadcrumb-only">
    <div class="container">
        <nav class="breadcrumb" aria-label="Fil d'Ariane">
            <a href="index.php">Accueil</a>
            <span>/</span>
            <a href="products.php">Boutique</a>
            <span>/</span>
            <a href="products.php?category=<?= urlencode($product['category_slug']) ?>"><?= htmlspecialchars($product['category_name']) ?></a>
            <span>/</span>
            <strong><?= htmlspecialchars($product['name']) ?></strong>
        </nav>
    </div>
</div>

<!-- MAIN PRODUCT SECTION -->
<section class="section product-details-section">
    <div class="container product-grid-layout">
        
        <!-- IMAGE GALLERY -->
        <div class="product-gallery">
            <div class="main-image-wrap">
                <?php if (!empty($product['badge'])): ?>
                    <span class="product-badge" style="top:18px; left:18px;"><?= htmlspecialchars($product['badge']) ?></span>
                <?php endif; ?>
                <img id="mainProductImg" src="<?= htmlspecialchars($gallery[0]) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
            </div>

            <?php if (count($gallery) > 1): ?>
                <div class="thumbnail-row">
                    <?php foreach ($gallery as $index => $img): ?>
                        <div class="thumbnail <?= $index === 0 ? 'active' : '' ?>" onclick="switchProductImg(this, '<?= htmlspecialchars($img) ?>')">
                            <img src="<?= htmlspecialchars($img) ?>" alt="Vue <?= $index + 1 ?>">
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- PRODUCT INFO & ACTIONS -->
        <div class="product-details-content">
            <div class="product-cat-name"><?= htmlspecialchars($product['category_name']) ?></div>
            <h1 class="product-main-title"><?= htmlspecialchars($product['name']) ?></h1>

            <div class="product-rating-box">
                <div class="stars">
                    <i class="fa-solid fa-star"></i>
                    <i class="fa-solid fa-star"></i>
                    <i class="fa-solid fa-star"></i>
                    <i class="fa-solid fa-star"></i>
                    <i class="fa-solid fa-star-half-stroke"></i>
                </div>
                <span class="rating-number"><?= number_format($product['rating'], 1) ?></span>
                <span class="rating-count">(<?= $product['reviews_count'] ?> avis certifiés)</span>
            </div>

            <div class="product-price-section">
                <div class="current-price"><?= format_price($product['price']) ?></div>
                <?php if (!empty($product['original_price']) && $product['original_price'] > $product['price']): ?>
                    <div class="old-price-box"><?= format_price($product['original_price']) ?></div>
                    <?php $discount = round((($product['original_price'] - $product['price']) / $product['original_price']) * 100); ?>
                    <div class="discount-pill">-<?= $discount ?>%</div>
                <?php endif; ?>
            </div>

            <?php if (!empty($product['short_description'])): ?>
                <p class="product-short-desc"><?= htmlspecialchars($product['short_description']) ?></p>
            <?php endif; ?>

            <div class="stock-status <?= $product['in_stock'] ? 'in-stock' : 'out-of-stock' ?>">
                <i class="fa-solid <?= $product['in_stock'] ? 'fa-check' : 'fa-xmark' ?>"></i>
                <span><?= $product['in_stock'] ? 'En stock (Expédition express sous 24h/48h)' : 'Rupture temporaire' ?></span>
            </div>

            <!-- ADD TO CART & WHATSAPP FORM -->
            <form action="cart.php" method="GET" class="purchase-form">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="id" value="<?= $product['id'] ?>">

                <div class="qty-and-cart">
                    <div class="qty-selector">
                        <button type="button" class="qty-btn" onclick="decreaseQty()">-</button>
                        <input type="number" name="qty" id="productQty" value="1" min="1" max="<?= $product['stock_quantity'] ?>" readonly>
                        <button type="button" class="qty-btn" onclick="increaseQty()">+</button>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block" <?= !$product['in_stock'] ? 'disabled' : '' ?>>
                        <i class="fa-solid fa-bag-shopping"></i> Ajouter au panier
                    </button>
                </div>

                <div class="action-buttons-group">
                    <a href="<?= htmlspecialchars($whatsapp_inquiry_url) ?>" target="_blank" class="btn btn-whatsapp btn-block">
                        <i class="fa-brands fa-whatsapp"></i> Commander directement par WhatsApp
                    </a>

                    <button type="button" class="btn btn-outline product-fav-btn <?= $is_fav ? 'active' : '' ?>" data-id="<?= $product['id'] ?>" style="width: auto; padding: 0 20px;">
                        <i class="fa-<?= $is_fav ? 'solid' : 'regular' ?> fa-heart"></i> <?= $is_fav ? 'Dans vos favoris' : 'Ajouter aux favoris' ?>
                    </button>
                </div>
            </form>

            <!-- TRUST REASSURANCES -->
            <div class="reassurance-box">
                <div class="reassurance-item">
                    <i class="fa-solid fa-truck-fast"></i>
                    <div>
                        <strong>Livraison partout au Maroc</strong>
                        <p>Casablanca, Rabat, Fès, Marrakech, Tanger et toutes les villes.</p>
                    </div>
                </div>
                <div class="reassurance-item">
                    <i class="fa-solid fa-money-bill-wave"></i>
                    <div>
                        <strong>Paiement à la livraison</strong>
                        <p>Payez en espèces lors de la réception de votre colis.</p>
                    </div>
                </div>
                <div class="reassurance-item">
                    <i class="fa-solid fa-rotate-left"></i>
                    <div>
                        <strong>Échange & Retours facilités</strong>
                        <p>Satisfaite ou échangée sous 7 jours ouvrés.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- PRODUCT TABS (DESCRIPTION, INGREDIENTS, HOW TO USE) -->
<section class="section product-tabs-section">
    <div class="container">
        <div class="tabs-header">
            <button class="tab-btn active" onclick="switchTab(this, 'tabDesc')">Description détaillée</button>
            <button class="tab-btn" onclick="switchTab(this, 'tabConseils')">Conseils d'application</button>
            <button class="tab-btn" onclick="switchTab(this, 'tabDelivery')">Livraison & Retours</button>
        </div>

        <div class="tab-body active" id="tabDesc">
            <div class="tab-content-text">
                <?= nl2br(htmlspecialchars($product['description'] ?: $product['short_description'])) ?>
            </div>
        </div>

        <div class="tab-body" id="tabConseils" style="display:none;">
            <div class="tab-content-text">
                <p><strong>Conseils d'utilisation de nos experts :</strong></p>
                <ol style="margin-left: 20px; line-height: 2;">
                    <li>Nettoyez et hydratez votre visage avant toute application.</li>
                    <li>Prélevez une petite quantité de produit et appliquez par touches délicates.</li>
                    <li>Estompez au doigt, à l'éponge blender humidifiée ou au pinceau pour un résultat sans démarcation.</li>
                    <li>Fixez avec notre spray fixateur pour garantir une tenue irréprochable toute la journée.</li>
                </ol>
            </div>
        </div>

        <div class="tab-body" id="tabDelivery" style="display:none;">
            <div class="tab-content-text">
                <p><strong>Conditions de livraison au Maroc :</strong></p>
                <p>Toutes nos commandes sont expédiées soigneusement depuis nos locaux sous 24h. Le délai moyen de réception est de 24h à 48h selon votre ville.</p>
                <p>Frais de livraison standard : <strong><?= format_price(DEFAULT_SHIPPING_FEE) ?></strong>. Livraison <strong>GRATUITE</strong> pour toute commande à partir de <strong><?= format_price(FREE_SHIPPING_THRESHOLD) ?></strong>.</p>
            </div>
        </div>
    </div>
</section>

<!-- RELATED PRODUCTS -->
<?php if (!empty($related_products)): ?>
<section class="section" style="background: var(--bg-light);">
    <div class="container">
        <div class="section-head">
            <div>
                <span class="section-tag">COMPLÉTEZ VOTRE ROUTINE</span>
                <h2 class="section-title">Produits similaires</h2>
            </div>
            <a href="products.php?category=<?= urlencode($product['category_slug']) ?>" class="see-all">
                Voir plus dans <?= htmlspecialchars($product['category_name']) ?> <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>

        <div class="products-grid">
            <?php foreach ($related_products as $rel): ?>
                <?php $rel_fav = is_in_wishlist($rel['id']); ?>
                <article class="product-card">
                    <div class="product-img-wrap">
                        <?php if (!empty($rel['badge'])): ?>
                            <span class="product-badge"><?= htmlspecialchars($rel['badge']) ?></span>
                        <?php endif; ?>

                        <button class="product-fav-btn <?= $rel_fav ? 'active' : '' ?>" data-id="<?= $rel['id'] ?>" aria-label="Ajouter aux favoris">
                            <i class="fa-<?= $rel_fav ? 'solid' : 'regular' ?> fa-heart"></i>
                        </button>

                        <a href="product.php?slug=<?= urlencode($rel['slug']) ?>">
                            <img src="<?= htmlspecialchars(product_image_url($rel['main_image'], (string)$rel['slug'], (int)$rel['id'])) ?>" alt="<?= htmlspecialchars($rel['name']) ?>" loading="lazy">
                        </a>
                    </div>

                    <div class="product-info">
                        <div class="product-cat"><?= htmlspecialchars($rel['category_name']) ?></div>
                        <h3 class="product-title">
                            <a href="product.php?slug=<?= urlencode($rel['slug']) ?>">
                                <?= htmlspecialchars($rel['name']) ?>
                            </a>
                        </h3>

                        <div class="product-bottom">
                            <div class="price-box">
                                <span class="price-current"><?= format_price($rel['price']) ?></span>
                                <?php if (!empty($rel['original_price'])): ?>
                                    <span class="price-old"><?= format_price($rel['original_price']) ?></span>
                                <?php endif; ?>
                            </div>

                            <a href="cart.php?action=add&id=<?= $rel['id'] ?>" class="add-cart-btn" data-id="<?= $rel['id'] ?>" title="Ajouter au panier">
                                <i class="fa-solid fa-bag-shopping"></i>
                            </a>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php
require_once __DIR__ . '/includes/footer.php';
