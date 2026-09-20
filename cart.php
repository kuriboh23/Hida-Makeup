<?php
/**
 * HIDA.MAKEUP - Shopping Cart (Session-Based)
 * Handles adding, modifying quantity, removing items, calculating totals and shipping thresholds.
 */

require_once __DIR__ . '/includes/db.php';

// Handle Cart Actions
$action = isset($_GET['action']) ? trim($_GET['action']) : '';
$product_id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;
$qty = isset($_GET['qty']) && is_numeric($_GET['qty']) ? max(1, (int)$_GET['qty']) : 1;
$is_ajax = isset($_GET['ajax']) && $_GET['ajax'] == '1';

if ($action === 'add' && $product_id > 0) {
    // Fetch product details from DB
    $stmt = $pdo->prepare("SELECT id, name, slug, price, main_image, stock_quantity FROM products WHERE id = :id AND in_stock = 1 LIMIT 1");
    $stmt->execute([':id' => $product_id]);
    $prod = $stmt->fetch();

    if ($prod) {
        if (!isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id] = [
                'id' => $prod['id'],
                'name' => $prod['name'],
                'slug' => $prod['slug'],
                'price' => (float)$prod['price'],
                'image' => $prod['main_image'],
                'quantity' => $qty
            ];
        } else {
            $_SESSION['cart'][$product_id]['quantity'] += $qty;
        }

        set_flash('success', "« " . $prod['name'] . " » a été ajouté à votre panier.");

        if ($is_ajax) {
            echo json_encode([
                'status' => 'success',
                'cart_count' => get_cart_count(),
                'cart_subtotal' => get_cart_subtotal()
            ]);
            exit;
        }
    }
    header("Location: cart.php");
    exit;
}

if ($action === 'update' && $product_id > 0) {
    if (isset($_SESSION['cart'][$product_id])) {
        if ($qty > 0) {
            $_SESSION['cart'][$product_id]['quantity'] = $qty;
            set_flash('success', "Quantité mise à jour.");
        } else {
            unset($_SESSION['cart'][$product_id]);
            set_flash('info', "Article retiré du panier.");
        }
    }
    header("Location: cart.php");
    exit;
}

if ($action === 'remove' && $product_id > 0) {
    if (isset($_SESSION['cart'][$product_id])) {
        $name = $_SESSION['cart'][$product_id]['name'];
        unset($_SESSION['cart'][$product_id]);
        set_flash('info', "« $name » a été retiré de votre panier.");
    }
    header("Location: cart.php");
    exit;
}

if ($action === 'clear') {
    $_SESSION['cart'] = [];
    set_flash('info', "Votre panier a été vidé.");
    header("Location: cart.php");
    exit;
}

// Calculate totals
$cart_items = $_SESSION['cart'] ?? [];
$subtotal = get_cart_subtotal();
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
<section class="cart-hero-bar">
    <div class="container">
        <h1>Mon Panier</h1>
        <div class="breadcrumb" style="justify-content:center;">
            <a href="index.php">Accueil</a>
            <span>/</span>
            <a href="products.php">Boutique</a>
            <span>/</span>
            <strong>Panier</strong>
        </div>
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
                    <div style="text-align: center;">Quantité</div>
                    <div style="text-align: right;">Total</div>
                </div>

                <div class="cart-items-list">
                    <?php foreach ($cart_items as $item): ?>
                        <?php $line_total = $item['price'] * $item['quantity']; ?>
                        <div class="cart-item-row">
                            <div class="cart-item-info">
                                <a href="cart.php?action=remove&id=<?= $item['id'] ?>" class="remove-btn" title="Supprimer cet article">
                                    <i class="fa-solid fa-trash-can"></i>
                                </a>
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
                                    <a href="cart.php?action=update&id=<?= $item['id'] ?>&qty=<?= $item['quantity'] - 1 ?>" class="qty-btn-sm">-</a>
                                    <span class="qty-num"><?= $item['quantity'] ?></span>
                                    <a href="cart.php?action=update&id=<?= $item['id'] ?>&qty=<?= $item['quantity'] + 1 ?>" class="qty-btn-sm">+</a>
                                </div>
                            </div>

                            <div class="cart-item-total">
                                <?= format_price($line_total) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="cart-actions-bottom">
                    <a href="products.php" class="btn btn-outline btn-sm">
                        <i class="fa-solid fa-arrow-left"></i> Continuer mes achats
                    </a>
                    <a href="cart.php?action=clear" class="btn btn-outline btn-sm" onclick="return confirm('Voulez-vous vraiment vider votre panier ?');">
                        <i class="fa-solid fa-trash"></i> Vider le panier
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

                    <a href="checkout.php" class="btn btn-primary btn-block btn-lg" style="margin-bottom: 12px;">
                        Passer la commande <i class="fa-solid fa-arrow-right"></i>
                    </a>

                    <a href="<?= htmlspecialchars(whatsapp_order_url('COMMANDE-DIRECTE', 'Client Panier', STORE_PHONE, STORE_CITY, $total, $cart_items)) ?>" target="_blank" class="btn btn-whatsapp btn-block">
                        <i class="fa-brands fa-whatsapp"></i> Commander par WhatsApp
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
            <div style="display:flex; justify-content:center; gap:12px; margin-top: 25px;">
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

