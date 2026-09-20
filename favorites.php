<?php
/**
 * HIDA.MAKEUP - Favorites / Wishlist Page
 * Handles toggling, removing, and viewing favorite products saved in session.
 */

require_once __DIR__ . '/includes/db.php';

$action = isset($_GET['action']) ? trim($_GET['action']) : '';
$product_id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;
$is_ajax = isset($_GET['ajax']) && $_GET['ajax'] == '1';

// Handle Toggle Action (Add/Remove from wishlist)
if ($action === 'toggle' && $product_id > 0) {
    $added = false;

    if (!isset($_SESSION['favorites'])) {
        $_SESSION['favorites'] = [];
    }

    $key = array_search($product_id, $_SESSION['favorites'], true);
    if ($key !== false) {
        // Remove
        unset($_SESSION['favorites'][$key]);
        $_SESSION['favorites'] = array_values($_SESSION['favorites']);
        $added = false;
        set_flash('info', "Produit retiré de vos favoris.");
    } else {
        // Add
        $_SESSION['favorites'][] = $product_id;
        $added = true;
        set_flash('success', "Produit ajouté à vos favoris !");
    }

    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'added' => $added,
            'total_count' => count($_SESSION['favorites'])
        ]);
        exit;
    }

    $redirect = $_SERVER['HTTP_REFERER'] ?? 'favorites.php';
    header("Location: $redirect");
    exit;
}

if ($action === 'clear') {
    $_SESSION['favorites'] = [];
    set_flash('info', "Votre liste de favoris a été vidée.");
    header("Location: favorites.php");
    exit;
}

// Fetch favorite products from DB
$fav_ids = $_SESSION['favorites'] ?? [];
$favorite_products = [];

if (!empty($fav_ids)) {
    $placeholders = implode(',', array_fill(0, count($fav_ids), '?'));
    $stmt = $pdo->prepare("
        SELECT p.*, c.name AS category_name, c.slug AS category_slug 
        FROM products p 
        JOIN categories c ON p.category_id = c.id 
        WHERE p.id IN ($placeholders)
        ORDER BY p.id DESC
    ");
    $stmt->execute($fav_ids);
    $favorite_products = $stmt->fetchAll();
}

$page_title = "Mes Favoris";
$page_description = "Retrouvez vos coups de cœur et vos essentiels beauté enregistrés.";

require_once __DIR__ . '/includes/header.php';
?>

<!-- WISHLIST HERO -->
<section class="wishlist-hero">
    <div class="container">
        <div class="breadcrumb" style="justify-content:center;">
            <a href="index.php">Accueil</a>
            <span>/</span>
            <a href="products.php">Boutique</a>
            <span>/</span>
            <strong>Mes Favoris</strong>
        </div>
        <h1>Mes Coups de Cœur</h1>
        <p class="wishlist-subtitle">Retrouvez tous les produits que vous avez enregistrés pour votre future commande.</p>
    </div>
</section>

<div class="container section">
    <?php if (!empty($favorite_products)): ?>
        
        <div class="wishlist-toolbar">
            <div class="wishlist-count-text">
                <strong><?= count($favorite_products) ?></strong> <?= count($favorite_products) > 1 ? 'articles enregistrés' : 'article enregistré' ?>
            </div>
            <a href="favorites.php?action=clear" class="btn btn-outline btn-sm" onclick="return confirm('Voulez-vous vraiment vider tous vos favoris ?');">
                <i class="fa-solid fa-trash-can"></i> Vider la liste
            </a>
        </div>

        <div class="products-grid">
            <?php foreach ($favorite_products as $product): ?>
                <article class="product-card">
                    <div class="product-img-wrap">
                        <?php if (!empty($product['badge'])): ?>
                            <span class="product-badge"><?= htmlspecialchars($product['badge']) ?></span>
                        <?php endif; ?>

                        <a href="favorites.php?action=toggle&id=<?= $product['id'] ?>" class="product-fav-btn active" title="Retirer des favoris">
                            <i class="fa-solid fa-heart"></i>
                        </a>

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
        
        <!-- EMPTY WISHLIST STATE -->
        <div class="empty-wishlist-card">
            <div class="empty-fav-icon">
                <i class="fa-regular fa-heart"></i>
            </div>
            <h2>Votre liste de favoris est vide</h2>
            <p>
                Vous n'avez pas encore de produits sauvegardés. Cliquez sur l'icône en forme de cœur sur vos articles préférés pour les retrouver ici en un clin d'œil.
            </p>
            <div style="margin-top: 25px;">
                <a href="products.php" class="btn btn-primary btn-lg">
                    Découvrir notre boutique <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>
        </div>

    <?php endif; ?>
</div>

<?php
$extra_css = '
.wishlist-hero { background: linear-gradient(135deg, var(--rose) 0%, #fef8f6 100%); padding: 45px 0 35px; text-align: center; border-bottom: 1px solid var(--border); }
.wishlist-hero h1 { font-family: var(--font-heading); font-size: clamp(28px, 4vw, 38px); margin-bottom: 8px; color: var(--burgundy-dark); }
.wishlist-subtitle { font-size: 14px; color: var(--muted); max-width: 500px; margin: 0 auto; }

.wishlist-toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; padding-bottom: 15px; border-bottom: 1px solid var(--border); }
.wishlist-count-text { font-size: 14px; color: var(--muted); }

.empty-wishlist-card { max-width: 550px; margin: 40px auto; padding: 60px 30px; background: var(--white); border: 1px solid var(--border); border-radius: var(--radius-lg); text-align: center; box-shadow: var(--shadow-sm); }
.empty-fav-icon { width: 80px; height: 80px; border-radius: 50%; background: var(--rose); color: var(--burgundy); display: grid; place-items: center; font-size: 32px; margin: 0 auto 20px; }
.empty-wishlist-card h2 { font-family: var(--font-heading); font-size: 26px; margin-bottom: 12px; }
.empty-wishlist-card p { font-size: 14px; color: var(--muted); line-height: 1.7; }
';

require_once __DIR__ . '/includes/footer.php';
