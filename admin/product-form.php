<?php
/**
 * HIDA.MAKEUP - Admin product create / edit
 *
 * Mobile-first form: one column on a phone, two or three from tablet up.
 * Photo slots live in their own small forms (never nested) so each upload or
 * removal is a separate, simple round trip.
 */

require_once __DIR__ . '/_layout.php';

$id      = (int)admin_get('id');
$is_edit = $id > 0;
$product = $is_edit ? admin_get_product($pdo, $id) : null;

if ($is_edit && !$product) {
    set_flash('error', "Produit introuvable.");
    header('Location: products.php');
    exit;
}

$errors = [];

/* ===========================================================================
   Photo slot actions — each slot posts its own form
   =========================================================================== */

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && $is_edit && isset($_POST['slot'])) {
    $slug = (string)$product['slug'];
    $slot = (int)$_POST['slot'];
    $slot = ($slot >= 0 && $slot <= PRODUCT_IMAGE_GALLERY_SLOTS) ? $slot : 0;
    $label = $slot === 0 ? "photo principale" : "photo " . $slot;

    if (isset($_POST['remove_photo'])) {
        $deleted = product_image_delete($slug, $id, $slot);

        set_flash($deleted > 0 ? 'success' : 'info', $deleted > 0
            ? "La $label a été retirée."
            : "Aucune $label à retirer.");
    } else {
        [$stored, $error] = product_image_upload($_FILES['photo'] ?? [], $slug, $id, $slot);

        if ($error !== null) {
            set_flash('error', $error);
        } elseif ($stored !== null) {
            set_flash('success', ucfirst($label) . " a bien été enregistrée.");
        } else {
            set_flash('info', "Aucun fichier sélectionné.");
        }
    }

    header('Location: product-form.php?id=' . $id . '#photos');
    exit;
}

