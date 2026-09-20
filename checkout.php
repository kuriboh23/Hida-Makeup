<?php
/**
 * HIDA.MAKEUP - Checkout Page (Cash on Delivery)
 * Validates shipping address and creates a complete order in MySQL with instant WhatsApp confirmation.
 */

require_once __DIR__ . '/includes/db.php';

$cart_items = $_SESSION['cart'] ?? [];
$subtotal = get_cart_subtotal();
$shipping_fee = calculate_shipping($subtotal);
$total = $subtotal + $shipping_fee;

$errors = [];
$order_placed = null;

// Handle Form Submission
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['place_order'])) {
    if (empty($cart_items)) {
        header("Location: cart.php");
        exit;
    }

    $customer_name = sanitize($_POST['customer_name'] ?? '');
    $customer_phone = sanitize($_POST['customer_phone'] ?? '');
    $customer_city = sanitize($_POST['customer_city'] ?? '');
    $customer_address = sanitize($_POST['customer_address'] ?? '');
    $notes = sanitize($_POST['notes'] ?? '');

    // Form Validation
    if (empty($customer_name)) {
        $errors[] = "Veuillez renseigner votre nom complet.";
    }
    if (empty($customer_phone)) {
        $errors[] = "Veuillez renseigner un numéro de téléphone joignable pour la livraison.";
    } elseif (strlen(preg_replace('/[^0-9]/', '', $customer_phone)) < 9) {
        $errors[] = "Le numéro de téléphone semble incomplet.";
    }
    if (empty($customer_city)) {
        $errors[] = "Veuillez préciser votre ville de livraison.";
    }
    if (empty($customer_address)) {
        $errors[] = "Veuillez indiquer votre adresse détaillée (quartier, rue, n°).";
    }

    // Process order if validation passes
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // Generate clean unique Moroccan order reference
            $order_number = 'HIDA-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));

            // 1. Insert into orders table
            $stmt = $pdo->prepare("
                INSERT INTO orders (order_number, customer_name, customer_phone, customer_city, customer_address, notes, subtotal_amount, shipping_fee, total_amount, payment_method, status)
                VALUES (:order_number, :customer_name, :customer_phone, :customer_city, :customer_address, :notes, :subtotal, :shipping, :total, 'cod', 'pending')
            ");
            $stmt->execute([
                ':order_number' => $order_number,
                ':customer_name' => $customer_name,
                ':customer_phone' => $customer_phone,
                ':customer_city' => $customer_city,
                ':customer_address' => $customer_address,
                ':notes' => $notes,
                ':subtotal' => $subtotal,
                ':shipping' => $shipping_fee,
                ':total' => $total,
            ]);
            $order_id = $pdo->lastInsertId();

            // 2. Insert order items
            $item_stmt = $pdo->prepare("
                INSERT INTO order_items (order_id, product_id, product_name, price, quantity, total_price)
                VALUES (:order_id, :product_id, :product_name, :price, :quantity, :total_price)
            ");
            $stock_stmt = $pdo->prepare("
                UPDATE products 
                SET stock_quantity = GREATEST(0, stock_quantity - :qty) 
                WHERE id = :id
            ");

            foreach ($cart_items as $item) {
                $line_total = $item['price'] * $item['quantity'];
                $item_stmt->execute([
                    ':order_id' => $order_id,
                    ':product_id' => $item['id'],
                    ':product_name' => $item['name'],
                    ':price' => $item['price'],
                    ':quantity' => $item['quantity'],
                    ':total_price' => $line_total
                ]);

                // Update product stock
                $stock_stmt->execute([
                    ':qty' => $item['quantity'],
                    ':id' => $item['id']
                ]);
            }

            $pdo->commit();

            // Store order in variable for success screen
            $order_placed = [
                'order_number' => $order_number,
                'customer_name' => $customer_name,
                'customer_phone' => $customer_phone,
                'customer_city' => $customer_city,
                'customer_address' => $customer_address,
                'total' => $total,
                'shipping_fee' => $shipping_fee,
                'items' => $cart_items
            ];

            // Clear session cart
            $_SESSION['cart'] = [];

        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = "Une erreur est survenue lors de l'enregistrement de votre commande. Veuillez réessayer ou commander directement par WhatsApp.";
        }
    }
}

// Redirect if cart is empty and no order was just placed
if (empty($cart_items) && !$order_placed) {
    header("Location: cart.php");
    exit;
}

$page_title = $order_placed ? "Commande Confirmée !" : "Validation de commande";
$page_description = "Finalisez votre commande de maquillage en toute simplicité. Paiement en espèces à la livraison.";

require_once __DIR__ . '/includes/header.php';
?>

<!-- CHECKOUT HERO -->
<section class="checkout-hero">
    <div class="container">
        <div class="breadcrumb">
            <a href="index.php">Accueil</a>
            <span>/</span>
            <a href="cart.php">Panier</a>
            <span>/</span>
            <strong>Caisse</strong>
        </div>
        <h1><?= $order_placed ? "Commande Reçue !" : "Finaliser ma commande" ?></h1>
    </div>
</section>

<div class="container section">
    <?php if ($order_placed): ?>
        
        <!-- SUCCESS ORDER SCREEN -->
        <div class="order-success-card">
            <div class="success-icon">
                <i class="fa-solid fa-check"></i>
            </div>
            <h2>Merci pour votre commande, <?= htmlspecialchars($order_placed['customer_name']) ?> !</h2>
            <p class="success-subtitle">
                Votre commande a été enregistrée avec succès sous la référence <strong><?= htmlspecialchars($order_placed['order_number']) ?></strong>.
            </p>

            <div class="order-summary-box">
                <div class="order-meta-grid">
                    <div>
                        <span class="meta-label">Référence :</span>
                        <strong><?= htmlspecialchars($order_placed['order_number']) ?></strong>
                    </div>
                    <div>
                        <span class="meta-label">Total :</span>
                        <strong style="color:var(--burgundy);"><?= format_price($order_placed['total']) ?></strong>
                    </div>
                    <div>
                        <span class="meta-label">Paiement :</span>
                        <strong>Espèces à la livraison</strong>
                    </div>
                    <div>
                        <span class="meta-label">Ville :</span>
                        <strong><?= htmlspecialchars($order_placed['customer_city']) ?></strong>
                    </div>
                </div>

                <div class="order-items-review">
                    <h4 style="margin-bottom: 12px; font-size: 14px;">Articles commandés :</h4>
                    <?php foreach ($order_placed['items'] as $it): ?>
                        <div class="review-item-row">
                            <span><?= htmlspecialchars($it['name']) ?> (x<?= $it['quantity'] ?>)</span>
                            <strong><?= format_price($it['price'] * $it['quantity']) ?></strong>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- WhatsApp Direct Confirmation Button -->
            <?php
                $wa_url = whatsapp_order_url(
                    $order_placed['order_number'],
                    $order_placed['customer_name'],
                    $order_placed['customer_phone'],
                    $order_placed['customer_city'],
                    $order_placed['total'],
                    $order_placed['items']
                );
            ?>
            <div style="margin: 30px 0;">
                <a href="<?= htmlspecialchars($wa_url) ?>" target="_blank" class="btn btn-whatsapp btn-lg btn-block" style="font-size: 15px;">
                    <i class="fa-brands fa-whatsapp" style="font-size: 20px;"></i> Confirmer ma commande par WhatsApp
                </a>
                <p style="font-size: 12px; color: var(--muted); margin-top: 10px;">
                    Cliquez sur le bouton ci-dessus pour envoyer instantanément les détails de votre commande à notre équipe.
                </p>
            </div>

            <a href="products.php" class="btn btn-outline">
                Continuer mes achats <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>

    <?php else: ?>

        <!-- CHECKOUT FORM & SUMMARY -->
        <?php if (!empty($errors)): ?>
            <div class="alert alert-error" style="background:#fff3f3; color:#a94442; border-left:4px solid #d9534f; padding:15px; border-radius:6px; margin-bottom:25px;">
                <ul style="margin-left: 20px; font-size: 13px; line-height: 1.6;">
                    <?php foreach ($errors as $err): ?>
                        <li><?= htmlspecialchars($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form action="checkout.php" method="POST" class="checkout-grid">
            
            <!-- LEFT: CUSTOMER ADDRESS FORM -->
            <div class="checkout-card">
                <h2 class="card-title">
                    <i class="fa-solid fa-location-dot"></i> Adresse de Livraison
                </h2>

                <div class="form-grid">
                    <div class="form-group full">
                        <label for="customer_name">Nom complet *</label>
                        <input type="text" name="customer_name" id="customer_name" class="form-input" 
                               placeholder="Ex: Salma Benjelloun" 
                               value="<?= htmlspecialchars($_POST['customer_name'] ?? '') ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="customer_phone">Numéro de Téléphone (WhatsApp) *</label>
                        <input type="tel" name="customer_phone" id="customer_phone" class="form-input" 
                               placeholder="Ex: 06 61 12 34 56" 
                               value="<?= htmlspecialchars($_POST['customer_phone'] ?? '') ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="customer_city">Ville *</label>
                        <select name="customer_city" id="customer_city" class="form-select" required>
                            <option value="">Sélectionnez votre ville</option>
                            <?php 
                            $cities = ['Casablanca', 'Rabat', 'Marrakech', 'Tanger', 'Fès', 'Agadir', 'Meknès', 'Oujda', 'Kénitra', 'Tétouan', 'Salé', 'Mohammedia', 'El Jadida', 'Nador', 'Autre ville au Maroc'];
                            $sel_city = $_POST['customer_city'] ?? '';
                            foreach ($cities as $c): ?>
                                <option value="<?= htmlspecialchars($c) ?>" <?= $sel_city === $c ? 'selected' : '' ?>><?= htmlspecialchars($c) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group full">
                        <label for="customer_address">Adresse complète de livraison *</label>
                        <textarea name="customer_address" id="customer_address" class="form-textarea" rows="2" 
                                  placeholder="Quartier, Rue, N° d'immeuble, N° d'appartement..." required><?= htmlspecialchars($_POST['customer_address'] ?? '') ?></textarea>
                    </div>

                    <div class="form-group full">
                        <label for="notes">Instructions de livraison (Optionnel)</label>
                        <input type="text" name="notes" id="notes" class="form-input" 
                               placeholder="Ex: Appeler avant d'arriver, digicode..." 
                               value="<?= htmlspecialchars($_POST['notes'] ?? '') ?>">
                    </div>
                </div>

                <div style="margin-top: 30px;">
                    <h2 class="card-title">
                        <i class="fa-solid fa-money-bill-wave"></i> Mode de Paiement
                    </h2>
                    <div class="payment-method-box">
                        <div class="payment-radio">
                            <input type="radio" id="cod" name="payment_method" value="cod" checked>
                            <label for="cod">
                                <strong>Paiement en espèces à la livraison (COD)</strong>
                                <span>Vous réglez en cash entre les mains du livreur lorsque vous recevez vos produits.</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RIGHT: ORDER SUMMARY PREVIEW -->
            <div class="checkout-summary-sidebar">
                <div class="summary-card">
                    <h3>Votre commande (<?= count($cart_items) ?>)</h3>

                    <div class="mini-cart-items">
                        <?php foreach ($cart_items as $item): ?>
                            <div class="mini-item">
                                <img src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>">
                                <div class="mini-item-details">
                                    <h4><?= htmlspecialchars($item['name']) ?></h4>
                                    <div class="mini-item-meta">
                                        Qté: <?= $item['quantity'] ?> × <?= format_price($item['price']) ?>
                                    </div>
                                </div>
                                <div class="mini-item-price">
                                    <?= format_price($item['price'] * $item['quantity']) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="summary-divider"></div>

                    <div class="summary-row">
                        <span>Sous-total</span>
                        <strong><?= format_price($subtotal) ?></strong>
                    </div>

                    <div class="summary-row">
                        <span>Livraison</span>
                        <?php if ($shipping_fee == 0): ?>
                            <strong style="color:var(--green);">GRATUIT</strong>
                        <?php else: ?>
                            <strong><?= format_price($shipping_fee) ?></strong>
                        <?php endif; ?>
                    </div>

                    <div class="summary-divider"></div>

                    <div class="summary-row total-row">
                        <span>Total à régler</span>
                        <strong class="total-amount"><?= format_price($total) ?></strong>
                    </div>

                    <button type="submit" name="place_order" class="btn btn-primary btn-block btn-lg" style="margin-top: 20px;">
                        <i class="fa-solid fa-check"></i> Confirmer la commande
                    </button>

                    <div class="summary-trust">
                        <div><i class="fa-solid fa-truck-fast"></i> Expédition rapide en 24/48h</div>
                        <div><i class="fa-solid fa-shield-halved"></i> Paiement 100% sécurisé à la réception</div>
                        <div><i class="fa-solid fa-award"></i> Produits 100% authentiques garantis</div>
                    </div>
                </div>
            </div>

        </form>

    <?php endif; ?>
</div>

<?php
$extra_css = '
.checkout-hero { background: linear-gradient(135deg, var(--rose) 0%, #fef8f6 100%); padding: 35px 0; text-align: center; border-bottom: 1px solid var(--border); }
.checkout-hero h1 { font-family: var(--font-heading); font-size: 32px; color: var(--burgundy-dark); margin-top: 6px; }

.checkout-grid { display: grid; grid-template-columns: 1fr 380px; gap: 40px; align-items: start; }
.checkout-card { background: var(--white); border: 1px solid var(--border); border-radius: var(--radius-md); padding: 32px; box-shadow: var(--shadow-sm); }
.card-title { font-family: var(--font-heading); font-size: 20px; font-weight: 600; margin-bottom: 22px; padding-bottom: 12px; border-bottom: 1px solid var(--border); display: flex; align-items: center; gap: 10px; }
.card-title i { color: var(--burgundy); font-size: 18px; }

.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }
.form-group { display: flex; flex-direction: column; }
.form-group.full { grid-column: 1 / -1; }
.form-group label { font-size: 13px; font-weight: 600; color: var(--text); margin-bottom: 8px; }

.payment-method-box { background: var(--rose-light); border: 1px solid #ebd3cd; border-radius: var(--radius-sm); padding: 18px; }
.payment-radio { display: flex; align-items: flex-start; gap: 12px; }
.payment-radio input { accent-color: var(--burgundy); margin-top: 4px; width: 18px; height: 18px; }
.payment-radio label strong { display: block; font-size: 14px; color: var(--text); }
.payment-radio label span { display: block; font-size: 12px; color: var(--muted); margin-top: 4px; line-height: 1.5; }

.mini-cart-items { display: flex; flex-direction: column; gap: 14px; max-height: 280px; overflow-y: auto; padding-right: 5px; }
.mini-item { display: flex; align-items: center; gap: 12px; }
.mini-item img { width: 50px; height: 60px; border-radius: var(--radius-sm); object-fit: cover; background: var(--rose-light); flex-shrink: 0; }
.mini-item-details { flex-grow: 1; min-width: 0; }
.mini-item-details h4 { font-size: 13px; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.mini-item-meta { font-size: 11px; color: var(--muted); margin-top: 3px; }
.mini-item-price { font-size: 13px; font-weight: 700; color: var(--burgundy); text-align: right; }

.order-success-card { max-width: 650px; margin: 20px auto; background: var(--white); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 50px 35px; text-align: center; box-shadow: var(--shadow-md); }
.success-icon { width: 75px; height: 75px; border-radius: 50%; background: #eafaf1; color: var(--green); display: grid; place-items: center; font-size: 34px; margin: 0 auto 20px; }
.order-success-card h2 { font-family: var(--font-heading); font-size: 26px; margin-bottom: 10px; }
.success-subtitle { font-size: 15px; color: var(--muted); margin-bottom: 25px; }

.order-summary-box { background: var(--bg-light); border: 1px solid var(--border); border-radius: var(--radius-md); padding: 22px; text-align: left; }
.order-meta-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; padding-bottom: 16px; border-bottom: 1px solid var(--border); font-size: 13px; }
.meta-label { color: var(--muted); display: block; font-size: 11px; text-transform: uppercase; margin-bottom: 2px; }
.order-items-review { padding-top: 16px; }
.review-item-row { display: flex; justify-content: space-between; font-size: 13px; padding: 6px 0; color: var(--text-light); }

@media (max-width: 992px) {
    .checkout-grid { grid-template-columns: 1fr; }
    .form-grid { grid-template-columns: 1fr; }
}
';

require_once __DIR__ . '/includes/footer.php';
