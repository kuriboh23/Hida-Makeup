<?php
/**
 * HIDA.MAKEUP - Admin product list
 *
 * Searchable, filterable list. Mobile-first: each product is a card that
 * turns into a table row from tablet width up.
 */

require_once __DIR__ . '/_layout.php';

/* ===========================================================================
   Actions
   =========================================================================== */

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['product_id'])) {
    $product_id = (int)$_POST['product_id'];
    $product = admin_get_product($pdo, $product_id);

    if (!$product) {
        set_flash('error', "Produit introuvable.");
    } elseif (isset($_POST['delete_product'])) {
        // Photos live on disk, so remove them too. Order lines keep their
        // snapshot (order_items.product_id becomes NULL).
        product_image_delete((string)$product['slug'], $product_id);

        $stmt = $pdo->prepare("DELETE FROM products WHERE id = :id");
        $stmt->execute([':id' => $product_id]);

        set_flash('success', "« " . $product['name'] . " » a été supprimé.");
    } elseif (isset($_POST['toggle_stock'])) {
        $stmt = $pdo->prepare("UPDATE products SET in_stock = :in_stock WHERE id = :id");
        $stmt->execute([
            ':in_stock' => (int)$product['in_stock'] === 1 ? 0 : 1,
            ':id'       => $product_id,
        ]);

        set_flash('success', (int)$product['in_stock'] === 1
            ? "« " . $product['name'] . " » est maintenant en rupture."
            : "« " . $product['name'] . " » est de nouveau en stock.");
    } elseif (isset($_POST['toggle_featured'])) {
        $stmt = $pdo->prepare("UPDATE products SET featured = :featured WHERE id = :id");
        $stmt->execute([
            ':featured' => (int)$product['featured'] === 1 ? 0 : 1,
            ':id'       => $product_id,
        ]);

        set_flash('success', (int)$product['featured'] === 1
            ? "« " . $product['name'] . " » retiré des nouveautés."
            : "« " . $product['name'] . " » ajouté aux nouveautés.");
    }

    $back = 'products.php';
    if (!empty($_SERVER['QUERY_STRING'])) {
        $back .= '?' . $_SERVER['QUERY_STRING'];
    }

    header('Location: ' . $back);
    exit;
}

/* ===========================================================================
   Filters
   =========================================================================== */

$search     = admin_get('q');
$category   = admin_get('category');
$photo      = admin_get('photo');
$sort       = admin_get('sort', 'recent');

$categories = admin_categories($pdo);

$where  = [];
$params = [];

if ($search !== '') {
    // Distinct placeholders: PDO with emulated prepares off rejects reuse.
    $where[] = "(p.name LIKE :s1 OR p.slug LIKE :s2 OR p.short_description LIKE :s3)";
    $like = '%' . $search . '%';
    $params[':s1'] = $like;
    $params[':s2'] = $like;
    $params[':s3'] = $like;
}

if ($category !== '' && ctype_digit($category)) {
    $where[] = "p.category_id = :category";
    $params[':category'] = (int)$category;
}

$order_by = match ($sort) {
    'price_asc'  => 'p.price ASC',
    'price_desc' => 'p.price DESC',
    'name'       => 'p.name ASC',
    default      => 'p.id DESC',
};

$sql = "
    SELECT p.*, c.name AS category_name
    FROM products p
    JOIN categories c ON p.category_id = c.id
" . ($where ? 'WHERE ' . implode(' AND ', $where) : '') . "
    ORDER BY $order_by
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// The "photo" filter has to run in PHP: it depends on files on disk.
if ($photo === 'missing') {
    $products = array_values(array_filter($products, fn($p) => !product_has_local_image((string)$p['slug'], (int)$p['id'])));
} elseif ($photo === 'local') {
    $products = array_values(array_filter($products, fn($p) => product_has_local_image((string)$p['slug'], (int)$p['id'])));
}