/* ===========================================================================
   Save
   =========================================================================== */

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['save_product'])) {
    $name        = admin_post('name');
    $slug_input  = admin_post('slug');
    $category_id = (int)admin_post('category_id');
    $short_desc  = admin_post('short_description');
    $description = admin_post('description');
    $price       = admin_post_decimal('price');
    $original    = admin_post_decimal('original_price');
    $rating      = admin_post_decimal('rating');
    $reviews     = (int)admin_post('reviews_count');
    $badge       = admin_post('badge');
    $stock_qty   = (int)admin_post('stock_quantity');
    $main_image  = admin_post('main_image');
    $in_stock    = isset($_POST['in_stock']) ? 1 : 0;
    $featured    = isset($_POST['featured']) ? 1 : 0;
    $best_seller = isset($_POST['best_seller']) ? 1 : 0;

    // --- validation ---
    if (mb_strlen($name) < 2) {
        $errors[] = "Le nom du produit est obligatoire.";
    }

    if ($price === null || $price < 0) {
        $errors[] = "Le prix est obligatoire et doit être un nombre positif.";
    }

    if ($original !== null && $original < 0) {
        $errors[] = "Le prix barré doit être un nombre positif.";
    }

    $category_ids = array_map(fn($c) => (int)$c['id'], admin_categories($pdo));
    if (!in_array($category_id, $category_ids, true)) {
        $errors[] = "Choisissez une catégorie.";
    }

    if ($original !== null && $price !== null && $original > 0 && $original < $price) {
        $errors[] = "Le prix barré doit être supérieur au prix de vente.";
    }

    if ($rating === null) {
        $rating = 5.0;
    }
    $rating = max(0.0, min(5.0, $rating));

    if ($reviews < 0) {
        $reviews = 0;
    }

    if ($stock_qty < 0) {
        $stock_qty = 0;
    }

    if (mb_strlen($short_desc) > 300) {
        $short_desc = mb_substr($short_desc, 0, 300);
    }

    // --- gallery URLs: one per line, http(s) only ---
    $gallery = [];
    foreach (preg_split('/\r\n|\r|\n/', admin_post('gallery_images')) ?: [] as $line) {
        $line = trim($line);

        if ($line === '') {
            continue;
        }

        if (preg_match('#^https?://#i', $line)) {
            $gallery[] = $line;
        }
    }
    $gallery = array_values(array_slice(array_unique($gallery), 0, PRODUCT_IMAGE_GALLERY_SLOTS));

    // Errors are rendered inline below, keeping what was typed. No flash there:
    // that would show the same message twice on one page load.
    if (!$errors) {
        $slug = $slug_input !== '' ? admin_slugify($slug_input) : admin_slugify($name);
        $slug = admin_unique_slug($pdo, $slug, $is_edit ? $id : 0);

        // Keep previously uploaded photos pointing at the new slug.
        if ($is_edit && $slug !== (string)$product['slug']) {
            for ($slot = 0; $slot <= PRODUCT_IMAGE_GALLERY_SLOTS; $slot++) {
                $existing = product_image_find((string)$product['slug'], $id, $slot);

                if ($existing === null) {
                    continue;
                }

                $target = product_image_key($slug, $id) . ($slot > 0 ? '-' . $slot : '') . '.' . pathinfo($existing, PATHINFO_EXTENSION);
                @rename(product_image_path() . '/' . $existing, product_image_path() . '/' . $target);
            }
            product_image_flush();
        }

        $fields = [
            ':category_id'       => $category_id,
            ':name'              => $name,
            ':slug'              => $slug,
            ':short_description' => $short_desc !== '' ? $short_desc : null,
            ':description'       => $description !== '' ? $description : null,
            ':price'             => $price,
            ':original_price'    => ($original !== null && $original > 0) ? $original : null,
            ':rating'            => $rating,
            ':reviews_count'     => $reviews,
            ':badge'             => $badge !== '' ? $badge : null,
            ':in_stock'          => $in_stock,
            ':stock_quantity'    => $stock_qty,
            ':main_image'        => $main_image,
            ':gallery_images'    => $gallery ? json_encode($gallery, JSON_UNESCAPED_SLASHES) : null,
            ':featured'          => $featured,
            ':best_seller'       => $best_seller,
        ];

        if ($is_edit) {
            $fields[':id'] = $id;

            $sql = "
                UPDATE products SET
                    category_id = :category_id,
                    name = :name,
                    slug = :slug,
                    short_description = :short_description,
                    description = :description,
                    price = :price,
                    original_price = :original_price,
                    rating = :rating,
                    reviews_count = :reviews_count,
                    badge = :badge,
                    in_stock = :in_stock,
                    stock_quantity = :stock_quantity,
                    main_image = :main_image,
                    gallery_images = :gallery_images,
                    featured = :featured,
                    best_seller = :best_seller
                WHERE id = :id
            ";
        } else {
            $sql = "
                INSERT INTO products (
                    category_id, name, slug, short_description, description, price,
                    original_price, rating, reviews_count, badge, in_stock,
                    stock_quantity, main_image, gallery_images, featured, best_seller
                ) VALUES (
                    :category_id, :name, :slug, :short_description, :description, :price,
                    :original_price, :rating, :reviews_count, :badge, :in_stock,
                    :stock_quantity, :main_image, :gallery_images, :featured, :best_seller
                )
            ";
        }

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($fields);

            if (!$is_edit) {
                $id = (int)$pdo->lastInsertId();
                set_flash('success', "« $name » a été créé. Ajoutez sa photo ci-dessous.");
                header('Location: product-form.php?id=' . $id . '#photos');
                exit;
            }

            set_flash('success', "« $name » a bien été mis à jour.");
            header('Location: products.php');
            exit;
        } catch (PDOException $e) {
            set_flash('error', "Enregistrement impossible. Vérifiez que le nom du produit est unique.");
        }
    }
}

/* ===========================================================================
   Delete (edit mode only)
   =========================================================================== */

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && $is_edit && isset($_POST['delete_product'])) {
    product_image_delete((string)$product['slug'], $id);

    $stmt = $pdo->prepare("DELETE FROM products WHERE id = :id");
    $stmt->execute([':id' => $id]);

    set_flash('success', "« " . $product['name'] . " » a été supprimé.");
    header('Location: products.php');
    exit;
}

/* ===========================================================================
   Form values
   =========================================================================== */

$submitted = ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['save_product']);

$val = function (string $key, $fallback = '') use ($submitted, $product) {
    if ($submitted) {
        $raw = $_POST[$key] ?? '';
        return is_scalar($raw) ? trim((string)$raw) : '';
    }

    return $product[$key] ?? $fallback;
};

