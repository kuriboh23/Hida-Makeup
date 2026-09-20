<?php
/**
 * HIDA.MAKEUP - Central Configuration & Brand Settings
 * 
 * Customize this file to rebrand the entire store in minutes.
 * Contains business settings, contact info, e-commerce variables, and helper functions.
 */

// 1. Session initialization
// Sessions are only used for the small "flash" notices (added to cart, etc.).
// The cart and the favorites list live in cookies - see includes/store.php.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Brand & Store Information
define('SITE_NAME', 'HIDA.MAKEUP');
define('SITE_LOGO_PREFIX', 'HIDA');
define('SITE_LOGO_SUFFIX', '.MAKEUP');
define('SITE_TAGLINE', 'Makeup & Cosmetics au Maroc');
define('SITE_DESCRIPTION', 'Votre destination beauté au Maroc. Découvrez notre sélection exclusive de maquillage, skincare, parfums et soins authentiques.');
define('SITE_FOOTER_ABOUT', 'Votre destination beauté au Maroc. Maquillage, skincare, parfums et essentiels sélectionnés pour vous avec amour.');

// 3. Contact & Location
define('STORE_PHONE', '+212 620 28 37 25');
define('STORE_WHATSAPP', '212620283725');
define('STORE_WHATSAPP_LINK', 'https://wa.me/212620283725');
define('STORE_EMAIL', 'contact@hidamakeup.ma');
define('STORE_CITY', 'Mohammedia');
define('STORE_ADDRESS', 'Mohammedia, Maroc');
define('STORE_HOURS', 'WhatsApp disponible 7j/7');

// 4. Social Links
define('SOCIAL_INSTAGRAM', 'https://instagram.com/hida.makeup');
define('SOCIAL_FACEBOOK', 'https://facebook.com/hida.makeup');
define('SOCIAL_TIKTOK', 'https://tiktok.com/@hida.makeup');

// 5. Top Announcement Bar
define('TOPBAR_ENABLED', true);
define('TOPBAR_TEXT', 'LIVRAISON PARTOUT AU MAROC | PAIEMENT À LA LIVRAISON');

// 6. E-Commerce & Currency Settings
define('CURRENCY_SYMBOL', 'DH');
define('CURRENCY_POSITION', 'after'); // 'after' => 250 DH, 'before' => DH 250
define('DEFAULT_SHIPPING_FEE', 35.00);
define('FREE_SHIPPING_THRESHOLD', 350.00); // Free shipping if cart >= 350 DH

// Secret used to sign the cart / favorites cookies. Replace it with your own
// long random string before going live: it is what stops a visitor from
// forging the contents of their basket.
define('STORE_COOKIE_SECRET', 'change-me-to-a-long-random-string');

// 7. Database Credentials
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'hida_makeup');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// 8. Base URL & Path helpers (dynamically handles root or subfolders in htdocs)
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$rootSubfolder = preg_replace('#/(includes|admin).*$#', '', $scriptDir);
$cleanSub = trim($rootSubfolder, '/');
define('BASE_PATH', $cleanSub ? '/' . $cleanSub . '/' : '/');

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
define('BASE_URL', rtrim($protocol . $host, '/') . BASE_PATH);

// 9. Global Helper Functions
// Cart & favorites helpers live in includes/store.php (cookie based storage).

/**
 * Format price with currency symbol
 */
function format_price(float|int $price): string {
    $formatted = number_format($price, 2, '.', ' ');
    // Remove .00 if whole number for cleaner look
    if (str_ends_with($formatted, '.00')) {
        $formatted = substr($formatted, 0, -3);
    }
    return CURRENCY_POSITION === 'after' ? $formatted . ' ' . CURRENCY_SYMBOL : CURRENCY_SYMBOL . ' ' . $formatted;
}

/**
 * Clean user input
 */
function sanitize(mixed $data): string {
    return htmlspecialchars(trim((string)$data), ENT_QUOTES, 'UTF-8');
}

/**
 * Calculate shipping fee based on threshold
 */
function calculate_shipping(float $subtotal): float {
    if ($subtotal <= 0) {
        return 0.0;
    }
    if ($subtotal >= FREE_SHIPPING_THRESHOLD) {
        return 0.0;
    }
    return DEFAULT_SHIPPING_FEE;
}

/**
 * Build the WhatsApp confirmation link for a placed order.
 *
 * @param array $order  Expects: order_number, customer_name, customer_phone,
 *                      customer_city, customer_address, notes, subtotal,
 *                      shipping_fee, total and items.
 */
function whatsapp_order_url(array $order): string {
    $items    = is_array($order['items'] ?? null) ? $order['items'] : [];
    $shipping = (float)($order['shipping_fee'] ?? 0);

    $lines = [];
    $lines[] = 'Bonjour *' . SITE_NAME . '* 👋';
    $lines[] = '';
    $lines[] = 'Je confirme ma commande *' . ($order['order_number'] ?? '') . '*';
    $lines[] = '';

    if ($items) {
        $lines[] = '🛍️ *Articles commandés*';
        foreach ($items as $item) {
            $qty        = (int)($item['quantity'] ?? 1);
            $line_total = (float)($item['price'] ?? 0) * $qty;
            $lines[]    = '• ' . ($item['name'] ?? 'Article') . ' × ' . $qty . ' — ' . format_price($line_total);
        }
        $lines[] = '';
    }

    $lines[] = '💵 *Sous-total :* ' . format_price($order['subtotal'] ?? 0);
    $lines[] = '🚚 *Livraison :* ' . ($shipping > 0 ? format_price($shipping) : 'Offerte');
    $lines[] = '✅ *Total à payer :* ' . format_price($order['total'] ?? 0);
    $lines[] = '';
    $lines[] = '👤 *Nom :* ' . ($order['customer_name'] ?? '');
    $lines[] = '📞 *Téléphone :* ' . ($order['customer_phone'] ?? '');
    $lines[] = '📍 *Ville :* ' . ($order['customer_city'] ?? '');

    if (!empty($order['customer_address'])) {
        $lines[] = '🏠 *Adresse :* ' . $order['customer_address'];
    }
    if (!empty($order['notes'])) {
        $lines[] = '📝 *Note :* ' . $order['notes'];
    }

    $lines[] = '';
    $lines[] = '💳 Paiement en espèces à la livraison.';
    $lines[] = 'Merci de me confirmer la livraison !';

    $message = implode("\n", $lines);
    return 'https://wa.me/' . STORE_WHATSAPP . '?text=' . urlencode($message);
}

/**
 * Generate a WhatsApp inquiry link for a single product
 */
function whatsapp_product_url(string $product_name, float $price, string $url = ''): string {
    $lines = [];
    $lines[] = 'Bonjour *' . SITE_NAME . '* 👋';
    $lines[] = 'Je suis intéressé(e) par ce produit :';
    $lines[] = '';
    $lines[] = '🛍️ *' . $product_name . '*';
    $lines[] = '💰 *Prix :* ' . format_price($price);
    if ($url) {
        $lines[] = '🔗 ' . $url;
    }
    $lines[] = '';
    $lines[] = 'Est-il disponible ? Merci !';

    return 'https://wa.me/' . STORE_WHATSAPP . '?text=' . urlencode(implode("\n", $lines));
}

/**
 * Set flash alert message
 */
function set_flash(string $type, string $message): void {
    $_SESSION['flash'] = [
        'type' => $type, // 'success', 'error', 'info'
        'message' => $message
    ];
}

/**
 * Get and clear flash message
 */
function get_flash(): ?array {
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}
