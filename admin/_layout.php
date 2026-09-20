<?php
/**
 * HIDA.MAKEUP - Admin shared bootstrap & layout
 *
 * Included by every admin page. Provides:
 *   - the database connection plus the cart / media helpers
 *   - admin_*() helper functions (slug generation, lookups, flash)
 *   - admin_layout_start() / admin_layout_end() for the mobile-first shell
 */

require_once __DIR__ . '/../includes/db.php';

/* ===========================================================================
   Helpers
   =========================================================================== */

/**
 * Turn a product name into a URL-safe slug.
 */
function admin_slugify(string $text): string {
    $text = trim($text);

    // Strip accents where possible (é -> e), then keep it alphanumeric.
    if (function_exists('iconv')) {
        $transliterated = @iconv('UTF-8', 'ASCII//TRANSLIT', $text);
        if ($transliterated !== false) {
            $text = $transliterated;
        }
    }

    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
    $text = trim($text, '-');

    return $text !== '' ? $text : 'produit';
}

/**
 * Build a slug that is not already taken, appending -2, -3 ... when needed.
 *
 * @param int $ignore_id Product being edited, so it keeps its own slug.
 */
function admin_unique_slug(PDO $pdo, string $name, int $ignore_id = 0): string {
    $base = admin_slugify($name);
    $slug = $base;
    $suffix = 1;

    while (true) {
        $stmt = $pdo->prepare("SELECT id FROM products WHERE slug = :slug AND id != :id LIMIT 1");
        $stmt->execute([':slug' => $slug, ':id' => $ignore_id]);

        if (!$stmt->fetch()) {
            return $slug;
        }

        $suffix++;
        $slug = $base . '-' . $suffix;
    }
}

/**
 * Fetch one product, with its category name.
 */
function admin_get_product(PDO $pdo, int $id): ?array {
    if ($id <= 0) {
        return null;
    }

    $stmt = $pdo->prepare("
        SELECT p.*, c.name AS category_name
        FROM products p
        JOIN categories c ON p.category_id = c.id
        WHERE p.id = :id
        LIMIT 1
    ");
    $stmt->execute([':id' => $id]);

    return $stmt->fetch() ?: null;
}

/**
 * All categories, for the product form select.
 */
function admin_categories(PDO $pdo): array {
    return $pdo->query("SELECT id, name FROM categories ORDER BY display_order ASC, id ASC")->fetchAll();
}

/**
 * Read a trimmed POST value.
 */
function admin_post(string $key, string $default = ''): string {
    return isset($_POST[$key]) && is_scalar($_POST[$key]) ? trim((string)$_POST[$key]) : $default;
}

/**
 * Read a trimmed GET value.
 */
function admin_get(string $key, string $default = ''): string {
    return isset($_GET[$key]) && is_scalar($_GET[$key]) ? trim((string)$_GET[$key]) : $default;
}

/**
 * Read a POST decimal. Returns null when empty OR not a number, so callers can
 * tell "nothing entered" from "typo" and reject the typo instead of saving 0.
 */
function admin_post_decimal(string $key): ?float {
    $raw = admin_post($key);

    if ($raw === '') {
        return null;
    }

    // Accept "340,50" as well as "340.50".
    $normalized = str_replace([',', ' '], ['.', ''], $raw);

    return is_numeric($normalized) ? (float)$normalized : null;
}

/* ===========================================================================
   Layout
   =========================================================================== */

/**
 * Open the page: <head>, top bar and the section nav.
 */
function admin_layout_start(string $title, string $active): void {
    $nav = [
        ['index.php', 'fa-gauge-high', 'Tableau de bord'],
        ['products.php', 'fa-box-open', 'Produits'],
        ['index.php#orders', 'fa-receipt', 'Commandes'],
    ];
    ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?= htmlspecialchars($title) ?> — Admin <?= htmlspecialchars(SITE_NAME) ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <!-- Store design tokens, then the admin layout -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body class="adm">

    <header class="adm-topbar">
        <a href="index.php" class="adm-brand">
            <?= htmlspecialchars(SITE_LOGO_PREFIX) ?><span><?= htmlspecialchars(SITE_LOGO_SUFFIX) ?></span>
            <small>ADMIN</small>
        </a>

        <nav class="adm-nav">
            <?php foreach ($nav as [$href, $icon, $label]): ?>
                <a href="<?= htmlspecialchars($href) ?>" class="<?= $active === $label ? 'active' : '' ?>">
                    <i class="fa-solid <?= $icon ?>"></i> <?= htmlspecialchars($label) ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="adm-topbar-actions">
            <a href="../index.php" target="_blank" class="adm-btn is-outline is-small">
                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                <span>Voir le site</span>
            </a>
        </div>
    </header>

    <main class="adm-main">
        <?php admin_flash(); ?>
    <?php
}

/**
 * Render and clear the flash message.
 */
function admin_flash(): void {
    $flash = get_flash();

    if (!$flash) {
        return;
    }

    $icons = [
        'success' => 'fa-circle-check',
        'error'   => 'fa-circle-exclamation',
        'info'    => 'fa-circle-info',
    ];

    $type = in_array($flash['type'], ['success', 'error', 'info'], true) ? $flash['type'] : 'info';
    ?>
    <div class="adm-flash is-<?= $type ?>" role="status">
        <i class="fa-solid <?= $icons[$type] ?>"></i>
        <span><?= htmlspecialchars($flash['message']) ?></span>
    </div>
    <?php
}

/**
 * Close the page and render the phone tab bar.
 */
function admin_layout_end(): void {
    $current = basename($_SERVER['PHP_SELF'] ?? '');
    ?>
    </main>

    <nav class="adm-tabbar">
        <a href="index.php" class="<?= $current === 'index.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-gauge-high"></i> Tableau
        </a>
        <a href="products.php" class="<?= in_array($current, ['products.php', 'product-form.php'], true) ? 'active' : '' ?>">
            <i class="fa-solid fa-box-open"></i> Produits
        </a>
        <a href="index.php#orders">
            <i class="fa-solid fa-receipt"></i> Commandes
        </a>
        <a href="../index.php" target="_blank">
            <i class="fa-solid fa-store"></i> Boutique
        </a>
    </nav>

</body>
</html>
    <?php
}
