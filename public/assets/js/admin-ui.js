/**
 * Leader for Trans (LFT) — Admin UI Scripts
 * Phase 4B: Theme Management, Responsive Drawer, Password Visibility, Charts
 */

(function () {
    'use strict';

    // 1. Theme Management (Light / Dark)
    function getStoredTheme() {
        return localStorage.getItem('lft_theme') || 'light';
    }

    function applyTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem('lft_theme', theme);

        // Update toggle buttons active state
        document.querySelectorAll('.lft-theme-toggle-btn').forEach(function (btn) {
            var btnTheme = btn.getAttribute('data-theme-val');
            if (btnTheme === theme) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }
        });

        // Trigger custom event for charts and widgets
        window.dispatchEvent(new CustomEvent('lftThemeChanged', { detail: { theme: theme } }));
    }

    // Initialize theme immediately
    var currentTheme = getStoredTheme();
    applyTheme(currentTheme);

    document.addEventListener('DOMContentLoaded', function () {
        // Apply theme to DOM elements on load
        applyTheme(getStoredTheme());

        // Attach theme button event listeners
        document.querySelectorAll('.lft-theme-toggle-btn').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                var newTheme = this.getAttribute('data-theme-val') || (document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark');
                applyTheme(newTheme);
            });
        });

        // Simple toggle button
        var simpleToggle = document.getElementById('lftThemeSimpleToggle');
        if (simpleToggle) {
            simpleToggle.addEventListener('click', function (e) {
                e.preventDefault();
                var nextTheme = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
                applyTheme(nextTheme);
            });
        }

        // 2. Mobile Sidebar Drawer Toggle
        var asideMobileBtn = document.getElementById('kt_aside_mobile_toggle');
        var asideEl = document.getElementById('kt_aside') || document.querySelector('.aside');
        var overlayEl = document.querySelector('.bs-canvas-overlay');

        if (asideMobileBtn && asideEl) {
            asideMobileBtn.addEventListener('click', function (e) {
                e.preventDefault();
                asideEl.classList.toggle('aside-on');
                if (overlayEl) {
                    overlayEl.classList.toggle('show');
                }
            });
        }

        if (overlayEl) {
            overlayEl.addEventListener('click', function () {
                if (asideEl) asideEl.classList.remove('aside-on');
                overlayEl.classList.remove('show');
            });
        }

        // 3. Password Visibility Toggle
        document.querySelectorAll('.lft-glass-toggle-pw').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                var targetId = this.getAttribute('data-target') || 'password';
                var input = document.getElementById(targetId);
                var icon = this.querySelector('i');
                if (input) {
                    if (input.type === 'password') {
                        input.type = 'text';
                        if (icon) {
                            icon.classList.remove('fa-eye');
                            icon.classList.add('fa-eye-slash');
                        }
                    } else {
                        input.type = 'password';
                        if (icon) {
                            icon.classList.remove('fa-eye-slash');
                            icon.classList.add('fa-eye');
                        }
                    }
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
