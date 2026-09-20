<?php
/**
 * HIDA.MAKEUP - Shopping Cart (Cookie-Based)
 * Handles adding, modifying quantity, removing items, calculating totals and shipping thresholds.
 *
 * The basket itself is stored in a signed browser cookie; every amount shown
 * here is re-read from the products table. Supports an `ajax=1` JSON response
 * so "add to cart" never forces a full page reload.
 */

require_once __DIR__ . '/includes/db.php';

// Handle Cart Actions
$action = isset($_GET['action']) ? trim($_GET['action']) : '';
$product_id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;
$qty = isset($_GET['qty']) && is_numeric($_GET['qty']) ? max(0, (int)$_GET['qty']) : 1;
$is_ajax = isset($_GET['ajax']) && $_GET['ajax'] == '1';

/**
 * Send a JSON payload for async requests and stop.
 */
function cart_json(array $payload, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload);
    exit;
}

if ($action === 'add' && $product_id > 0) {
    $qty = max(1, $qty);

    // The product must exist and be in stock - never trust the cookie alone.
    $stmt = $pdo->prepare("SELECT id, price, stock_quantity FROM products WHERE id = :id AND in_stock = 1 LIMIT 1");
    $stmt->execute([':id' => $product_id]);
    $prod = $stmt->fetch();

    if (!$prod) {
        if ($is_ajax) {
            cart_json(['status' => 'error', 'message' => "Ce produit n'est plus disponible."], 404);
        }
        set_flash('error', "Ce produit n'est plus disponible.");
        header("Location: cart.php");
        exit;
    }

    cart_add($product_id, $qty);

    // Response used by the no-reload add to cart flow.
    if ($is_ajax) {
        cart_json([
            'status'        => 'success',
            'cart_count'    => get_cart_count(),
            'cart_subtotal' => get_cart_subtotal(),
        ]);
    }

    // Deliberately no product name: a short, uniform confirmation.
    set_flash('success', "Ajouté au panier !");
    header("Location: cart.php");
    exit;
}

if ($action === 'update' && $product_id > 0) {
    $cart = cart_read();

    if (isset($cart[$product_id])) {
        cart_set_quantity($product_id, $qty);
        set_flash($qty > 0 ? 'success' : 'info', $qty > 0 ? "Quantité mise à jour." : "Article retiré du panier.");
    }
    header("Location: cart.php");
    exit;
}

if ($action === 'remove' && $product_id > 0) {
    $items = get_cart_items();
    foreach ($items as $item) {
        if ($item['id'] === $product_id) {
            cart_remove($product_id);
            set_flash('info', "« " . $item['name'] . " » a été retiré de votre panier.");
            break;
        }
    }
    header("Location: cart.php");
    exit;
}

if ($action === 'clear') {
    cart_clear();
    set_flash('info', "Votre panier a été vidé.");
    header("Location: cart.php");
    exit;
}

// Calculate totals from live database prices
$cart_items = get_cart_items();
$subtotal = get_cart_subtotal($cart_items);
$shipping_fee = calculate_shipping($subtotal);
$total = $subtotal + $shipping_fee;

// Free shipping calculation
$amount_for_free_shipping = max(0, FREE_SHIPPING_THRESHOLD - $subtotal);
$progress_percent = min(100, ($subtotal / FREE_SHIPPING_THRESHOLD) * 100);

$page_title = "Mon Panier";
$page_description = "Visualisez vos articles de beauté sélectionnés et finalisez votre commande.";

require_once __DIR__ . '/includes/header.php';
?>

<!-- CART HERO -->
<section class="page-hero">
    <div class="container">
        <nav class="breadcrumb" aria-label="Fil d'Ariane">
            <a href="index.php">Accueil</a>
            <span>/</span>
            <a href="products.php">Boutique</a>
            <span>/</span>
            <strong>Panier</strong>
        </nav>

        <h1>Mon Panier</h1>

        <p class="page-hero-desc">
            Vérifiez vos articles, ajustez les quantités puis finalisez votre commande.
        </p>
    </div>
</section>