<?php
$extra_css = '
.cart-hero-bar { background: linear-gradient(135deg, var(--rose) 0%, #fef8f6 100%); padding: 40px 0; text-align: center; border-bottom: 1px solid var(--border); }
.cart-hero-bar h1 { font-family: var(--font-heading); font-size: 34px; margin-bottom: 8px; }

.shipping-threshold-card { background: var(--white); border: 1px solid var(--border); border-radius: var(--radius-md); padding: 18px 24px; margin-bottom: 35px; box-shadow: var(--shadow-sm); }
.threshold-msg { display: flex; align-items: center; gap: 10px; font-size: 14px; margin-bottom: 12px; color: var(--text); }
.threshold-msg i { color: var(--burgundy); font-size: 18px; }
.threshold-msg.free-success i { color: var(--green); }
.progress-bar-bg { width: 100%; height: 8px; background: var(--rose); border-radius: var(--radius-full); overflow: hidden; }
.progress-bar-fill { height: 100%; background: var(--burgundy); transition: width 0.4s ease; border-radius: var(--radius-full); }

.cart-layout { display: grid; grid-template-columns: 1fr 360px; gap: 40px; align-items: start; }
.cart-table-head { display: grid; grid-template-columns: 2.5fr 1fr 1fr 1fr; padding-bottom: 15px; border-bottom: 2px solid var(--border); font-size: 13px; font-weight: 700; text-transform: uppercase; color: var(--muted); letter-spacing: 0.5px; }

.cart-items-list { display: flex; flex-direction: column; }
.cart-item-row { display: grid; grid-template-columns: 2.5fr 1fr 1fr 1fr; align-items: center; padding: 22px 0; border-bottom: 1px solid var(--border); }
.cart-item-info { display: flex; align-items: center; gap: 16px; }
.remove-btn { color: var(--muted); font-size: 15px; transition: var(--transition); cursor: pointer; }
.remove-btn:hover { color: var(--red); transform: scale(1.15); }
.cart-item-thumb { width: 70px; height: 85px; border-radius: var(--radius-sm); overflow: hidden; background: var(--rose-light); flex-shrink: 0; }
.cart-item-thumb img { width: 100%; height: 100%; object-fit: cover; }
.cart-item-name { font-size: 14px; font-weight: 600; line-height: 1.4; }
.cart-item-name a:hover { color: var(--burgundy); }
.cart-item-price-mobile { display: none; font-size: 13px; color: var(--burgundy); font-weight: 700; margin-top: 4px; }
.cart-item-price { font-size: 15px; font-weight: 600; color: var(--text); }
.cart-item-qty { display: flex; justify-content: center; }
.qty-selector-sm { display: flex; align-items: center; border: 1px solid var(--border); border-radius: var(--radius-full); background: var(--white); }
.qty-btn-sm { width: 30px; height: 32px; display: grid; place-items: center; font-size: 14px; font-weight: 700; color: var(--text); }
.qty-btn-sm:hover { color: var(--burgundy); }
.qty-num { width: 30px; text-align: center; font-size: 13px; font-weight: 700; }
.cart-item-total { font-size: 16px; font-weight: 700; color: var(--burgundy); text-align: right; }

.cart-actions-bottom { display: flex; justify-content: space-between; align-items: center; padding-top: 25px; }

.summary-card { background: var(--bg-light); border: 1px solid var(--border); border-radius: var(--radius-md); padding: 30px 25px; position: sticky; top: 100px; }
.summary-card h3 { font-family: var(--font-heading); font-size: 20px; margin-bottom: 22px; padding-bottom: 12px; border-bottom: 1px solid var(--border); }
.summary-row { display: flex; justify-content: space-between; font-size: 14px; color: var(--text-light); margin-bottom: 14px; }
.summary-divider { height: 1px; background: var(--border); margin: 18px 0; }
.total-row { font-size: 17px; font-weight: 700; color: var(--text); margin-bottom: 6px; }
.total-amount { font-size: 22px; color: var(--burgundy); font-weight: 800; }
.summary-trust { margin-top: 25px; padding-top: 18px; border-top: 1px dashed var(--border-dark); display: flex; flex-direction: column; gap: 8px; font-size: 12px; color: var(--muted); }
.summary-trust i { color: var(--burgundy); margin-right: 6px; }

.empty-cart-card { text-align: center; max-width: 580px; margin: 40px auto; padding: 60px 30px; background: var(--white); border: 1px solid var(--border); border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); }
.empty-icon-wrap { width: 80px; height: 80px; border-radius: 50%; background: var(--rose); color: var(--burgundy); display: grid; place-items: center; font-size: 32px; margin: 0 auto 20px; }
.empty-cart-card h2 { font-family: var(--font-heading); font-size: 28px; margin-bottom: 12px; }
.empty-cart-card p { font-size: 14px; color: var(--muted); line-height: 1.7; }

@media (max-width: 992px) {
    .cart-layout { grid-template-columns: 1fr; }
    .cart-table-head { display: none; }
    .cart-item-row { grid-template-columns: 1fr auto; gap: 15px; position: relative; }
    .cart-item-price { display: none; }
    .cart-item-price-mobile { display: block; }
}
';

require_once __DIR__ . '/includes/footer.php';
