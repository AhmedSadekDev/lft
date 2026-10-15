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

        // 4. Fullscreen Toggle
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