<div class="container section">
    <?php if (!empty($cart_items)): ?>
        
        <!-- FREE SHIPPING BAR -->
        <div class="shipping-threshold-card">
            <?php if ($amount_for_free_shipping > 0): ?>
                <div class="threshold-msg">
                    <i class="fa-solid fa-truck-fast"></i>
                    <span>Plus que <strong><?= format_price($amount_for_free_shipping) ?></strong> pour profiter de la <strong>LIVRAISON GRATUITE</strong> !</span>
                </div>
            <?php else: ?>
                <div class="threshold-msg free-success">
                    <i class="fa-solid fa-circle-check"></i>
                    <span>Félicitations ! Vous bénéficiez de la <strong>LIVRAISON GRATUITE</strong> sur cette commande !</span>
                </div>
            <?php endif; ?>
            <div class="progress-bar-bg">
                <div class="progress-bar-fill" style="width: <?= $progress_percent ?>%;"></div>
            </div>
        </div>

        <div class="cart-layout">
            
            <!-- CART ITEMS TABLE -->
            <div class="cart-items-wrapper">
                <div class="cart-table-head">
                    <div>Produit</div>
                    <div>Prix</div>
                    <div>Quantité</div>
                    <div>Total</div>
                    <div></div>
                </div>

                <div class="cart-items-list">
                    <?php foreach ($cart_items as $item): ?>
                        <?php $line_total = $item['price'] * $item['quantity']; ?>
                        <div class="cart-item-row">
                            <div class="cart-item-info">
                                <div class="cart-item-thumb">
                                    <img src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>">
                                </div>
                                <div>
                                    <h3 class="cart-item-name">
                                        <a href="product.php?slug=<?= urlencode($item['slug']) ?>">
                                            <?= htmlspecialchars($item['name']) ?>
                                        </a>
                                    </h3>
                                    <span class="cart-item-price-mobile"><?= format_price($item['price']) ?></span>
                                </div>
                            </div>

                            <div class="cart-item-price">
                                <?= format_price($item['price']) ?>
                            </div>

                            <div class="cart-item-qty">
                                <div class="qty-selector-sm">
                                    <a href="cart.php?action=update&id=<?= $item['id'] ?>&qty=<?= $item['quantity'] - 1 ?>" class="qty-btn-sm" aria-label="Diminuer la quantité">-</a>
                                    <span class="qty-num"><?= $item['quantity'] ?></span>
                                    <a href="cart.php?action=update&id=<?= $item['id'] ?>&qty=<?= $item['quantity'] + 1 ?>" class="qty-btn-sm" aria-label="Augmenter la quantité">+</a>
                                </div>
                            </div>

                            <div class="cart-item-total">
                                <?= format_price($line_total) ?>
                            </div>

                            <div class="cart-item-remove">
                                <a href="cart.php?action=remove&id=<?= $item['id'] ?>" class="remove-btn" title="Supprimer cet article" aria-label="Supprimer <?= htmlspecialchars($item['name']) ?> du panier">
                                    <i class="fa-solid fa-trash-can"></i>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="cart-actions-bottom">
                    <a href="products.php" class="btn btn-outline btn-sm">
                        <i class="fa-solid fa-arrow-left"></i> Continuer mes achats
                    </a>
                    <a href="cart.php?action=clear" class="btn btn-outline btn-sm" onclick="return confirm('Voulez-vous vraiment vider votre panier ?');">
                        <i class="fa-solid fa-trash-can"></i> Vider le panier
                    </a>
                </div>
            </div>

            <!-- ORDER SUMMARY CARD -->
            <div class="cart-summary-sidebar">
                <div class="summary-card">
                    <h3>Récapitulatif de commande</h3>

                    <div class="summary-row">
                        <span>Sous-total</span>
                        <strong><?= format_price($subtotal) ?></strong>
                    </div>

                    <div class="summary-row">
                        <span>Frais de livraison</span>
                        <?php if ($shipping_fee == 0): ?>
                            <strong style="color: var(--green);">GRATUIT</strong>
                        <?php else: ?>
                            <strong><?= format_price($shipping_fee) ?></strong>
                        <?php endif; ?>
                    </div>

                    <div class="summary-divider"></div>

                    <div class="summary-row total-row">
                        <span>Total à régler</span>
                        <strong class="total-amount"><?= format_price($total) ?></strong>
                    </div>
                    <div style="font-size: 11px; color: var(--muted); text-align: right; margin-top: -6px; margin-bottom: 20px;">
                        Paiement en espèces à la livraison
                    </div>

                    <a href="checkout.php" class="btn btn-primary btn-block btn-lg">
                        Passer la commande <i class="fa-solid fa-arrow-right"></i>
                    </a>

                    <div class="summary-trust">
                        <div><i class="fa-solid fa-shield-halved"></i> Paiement sécurisé à la livraison</div>
                        <div><i class="fa-solid fa-box-open"></i> Vérification du colis autorisée</div>
                        <div><i class="fa-solid fa-clock"></i> Expédition rapide sous 24h / 48h</div>
                    </div>
                </div>
            </div>

        </div>

    <?php else: ?>
        <!-- EMPTY CART STATE -->
        <div class="empty-cart-card">
            <div class="empty-icon-wrap">
                <i class="fa-solid fa-bag-shopping"></i>
            </div>
            <h2>Votre panier est vide</h2>
            <p>Il semble que vous n'ayez pas encore ajouté d'articles à votre panier. Découvrez notre sélection exclusive de soins et maquillage pour sublimer votre éclat.</p>
            <div class="empty-actions">
                <a href="products.php" class="btn btn-primary btn-lg">
                    Explorer la boutique <i class="fa-solid fa-arrow-right"></i>
                </a>
                <a href="favorites.php" class="btn btn-outline btn-lg">
                    Voir mes favoris <i class="fa-solid fa-heart"></i>
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php';
