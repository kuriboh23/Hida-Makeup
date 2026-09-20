<?php
/**
 * HIDA.MAKEUP - Global Reusable Header
 */
require_once __DIR__ . '/config.php';

// Detect active page for navigation highlight
$current_page = basename($_SERVER['PHP_SELF']);
$cart_count = get_cart_count();
$wishlist_count = get_wishlist_count();
$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? htmlspecialchars($page_title) . " — " . SITE_NAME : SITE_NAME . " — " . SITE_TAGLINE ?></title>
    <meta name="description" content="<?= isset($page_description) ? htmlspecialchars($page_description) : SITE_DESCRIPTION ?>">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=Playfair+Display:ital,wght@0,500;0,600;0,700;1,500;1,600&display=swap" rel="stylesheet">

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <!-- Master CSS Design System -->
    <link rel="stylesheet" href="<?= BASE_PATH ?>assets/css/style.css">
    <link rel="stylesheet" href="<?= BASE_PATH ?>assets/css/responsive.css">
</head>
<body>

    <!-- TOP ANNOUNCEMENT BAR -->
    <?php if (TOPBAR_ENABLED): ?>
    <div class="topbar">
        <i class="fa-solid fa-truck-fast"></i>
        <span><?= htmlspecialchars(TOPBAR_TEXT) ?></span>
    </div>
    <?php endif; ?>

    <!-- MAIN STICKY HEADER -->
    <header>
        <div class="container header-inner">
            <button class="icon-btn mobile-menu-btn" onclick="openMenu()" aria-label="Menu de navigation">
                <i class="fa-solid fa-bars"></i>
            </button>

            <nav class="nav">
                <a href="<?= BASE_PATH ?>index.php" class="<?= $current_page === 'index.php' ? 'active' : '' ?>">Accueil</a>
                <a href="<?= BASE_PATH ?>products.php" class="<?= $current_page === 'products.php' ? 'active' : '' ?>">Boutique</a>
                <a href="<?= BASE_PATH ?>category.php" class="<?= $current_page === 'category.php' ? 'active' : '' ?>">Catégories</a>
                <a href="<?= BASE_PATH ?>index.php#about">À propos</a>
                <a href="<?= BASE_PATH ?>index.php#contact">Contact</a>
            </nav>

            <a href="<?= BASE_PATH ?>index.php" class="logo">
                <?= htmlspecialchars(SITE_LOGO_PREFIX) ?><span><?= htmlspecialchars(SITE_LOGO_SUFFIX) ?></span>
            </a>

            <div class="header-actions">
                <button class="icon-btn search-btn" onclick="openSearch()" aria-label="Rechercher des produits">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </button>

                <a href="<?= BASE_PATH ?>favorites.php" class="icon-btn" aria-label="Mes favoris">
                    <i class="fa-regular fa-heart"></i>
                    <span class="cart-count wishlist-count" style="<?= $wishlist_count > 0 ? '' : 'display:none;' ?>">
                        <?= $wishlist_count ?>
                    </span>
                </a>

                <a href="<?= BASE_PATH ?>cart.php" class="icon-btn cart-btn" aria-label="Mon Panier">
                    <i class="fa-solid fa-bag-shopping"></i>
                    <span class="cart-count" id="headerCartCount"><?= $cart_count ?></span>
                </a>
            </div>
        </div>
    </header>

    <!-- MOBILE MENU DRAWER -->
    <div class="mobile-menu" id="mobileMenu">
        <div>
            <div class="mobile-menu-top">
                <div class="logo"><?= htmlspecialchars(SITE_LOGO_PREFIX) ?><span><?= htmlspecialchars(SITE_LOGO_SUFFIX) ?></span></div>
                <button class="close-btn" onclick="closeMenu()" aria-label="Fermer le menu">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <nav class="mobile-links">
                <a href="<?= BASE_PATH ?>index.php">Accueil <i class="fa-solid fa-angle-right" style="font-size:14px;color:var(--muted)"></i></a>
                <a href="<?= BASE_PATH ?>products.php">Boutique <i class="fa-solid fa-angle-right" style="font-size:14px;color:var(--muted)"></i></a>
                <a href="<?= BASE_PATH ?>category.php">Catégories <i class="fa-solid fa-angle-right" style="font-size:14px;color:var(--muted)"></i></a>
                <a href="<?= BASE_PATH ?>favorites.php">Mes favoris (<?= $wishlist_count ?>) <i class="fa-solid fa-angle-right" style="font-size:14px;color:var(--muted)"></i></a>
                <a href="<?= BASE_PATH ?>cart.php">Mon Panier (<?= $cart_count ?>) <i class="fa-solid fa-angle-right" style="font-size:14px;color:var(--muted)"></i></a>
                <a href="<?= BASE_PATH ?>index.php#about" onclick="closeMenu()">À propos <i class="fa-solid fa-angle-right" style="font-size:14px;color:var(--muted)"></i></a>
                <a href="<?= BASE_PATH ?>index.php#contact" onclick="closeMenu()">Contact <i class="fa-solid fa-angle-right" style="font-size:14px;color:var(--muted)"></i></a>
            </nav>
        </div>

        <div class="mobile-menu-footer">
            <div class="mobile-contact-info">
                <div><i class="fa-solid fa-phone" style="color:var(--burgundy);margin-right:6px;"></i> <?= htmlspecialchars(STORE_PHONE) ?></div>
                <div><i class="fa-solid fa-location-dot" style="color:var(--burgundy);margin-right:6px;"></i> <?= htmlspecialchars(STORE_ADDRESS) ?></div>
            </div>
            <a href="<?= htmlspecialchars(STORE_WHATSAPP_LINK) ?>" target="_blank" class="btn btn-whatsapp btn-block">
                <i class="fa-brands fa-whatsapp"></i> Commander par WhatsApp
            </a>
        </div>
    </div>

    <!-- QUICK SEARCH OVERLAY -->
    <div class="search-modal" id="searchModal">
        <div class="search-modal-inner">
            <form action="products.php" method="GET" class="search-input-wrap">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" name="q" id="searchInput" placeholder="Rechercher un rouge à lèvres, fond de teint, parfum..." autocomplete="off">
            </form>
            <button class="close-btn" onclick="closeSearch()" aria-label="Fermer la recherche">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    </div>

    <!-- FLASH MESSAGE TOAST (IF ANY) -->
    <?php if ($flash): ?>
    <div class="toast-container">
        <div class="toast toast-<?= htmlspecialchars($flash['type']) ?> toast-auto-dismiss">
            <i class="fa-solid <?= $flash['type'] === 'success' ? 'fa-check-circle' : 'fa-circle-exclamation' ?>"></i>
            <span><?= htmlspecialchars($flash['message']) ?></span>
        </div>
    </div>
    <?php endif; ?>

    <!-- MAIN PAGE CONTENT WRAPPER -->
    <main>
