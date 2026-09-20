<?php
/**
 * HIDA.MAKEUP - Boutique (All Products Catalog)
 * Features dynamic filtering by category, search term, price range, and sorting.
 */

require_once __DIR__ . '/includes/db.php';

// 1. Get and sanitize query filters
$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$selected_category = isset($_GET['category']) ? trim($_GET['category']) : '';
$filter_tag = isset($_GET['filter']) ? trim($_GET['filter']) : ''; // e.g. 'nouveau', 'bestseller'
$sort = isset($_GET['sort']) ? trim($_GET['sort']) : 'featured';
$min_price = isset($_GET['min_price']) && is_numeric($_GET['min_price']) ? (float)$_GET['min_price'] : 0;
$max_price = isset($_GET['max_price']) && is_numeric($_GET['max_price']) ? (float)$_GET['max_price'] : 1000;
$only_stock = isset($_GET['in_stock']) && $_GET['in_stock'] === '1';

// 2. Fetch all categories for sidebar
$categories = $pdo->query("SELECT * FROM categories ORDER BY display_order ASC")->fetchAll();

// 3. Build dynamic SQL query
$sql = "
    SELECT p.*, c.name AS category_name, c.slug AS category_slug
    FROM products p
    JOIN categories c ON p.category_id = c.id
    WHERE 1=1
";
$params = [];

if ($search !== '') {
    // One distinct placeholder per LIKE: MySQL native prepares (emulation is
    // off) reject a named placeholder that is reused in the same statement.
    $sql .= " AND (p.name LIKE :search_name OR p.short_description LIKE :search_desc OR c.name LIKE :search_cat)";

    $term = '%' . $search . '%';
    $params[':search_name'] = $term;
    $params[':search_desc'] = $term;
    $params[':search_cat']  = $term;
}

if ($selected_category !== '') {
    $sql .= " AND c.slug = :category";
    $params[':category'] = $selected_category;
}

if ($filter_tag === 'nouveau') {
    $sql .= " AND p.badge LIKE '%Nouveau%'";
} elseif ($filter_tag === 'bestseller') {
    $sql .= " AND (p.best_seller = 1 OR p.badge LIKE '%Best%')";
}

if ($min_price > 0) {
    $sql .= " AND p.price >= :min_price";
    $params[':min_price'] = $min_price;
}

if ($max_price < 1000) {
    $sql .= " AND p.price <= :max_price";
    $params[':max_price'] = $max_price;
}

if ($only_stock) {
    $sql .= " AND p.in_stock = 1";
}

