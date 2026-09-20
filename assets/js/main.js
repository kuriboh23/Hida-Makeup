/**
 * HIDA.MAKEUP - Main Interactive JavaScript
 *
 * Handles navigation drawers, search overlay, toasts, and the asynchronous
 * cart / favorites actions. Adding to the cart never reloads the page: the
 * header counter and the cart cookie are updated from the JSON response.
 * Every action still has a working non-JavaScript fallback (plain link/form).
 */

const HIDA_BASE = window.HIDA_BASE || '/';

document.addEventListener('DOMContentLoaded', () => {
    /* ------------------------------------------------------------------ *
     * 1. Mobile menu
     * ------------------------------------------------------------------ */
    const mobileMenu = document.getElementById('mobileMenu');

    window.openMenu = function () {
        if (mobileMenu) {
            mobileMenu.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    };

    window.closeMenu = function () {
        if (mobileMenu) {
            mobileMenu.classList.remove('active');
            document.body.style.overflow = '';
        }
    };

    /* ------------------------------------------------------------------ *
     * 2. Quick search overlay
     * ------------------------------------------------------------------ */
    const searchModal = document.getElementById('searchModal');
    const searchInput = document.getElementById('searchInput');

    window.openSearch = function () {
        if (searchModal) {
            searchModal.classList.add('active');
            if (searchInput) {
                setTimeout(() => searchInput.focus(), 150);
            }
        }
    };

    window.closeSearch = function () {
        if (searchModal) {
            searchModal.classList.remove('active');
        }
    };

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeMenu();
            closeSearch();
        }
    });

    /* ------------------------------------------------------------------ *
     * 3. Toast notifications
     * ------------------------------------------------------------------ */
    window.showToast = function (message, type = 'success') {
        let container = document.getElementById('toastContainer');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toastContainer';
            container.className = 'toast-container';
            document.body.appendChild(container);
        }

        const icons = {
            success: 'fa-check-circle',
            error: 'fa-circle-exclamation',
            info: 'fa-circle-info',
        };

        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `<i class="fa-solid ${icons[type] || icons.info}"></i> <span></span>`;
        toast.querySelector('span').textContent = message;

        container.appendChild(toast);

        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(10px)';
            toast.style.transition = 'all 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, 3500);
    };

    /* ------------------------------------------------------------------ *
     * 4. Header counters
     * ------------------------------------------------------------------ */
    function bump(element) {
        element.classList.remove('bump');
        void element.offsetWidth; // restart the animation
        element.classList.add('bump');
    }

    function setCartCount(count) {
        const badge = document.getElementById('headerCartCount');
        if (!badge) return;
        badge.textContent = count;
        bump(badge);
    }

    function setWishlistCount(count) {
        const badge = document.querySelector('.wishlist-count');
        if (!badge) return;
        badge.textContent = count;
        badge.style.display = count > 0 ? 'grid' : 'none';
        bump(badge);
    }

    /* ------------------------------------------------------------------ *
     * 5. Add to cart (asynchronous - stays on the current page)
     * ------------------------------------------------------------------ */
    async function addToCart(productId, quantity = 1, trigger = null) {
        if (!productId) return;

        const params = new URLSearchParams({
            action: 'add',
            ajax: '1',
            id: productId,
            qty: quantity,
        });

        if (trigger) trigger.classList.add('is-loading');

        try {
            const response = await fetch(`${HIDA_BASE}cart.php?${params.toString()}`);
            const data = await response.json();

            if (data.status !== 'success') {
                showToast(data.message || "Impossible d'ajouter ce produit.", 'error');
                return;
            }

            setCartCount(data.cart_count);
            showToast('Ajouté au panier !', 'success');
        } catch (err) {
            console.error('Add to cart error:', err);
            // Last resort: fall back to the classic full-page add.
            window.location.href = `${HIDA_BASE}cart.php?action=add&id=${encodeURIComponent(productId)}&qty=${encodeURIComponent(quantity)}`;
        } finally {
            if (trigger) trigger.classList.remove('is-loading');
        }
    }

    /* Product cards & related products */
    document.querySelectorAll('.add-cart-btn[data-id]').forEach((btn) => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            addToCart(btn.dataset.id, 1, btn);
        });
    });

    /* Single product page: use the quantity the customer picked */
    const purchaseForm = document.querySelector('.purchase-form');
    if (purchaseForm) {
        purchaseForm.addEventListener('submit', (e) => {
            e.preventDefault();

            const id = purchaseForm.querySelector('input[name="id"]')?.value;
            const qty = purchaseForm.querySelector('input[name="qty"]')?.value || 1;

            addToCart(id, qty, purchaseForm.querySelector('button[type="submit"]'));
        });
    }

    /* ------------------------------------------------------------------ *
     * 6. Favorites toggle
     * ------------------------------------------------------------------ */
    document.querySelectorAll('.product-fav-btn[data-id]').forEach((btn) => {
        btn.addEventListener('click', async function (e) {
            e.preventDefault();
            e.stopPropagation();

            const productId = this.dataset.id;

            try {
                const response = await fetch(`${HIDA_BASE}favorites.php?action=toggle&ajax=1&id=${encodeURIComponent(productId)}`);
                const data = await response.json();

                if (data.status !== 'success') return;

                this.classList.toggle('active', data.added);

                const icon = `<i class="fa-${data.added ? 'solid' : 'regular'} fa-heart"></i>`;
                // The product page shows a worded button, cards only an icon.
                this.innerHTML = this.classList.contains('btn')
                    ? `${icon} ${data.added ? 'Dans vos favoris' : 'Ajouter aux favoris'}`
                    : icon;

                setWishlistCount(data.total_count);
                showToast(data.added ? 'Produit ajouté à vos favoris !' : 'Produit retiré de vos favoris.', data.added ? 'success' : 'info');
            } catch (err) {
                console.error('Wishlist error:', err);
                window.location.href = `${HIDA_BASE}favorites.php?action=toggle&id=${encodeURIComponent(productId)}`;
            }
        });
    });

    /* ------------------------------------------------------------------ *
     * 7. Auto dismiss the flash toast rendered by PHP
     * ------------------------------------------------------------------ */
    const phpToast = document.querySelector('.toast-auto-dismiss');
    if (phpToast) {
        setTimeout(() => {
            phpToast.style.opacity = '0';
            phpToast.style.transform = 'translateY(10px)';
            setTimeout(() => phpToast.remove(), 300);
        }, 4000);
    }
});

