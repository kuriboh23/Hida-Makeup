<?php
/**
 * HIDA.MAKEUP - Admin Dashboard
 * Quick overview of orders, customers, products, and inventory.
 */

require_once __DIR__ . '/../includes/db.php';

// Handle order status update
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['update_status'], $_POST['order_id'], $_POST['status'])) {
    $order_id = (int)$_POST['order_id'];
    $new_status = sanitize($_POST['status']);
    $allowed = ['pending', 'confirmed', 'shipped', 'delivered', 'cancelled'];
    if (in_array($new_status, $allowed, true)) {
        $stmt = $pdo->prepare("UPDATE orders SET status = :status WHERE id = :id");
        $stmt->execute([':status' => $new_status, ':id' => $order_id]);
        set_flash('success', "Statut de la commande mis à jour avec succès.");
        header("Location: index.php");
        exit;
    }
}

// Stats
$total_orders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$total_sales = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE status != 'cancelled'")->fetchColumn();
$total_products = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$pending_orders = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();

// Recent Orders
$orders_stmt = $pdo->query("SELECT * FROM orders ORDER BY id DESC LIMIT 15");
$recent_orders = $orders_stmt->fetchAll();

// Products list
$prod_stmt = $pdo->query("SELECT p.*, c.name AS category_name FROM products p JOIN categories c ON p.category_id = c.id ORDER BY p.id DESC LIMIT 10");
$products = $prod_stmt->fetchAll();

