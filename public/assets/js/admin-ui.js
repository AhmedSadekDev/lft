/* Presentation and accessibility only. Existing request handlers own all business actions. */
(function () {
    'use strict';
    function ready() {
        var shell = document.querySelector('.lft-admin');
        if (!shell) return;
        var content = document.getElementById('kt_content');
        var menu = document.getElementById('kt_aside_menu');
        var aside = document.getElementById('kt_aside');
        var toggle = document.getElementById('kt_aside_mobile_toggle');
        var normalize = function (path) { return path.replace(/\/+$/, '') || '/'; };
        var path = normalize(location.pathname);
        var links = menu ? Array.from(menu.querySelectorAll('a.menu-link[href]')) : [];
        var candidates = links.filter(function (link) {
            return link.getAttribute('href').indexOf('javascript:') !== 0 && link.origin === location.origin && normalize(link.pathname) === path;
        });
        if (!candidates.length) {
            candidates = links.filter(function (link) {
                var target = normalize(link.pathname);
                return target !== '/dashboard' && link.origin === location.origin && path.indexOf(target + '/') === 0;
            }).sort(function (a, b) { return b.pathname.length - a.pathname.length; }).slice(0, 1);
        }
        candidates.forEach(function (link) {
            link.setAttribute('aria-current', 'page');
            link.closest('.menu-item').classList.add('menu-item-active');
            var parent = link.parentElement;
            while (parent && parent !== menu) {
                if (parent.classList.contains('menu-item-submenu') && parent.querySelector('.menu-submenu')) parent.classList.add('menu-item-open');
                parent = parent.parentElement;
            }
        });
        if (menu) {
            var syncMenu = function () {
                menu.querySelectorAll('.menu-toggle').forEach(function (link) {
                    link.setAttribute('role', 'button');
                    link.setAttribute('aria-expanded', String(link.parentElement.classList.contains('menu-item-open')));
                });
            };
            syncMenu();
            new MutationObserver(syncMenu).observe(menu, {subtree: true, attributes: true, attributeFilter: ['class']});
            menu.addEventListener('keydown', function (event) {
                if (event.key === ' ' && event.target.matches('.menu-toggle')) { event.preventDefault(); event.target.click(); }
            });
        }
        if (aside && toggle) {
            toggle.setAttribute('aria-controls', 'kt_aside');
            var syncDrawer = function () {
                var open = aside.classList.contains('aside-on');
                toggle.setAttribute('aria-expanded', String(open));
                if (open && matchMedia('(max-width: 991.98px)').matches) {
                    var first = aside.querySelector('a[href],button');
                    if (!aside.contains(document.activeElement) && first) first.focus();
                }
            };
            new MutationObserver(syncDrawer).observe(aside, {attributes: true, attributeFilter: ['class']});
            syncDrawer();
            document.addEventListener('keydown', function (event) {
                if (!aside.classList.contains('aside-on') || !matchMedia('(max-width: 991.98px)').matches) return;
                if (event.key === 'Escape' && window.KTLayoutAside) {
                    KTLayoutAside.getOffcanvas().hide(); toggle.focus();
                }
                if (event.key === 'Tab') {
                    var items = Array.from(aside.querySelectorAll('a[href],button,[tabindex="0"]')).filter(function (el) { return el.getClientRects().length && !el.disabled; });
                    var first = items[0], last = items[items.length - 1];
                    if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
                    else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
                }
            });
        }
        var sequence = 0;
        function enhance(scope) {
            scope.querySelectorAll('table').forEach(function (table) {
                // Keep plugin IDs, classes and table contents intact; wrap only the table, never its toolbar.
                if (!table.closest('.table-responsive,.lft-table-scroll,.dataTables_scrollBody') && !table.closest('[data-lft-print]')) {
                    var region = document.createElement('div');
                    region.className = 'lft-table-scroll';
                    table.parentNode.insertBefore(region, table); region.appendChild(table);
                }
            });
            scope.querySelectorAll('.table-responsive,.lft-table-scroll').forEach(function (region) {
                region.setAttribute('tabindex', '0');
                region.setAttribute('role', 'region');
                if (!region.hasAttribute('aria-label')) region.setAttribute('aria-label', 'جدول البيانات — يمكن التمرير أفقياً');
            });
            scope.querySelectorAll('input:not([type=hidden]),select,textarea').forEach(function (input) {
                var group = input.closest('.form-group');
                if (group) {
                    var label = group.querySelector('label');
                    if (label && !label.htmlFor && group.querySelectorAll('input:not([type=hidden]),select,textarea').length === 1) {
                        if (!input.id) input.id = 'lft-field-' + (++sequence);
                        label.htmlFor = input.id;
                    }
                }
                if (!input.labels?.length && !input.hasAttribute('aria-label') && input.placeholder) input.setAttribute('aria-label', input.placeholder);
                if (input.required && input.labels) Array.from(input.labels).forEach(function (label) { label.classList.add('required-field'); });
                if (input.classList.contains('is-invalid')) {
                    input.setAttribute('aria-invalid', 'true');
                    var error = input.parentElement.querySelector('.invalid-feedback');
                    if (error) {
                        if (!error.id) error.id = 'lft-error-' + (++sequence);
                        var described = (input.getAttribute('aria-describedby') || '').split(' ').filter(Boolean);
                        if (!described.includes(error.id)) described.push(error.id);
                        input.setAttribute('aria-describedby', described.join(' '));
                    }
                }
            });
            scope.querySelectorAll('a[title],button[title]').forEach(function (el) {
                if (!el.textContent.trim() && !el.hasAttribute('aria-label')) el.setAttribute('aria-label', el.title);
            });
        }
        if (content) enhance(content);
        if (window.jQuery) {
            jQuery(document).on('draw.dt shown.bs.modal', function (event) {
                if (event.type === 'draw' && content) enhance(content);
                else if (event.target.querySelectorAll) enhance(event.target);
            });
        }
        // A status indicator only: do not disable named submitters or intercept AJAX / confirmation handlers.
        document.addEventListener('submit', function (event) {
            var form = event.target;
            if (!(form instanceof HTMLFormElement)) return;
            setTimeout(function () {
                if (event.defaultPrevented) return;
                form.setAttribute('aria-busy', 'true');
                var button = event.submitter;
                if (button) button.classList.add('lft-submitting');
            }, 0);
        });
        window.addEventListener('pageshow', function () {
            document.querySelectorAll('form[aria-busy]').forEach(function (form) { form.removeAttribute('aria-busy'); });
            document.querySelectorAll('.lft-submitting').forEach(function (el) { el.classList.remove('lft-submitting'); });
        });
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', function () { if (window.jQuery) jQuery(ready); else ready(); });
    else if (window.jQuery) jQuery(ready); else ready();
})();
