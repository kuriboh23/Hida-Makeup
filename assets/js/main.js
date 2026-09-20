/**
 * HIDA.MAKEUP - Main Interactive JavaScript
 * Handles navigation drawers, search overlay, toast messages, and interactive client actions.
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Setup Mobile Menu listeners
    const mobileMenu = document.getElementById('mobileMenu');
    
    window.openMenu = function() {
        if (mobileMenu) {
            mobileMenu.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    };

    window.closeMenu = function() {
        if (mobileMenu) {
            mobileMenu.classList.remove('active');
            document.body.style.overflow = '';
        }
    };

    // Close mobile menu on Escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeMenu();
            closeSearch();
        }
    });

    // 2. Setup Search Modal
    const searchModal = document.getElementById('searchModal');
    const searchInput = document.getElementById('searchInput');

    window.openSearch = function() {
        if (searchModal) {
            searchModal.classList.add('active');
            if (searchInput) {
                setTimeout(() => searchInput.focus(), 150);
            }
        }
    };

    window.closeSearch = function() {
        if (searchModal) {
            searchModal.classList.remove('active');
        }
    };

    // 3. Toast Notifications
    window.showToast = function(message, type = 'success') {
        let container = document.getElementById('toastContainer');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toastContainer';
            container.className = 'toast-container';
            document.body.appendChild(container);
        }

        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        const icon = type === 'success' ? 'fa-check-circle' : 'fa-circle-exclamation';
        toast.innerHTML = `<i class="fa-solid ${icon}"></i> <span>${message}</span>`;

        container.appendChild(toast);

        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(10px)';
            toast.style.transition = 'all 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, 3500);
    };

    // 4. Wishlist / Favorites Toggle handler
    document.querySelectorAll('.product-fav-btn').forEach(btn => {
        btn.addEventListener('click', async function(e) {
            e.preventDefault();
            e.stopPropagation();

            const productId = this.dataset.id;
            if (!productId) return;

            try {
                const response = await fetch(`favorites.php?action=toggle&id=${productId}&ajax=1`);
                const data = await response.json();

                if (data.status === 'success') {
                    if (data.added) {
                        this.classList.add('active');
                        this.innerHTML = '<i class="fa-solid fa-heart"></i>';
                        showToast('Produit ajouté à vos favoris !', 'success');
                    } else {
                        this.classList.remove('active');
                        this.innerHTML = '<i class="fa-regular fa-heart"></i>';
                        showToast('Produit retiré de vos favoris.', 'info');
                    }

                    // Update header wishlist counter if present
                    const countEl = document.querySelector('.wishlist-count');
                    if (countEl) {
                        countEl.textContent = data.total_count;
                        countEl.style.display = data.total_count > 0 ? 'grid' : 'none';
                    }
                }
            } catch (err) {
                console.error('Wishlist error:', err);
                // Fallback to standard redirect if needed
                window.location.href = `favorites.php?action=toggle&id=${productId}`;
            }
        });
    });

    // 5. Auto dismiss flash toasts if rendered from PHP
    const phpToast = document.querySelector('.toast-auto-dismiss');
    if (phpToast) {
        setTimeout(() => {
            phpToast.style.opacity = '0';
            phpToast.style.transform = 'translateY(10px)';
            setTimeout(() => phpToast.remove(), 300);
        }, 4000);
    }
});

// 6. Shop Filters & Sorting Helpers
window.toggleFilterSidebar = function() {
    const sidebar = document.getElementById("shopSidebar");
    if (sidebar) {
        sidebar.classList.toggle("active");
        document.body.style.overflow = sidebar.classList.contains("active") ? "hidden" : "";
    }
};

window.applySort = function(sortVal) {
    const url = new URL(window.location.href);
    url.searchParams.set("sort", sortVal);
    window.location.href = url.toString();
};

window.applyCatSort = window.applySort;

// 7. Product Page Gallery, Tabs & Quantity Helpers
window.switchProductImg = function(element, src) {
    const mainImg = document.getElementById("mainProductImg");
    if (mainImg) mainImg.src = src;
    document.querySelectorAll(".thumbnail").forEach(el => el.classList.remove("active"));
    element.classList.add("active");
};

window.increaseQty = function() {
    const input = document.getElementById("productQty");
    if (input) {
        const max = parseInt(input.getAttribute("max") || 100);
        let val = parseInt(input.value) || 1;
        if (val < max) {
            input.value = val + 1;
        }
    }
};

window.decreaseQty = function() {
    const input = document.getElementById("productQty");
    if (input) {
        let val = parseInt(input.value) || 1;
        if (val > 1) {
            input.value = val - 1;
        }
    }
};

window.switchTab = function(btn, tabId) {
    document.querySelectorAll(".tab-btn").forEach(b => b.classList.remove("active"));
    document.querySelectorAll(".tab-body").forEach(t => {
        t.style.display = "none";
        t.classList.remove("active");
    });
    btn.classList.add("active");
    const target = document.getElementById(tabId);
    if (target) {
        target.style.display = "block";
        target.classList.add("active");
    }
};

