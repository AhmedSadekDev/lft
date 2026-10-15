/**
 * Leader for Trans (LFT) — Admin UI Scripts
 * Phase 4B: Theme Management, Responsive Drawer, Password Visibility, Charts
 */

(function () {
    'use strict';

    // 1. Theme Management (Light / Dark)
    // The inline <head> script already resolved saved choice → system preference,
    // so read the attribute instead of imposing a second default.
    function getCurrentTheme() {
        return localStorage.getItem('lft_theme')
            || document.documentElement.getAttribute('data-theme')
            || 'light';
    }

    function syncThemeControls(theme) {
        document.querySelectorAll('.lft-theme-toggle-btn').forEach(function (btn) {
            var isActive = btn.getAttribute('data-theme-val') === theme;
            btn.classList.toggle('active', isActive);
            btn.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });
    }

    function applyTheme(theme, persist) {
        document.documentElement.setAttribute('data-theme', theme);
        if (persist) {
            localStorage.setItem('lft_theme', theme);
        }
        syncThemeControls(theme);

        // Trigger custom event for charts and widgets
        window.dispatchEvent(new CustomEvent('lftThemeChanged', { detail: { theme: theme } }));
    }

    applyTheme(getCurrentTheme(), false);

    if (window.matchMedia) {
        var systemDark = window.matchMedia('(prefers-color-scheme: dark)');
        var onSystemChange = function (e) {
            if (!localStorage.getItem('lft_theme')) {
                applyTheme(e.matches ? 'dark' : 'light', false);
            }
        };
        if (systemDark.addEventListener) {
            systemDark.addEventListener('change', onSystemChange);
        }
    }

    // Keep tabs in sync when the theme is changed in another tab.
    window.addEventListener('storage', function (e) {
        if (e.key === 'lft_theme' && e.newValue) {
            applyTheme(e.newValue, false);
        }
    });

    document.addEventListener('DOMContentLoaded', function () {
        syncThemeControls(document.documentElement.getAttribute('data-theme'));

        // Attach theme button event listeners
        document.querySelectorAll('.lft-theme-toggle-btn').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                var newTheme = this.getAttribute('data-theme-val') || (document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark');
                applyTheme(newTheme, true);
            });
        });

        // Simple toggle button
        var simpleToggle = document.getElementById('lftThemeSimpleToggle');
        if (simpleToggle) {
            simpleToggle.addEventListener('click', function (e) {
                e.preventDefault();
                var nextTheme = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
                applyTheme(nextTheme, true);
            });
        }

        // Auth forms: block double submits and show a loading label.
        document.querySelectorAll('form[data-lft-auth-form]').forEach(function (form) {
            form.addEventListener('submit', function () {
                var submit = form.querySelector('[type="submit"]');
                if (!submit) return;
                var label = submit.querySelector('span');
                if (label && submit.getAttribute('data-loading-text')) {
                    label.textContent = submit.getAttribute('data-loading-text');
                }
                submit.disabled = true;
                submit.setAttribute('aria-busy', 'true');
            });
        });

        // 2. Mobile Sidebar Drawer — opening/closing is owned by Metronic's KTOffcanvas
        // (it toggles .aside-on and the overlay); a second toggle here would cancel it out.
        var asideMobileBtn = document.getElementById('kt_aside_mobile_toggle');
        var asideEl = document.getElementById('kt_aside') || document.querySelector('.aside');

        if (asideMobileBtn && asideEl && 'MutationObserver' in window) {
            var syncAsideExpanded = function () {
                asideMobileBtn.setAttribute('aria-expanded', asideEl.classList.contains('aside-on') ? 'true' : 'false');
            };
            new MutationObserver(syncAsideExpanded).observe(asideEl, { attributes: true, attributeFilter: ['class'] });
            syncAsideExpanded();
        }

        // 3. Password Visibility Toggle
        document.querySelectorAll('.lft-glass-toggle-pw').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                var targetId = this.getAttribute('data-target') || 'password';
                var input = document.getElementById(targetId);
                var icon = this.querySelector('i');
                if (input) {
                    var reveal = input.type === 'password';
                    input.type = reveal ? 'text' : 'password';
                    if (icon) {
                        icon.classList.toggle('fa-eye', !reveal);
                        icon.classList.toggle('fa-eye-slash', reveal);
                    }
                    this.setAttribute('aria-pressed', reveal ? 'true' : 'false');
                    this.setAttribute('aria-label', reveal ? 'إخفاء كلمة المرور' : 'إظهار كلمة المرور');
                }
            });
        });

        // 4. Table row dropdowns: tables scroll horizontally, so their menus are
        // positioned fixed to escape the scroll container instead of being clipped.
        // Capture phase runs before Bootstrap's delegated handler builds the instance.
        document.addEventListener('click', function (e) {
            var toggle = e.target.closest ? e.target.closest('[data-toggle="dropdown"]') : null;
            if (toggle && toggle.closest('table, .table-responsive') && !toggle.hasAttribute('data-popper-config')) {
                toggle.setAttribute('data-popper-config', '{"positionFixed":true}');
            }
        }, true);

        // 5. Icon-only row actions get an accessible name from their icon.
        var iconLabels = {
            'fa-edit': 'تعديل', 'fa-pen': 'تعديل', 'fa-pencil-alt': 'تعديل',
            'fa-trash': 'حذف', 'fa-trash-alt': 'حذف',
            'fa-eye': 'عرض', 'fa-ellipsis-v': 'خيارات', 'fa-ellipsis-h': 'خيارات',
            'fa-print': 'طباعة', 'fa-download': 'تحميل'
        };
        var labelIconButtons = function () {
            document.querySelectorAll('.btn-icon:not([aria-label])').forEach(function (btn) {
                if (btn.textContent.trim()) return;
                var icon = btn.querySelector('i');
                if (!icon) return;
                for (var cls in iconLabels) {
                    if (icon.classList.contains(cls)) {
                        btn.setAttribute('aria-label', iconLabels[cls]);
                        if (!btn.hasAttribute('title')) btn.setAttribute('title', iconLabels[cls]);
                        return;
                    }
                }
            });
        };
        labelIconButtons();
        if (window.jQuery) {
            window.jQuery(document).on('draw.dt', labelIconButtons);
        }

        // 6. Sidebar: highlight the current page and open its group.
        var asideMenu = document.getElementById('kt_aside_menu');
        if (asideMenu) {
            var trimPath = function (p) { return p.replace(/\/+$/, '') || '/'; };
            var herePath = trimPath(location.pathname);
            var links = asideMenu.querySelectorAll('a.menu-link[href]');
            var homePath = null;
            var bestLink = null;
            var bestScore = -1;
            links.forEach(function (a) {
                var url;
                if (a.getAttribute('href').indexOf('javascript') === 0) return;
                try { url = new URL(a.href); } catch (err) { return; }
                if (url.origin !== location.origin) return;
                var path = trimPath(url.pathname);
                if (homePath === null) homePath = path;
                var score = -1;
                if (path === herePath) {
                    score = path.length * 2 + (url.search === location.search ? 1 : 0);
                } else if (path !== homePath && herePath.indexOf(path + '/') === 0) {
                    score = path.length * 2;
                }
                if (score > bestScore) {
                    bestScore = score;
                    bestLink = a;
                }
            });
            if (bestLink) {
                var activeItem = bestLink.closest('.menu-item');
                activeItem.classList.add('menu-item-active');
                bestLink.setAttribute('aria-current', 'page');
                var group = activeItem.parentElement.closest('.menu-item');
                if (group) {
                    group.classList.add('menu-item-open', 'menu-item-here');
                }
            }
        }

        // 7. Sidebar page search (filters menu items by their visible labels).
        var sidebarSearch = document.getElementById('lftSidebarSearch');
        if (asideMenu && sidebarSearch) {
            var normalize = function (s) {
                return s.toLowerCase().replace(/[أإآ]/g, 'ا').replace(/ة/g, 'ه').replace(/ى/g, 'ي').replace(/\s+/g, ' ').trim();
            };
            var emptyNote = document.getElementById('lftSidebarEmpty');
            var topItems = asideMenu.querySelectorAll('.menu-nav > .menu-item');
            var filterMenu = function () {
                var q = normalize(sidebarSearch.value);
                var anyVisible = false;
                topItems.forEach(function (item) {
                    var label = item.querySelector('.menu-link > .menu-text');
                    var selfMatch = !!q && !!label && normalize(label.textContent).indexOf(q) !== -1;
                    var subs = item.querySelectorAll('.menu-subnav > .menu-item:not(.menu-item-parent)');
                    var subMatch = false;
                    subs.forEach(function (sub) {
                        var show = !q || selfMatch || normalize(sub.textContent).indexOf(q) !== -1;
                        sub.classList.toggle('lft-filter-hidden', !show);
                        if (show && q) subMatch = true;
                    });
                    var visible = !q || selfMatch || subMatch;
                    item.classList.toggle('lft-filter-hidden', !visible);
                    item.classList.toggle('lft-filter-open', !!q && subs.length > 0 && visible);
                    if (visible) anyVisible = true;
                });
                if (emptyNote) emptyNote.hidden = anyVisible;
            };
            sidebarSearch.addEventListener('input', filterMenu);
            sidebarSearch.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') {
                    sidebarSearch.value = '';
                    filterMenu();
                }
            });
        }

        // 8. Fullscreen Toggle
        var fullscreenBtn = document.getElementById('lftFullscreenToggle');
        if (fullscreenBtn) {
            fullscreenBtn.addEventListener('click', function (e) {
                e.preventDefault();
                if (!document.fullscreenElement) {
                    document.documentElement.requestFullscreen().catch(function () {});
                } else {
                    if (document.exitFullscreen) {
                        document.exitFullscreen().catch(function () {});
                    }
                }
            });
        }
    });
})();
