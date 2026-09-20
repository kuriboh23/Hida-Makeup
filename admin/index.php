<?php
/**
 * HIDA.MAKEUP - Admin dashboard
 * Mobile-first overview: key numbers, quick actions and recent orders.
 */

require_once __DIR__ . '/_layout.php';

// Handle order status update
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['update_status'], $_POST['order_id'], $_POST['status'])) {
    $order_id = (int)$_POST['order_id'];
    $new_status = admin_post('status');
    $allowed = ['pending', 'confirmed', 'shipped', 'delivered', 'cancelled'];

    if (in_array($new_status, $allowed, true)) {
        $stmt = $pdo->prepare("UPDATE orders SET status = :status WHERE id = :id");
        $stmt->execute([':status' => $new_status, ':id' => $order_id]);
        set_flash('success', "Statut de la commande mis à jour.");
    } else {
        set_flash('error', "Statut inconnu.");
    }

    header("Location: index.php#orders");
    exit;
}

// Stats
$total_orders    = (int)$pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$total_sales     = (float)$pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE status != 'cancelled'")->fetchColumn();
$pending_orders  = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();
$total_products  = (int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();

// Recent orders
$recent_orders = $pdo->query("SELECT * FROM orders ORDER BY id DESC LIMIT 20")->fetchAll();

// How many products still have no photo of their own?
$all_slugs = $pdo->query("SELECT id, slug FROM products")->fetchAll();
$without_photo = 0;
foreach ($all_slugs as $row) {
    if (!product_has_local_image((string)$row['slug'], (int)$row['id'])) {
        $without_photo++;
    }
}

$status_labels = [
    'pending'   => 'En attente',
    'confirmed' => 'Confirmée',
    'shipped'   => 'Expédiée',
    'delivered' => 'Livrée',
    'cancelled' => 'Annulée',
];

admin_layout_start('Tableau de bord', 'Tableau de bord');
?>

<h1 class="adm-page-title">Tableau de bord</h1>
<p class="adm-page-sub">Vue d'ensemble de <?= htmlspecialchars(SITE_NAME) ?>.</p>

<!-- STATS -->
<div class="adm-stats">
    <div class="adm-stat">
        <div class="adm-stat-icon"><i class="fa-solid fa-cart-shopping"></i></div>
        <div class="adm-stat-val"><?= $total_orders ?></div>
        <div class="adm-stat-label">Commandes</div>
    </div>
    <div class="adm-stat">
        <div class="adm-stat-icon is-green"><i class="fa-solid fa-money-bill-wave"></i></div>
        <div class="adm-stat-val"><?= format_price($total_sales) ?></div>
        <div class="adm-stat-label">Chiffre d'affaires</div>
    </div>
    <div class="adm-stat">
        <div class="adm-stat-icon is-amber"><i class="fa-solid fa-hourglass-half"></i></div>
        <div class="adm-stat-val"><?= $pending_orders ?></div>
        <div class="adm-stat-label">À traiter</div>
    </div>
    <div class="adm-stat">
        <div class="adm-stat-icon is-blue"><i class="fa-solid fa-boxes-stacked"></i></div>
        <div class="adm-stat-val"><?= $total_products ?></div>
        <div class="adm-stat-label">Produits</div>
    </div>
</div>

<!-- QUICK ACTIONS -->
<div class="adm-card">
    <div class="adm-card-head">
        <div>
            <h2>Actions rapides</h2>
            <p>Gérez votre boutique depuis votre téléphone.</p>
        </div>
    </div>

    <div class="adm-actions-row">
        <a href="product-form.php" class="adm-btn is-primary">
            <i class="fa-solid fa-plus"></i> Nouveau produit
        </a>
        <a href="products.php" class="adm-btn is-outline">
            <i class="fa-solid fa-box-open"></i> Gérer les produits
        </a>
        <a href="https://wa.me/<?= htmlspecialchars(STORE_WHATSAPP) ?>" target="_blank" class="adm-btn is-outline">
            <i class="fa-brands fa-whatsapp"></i> WhatsApp
        </a>
    </div>

    <?php if ($without_photo > 0): ?>
        <a href="products.php?photo=missing" class="adm-flash is-info" style="margin: 14px 0 0; text-decoration: none;">
            <i class="fa-solid fa-image"></i>
            <span><strong><?= $without_photo ?></strong> produit(s) sans photo. Touchez ici pour les remplir.</span>
        </a>
    <?php else: ?>
        <div class="adm-flash is-success" style="margin: 14px 0 0;">
            <i class="fa-solid fa-image"></i>
            <span>Tous vos produits ont leur propre photo.</span>
        </div>
    <?php endif; ?>
</div>

<!-- RECENT ORDERS -->
<div class="adm-card" id="orders">
    <div class="adm-card-head">
        <div>
            <h2>Commandes récentes</h2>
            <p>Confirmez ou suivez une commande en changeant son statut.</p>
        </div>
    </div>

    <?php if (!$recent_orders): ?>
        <div class="adm-empty">
            <i class="fa-solid fa-receipt"></i>
            Aucune commande enregistrée pour le moment.
        </div>
    <?php else: ?>
        <div class="adm-table-wrap">
            <table class="adm-table">
                <thead>
                    <tr>
                        <th>Commande</th>
                        <th>Date</th>
                        <th>Client</th>
                        <th>Téléphone</th>
                        <th>Ville</th>
                        <th>Montant</th>
                        <th>Statut</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_orders as $ord): ?>
                        <?php
                        $status = (string)$ord['status'];
                        $status_class = 'is-' . (isset($status_labels[$status]) ? $status : 'pending');
                        $phone_digits = preg_replace('/[^0-9]/', '', (string)$ord['customer_phone']);
                        ?>
                        <tr>
                            <td class="adm-cell-main">
                                <div>
                                    <div class="adm-row-title"><?= htmlspecialchars($ord['order_number']) ?></div>
                                    <div class="adm-row-meta"><?= htmlspecialchars(date('d/m/Y · H:i', strtotime((string)$ord['created_at']))) ?></div>
                                </div>
                            </td>
                            <td data-label="Date"><?= htmlspecialchars(date('d/m/Y H:i', strtotime((string)$ord['created_at']))) ?></td>
                            <td data-label="Client"><?= htmlspecialchars($ord['customer_name']) ?></td>
                            <td data-label="Téléphone">
                                <a href="https://wa.me/<?= htmlspecialchars($phone_digits) ?>" target="_blank" style="color:var(--green); font-weight:600;">
                                    <i class="fa-brands fa-whatsapp"></i> <?= htmlspecialchars($ord['customer_phone']) ?>
                                </a>
                            </td>
                            <td data-label="Ville"><?= htmlspecialchars($ord['customer_city']) ?></td>
                            <td data-label="Montant"><strong><?= format_price($ord['total_amount']) ?></strong></td>
                            <td data-label="Statut">
                                <span class="adm-badge <?= $status_class ?>"><?= htmlspecialchars($status_labels[$status] ?? ucfirst($status)) ?></span>
                            </td>
                            <td class="adm-cell-block" data-label="Changer le statut">
                                <form method="POST">
                                    <input type="hidden" name="order_id" value="<?= (int)$ord['id'] ?>">
                                    <div class="adm-actions-row">
                                        <select name="status" class="adm-select" style="flex:1; min-width:140px; min-height:44px;">
                                            <?php foreach ($status_labels as $value => $label): ?>
                                                <option value="<?= $value ?>" <?= $status === $value ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" name="update_status" class="adm-btn is-primary">
                                            <i class="fa-solid fa-check"></i> Valider
                                        </button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php admin_layout_end(); ?>
