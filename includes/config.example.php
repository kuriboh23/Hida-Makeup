<?php
/**
 * HIDA.MAKEUP - Central Configuration & Brand Settings
 * 
 * Customize this file to rebrand the entire store in minutes.
 * Contains business settings, contact info, e-commerce variables, and helper functions.
 */

// 1. Session initialization
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Initialize cart and wishlist session storage if not present
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}
if (!isset($_SESSION['favorites'])) {
    $_SESSION['favorites'] = [];
}

// 3. Brand & Store Information
define('SITE_NAME', 'HIDA.MAKEUP');
define('SITE_LOGO_PREFIX', 'HIDA');
define('SITE_LOGO_SUFFIX', '.MAKEUP');
define('SITE_TAGLINE', 'Makeup & Cosmetics au Maroc');
define('SITE_DESCRIPTION', 'Votre destination beauté au Maroc. Découvrez notre sélection exclusive de maquillage, skincare, parfums et soins authentiques.');
define('SITE_FOOTER_ABOUT', 'Votre destination beauté au Maroc. Maquillage, skincare, parfums et essentiels sélectionnés pour vous avec amour.');

// 4. Contact & Location
define('STORE_PHONE', '+212 620 28 37 25');
define('STORE_WHATSAPP', '212620283725');
define('STORE_WHATSAPP_LINK', 'https://wa.me/212620283725');
define('STORE_EMAIL', 'contact@hidamakeup.ma');
define('STORE_CITY', 'Mohammedia');
define('STORE_ADDRESS', 'Mohammedia, Maroc');
define('STORE_HOURS', 'WhatsApp disponible 7j/7');

// 5. Social Links
define('SOCIAL_INSTAGRAM', 'https://instagram.com/hida.makeup');
define('SOCIAL_FACEBOOK', 'https://facebook.com/hida.makeup');
define('SOCIAL_TIKTOK', 'https://tiktok.com/@hida.makeup');

// 6. Top Announcement Bar
define('TOPBAR_ENABLED', true);
define('TOPBAR_TEXT', 'LIVRAISON PARTOUT AU MAROC | PAIEMENT À LA LIVRAISON');

// 7. E-Commerce & Currency Settings
define('CURRENCY_SYMBOL', 'DH');
define('CURRENCY_POSITION', 'after'); // 'after' => 250 DH, 'before' => DH 250
define('DEFAULT_SHIPPING_FEE', 35.00);
define('FREE_SHIPPING_THRESHOLD', 350.00); // Free shipping if cart >= 350 DH

// 8. Database Credentials
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'hida_makeup');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// 9. Base URL & Path helpers (dynamically handles root or subfolders in htdocs)
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$rootSubfolder = preg_replace('#/(includes|admin).*$#', '', $scriptDir);
$cleanSub = trim($rootSubfolder, '/');
define('BASE_PATH', $cleanSub ? '/' . $cleanSub . '/' : '/');

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
define('BASE_URL', rtrim($protocol . $host, '/') . BASE_PATH);

// 10. Global Helper Functions

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
 * Get total number of items in cart
 */
function get_cart_count(): int {
    $count = 0;
    if (!empty($_SESSION['cart']) && is_array($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $item) {
            $count += isset($item['quantity']) ? (int)$item['quantity'] : 1;
        }
    }
    return $count;
}

/**
 * Get cart subtotal amount
 */
function get_cart_subtotal(): float {
    $subtotal = 0.0;
    if (!empty($_SESSION['cart']) && is_array($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $item) {
            $price = (float)($item['price'] ?? 0);
            $qty = (int)($item['quantity'] ?? 1);
            $subtotal += ($price * $qty);
        }
    }
    return $subtotal;
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
 * Get total number of favorite items
 */
function get_wishlist_count(): int {
    return !empty($_SESSION['favorites']) && is_array($_SESSION['favorites']) ? count($_SESSION['favorites']) : 0;
}

/**
 * Check if a product is in wishlist
 */
function is_in_wishlist(int $product_id): bool {
    return !empty($_SESSION['favorites']) && in_array($product_id, $_SESSION['favorites'], true);
}

/**
 * Generate a direct WhatsApp order link
 */
function whatsapp_order_url(string $order_number, string $customer_name, string $phone, string $city, float $total, array $items = []): string {
    $lines = [];
    $lines[] = "Bonjour *" . SITE_NAME . "* !";
    $lines[] = "Je souhaite confirmer ma commande *" . $order_number . "* :";
    $lines[] = "👤 *Nom :* " . $customer_name;
    $lines[] = "📞 *Tél :* " . $phone;
    $lines[] = "📍 *Ville :* " . $city;
    if (!empty($items)) {
        $lines[] = "🛍️ *Articles :*";
        foreach ($items as $it) {
            $lines[] = "- " . ($it['name'] ?? 'Article') . " x" . ($it['quantity'] ?? 1) . " (" . format_price($it['price'] ?? 0) . ")";
        }
    }
    $lines[] = "💰 *Total :* " . format_price($total);
    $lines[] = "Merci de me confirmer la livraison !";

    $message = implode("\n", $lines);
    return "https://wa.me/" . STORE_WHATSAPP . "?text=" . urlencode($message);
}

/**
 * Generate a WhatsApp inquiry link for a single product
 */
function whatsapp_product_url(string $product_name, float $price, string $url = ''): string {
    $msg = "Bonjour *" . SITE_NAME . "* ! Je suis intéressé(e) par votre produit : *" . $product_name . "* (" . format_price($price) . "). Est-il disponible ?";
    if ($url) {
        $msg .= "\nLien : " . $url;
    }
    return "https://wa.me/" . STORE_WHATSAPP . "?text=" . urlencode($msg);
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