$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration — <?= htmlspecialchars(SITE_NAME) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { background: #f8f6f5; }
        .admin-nav { background: var(--white); border-bottom: 1px solid var(--border); padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; }
        .admin-nav .logo { font-size: 18px; }
        .admin-wrapper { max-width: 1200px; margin: 30px auto; padding: 0 20px; }
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: var(--white); border: 1px solid var(--border); border-radius: var(--radius-md); padding: 22px; display: flex; align-items: center; gap: 18px; }
        .stat-icon { width: 50px; height: 50px; border-radius: var(--radius-md); background: var(--rose); color: var(--burgundy); display: grid; place-items: center; font-size: 20px; }
        .stat-val { font-size: 24px; font-weight: 700; color: var(--text); }
        .stat-label { font-size: 12px; color: var(--muted); text-transform: uppercase; letter-spacing: 0.5px; }

        .admin-table-card { background: var(--white); border: 1px solid var(--border); border-radius: var(--radius-md); padding: 25px; margin-bottom: 30px; box-shadow: var(--shadow-sm); }
        .admin-table-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .admin-table-header h2 { font-family: var(--font-heading); font-size: 20px; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; text-align: left; }
        th { padding: 12px 14px; background: var(--bg-light); color: var(--muted); font-weight: 700; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px; border-bottom: 1px solid var(--border); }
        td { padding: 14px; border-bottom: 1px solid var(--border); vertical-align: middle; }
        .status-badge { display: inline-block; padding: 4px 10px; border-radius: var(--radius-full); font-size: 11px; font-weight: 700; text-transform: uppercase; }
        .status-pending { background: #fff8e1; color: #f57f17; }
        .status-confirmed { background: #e3f2fd; color: #1976d2; }
        .status-shipped { background: #f3e5f5; color: #7b1fa2; }
        .status-delivered { background: #e8f5e9; color: #2e7d32; }
        .status-cancelled { background: #ffebee; color: #c62828; }

        @media (max-width: 992px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .table-responsive { overflow-x: auto; }
        }
    </style>
</head>
<body>

    <nav class="admin-nav">
        <a href="index.php" class="logo">
            <?= htmlspecialchars(SITE_LOGO_PREFIX) ?><span><?= htmlspecialchars(SITE_LOGO_SUFFIX) ?></span> &nbsp;<small style="font-size:11px;color:var(--burgundy);letter-spacing:1px;font-weight:600;">ADMIN</small>
        </a>
        <div style="display:flex; gap:12px;">
            <a href="../index.php" target="_blank" class="btn btn-outline btn-sm">
                <i class="fa-solid fa-arrow-up-right-from-square"></i> Voir le site
            </a>
            <a href="https://wa.me/<?= STORE_WHATSAPP ?>" target="_blank" class="btn btn-whatsapp btn-sm">
                <i class="fa-brands fa-whatsapp"></i> WhatsApp Store
            </a>
        </div>
    </nav>

    <div class="admin-wrapper">
        <?php if ($flash): ?>
            <div style="background:#eafaf1; color:#2e7d32; border-left:4px solid #2e7d32; padding:14px; border-radius:6px; margin-bottom:25px; font-size:13px;">
                <i class="fa-solid fa-check-circle"></i> <?= htmlspecialchars($flash['message']) ?>
            </div>
        <?php endif; ?>

        <!-- STATS -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fa-solid fa-cart-shopping"></i></div>
                <div>
                    <div class="stat-val"><?= $total_orders ?></div>
                    <div class="stat-label">Total Commandes</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:#eafaf1; color:var(--green);"><i class="fa-solid fa-money-bill-wave"></i></div>
                <div>
                    <div class="stat-val"><?= format_price($total_sales) ?></div>
                    <div class="stat-label">Chiffre d'Affaires</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:#fff8e1; color:#f57f17;"><i class="fa-solid fa-clock"></i></div>
                <div>
                    <div class="stat-val"><?= $pending_orders ?></div>
                    <div class="stat-label">En Attente</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fa-solid fa-boxes-stacked"></i></div>
                <div>
                    <div class="stat-val"><?= $total_products ?></div>
                    <div class="stat-label">Articles en Ligne</div>
                </div>
            </div>
        </div>

        <!-- RECENT ORDERS -->
        <div class="admin-table-card">
            <div class="admin-table-header">
                <h2>Commandes Récentes</h2>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>N° Commande</th>
                            <th>Date</th>
                            <th>Cliente</th>
                            <th>Téléphone</th>
                            <th>Ville</th>
                            <th>Montant</th>
                            <th>Statut</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($recent_orders)): ?>
                            <?php foreach ($recent_orders as $ord): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($ord['order_number']) ?></strong></td>
                                    <td><?= date('d/m/Y H:i', strtotime($ord['created_at'])) ?></td>
                                    <td><?= htmlspecialchars($ord['customer_name']) ?></td>
                                    <td>
                                        <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $ord['customer_phone']) ?>" target="_blank" style="color:var(--green); font-weight:600;">
                                            <i class="fa-brands fa-whatsapp"></i> <?= htmlspecialchars($ord['customer_phone']) ?>
                                        </a>
                                    </td>
                                    <td><?= htmlspecialchars($ord['customer_city']) ?></td>
                                    <td><strong><?= format_price($ord['total_amount']) ?></strong></td>
                                    <td>
                                        <span class="status-badge status-<?= $ord['status'] ?>">
                                            <?= htmlspecialchars(ucfirst($ord['status'])) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <form method="POST" style="display:inline-flex; gap:6px;">
                                            <input type="hidden" name="order_id" value="<?= $ord['id'] ?>">
                                            <select name="status" style="padding:4px 8px; border:1px solid var(--border); border-radius:4px; font-size:12px;">
                                                <option value="pending" <?= $ord['status'] === 'pending' ? 'selected' : '' ?>>En attente</option>
                                                <option value="confirmed" <?= $ord['status'] === 'confirmed' ? 'selected' : '' ?>>Confirmée</option>
                                                <option value="shipped" <?= $ord['status'] === 'shipped' ? 'selected' : '' ?>>Expédiée</option>
                                                <option value="delivered" <?= $ord['status'] === 'delivered' ? 'selected' : '' ?>>Livrée</option>
                                                <option value="cancelled" <?= $ord['status'] === 'cancelled' ? 'selected' : '' ?>>Annulée</option>
                                            </select>
                                            <button type="submit" name="update_status" class="btn btn-primary btn-sm" style="padding:4px 8px; font-size:11px;">
                                                OK
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" style="text-align:center; padding:30px; color:var(--muted);">
                                    Aucune commande enregistrée pour le moment.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- PRODUCTS OVERVIEW -->
        <div class="admin-table-card">
            <div class="admin-table-header">
                <h2>Aperçu du Catalogue Produits</h2>
                <a href="../products.php" target="_blank" class="btn btn-outline btn-sm">Voir la boutique</a>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Visuel</th>
                            <th>Produit</th>
                            <th>Catégorie</th>
                            <th>Prix</th>
                            <th>Stock</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $p): ?>
                            <tr>
                                <td>
                                    <img src="<?= htmlspecialchars($p['main_image']) ?>" style="width:40px; height:48px; border-radius:4px; object-fit:cover;">
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($p['name']) ?></strong>
                                    <?php if ($p['badge']): ?>
                                        <span class="badge badge-sale" style="margin-left:5px;"><?= htmlspecialchars($p['badge']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($p['category_name']) ?></td>
                                <td><strong><?= format_price($p['price']) ?></strong></td>
                                <td><?= $p['stock_quantity'] ?> unités</td>
                                <td>
                                    <span class="status-badge <?= $p['in_stock'] ? 'status-delivered' : 'status-cancelled' ?>">
                                        <?= $p['in_stock'] ? 'En stock' : 'Rupture' ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</body>
</html>
