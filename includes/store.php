<?php
/**
 * HIDA.MAKEUP - Cart & Favorites Store (cookie based)
 *
 * The basket and the favorites list live in the visitor's browser as cookies,
 * so browsing and adding items never writes anything to the server. Confirmed
 * orders are still written to MySQL - only the pre-purchase basket is client
 * side.
 *
 * Safety rules:
 *   - Only product IDs and quantities are stored. Names, images and prices are
 *     always re-read from the products table, so a modified cookie can never
 *     change what a customer actually pays.
 *   - Every cookie is signed with an HMAC using STORE_COOKIE_SECRET. A cookie
 *     whose signature does not match is ignored and dropped.
 *   - Payloads are validated and capped, so a cookie can never grow past the
 *     4 KB browser limit.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/media.php';

const CART_COOKIE       = 'hida_cart';
const FAVORITES_COOKIE  = 'hida_favorites';
const STORE_COOKIE_DAYS = 30;
const STORE_MAX_LINES   = 50;  // Maximum distinct products in a cookie
const STORE_MAX_QTY     = 99;  // Maximum quantity per line

/* ===========================================================================
   Cookie plumbing (signed, tamper-evident)
   =========================================================================== */

/**
 * Sign a cookie payload so its contents cannot be forged.
 */
function store_sign(string $payload): string {
    return hash_hmac('sha256', $payload, STORE_COOKIE_SECRET);
}

/**
 * Forget every cached view of a cookie. Called by every write so a value can
 * never go stale within the request that changed it.
 */
function store_cache_flush(string $name): void {
    if ($name === CART_COOKIE) {
        unset($GLOBALS['hida_cart_cache'], $GLOBALS['hida_cart_items_cache']);
    } elseif ($name === FAVORITES_COOKIE) {
        unset($GLOBALS['hida_favorites_cache']);
    }
}

/**
 * Write a signed JSON cookie.
 */
