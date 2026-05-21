/**
 * Frosty Mobile UI — theme, pull-to-refresh, inventory modal helper.
 */
(function () {
    const html = document.documentElement;
    const body = document.body;

    function initTheme() {
        const toggle = document.getElementById('themeToggle');
        const icon = document.getElementById('themeIcon');
        const stored = localStorage.getItem('frosty-theme');
        if (stored) {
            html.setAttribute('data-bs-theme', stored);
        }
        const meta = document.querySelector('meta[name="theme-color"]');
        const syncIcon = () => {
            const dark = html.getAttribute('data-bs-theme') === 'dark';
            if (icon) {
                icon.className = dark ? 'fa-solid fa-sun' : 'fa-solid fa-moon';
            }
            if (meta && window.FrostyUI?.colors) {
                meta.content = dark ? '#1a1d21' : window.FrostyUI.colors.primary;
            }
        };
        syncIcon();
        toggle?.addEventListener('click', () => {
            const next = html.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
            html.setAttribute('data-bs-theme', next);
            localStorage.setItem('frosty-theme', next);
            syncIcon();
            document.dispatchEvent(new CustomEvent('frosty-theme-changed', { detail: { theme: next } }));
        });
    }

    function initPullToRefresh() {
        if (body.dataset.ptr !== '1') {
            return;
        }
        const ptr = document.getElementById('ptr-scroll');
        const indicator = document.getElementById('ptr-indicator');
        if (!ptr || !indicator) {
            return;
        }
        let startY = 0;
        let pulling = false;
        ptr.addEventListener(
            'touchstart',
            (e) => {
                if (window.scrollY <= 0) {
                    startY = e.touches[0].clientY;
                    pulling = true;
                }
            },
            { passive: true },
        );
        const setVisible = (visible) => {
            indicator.classList.toggle('ptr-visible', visible);
            indicator.setAttribute('aria-hidden', visible ? 'false' : 'true');
        };
        ptr.addEventListener(
            'touchmove',
            (e) => {
                if (!pulling) {
                    return;
                }
                const dy = e.touches[0].clientY - startY;
                setVisible(dy > 50);
            },
            { passive: true },
        );
        ptr.addEventListener('touchend', () => {
            if (indicator.classList.contains('ptr-visible')) {
                window.location.reload();
            }
            setVisible(false);
            pulling = false;
        });
    }

    function initInventoryAdjustModal() {
        const modal = document.getElementById('adjustModal');
        if (!modal) {
            return;
        }
        modal.addEventListener('show.bs.modal', (e) => {
            const btn = e.relatedTarget;
            if (!btn?.dataset) {
                return;
            }
            const set = (id, val) => {
                const el = document.getElementById(id);
                if (el) {
                    el.value = val ?? '';
                }
            };
            set('adj-product-id', btn.dataset.productId);
            const nameEl = document.getElementById('adj-product-name');
            if (nameEl) {
                nameEl.textContent = btn.dataset.productName || '—';
            }
            const stockEl = document.getElementById('adj-current-stock');
            if (stockEl) {
                stockEl.textContent = btn.dataset.stock || '0';
            }
            set('adj-min-stock', btn.dataset.min || '');
        });
    }

    function applyThemeTokens() {
        const ui = window.FrostyUI;
        if (!ui?.colors) {
            return;
        }
        const root = document.documentElement;
        Object.entries(ui.colors).forEach(([key, value]) => {
            root.style.setProperty(`--frosty-${key.replace(/_/g, '-')}`, value);
        });
    }

    applyThemeTokens();
    initTheme();
    initPullToRefresh();
    initInventoryAdjustModal();
})();