/* ---------------------------------------------------------------------- *
 * Global helpers used from inline markup
 * ---------------------------------------------------------------------- */

window.toggleFilterSidebar = function () {
    const sidebar = document.getElementById('shopSidebar');
    if (sidebar) {
        sidebar.classList.toggle('active');
        document.body.style.overflow = sidebar.classList.contains('active') ? 'hidden' : '';
    }
};

window.applySort = function (sortVal) {
    const url = new URL(window.location.href);
    url.searchParams.set('sort', sortVal);
    window.location.href = url.toString();
};

window.switchProductImg = function (element, src) {
    const mainImg = document.getElementById('mainProductImg');
    if (mainImg) mainImg.src = src;
    document.querySelectorAll('.thumbnail').forEach((el) => el.classList.remove('active'));
    element.classList.add('active');
};

window.increaseQty = function () {
    const input = document.getElementById('productQty');
    if (!input) return;
    const max = parseInt(input.getAttribute('max') || 100);
    const val = parseInt(input.value) || 1;
    if (val < max) input.value = val + 1;
};

window.decreaseQty = function () {
    const input = document.getElementById('productQty');
    if (!input) return;
    const val = parseInt(input.value) || 1;
    if (val > 1) input.value = val - 1;
};

window.switchTab = function (btn, tabId) {
    document.querySelectorAll('.tab-btn').forEach((b) => b.classList.remove('active'));
    document.querySelectorAll('.tab-body').forEach((t) => {
        t.style.display = 'none';
        t.classList.remove('active');
    });

    btn.classList.add('active');

    const target = document.getElementById(tabId);
    if (target) {
        target.style.display = 'block';
        target.classList.add('active');
    }
};