function store_cookie_write(string $name, array $data): void {
    $payload = base64_encode(json_encode($data));
    $value = $payload . '.' . store_sign($payload);

    // Remember it for the rest of this request even if headers are already sent.
    $_COOKIE[$name] = $value;
    store_cache_flush($name);

    if (headers_sent()) {
        return;
    }

    setcookie($name, $value, [
        'expires'  => time() + (STORE_COOKIE_DAYS * 86400),
        'path'     => BASE_PATH,
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
}

/**
 * Read a signed JSON cookie.
 * Returns $default when the cookie is missing, malformed or tampered with.
 */
function store_cookie_read(string $name, array $default = []): array {
    $raw = $_COOKIE[$name] ?? '';

    if (!is_string($raw) || $raw === '') {
        return $default;
    }

    $parts = explode('.', $raw, 2);
    if (count($parts) !== 2) {
        return $default;
    }

    [$payload, $signature] = $parts;

    // Reject anything that was not signed by this site.
    if (!hash_equals(store_sign($payload), $signature)) {
        return $default;
    }

    $decoded = base64_decode($payload, true);
    if ($decoded === false) {
        return $default;
    }

    $data = json_decode($decoded, true);

    return is_array($data) ? $data : $default;
}

/**
 * Remove a cookie completely.
 */
function store_cookie_delete(string $name): void {
    unset($_COOKIE[$name]);
    store_cache_flush($name);

    if (headers_sent()) {
        return;
    }

    setcookie($name, '', [
        'expires'  => time() - 3600,
        'path'     => BASE_PATH,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

/* ===========================================================================
   Cart
   =========================================================================== */

/**
 * Raw cart contents, as [product_id => quantity], in the order they were added.
 */
function cart_read(): array {
    if (isset($GLOBALS['hida_cart_cache'])) {
        return $GLOBALS['hida_cart_cache'];
    }

    $cart = [];
    foreach (store_cookie_read(CART_COOKIE) as $id => $qty) {
        $id  = (int)$id;
        $qty = (int)$qty;

        if ($id > 0 && $qty > 0) {
            $cart[$id] = min($qty, STORE_MAX_QTY);
        }
    }

    $GLOBALS['hida_cart_cache'] = array_slice($cart, 0, STORE_MAX_LINES, true);

    return $GLOBALS['hida_cart_cache'];
}

/**
 * Persist the cart.
 */
function cart_write(array $cart): void {
    if (empty($cart)) {
        store_cookie_delete(CART_COOKIE);
        return;
    }

    store_cookie_write(CART_COOKIE, $cart);
}

/**
 * Add a product to the cart, or increase its quantity when already present.
 */
function cart_add(int $product_id, int $quantity = 1): void {
    if ($product_id <= 0) {
        return;
    }

    $quantity = max(1, min($quantity, STORE_MAX_QTY));
    $cart = cart_read();

    if (count($cart) >= STORE_MAX_LINES && !isset($cart[$product_id])) {
        return;
    }

    $cart[$product_id] = min(($cart[$product_id] ?? 0) + $quantity, STORE_MAX_QTY);
    cart_write($cart);
}

/**
 * Set an exact quantity. A quantity of 0 removes the line.
 */
function cart_set_quantity(int $product_id, int $quantity): void {
    $cart = cart_read();

    if (!isset($cart[$product_id])) {
        return;
    }

    if ($quantity <= 0) {
        unset($cart[$product_id]);
    } else {
        $cart[$product_id] = min($quantity, STORE_MAX_QTY);
    }

    cart_write($cart);
}

/**
 * Remove a single product from the cart.
 */
function cart_remove(int $product_id): void {
    $cart = cart_read();
    unset($cart[$product_id]);
    cart_write($cart);
}

/**
 * Empty the cart.
 */
function cart_clear(): void {
    cart_write([]);
}

/**
 * Number of individual units in the cart (drives the header badge).
 */
function get_cart_count(): int {
    return (int)array_sum(cart_read());
}

/**
 * Resolve the cart cookie against the products table.
 *
 * Only IDs travel in the cookie: the name, slug, image and price shown to the
 * customer always come from MySQL. Lines whose product no longer exists are
 * dropped. The result is cached for the current request.
 */
function get_cart_items(?PDO $pdo = null): array {
    if (isset($GLOBALS['hida_cart_items_cache'])) {
        return $GLOBALS['hida_cart_items_cache'];
    }

    $cart = cart_read();
    if (empty($cart)) {
        $GLOBALS['hida_cart_items_cache'] = [];
        return [];
    }

    $pdo = $pdo ?: ($GLOBALS['pdo'] ?? null);
    if (!$pdo instanceof PDO) {
        $GLOBALS['hida_cart_items_cache'] = [];
        return [];
    }

    $ids = array_keys($cart);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    $stmt = $pdo->prepare("
        SELECT id, name, slug, price, main_image, stock_quantity, in_stock
        FROM products
        WHERE id IN ($placeholders)
    ");
    $stmt->execute($ids);

    $rows = [];
    foreach ($stmt->fetchAll() as $row) {
        $rows[(int)$row['id']] = $row;
    }

    $items = [];
    $valid = [];
    foreach ($cart as $id => $quantity) {
        if (!isset($rows[$id])) {
            continue; // Product deleted meanwhile: drop the line below.
        }

        $row = $rows[$id];
        $valid[$id] = (int)$quantity;
        $items[] = [
            'id'       => (int)$row['id'],
            'name'     => $row['name'],
            'slug'     => $row['slug'],
            'price'    => (float)$row['price'],
            'image'    => product_image_url($row['main_image'], (string)$row['slug'], (int)$row['id']),
            'stock'    => (int)$row['stock_quantity'],
            'in_stock' => (int)$row['in_stock'],
            'quantity' => (int)$quantity,
        ];
    }

    // Self-heal: rewrite the cookie without the lines that no longer resolve.
    if (count($valid) !== count($cart)) {
        cart_write($valid);
    }

    $GLOBALS['hida_cart_items_cache'] = $items;

    return $items;
}

/**
 * Cart subtotal, priced from the database.
 */
function get_cart_subtotal(?array $items = null): float {
    $subtotal = 0.0;

    foreach ($items ?? get_cart_items() as $item) {
        $subtotal += $item['price'] * $item['quantity'];
    }

    return $subtotal;
}

/* ===========================================================================
   Favorites
   =========================================================================== */

/**
 * List of favorite product IDs.
 */
function favorites_read(): array {
    if (isset($GLOBALS['hida_favorites_cache'])) {
        return $GLOBALS['hida_favorites_cache'];
    }

    $ids = [];
    foreach (store_cookie_read(FAVORITES_COOKIE) as $id) {
        $id = (int)$id;

        if ($id > 0 && !in_array($id, $ids, true)) {
            $ids[] = $id;
        }
    }

    $GLOBALS['hida_favorites_cache'] = array_slice($ids, 0, STORE_MAX_LINES);

    return $GLOBALS['hida_favorites_cache'];
}

/**
 * Persist the favorites list.
 */
function favorites_write(array $ids): void {
    if (empty($ids)) {
        store_cookie_delete(FAVORITES_COOKIE);
        return;
    }

    store_cookie_write(FAVORITES_COOKIE, array_values($ids));
}

/**
 * Add or remove a product from the favorites. Returns true when it was added.
 */
function favorites_toggle(int $product_id): bool {
    if ($product_id <= 0) {
        return false;
    }

    $ids = favorites_read();
    $key = array_search($product_id, $ids, true);

    if ($key !== false) {
        unset($ids[$key]);
        favorites_write(array_values($ids));
        return false;
    }

    $ids[] = $product_id;
    favorites_write($ids);

    return true;
}

/**
 * Empty the favorites list.
 */
function favorites_clear(): void {
    favorites_write([]);
}

/**
 * Number of saved favorites.
 */
function get_wishlist_count(): int {
    return count(favorites_read());
}

/**
 * Is a product already saved in the favorites?
 */
function is_in_wishlist(int $product_id): bool {
    return in_array($product_id, favorites_read(), true);
}