$form = [
    'name'              => (string)$val('name'),
    'slug'              => (string)$val('slug'),
    'category_id'       => (string)$val('category_id', ''),
    'short_description' => (string)$val('short_description'),
    'description'       => (string)$val('description'),
    'price'             => (string)$val('price'),
    'original_price'    => (string)$val('original_price'),
    'rating'            => (string)$val('rating', '5.0'),
    'reviews_count'     => (string)$val('reviews_count', '0'),
    'badge'             => (string)$val('badge'),
    'stock_quantity'    => (string)$val('stock_quantity', '50'),
    'main_image'        => (string)$val('main_image'),
];

if ($submitted) {
    $form['in_stock']    = isset($_POST['in_stock']) ? 1 : 0;
    $form['featured']    = isset($_POST['featured']) ? 1 : 0;
    $form['best_seller'] = isset($_POST['best_seller']) ? 1 : 0;
    $form['gallery_text'] = admin_post('gallery_images');
} else {
    $form['in_stock']    = (int)($product['in_stock'] ?? 1) === 1 ? 1 : 0;
    $form['featured']    = (int)($product['featured'] ?? 0) === 1 ? 1 : 0;
    $form['best_seller'] = (int)($product['best_seller'] ?? 0) === 1 ? 1 : 0;

    $decoded = json_decode((string)($product['gallery_images'] ?? ''), true);
    $form['gallery_text'] = is_array($decoded) ? implode("\n", array_filter($decoded, 'is_string')) : '';
}

if ($form['category_id'] === '' && !$is_edit) {
    $first = admin_categories($pdo);
    $form['category_id'] = $first ? (string)$first[0]['id'] : '';
}

$categories = admin_categories($pdo);

admin_layout_start($is_edit ? 'Modifier un produit' : 'Nouveau produit', 'Produits');
?>

