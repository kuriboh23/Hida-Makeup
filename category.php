<?php
/**
 * HIDA.MAKEUP - Category Page
 * Shows either the complete categories overview or all products inside a chosen category.
 */

require_once __DIR__ . '/includes/db.php';

$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';
$current_category = null;
$products = [];

// Fetch all categories for navigation tabs
$categories_stmt = $pdo->query("
    SELECT c.*, COUNT(p.id) AS product_count 
    FROM categories c 
    LEFT JOIN products p ON c.id = p.category_id 
    GROUP BY c.id 
    ORDER BY c.display_order ASC
");
$all_categories = $categories_stmt->fetchAll();

// If slug is provided, fetch specific category and its products
if ($slug !== '') {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE slug = :slug LIMIT 1");
    $stmt->execute([':slug' => $slug]);
    $current_category = $stmt->fetch();

    if ($current_category) {
        $sort = isset($_GET['sort']) ? trim($_GET['sort']) : 'featured';
        $order_by = "p.featured DESC, p.id DESC";
        if ($sort === 'price_asc') $order_by = "p.price ASC";
        if ($sort === 'price_desc') $order_by = "p.price DESC";
        if ($sort === 'newest') $order_by = "p.id DESC";

        $prod_stmt = $pdo->prepare("
            SELECT p.*, c.name AS category_name 
            FROM products p 
            JOIN categories c ON p.category_id = c.id 
            WHERE p.category_id = :cat_id 
            ORDER BY $order_by
        ");
        $prod_stmt->execute([':cat_id' => $current_category['id']]);
        $products = $prod_stmt->fetchAll();
    }
}

$page_title = $current_category ? $current_category['name'] : "Nos Catégories Beauté";
$page_description = $current_category ? ($current_category['description'] ?? SITE_DESCRIPTION) : "Explorez toutes nos catégories de cosmétiques au Maroc.";

require_once __DIR__ . '/includes/header.php';
?>

<!-- CATEGORY HERO BANNER -->
<section class="category-hero">
    <div class="container">
        <div class="breadcrumb" style="justify-content: center; margin-bottom: 12px;">
            <a href="index.php">Accueil</a>
            <span>/</span>
            <?php if ($current_category): ?>
                <a href="category.php">Catégories</a>
                <span>/</span>
                <strong><?= htmlspecialchars($current_category['name']) ?></strong>
            <?php else: ?>
                <strong>Catégories</strong>
            <?php endif; ?>
        </div>

        <h1><?= $current_category ? htmlspecialchars($current_category['name']) : "Nos Catégories Beauté" ?></h1>
        <p class="category-desc">
            <?= $current_category ? htmlspecialchars($current_category['description']) : "Trouvez les soins, textures et teintes parfaitement adaptés à vos envies." ?>
        </p>

        <!-- CATEGORIES PILL TABS -->
        <div class="category-pills">
            <a href="category.php" class="cat-pill <?= empty($slug) ? 'active' : '' ?>">
                Toutes les catégories
            </a>
            <?php foreach ($all_categories as $cat): ?>
                <a href="category.php?slug=<?= urlencode($cat['slug']) ?>" class="cat-pill <?= $slug === $cat['slug'] ? 'active' : '' ?>">
                    <?= htmlspecialchars($cat['name']) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<div class="container section">
    <?php if ($current_category): ?>
        <!-- VIEW: PRODUCTS IN SELECTED CATEGORY -->
        <div class="shop-toolbar" style="margin-bottom: 30px;">
            <div class="products-count">
                <strong><?= count($products) ?></strong> <?= count($products) > 1 ? 'produits disponibles' : 'produit disponible' ?> dans cette catégorie
            </div>

            <div class="toolbar-actions">
                <div class="sort-select-wrap">
                    <label for="sortSelect" style="font-size:12px; color:var(--muted);">Trier par :</label>
                    <select id="sortSelect" onchange="applyCatSort(this.value)">
                        <option value="featured" <?= (!isset($_GET['sort']) || $_GET['sort'] === 'featured') ? 'selected' : '' ?>>Recommandés</option>
                        <option value="price_asc" <?= (isset($_GET['sort']) && $_GET['sort'] === 'price_asc') ? 'selected' : '' ?>>Prix croissant</option>
                        <option value="price_desc" <?= (isset($_GET['sort']) && $_GET['sort'] === 'price_desc') ? 'selected' : '' ?>>Prix décroissant</option>
                        <option value="newest" <?= (isset($_GET['sort']) && $_GET['sort'] === 'newest') ? 'selected' : '' ?>>Nouveautés</option>
                    </select>
                </div>
            </div>
        </div>

        <?php if (!empty($products)): ?>
            <div class="products-grid">
                <?php foreach ($products as $product): ?>
                    <?php $is_fav = is_in_wishlist($product['id']); ?>
                    <article class="product-card">
                        <div class="product-img-wrap">
                            <?php if (!empty($product['badge'])): ?>
                                <span class="product-badge"><?= htmlspecialchars($product['badge']) ?></span>
                            <?php endif; ?>

                            <button class="product-fav-btn <?= $is_fav ? 'active' : '' ?>" 
                                    data-id="<?= $product['id'] ?>" 
                                    aria-label="Ajouter aux favoris">
                                <i class="fa-<?= $is_fav ? 'solid' : 'regular' ?> fa-heart"></i>
                            </button>

                            <a href="product.php?slug=<?= urlencode($product['slug']) ?>">
                                <img src="<?= htmlspecialchars($product['main_image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" loading="lazy">
                            </a>
                        </div>

                        <div class="product-info">
                            <div class="product-cat"><?= htmlspecialchars($product['category_name']) ?></div>
                            <h3 class="product-title">
                                <a href="product.php?slug=<?= urlencode($product['slug']) ?>">
                                    <?= htmlspecialchars($product['name']) ?>
                                </a>
                            </h3>

                            <div class="product-meta">
                                <div class="product-stars">
                                    <i class="fa-solid fa-star"></i>
                                    <span><?= number_format($product['rating'], 1) ?></span>
                                </div>
                                <span>(<?= $product['reviews_count'] ?> avis)</span>
                            </div>

                            <div class="product-bottom">
                                <div class="price-box">
                                    <span class="price-current"><?= format_price($product['price']) ?></span>
                                    <?php if (!empty($product['original_price']) && $product['original_price'] > $product['price']): ?>
                                        <span class="price-old"><?= format_price($product['original_price']) ?></span>
                                    <?php endif; ?>
                                </div>

                                <a href="cart.php?action=add&id=<?= $product['id'] ?>" class="add-cart-btn" title="Ajouter au panier">
                                    <i class="fa-solid fa-bag-shopping"></i>
                                </a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-products-state">
                <i class="fa-solid fa-boxes-stacked"></i>
                <h2>Bientôt disponible</h2>
                <p>Les articles de cette catégorie sont en cours d'approvisionnement.</p>
                <a href="products.php" class="btn btn-primary" style="margin-top: 15px;">
                    Voir les autres produits
                </a>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <!-- VIEW: ALL CATEGORIES OVERVIEW -->
        <div class="cat-overview-grid">
            <?php foreach ($all_categories as $cat): ?>
                <div class="cat-card">
                    <div class="cat-card-img">
                        <img src="<?= htmlspecialchars($cat['image']) ?>" alt="<?= htmlspecialchars($cat['name']) ?>" loading="lazy">
                        <span class="cat-count-badge"><?= $cat['product_count'] ?> produits</span>
                    </div>
                    <div class="cat-card-body">
                        <h3><?= htmlspecialchars($cat['name']) ?></h3>
                        <p><?= htmlspecialchars($cat['description']) ?></p>
                        <a href="category.php?slug=<?= urlencode($cat['slug']) ?>" class="btn btn-outline btn-sm">
                            Découvrir la sélection <i class="fa-solid fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