// 4. Sorting logic
switch ($sort) {
    case 'price_asc':
        $sql .= " ORDER BY p.price ASC";
        break;
    case 'price_desc':
        $sql .= " ORDER BY p.price DESC";
        break;
    case 'newest':
        $sql .= " ORDER BY p.id DESC";
        break;
    case 'rating':
        $sql .= " ORDER BY p.rating DESC";
        break;
    case 'featured':
    default:
        $sql .= " ORDER BY p.featured DESC, p.best_seller DESC, p.id DESC";
        break;
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();
$total_products = count($products);

// Current Category Name (if selected)
$active_cat_name = "Tous les produits";
if ($selected_category) {
    foreach ($categories as $cat) {
        if ($cat['slug'] === $selected_category) {
            $active_cat_name = $cat['name'];
            break;
        }
    }
}

// Page heading: an active search takes priority over the plain catalogue label
$page_heading = $search ? 'Résultats de recherche' : $active_cat_name;

// Filters carried over when a new search term is submitted, so searching never
// silently drops what the visitor had already narrowed down.
$preserved_filters = [];
if ($selected_category !== '') {
    $preserved_filters['category'] = $selected_category;
}
if ($filter_tag !== '') {
    $preserved_filters['filter'] = $filter_tag;
}
if ($only_stock) {
    $preserved_filters['in_stock'] = '1';
}
if ($max_price < 1000) {
    $preserved_filters['max_price'] = (string)$max_price;
}
if ($sort !== 'featured') {
    $preserved_filters['sort'] = $sort;
}

$page_title = $search ? 'Recherche : ' . $search : 'Boutique — ' . $active_cat_name;
$page_description = "Découvrez notre collection complète de cosmétiques, maquillage et soins disponibles au Maroc.";

require_once __DIR__ . '/includes/header.php';
?>

<!-- PAGE HEADER & BREADCRUMB -->
<section class="page-hero">
    <div class="container">
        <nav class="breadcrumb" aria-label="Fil d'Ariane">
            <a href="index.php">Accueil</a>
            <span>/</span>
            <a href="products.php">Boutique</a>
            <span>/</span>
            <strong>
                <?php if ($search): ?>
                    Recherche
                <?php else: ?>
                    <?= htmlspecialchars($active_cat_name) ?>
                <?php endif; ?>
            </strong>
        </nav>

        <h1><?= htmlspecialchars($page_heading) ?></h1>

        <p class="page-hero-desc">
            <?php if ($search): ?>
                Résultats pour « <?= htmlspecialchars($search) ?> » — <?= $total_products ?> <?= $total_products > 1 ? 'articles trouvés' : 'article trouvé' ?>.
            <?php else: ?>
                Maquillage, soins, parfums et essentiels beauté sélectionnés avec soin, livrés partout au Maroc.
            <?php endif; ?>
        </p>

        <!-- SHOP SEARCH BAR -->
        <form class="shop-search" action="products.php" method="GET" role="search">
            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>

            <input type="search" name="q" id="shopSearchInput"
                   value="<?= htmlspecialchars($search) ?>"
                   placeholder="Rechercher un rouge à lèvres, un fond de teint, un parfum..."
                   aria-label="Rechercher un produit"
                   autocomplete="off">

            <?php foreach ($preserved_filters as $filter_name => $filter_value): ?>
                <input type="hidden" name="<?= htmlspecialchars($filter_name) ?>" value="<?= htmlspecialchars($filter_value) ?>">
            <?php endforeach; ?>

            <button type="submit" class="shop-search-btn" aria-label="Lancer la recherche">
                <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
            </button>
        </form>
    </div>
</section>

<!-- MAIN SHOP LAYOUT -->
<div class="container shop-layout">
    
    <!-- SIDEBAR FILTERS -->
    <aside class="shop-sidebar" id="shopSidebar">
        <div class="sidebar-header-mobile">
            <h3>Filtres</h3>
            <button class="close-btn" onclick="toggleFilterSidebar()" aria-label="Fermer les filtres">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form action="products.php" method="GET" id="filterForm">
            <?php if ($search): ?>
                <input type="hidden" name="q" value="<?= htmlspecialchars($search) ?>">
            <?php endif; ?>

            <?php // Keep the active sort order and "Nouveautés / Best-sellers" tag alive when a filter changes ?>
            <?php if ($sort !== 'featured'): ?>
                <input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>">
            <?php endif; ?>
            <?php if ($filter_tag !== ''): ?>
                <input type="hidden" name="filter" value="<?= htmlspecialchars($filter_tag) ?>">
            <?php endif; ?>

            <!-- Category Filter -->
            <div class="filter-group">
                <div class="filter-title">Catégories</div>
                <div class="filter-list">
                    <label class="filter-radio-item <?= empty($selected_category) ? 'active' : '' ?>">
                        <input type="radio" name="category" value="" <?= empty($selected_category) ? 'checked' : '' ?> onchange="this.form.submit()">
                        <span>Toutes les catégories</span>
                    </label>
                    <?php foreach ($categories as $cat): ?>
                        <label class="filter-radio-item <?= $selected_category === $cat['slug'] ? 'active' : '' ?>">
                            <input type="radio" name="category" value="<?= htmlspecialchars($cat['slug']) ?>" <?= $selected_category === $cat['slug'] ? 'checked' : '' ?> onchange="this.form.submit()">
                            <span><?= htmlspecialchars($cat['name']) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Price Range Filter -->
            <div class="filter-group">
                <div class="filter-title">Prix Maximum</div>
                <div style="padding: 0 5px;">
                    <input type="range" name="max_price" min="50" max="600" step="25" value="<?= $max_price ?>" 
                           oninput="document.getElementById('priceVal').textContent = this.value + ' <?= CURRENCY_SYMBOL ?>'"
                           onchange="this.form.submit()" style="width: 100%; accent-color: var(--burgundy);">
                    <div style="display:flex; justify-content:space-between; font-size:12px; color:var(--muted); margin-top:8px;">
                        <span>0 <?= CURRENCY_SYMBOL ?></span>
                        <strong id="priceVal" style="color:var(--burgundy); font-weight:700;"><?= $max_price ?> <?= CURRENCY_SYMBOL ?></strong>
                    </div>
                </div>
            </div>

            <!-- Stock Filter -->
            <div class="filter-group">
                <label class="filter-checkbox-item">
                    <input type="checkbox" name="in_stock" value="1" <?= $only_stock ? 'checked' : '' ?> onchange="this.form.submit()">
                    <span>Uniquement en stock</span>
                </label>
            </div>

            <!-- Clear Filters -->
            <?php if ($selected_category || $search || $max_price < 1000 || $only_stock || $filter_tag): ?>
                <a href="products.php" class="btn btn-outline btn-sm btn-block" style="margin-top: 15px;">
                    <i class="fa-solid fa-rotate-left"></i> Réinitialiser les filtres
                </a>
            <?php endif; ?>
        </form>
    </aside>

    <!-- PRODUCTS GRID CONTENT -->
    <main class="shop-main">
        <!-- TOP TOOLBAR -->
        <div class="shop-toolbar">
            <div class="products-count">
                <strong><?= $total_products ?></strong> <?= $total_products > 1 ? 'produits trouvés' : 'produit trouvé' ?>
            </div>

            <div class="toolbar-actions">
                <button class="mobile-filter-btn" onclick="toggleFilterSidebar()">
                    <i class="fa-solid fa-sliders"></i> Filtrer
                </button>

                <div class="sort-select-wrap">
                    <label for="sortSelect" style="font-size:12px; color:var(--muted);">Trier par :</label>
                    <select id="sortSelect" onchange="applySort(this.value)">
                        <option value="featured" <?= $sort === 'featured' ? 'selected' : '' ?>>Recommandés</option>
                        <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Prix croissant</option>
                        <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Prix décroissant</option>
                        <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Nouveautés</option>
                        <option value="rating" <?= $sort === 'rating' ? 'selected' : '' ?>>Meilleures notes</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- PRODUCTS LIST -->
        <?php if ($total_products > 0): ?>
            <div class="products-grid">
                <?php foreach ($products as $product): ?>
                    <?php
                        $is_fav = is_in_wishlist($product['id']);
                        $product_image = product_image_url($product['main_image'], (string)$product['slug'], (int)$product['id']);
                    ?>
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
                                <img src="<?= htmlspecialchars($product_image) ?>" alt="<?= htmlspecialchars($product['name']) ?>" loading="lazy">
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

                                <a href="cart.php?action=add&id=<?= $product['id'] ?>" class="add-cart-btn" data-id="<?= $product['id'] ?>" title="Ajouter au panier">
                                    <i class="fa-solid fa-bag-shopping"></i>
                                </a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-products-state">
                <i class="fa-solid fa-bag-shopping"></i>
                <h2>Aucun produit trouvé</h2>
                <p>Aucun article ne correspond à vos critères de recherche actuels.</p>
                <a href="products.php" class="btn btn-primary" style="margin-top: 15px;">
                    Voir tous les produits
                </a>
            </div>
        <?php endif; ?>
    </main>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