<form method="POST" action="product-form.php<?= $is_edit ? '?id=' . $id : '' ?>">
    <h1 class="adm-page-title"><?= $is_edit ? 'Modifier le produit' : 'Nouveau produit' ?></h1>
    <p class="adm-page-sub">
        <?= $is_edit
            ? htmlspecialchars((string)$product['name'])
            : "Remplissez les informations, puis enregistrez pour ajouter les photos." ?>
    </p>

    <?php if ($errors): ?>
        <div class="adm-flash is-error">
            <i class="fa-solid fa-circle-exclamation"></i>
            <span><?= htmlspecialchars(implode(' ', $errors)) ?></span>
        </div>
    <?php endif; ?>

    <!-- 1. ESSENTIEL -->
    <div class="adm-card">
        <div class="adm-card-head">
            <div>
                <h2>Informations essentielles</h2>
                <p>Ce que la cliente voit en premier.</p>
            </div>
        </div>

        <div class="adm-field">
            <label for="name">Nom du produit <span style="color:var(--red);">*</span></label>
            <input type="text" id="name" name="name" class="adm-input" required
                   value="<?= htmlspecialchars($form['name']) ?>" placeholder="Fond de Teint HD Skin">
        </div>

        <div class="adm-field-row is-three">
            <div class="adm-field">
                <label for="category_id">Catégorie <span style="color:var(--red);">*</span></label>
                <select id="category_id" name="category_id" class="adm-select" required>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= (int)$cat['id'] ?>" <?= $form['category_id'] === (string)$cat['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="adm-field">
                <label for="price">Prix (DH) <span style="color:var(--red);">*</span></label>
                <input type="text" inputmode="decimal" id="price" name="price" class="adm-input" required
                       value="<?= htmlspecialchars($form['price']) ?>" placeholder="340">
            </div>

            <div class="adm-field">
                <label for="original_price">Prix barré (DH)</label>
                <input type="text" inputmode="decimal" id="original_price" name="original_price" class="adm-input"
                       value="<?= htmlspecialchars($form['original_price']) ?>" placeholder="420">
                <span class="adm-hint">Laissez vide s'il n'y a pas de promotion.</span>
            </div>
        </div>

        <div class="adm-field">
            <label for="short_description">Description courte</label>
            <input type="text" id="short_description" name="short_description" class="adm-input" maxlength="300"
                   value="<?= htmlspecialchars($form['short_description']) ?>"
                   placeholder="Couverture totale, tenue 12 h, fini naturel.">
            <span class="adm-hint">Une phrase. Affichée sous le nom dans la boutique.</span>
        </div>

        <div class="adm-field">
            <label for="description">Description complète</label>
            <textarea id="description" name="description" class="adm-textarea"
                      placeholder="Description détaillée affichée sur la page du produit…"><?= htmlspecialchars($form['description']) ?></textarea>
        </div>
    </div>

    <!-- 2. STOCK & BADGES -->
    <div class="adm-card">
        <div class="adm-card-head">
            <div>
                <h2>Stock et mises en avant</h2>
                <p>Touchez un interrupteur pour l'activer.</p>
            </div>
        </div>

        <div class="adm-field-row">
            <div class="adm-field">
                <label for="stock_quantity">Quantité en stock</label>
                <input type="number" inputmode="numeric" min="0" id="stock_quantity" name="stock_quantity"
                       class="adm-input" value="<?= htmlspecialchars($form['stock_quantity']) ?>">
            </div>

            <div class="adm-field">
                <label for="badge">Étiquette</label>
                <input type="text" id="badge" name="badge" class="adm-input" maxlength="50"
                       value="<?= htmlspecialchars($form['badge']) ?>" placeholder="Nouveau, -20%, Best-seller">
                <span class="adm-hint">Petite pastille affichée sur la photo.</span>
            </div>
        </div>

        <div class="adm-field-row is-three">
            <div class="adm-field">
                <label for="rating">Note (0 à 5)</label>
                <input type="text" inputmode="decimal" id="rating" name="rating" class="adm-input"
                       value="<?= htmlspecialchars($form['rating']) ?>">
            </div>

            <div class="adm-field">
                <label for="reviews_count">Nombre d'avis</label>
                <input type="number" inputmode="numeric" min="0" id="reviews_count" name="reviews_count"
                       class="adm-input" value="<?= htmlspecialchars($form['reviews_count']) ?>">
            </div>

            <div class="adm-field">
                <label for="slug">Lien (avancé)</label>
                <input type="text" id="slug" name="slug" class="adm-input"
                       value="<?= htmlspecialchars($form['slug']) ?>" placeholder="généré automatiquement">
                <span class="adm-hint">Modifiable, mais évitez de le changer.</span>
            </div>
        </div>

        <label class="adm-switch" for="in_stock" style="margin-bottom:10px;">
            <span class="adm-switch-text">
                <strong>En stock</strong>
                <span>Désactivez pour afficher « Rupture de stock ».</span>
            </span>
            <input type="checkbox" id="in_stock" name="in_stock" value="1" <?= $form['in_stock'] ? 'checked' : '' ?>>
        </label>

        <label class="adm-switch" for="featured" style="margin-bottom:10px;">
            <span class="adm-switch-text">
                <strong>Nouveauté</strong>
                <span>Met le produit en avant sur la page d'accueil.</span>
            </span>
            <input type="checkbox" id="featured" name="featured" value="1" <?= $form['featured'] ? 'checked' : '' ?>>
        </label>

        <label class="adm-switch" for="best_seller">
            <span class="adm-switch-text">
                <strong>Best-seller</strong>
                <span>Apparaît dans le filtre « Best-sellers ».</span>
            </span>
            <input type="checkbox" id="best_seller" name="best_seller" value="1" <?= $form['best_seller'] ? 'checked' : '' ?>>
        </label>
    </div>

    <!-- 3. IMAGES EXTERNES -->
    <div class="adm-card">
        <div class="adm-card-head">
            <div>
                <h2>Photos par lien (optionnel)</h2>
                <p>Utilisez plutôt vos propres photos ci-dessous : elles sont plus rapides à charger.</p>
            </div>
        </div>

        <div class="adm-field">
            <label for="main_image">Lien de la photo principale</label>
            <input type="text" id="main_image" name="main_image" class="adm-input"
                   value="<?= htmlspecialchars($form['main_image']) ?>" placeholder="https://…jpg">
            <?php if ($is_edit && product_has_local_image((string)$product['slug'], $id)): ?>
                <span class="adm-hint">
                    <i class="fa-solid fa-circle-info"></i>
                    Une photo téléversée est en place : elle remplace ce lien.
                </span>
            <?php endif; ?>
        </div>

        <div class="adm-field" style="margin-bottom:0;">
            <label for="gallery_images">Photos de la galerie (un lien par ligne)</label>
            <textarea id="gallery_images" name="gallery_images" class="adm-textarea"
                      placeholder="https://…-2.jpg&#10;https://…-3.jpg"><?= htmlspecialchars($form['gallery_text']) ?></textarea>
        </div>
    </div>

    <div class="adm-savebar">
        <button type="submit" name="save_product" value="1" class="adm-btn is-primary">
            <i class="fa-solid fa-floppy-disk"></i> Enregistrer
        </button>
        <a href="products.php" class="adm-btn is-outline">Annuler</a>
    </div>