$total_all     = (int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$without_photo = 0;
foreach ($pdo->query("SELECT id, slug FROM products")->fetchAll() as $row) {
    if (!product_has_local_image((string)$row['slug'], (int)$row['id'])) {
        $without_photo++;
    }
}

$has_filters = $search !== '' || $category !== '' || $photo !== '';

admin_layout_start('Produits', 'Produits');
?>

<div class="adm-head-row">
    <div>
        <h1 class="adm-page-title">Produits</h1>
        <p class="adm-page-sub">
            <?= $total_all ?> produit(s) au total
            <?php if ($without_photo > 0): ?>
                · <strong><?= $without_photo ?></strong> sans photo
            <?php endif; ?>
        </p>
    </div>

    <a href="product-form.php" class="adm-btn is-primary">
        <i class="fa-solid fa-plus"></i> Ajouter
    </a>
</div>

<!-- FILTERS -->
<form method="GET" class="adm-card adm-filters">
    <div class="adm-field" style="margin-bottom:0;">
        <label for="q">Rechercher</label>
        <div class="adm-input-icon">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="search" id="q" name="q" class="adm-input" value="<?= htmlspecialchars($search) ?>"
                   placeholder="Nom, description…" autocomplete="off">
        </div>
    </div>

    <div class="adm-field" style="margin-bottom:0;">
        <label for="category">Catégorie</label>
        <select id="category" name="category" class="adm-select">
            <option value="">Toutes</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= (int)$cat['id'] ?>" <?= $category === (string)$cat['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($cat['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="adm-field" style="margin-bottom:0;">
        <label for="photo">Photo</label>
        <select id="photo" name="photo" class="adm-select">
            <option value="">Peu importe</option>
            <option value="missing" <?= $photo === 'missing' ? 'selected' : '' ?>>Sans photo (<?= $without_photo ?>)</option>
            <option value="local" <?= $photo === 'local' ? 'selected' : '' ?>>Avec photo</option>
        </select>
    </div>

    <div class="adm-field" style="margin-bottom:0;">
        <label for="sort">Trier par</label>
        <select id="sort" name="sort" class="adm-select">
            <option value="recent" <?= $sort === 'recent' ? 'selected' : '' ?>>Plus récents</option>
            <option value="name" <?= $sort === 'name' ? 'selected' : '' ?>>Nom (A → Z)</option>
            <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Prix croissant</option>
            <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Prix décroissant</option>
        </select>
    </div>

    <div class="adm-actions-row">
        <button type="submit" class="adm-btn is-primary">
            <i class="fa-solid fa-filter"></i> Filtrer
        </button>
        <?php if ($has_filters): ?>
            <a href="products.php" class="adm-btn is-outline">
                <i class="fa-solid fa-xmark"></i> Réinitialiser
            </a>
        <?php endif; ?>
    </div>
</form>

<!-- LIST -->
<div class="adm-card">
    <?php if (!$products): ?>
        <div class="adm-empty">
            <i class="fa-solid fa-box-open"></i>
            <?= $has_filters ? "Aucun produit ne correspond à cette recherche." : "Aucun produit pour le moment." ?>
        </div>
    <?php else: ?>
        <div class="adm-table-wrap">
            <table class="adm-table">
                <thead>
                    <tr>
                        <th>Produit</th>
                        <th>Catégorie</th>
                        <th>Prix</th>
                        <th>Stock</th>
                        <th>Photo</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $p): ?>
                        <?php
                        $pid      = (int)$p['id'];
                        $in_stock = (int)$p['in_stock'] === 1;
                        $is_local = product_has_local_image((string)$p['slug'], $pid);
                        ?>
                        <tr>
                            <td class="adm-cell-main">
                                <img src="<?= htmlspecialchars(product_image_url($p['main_image'] ?? null, (string)$p['slug'], $pid)) ?>"
                                     alt="" class="adm-thumb" loading="lazy">

                                <div style="min-width:0;">
                                    <div class="adm-row-title"><?= htmlspecialchars($p['name']) ?></div>
                                    <div class="adm-row-meta"><?= format_price($p['price']) ?></div>
                                </div>
                            </td>

                            <td data-label="Catégorie"><?= htmlspecialchars($p['category_name']) ?></td>

                            <td data-label="Prix">
                                <strong><?= format_price($p['price']) ?></strong>
                                <?php if (!empty($p['original_price']) && (float)$p['original_price'] > (float)$p['price']): ?>
                                    <div class="adm-row-meta"><s><?= format_price($p['original_price']) ?></s></div>
                                <?php endif; ?>
                            </td>

                            <td data-label="Stock">
                                <span class="adm-badge <?= $in_stock ? 'is-delivered' : 'is-cancelled' ?>">
                                    <?= $in_stock ? 'En stock' : 'Rupture' ?>
                                </span>
                            </td>

                            <td data-label="Photo">
                                <span class="adm-badge <?= $is_local ? 'is-local' : 'is-external' ?>">
                                    <i class="fa-solid <?= $is_local ? 'fa-image' : 'fa-link' ?>"></i>
                                    <?= $is_local ? 'Locale' : 'URL' ?>
                                </span>
                            </td>

                            <td class="adm-cell-block" data-label="Actions">
                                <div class="adm-actions-row">
                                    <a href="product-form.php?id=<?= $pid ?>" class="adm-btn is-primary is-small">
                                        <i class="fa-solid fa-pen"></i> Modifier
                                    </a>

                                    <form method="POST" style="display:inline-flex;">
                                        <input type="hidden" name="product_id" value="<?= $pid ?>">
                                        <div class="adm-actions-row">
                                            <button type="submit" name="toggle_stock" value="1"
                                                    class="adm-btn is-outline is-small">
                                                <i class="fa-solid <?= $in_stock ? 'fa-eye-slash' : 'fa-eye' ?>"></i>
                                                <?= $in_stock ? 'Rupture' : 'En stock' ?>
                                            </button>

                                            <button type="submit" name="toggle_featured" value="1"
                                                    class="adm-btn is-outline is-small">
                                                <i class="fa-solid fa-star"></i>
                                                <?= (int)$p['featured'] === 1 ? 'Retirer' : 'Nouveauté' ?>
                                            </button>

                                            <button type="submit" name="delete_product" value="1"
                                                    class="adm-btn is-danger is-small"
                                                    onclick="return confirm('Supprimer « <?= htmlspecialchars($p['name'], ENT_QUOTES) ?> » ? Cette action est définitive.');">
                                                <i class="fa-solid fa-trash"></i> Supprimer
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php admin_layout_end(); ?>