</form>

<?php if ($is_edit): ?>

<!-- 4. PHOTOS -->
<div class="adm-card" id="photos">
    <div class="adm-card-head">
        <div>
            <h2>Photos du produit</h2>
            <p>Depuis votre téléphone : touchez une case, choisissez une photo, puis « Envoyer ».</p>
        </div>
    </div>

    <div class="adm-photo-grid">
        <?php for ($slot = 0; $slot <= PRODUCT_IMAGE_GALLERY_SLOTS; $slot++): ?>
            <?php
            $existing = product_image_find((string)$product['slug'], $id, $slot);
            $label    = $slot === 0 ? 'Principale' : 'Photo ' . $slot;
            ?>
            <div class="adm-photo-slot <?= $existing === null ? 'is-empty' : '' ?>">
                <div class="adm-photo-slot-head">
                    <strong><?= $label ?></strong>
                    <span class="adm-badge <?= $existing !== null ? 'is-local' : 'is-muted' ?>">
                        <?= $existing !== null ? 'OK' : 'Vide' ?>
                    </span>
                </div>

                <?php if ($existing !== null): ?>
                    <img src="<?= htmlspecialchars(product_image_url_for($existing)) ?>" alt="" class="adm-photo-preview">
                <?php else: ?>
                    <div class="adm-photo-preview"></div>
                <?php endif; ?>

                <form method="POST" action="product-form.php?id=<?= $id ?>#photos" enctype="multipart/form-data">
                    <input type="hidden" name="slot" value="<?= $slot ?>">
                    <input type="file" name="photo" class="adm-input" accept="image/*" required>
                    <button type="submit" class="adm-btn is-primary is-block" style="margin-top:8px;">
                        <i class="fa-solid fa-upload"></i>
                        <?= $existing !== null ? 'Remplacer' : 'Envoyer' ?>
                    </button>
                </form>

                <?php if ($existing !== null): ?>
                    <form method="POST" action="product-form.php?id=<?= $id ?>#photos"
                          onsubmit="return confirm('Retirer la <?= strtolower($label === 'Principale' ? 'photo principale' : $label) ?> ?');">
                        <input type="hidden" name="slot" value="<?= $slot ?>">
                        <button type="submit" name="remove_photo" value="1" class="adm-photo-remove">
                            <i class="fa-solid fa-trash"></i> Retirer
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endfor; ?>
    </div>

    <p class="adm-hint" style="margin-top:14px;">
        JPG, PNG, WebP ou GIF · 3 Mo maximum · 200 × 200 px minimum.
        La photo principale remplace le lien externe indiqué plus haut.
    </p>
</div>

<!-- 5. DANGER ZONE -->
<div class="adm-card">
    <div class="adm-card-head">
        <div>
            <h2>Supprimer</h2>
            <p>Le produit disparaît du site. Les commandes passées restent intactes.</p>
        </div>
    </div>

    <form method="POST" action="product-form.php?id=<?= $id ?>"
          onsubmit="return confirm('Supprimer définitivement « <?= htmlspecialchars((string)$product['name'], ENT_QUOTES) ?> » ?');">
        <button type="submit" name="delete_product" value="1" class="adm-btn is-danger is-block">
            <i class="fa-solid fa-trash"></i> Supprimer ce produit
        </button>
    </form>
</div>

<?php endif; ?>

<?php admin_layout_end(); ?>
