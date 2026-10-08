<?php
$cssFile = __DIR__ . '/../public/assets/css/admin-ui.css';
$css = file_get_contents($cssFile);

// 1. Update the :root and [data-theme="dark"] token definitions at the top
$oldTokens = <<<'CSS'
:root {
    --primary: #2563eb;
    --primary-hover: #1d4ed8;
    --primary-light: #eff6ff;

    --success: #10b981;
    --warning: #f59e0b;
    --danger: #ef4444;
    --info: #0ea5e9;
    --purple: #8b5cf6;

    --background: #f5f7fb;
    --surface: #ffffff;
    --surface-secondary: #f8fafc;

    --text-primary: #0f172a;
    --text-secondary: #64748b;
    --text-muted: #94a3b8;

    --border: #e2e8f0;
    --border-light: #f1f5f9;
    --sidebar-background: #ffffff;
    --sidebar-text: #475569;
    --sidebar-hover: #f1f5f9;

    --radius: 14px;
    --radius-lg: 18px;
    --radius-sm: 8px;
    --shadow-sm: 0 1px 3px rgba(15, 23, 42, 0.05);
    --shadow: 0 4px 12px -2px rgba(15, 23, 42, 0.06), 0 2px 6px -1px rgba(15, 23, 42, 0.03);
    --shadow-lg: 0 10px 25px -3px rgba(15, 23, 42, 0.08), 0 4px 10px -2px rgba(15, 23, 42, 0.04);

    --font-family: 'Cairo', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
}

[data-theme="dark"] {
    --background: #071426;
    --surface: #101e34;
    --surface-secondary: #15253d;

    --text-primary: #f8fafc;
    --text-secondary: #94a3b8;
    --text-muted: #64748b;

    --border: #24354e;
    --border-light: #1c2b42;
    --sidebar-background: #0c192d;
    --sidebar-text: #94a3b8;
    --sidebar-hover: #172a47;

    --primary-light: #172d52;
    --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.3);
    --shadow: 0 4px 16px rgba(0, 0, 0, 0.35);
    --shadow-lg: 0 12px 30px rgba(0, 0, 0, 0.5);
}
CSS;

$newTokens = <<<'CSS'
:root {
    /* Semantic Hierarchy Tokens — Light Mode */
    --bg-app: #f5f7fb;
    --bg-sidebar: #ffffff;
    --bg-topbar: #ffffff;
    --bg-card: #ffffff;
    --bg-card-hover: #f8fafc;
    --bg-input: #ffffff;

    --text-primary: #0f172a;
    --text-secondary: #64748b;
    --text-muted: #94a3b8;

    --border-color: #e2e8f0;
    --border-subtle: #f1f5f9;

    --primary: #2563eb;
    --primary-hover: #1d4ed8;
    --primary-soft: #eff6ff;

    --success: #10b981;
    --warning: #f59e0b;
    --danger: #ef4444;
    --info: #0ea5e9;
    --purple: #8b5cf6;

    /* Backward compatibility aliases */
    --background: var(--bg-app);
    --surface: var(--bg-card);
    --surface-secondary: var(--bg-card-hover);
    --border: var(--border-color);
    --border-light: var(--border-subtle);
    --sidebar-background: var(--bg-sidebar);
    --sidebar-text: #475569;
    --sidebar-hover: #f1f5f9;
    --primary-light: var(--primary-soft);

    --radius: 14px;
    --radius-lg: 18px;
    --radius-sm: 8px;
    --shadow-sm: 0 1px 3px rgba(15, 23, 42, 0.05);
    --shadow: 0 4px 12px -2px rgba(15, 23, 42, 0.06), 0 2px 6px -1px rgba(15, 23, 42, 0.03);
    --shadow-lg: 0 10px 25px -3px rgba(15, 23, 42, 0.08), 0 4px 10px -2px rgba(15, 23, 42, 0.04);

    --font-family: 'Cairo', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
}

[data-theme="dark"] {
    /* Semantic Hierarchy Tokens — Dark Navy SaaS Theme */
    --bg-app: #081426;
    --bg-sidebar: #0d1b30;
    --bg-topbar: #101f35;
    --bg-card: #14243c;
    --bg-card-hover: #1a2d49;
    --bg-input: #192b46;

    --text-primary: #f1f5f9;
    --text-secondary: #b4c3d8;
    --text-muted: #94a3b8;

    --border-color: #293b55;
    --border-subtle: #20324a;

    --primary: #3b82f6;
    --primary-hover: #60a5fa;
    --primary-soft: rgba(59, 130, 246, 0.14);

    --success: #34d399;
    --warning: #fbbf24;
    --danger: #fb7185;
    --info: #38bdf8;
    --purple: #a78bfa;

    /* Backward compatibility aliases */
    --background: var(--bg-app);
    --surface: var(--bg-card);
    --surface-secondary: var(--bg-card-hover);
    --border: var(--border-color);
    --border-light: var(--border-subtle);
    --sidebar-background: var(--bg-sidebar);
    --sidebar-text: var(--text-secondary);
    --sidebar-hover: var(--bg-card-hover);
    --primary-light: var(--primary-soft);

    --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.3);
    --shadow: 0 4px 16px rgba(0, 0, 0, 0.35);
    --shadow-lg: 0 12px 30px rgba(0, 0, 0, 0.5);
}
CSS;

$css = str_replace($oldTokens, $newTokens, $css);

// 2. Comprehensive Dark Mode Refinements Section to append
$comprehensiveRefinements = <<<'CSS'

/* ==========================================================================
   PHASE 4B — COMPREHENSIVE DARK MODE REFINEMENTS & COMPONENT HARMONIZATION
   Covers: Text contrast, Sidebar, Topbar, Breadcrumbs, Tables, Forms, Badges, Modals
   ========================================================================== */

/* 1. TEXT CONTRAST & TYPOGRAPHY FIXES */
[data-theme="dark"] body,
[data-theme="dark"] body.lft-admin,
[data-theme="dark"] body#kt_body {
    background-color: var(--bg-app) !important;
    color: var(--text-primary) !important;
}

[data-theme="dark"] h1,
[data-theme="dark"] h2,
[data-theme="dark"] h3,
[data-theme="dark"] h4,
[data-theme="dark"] h5,
[data-theme="dark"] h6,
[data-theme="dark"] .card-title,
[data-theme="dark"] .card-label,
[data-theme="dark"] label {
    color: var(--text-primary) !important;
}

[data-theme="dark"] .text-dark,
[data-theme="dark"] .text-dark-50,
[data-theme="dark"] .text-dark-65,
[data-theme="dark"] .text-dark-75 {
    color: var(--text-primary) !important;
}

[data-theme="dark"] .text-muted {
    color: var(--text-muted) !important;
}

[data-theme="dark"] a {
    color: var(--primary);
}

[data-theme="dark"] a:hover {
    color: var(--primary-hover);
}

/* 2. SIDEBAR NAVIGATION HARMONIZATION */
[data-theme="dark"] .aside,
[data-theme="dark"] #kt_aside,
[data-theme="dark"] .lft-admin .aside {
    background-color: var(--bg-sidebar) !important;
    border-color: var(--border-color) !important;
}

[data-theme="dark"] .aside .brand,
[data-theme="dark"] #kt_brand,
[data-theme="dark"] .lft-admin .brand {
    background-color: var(--bg-sidebar) !important;
    border-color: var(--border-color) !important;
}

[data-theme="dark"] .lft-brand-title {
    color: #ffffff !important;
}

[data-theme="dark"] .lft-brand-subtitle {
    color: var(--text-muted) !important;
}

[data-theme="dark"] .brand-toggle,
[data-theme="dark"] #kt_aside_toggle {
    color: var(--text-muted) !important;
}

[data-theme="dark"] .brand-toggle svg path {
    fill: var(--text-muted) !important;
}

[data-theme="dark"] .lft-nav-label {
    color: var(--text-muted) !important;
    font-size: 11px !important;
    letter-spacing: 0.5px !important;
}

[data-theme="dark"] .aside-menu .menu-nav > .menu-item > .menu-link,
[data-theme="dark"] .lft-admin .aside-menu .menu-nav > .menu-item > .menu-link {
    color: var(--text-secondary) !important;
    background-color: transparent !important;
    border: 1px solid transparent !important;
}

[data-theme="dark"] .aside-menu .menu-nav > .menu-item > .menu-link .menu-text {
    color: var(--text-secondary) !important;
    font-weight: 500 !important;
}

[data-theme="dark"] .aside-menu .menu-nav > .menu-item > .menu-link .menu-icon,
[data-theme="dark"] .aside-menu .menu-nav > .menu-item > .menu-link .menu-icon i,
[data-theme="dark"] .aside-menu .menu-nav > .menu-item > .menu-link .svg-icon svg {
    color: var(--text-muted) !important;
    fill: var(--text-muted) !important;
}

[data-theme="dark"] .aside-menu .menu-arrow {
    color: var(--text-muted) !important;
}

[data-theme="dark"] .aside-menu .menu-nav > .menu-item > .menu-link:hover,
[data-theme="dark"] .aside-menu .menu-nav > .menu-item:hover > .menu-link {
    background-color: var(--bg-card-hover) !important;
    color: #ffffff !important;
}

[data-theme="dark"] .aside-menu .menu-nav > .menu-item > .menu-link:hover .menu-text,
[data-theme="dark"] .aside-menu .menu-nav > .menu-item:hover > .menu-link .menu-text {
    color: #ffffff !important;
}

[data-theme="dark"] .aside-menu .menu-nav > .menu-item > .menu-link:hover .menu-icon,
[data-theme="dark"] .aside-menu .menu-nav > .menu-item:hover > .menu-link .menu-icon i {
    color: var(--primary) !important;
}

[data-theme="dark"] .aside-menu .menu-nav > .menu-item.menu-item-active > .menu-link,
[data-theme="dark"] .lft-admin .aside-menu .menu-nav > .menu-item.menu-item-active > .menu-link {
    background-color: var(--primary) !important;
    color: #ffffff !important;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.35) !important;
}

[data-theme="dark"] .aside-menu .menu-nav > .menu-item.menu-item-active > .menu-link .menu-text,
[data-theme="dark"] .aside-menu .menu-nav > .menu-item.menu-item-active > .menu-link .menu-icon,
[data-theme="dark"] .aside-menu .menu-nav > .menu-item.menu-item-active > .menu-link .menu-icon i {
    color: #ffffff !important;
}

[data-theme="dark"] .aside-menu .menu-submenu {
    background-color: rgba(13, 27, 48, 0.4) !important;
}

[data-theme="dark"] .aside-menu .menu-subnav .menu-link {
    color: var(--text-secondary) !important;
}

[data-theme="dark"] .aside-menu .menu-subnav .menu-link .menu-text {
    color: var(--text-secondary) !important;
}

[data-theme="dark"] .aside-menu .menu-subnav .menu-item:hover > .menu-link {
    background-color: var(--bg-card-hover) !important;
}

[data-theme="dark"] .aside-menu .menu-subnav .menu-item:hover > .menu-link .menu-text {
    color: #ffffff !important;
}

[data-theme="dark"] .aside-menu .menu-subnav .menu-item.menu-item-active > .menu-link {
    background-color: var(--primary-soft) !important;
    color: var(--primary) !important;
}

[data-theme="dark"] .aside-menu .menu-subnav .menu-item.menu-item-active > .menu-link .menu-text {
    color: var(--primary) !important;
    font-weight: 700 !important;
}

/* 3. TOPBAR HARMONIZATION */
[data-theme="dark"] .header,
[data-theme="dark"] #kt_header,
[data-theme="dark"] .lft-admin .header {
    background-color: var(--bg-topbar) !important;
    border-color: var(--border-color) !important;
}

[data-theme="dark"] .lft-topbar-search input {
    background-color: var(--bg-input) !important;
    border-color: var(--border-color) !important;
    color: var(--text-primary) !important;
}

[data-theme="dark"] .lft-topbar-search input::placeholder {
    color: var(--text-muted) !important;
}

[data-theme="dark"] .lft-topbar-search-icon {
    color: var(--text-muted) !important;
}

[data-theme="dark"] .lft-icon-btn {
    background-color: var(--bg-card) !important;
    border-color: var(--border-color) !important;
    color: var(--text-secondary) !important;
}

[data-theme="dark"] .lft-icon-btn:hover {
    background-color: var(--bg-card-hover) !important;
    color: var(--primary) !important;
    border-color: var(--primary) !important;
}

[data-theme="dark"] .lft-theme-toggle {
    background-color: var(--bg-input) !important;
    border-color: var(--border-color) !important;
}

[data-theme="dark"] .lft-theme-toggle-btn {
    color: var(--text-muted) !important;
}

[data-theme="dark"] .lft-theme-toggle-btn.active {
    background-color: var(--primary) !important;
    color: #ffffff !important;
}

[data-theme="dark"] .lft-user-profile {
    border-color: transparent !important;
}

[data-theme="dark"] .lft-user-profile:hover {
    background-color: var(--bg-card) !important;
    border-color: var(--border-color) !important;
}

[data-theme="dark"] .lft-user-name {
    color: var(--text-primary) !important;
}

[data-theme="dark"] .lft-user-role {
    color: var(--text-muted) !important;
}

/* 4. BREADCRUMBS & HEADING (ELIMINATES BRIGHT WHITE STRIP) */
.lft-page-heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    padding: 16px 22px;
    background-color: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: var(--radius);
    margin-bottom: 24px;
    box-shadow: var(--shadow-sm);
    transition: background-color 0.25s ease, border-color 0.25s ease;
}

.lft-page-heading h1 {
    font-size: 20px !important;
    font-weight: 800 !important;
    color: var(--text-primary) !important;
    margin: 0 0 6px 0 !important;
    line-height: 1.2 !important;
}

.lft-page-heading .breadcrumb,
.breadcrumb,
ol.breadcrumb,
ul.breadcrumb {
    background: transparent !important;
    background-color: transparent !important;
    padding: 0 !important;
    margin: 0 !important;
    border: none !important;
    border-radius: 0 !important;
}

.breadcrumb-item {
    color: var(--text-muted) !important;
    font-size: 13px !important;
}

.breadcrumb-item a {
    color: var(--text-secondary) !important;
    text-decoration: none;
    transition: color 0.2s ease;
}

.breadcrumb-item a:hover {
    color: var(--primary) !important;
}

.breadcrumb-item + .breadcrumb-item::before {
    content: "/" !important;
    padding: 0 8px !important;
    color: var(--text-muted) !important;
}

.breadcrumb-item.active {
    color: var(--primary) !important;
    font-weight: 600 !important;
}

[data-theme="dark"] .lft-page-heading {
    background-color: var(--bg-card) !important;
    border-color: var(--border-color) !important;
}

[data-theme="dark"] .lft-page-heading .breadcrumb,
[data-theme="dark"] .breadcrumb {
    background-color: transparent !important;
}

/* 5. CARDS & PANELS HIERARCHY */
[data-theme="dark"] .card,
[data-theme="dark"] .card.card-custom,
[data-theme="dark"] .lft-admin .card {
    background-color: var(--bg-card) !important;
    border: 1px solid var(--border-color) !important;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.25) !important;
}

[data-theme="dark"] .card-header,
[data-theme="dark"] .lft-admin .card > .card-header {
    background-color: transparent !important;
    border-bottom: 1px solid var(--border-subtle) !important;
}

[data-theme="dark"] .card-body.border-top {
    border-top: 1px solid var(--border-subtle) !important;
}

[data-theme="dark"] .card-footer {
    background-color: var(--bg-card-hover) !important;
    border-top: 1px solid var(--border-subtle) !important;
}

/* 6. FORMS, SEARCH & INPUTS ALIGNMENT */
[data-theme="dark"] .form-control,
[data-theme="dark"] .form-control-solid,
[data-theme="dark"] input[type="text"],
[data-theme="dark"] input[type="search"],
[data-theme="dark"] input[type="number"],
[data-theme="dark"] input[type="email"],
[data-theme="dark"] input[type="password"],
[data-theme="dark"] select.form-control,
[data-theme="dark"] textarea.form-control {
    background-color: var(--bg-input) !important;
    border: 1px solid var(--border-color) !important;
    color: var(--text-primary) !important;
    border-radius: var(--radius-sm) !important;
    height: 42px;
}

[data-theme="dark"] textarea.form-control {
    height: auto;
}

[data-theme="dark"] .form-control:focus,
[data-theme="dark"] .form-control-solid:focus {
    background-color: var(--bg-card) !important;
    border-color: var(--primary) !important;
    color: #ffffff !important;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2) !important;
}

[data-theme="dark"] .form-control::placeholder,
[data-theme="dark"] .form-control-solid::placeholder {
    color: var(--text-muted) !important;
    opacity: 0.85;
}

[data-theme="dark"] .input-group-text,
[data-theme="dark"] .input-group-solid .input-group-text,
[data-theme="dark"] .input-group-text.bg-light {
    background-color: var(--bg-input) !important;
    border: 1px solid var(--border-color) !important;
    color: var(--text-muted) !important;
    height: 42px;
}

[data-theme="dark"] .btn.btn-primary {
    background-color: var(--primary) !important;
    border-color: var(--primary) !important;
    color: #ffffff !important;
    height: 42px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

[data-theme="dark"] .btn.btn-primary:hover {
    background-color: var(--primary-hover) !important;
    border-color: var(--primary-hover) !important;
}

[data-theme="dark"] .btn.btn-secondary {
    background-color: var(--bg-card-hover) !important;
    border-color: var(--border-color) !important;
    color: var(--text-secondary) !important;
    height: 42px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

[data-theme="dark"] .btn.btn-secondary:hover {
    background-color: var(--border-color) !important;
    color: #ffffff !important;
}

/* Ensure Search form buttons and inputs align strictly */
.card-body form .row.align-items-end {
    align-items: flex-end !important;
}

.card-body form .row.align-items-end .col-md-2 .btn,
.card-body form .row.align-items-end .col-lg-2 .btn,
.card-body form .row.align-items-end .col-sm-3 .btn {
    height: 42px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
}

/* 7. TABLES & DATATABLES REFINEMENT (SUBTLE BORDERS, READABLE TEXT) */
[data-theme="dark"] .table-responsive,
[data-theme="dark"] .lft-table-responsive {
    background-color: var(--bg-card) !important;
    border: 1px solid var(--border-color) !important;
    border-radius: var(--radius) !important;
    box-shadow: none !important;
}

[data-theme="dark"] table.table {
    background-color: var(--bg-card) !important;
    color: var(--text-primary) !important;
    border-color: var(--border-subtle) !important;
}

[data-theme="dark"] table.table thead th,
[data-theme="dark"] table.table thead.thead-light th,
[data-theme="dark"] table.table.table-head-custom thead th {
    background-color: var(--bg-card-hover) !important;
    color: var(--text-secondary) !important;
    border: 1px solid var(--border-subtle) !important;
    border-top: none !important;
    border-bottom: 1px solid var(--border-color) !important;
    font-size: 13px !important;
    font-weight: 700 !important;
    padding: 12px 16px !important;
    letter-spacing: 0 !important;
    text-transform: none !important;
}

[data-theme="dark"] table.table tbody td,
[data-theme="dark"] table.table-bordered th,
[data-theme="dark"] table.table-bordered td {
    background-color: transparent !important;
    border: 1px solid var(--border-subtle) !important;
    color: var(--text-primary) !important;
    padding: 12px 16px !important;
    font-size: 13.5px !important;
    vertical-align: middle !important;
}

[data-theme="dark"] table.table tbody tr:hover td,
[data-theme="dark"] table.table-hover tbody tr:hover td,
[data-theme="dark"] table.table-hover tbody tr:hover {
    background-color: var(--bg-card-hover) !important;
}

[data-theme="dark"] table.table td .text-dark,
[data-theme="dark"] table.table td .font-weight-bold {
    color: var(--text-primary) !important;
}

[data-theme="dark"] table.table td .text-muted {
    color: var(--text-muted) !important;
}

[data-theme="dark"] .dataTables_wrapper .dataTables_length,
[data-theme="dark"] .dataTables_wrapper .dataTables_filter,
[data-theme="dark"] .dataTables_wrapper .dataTables_info {
    color: var(--text-secondary) !important;
}

[data-theme="dark"] .dataTables_wrapper .dataTables_filter input {
    background-color: var(--bg-input) !important;
    border: 1px solid var(--border-color) !important;
    color: var(--text-primary) !important;
    border-radius: var(--radius-sm) !important;
    padding: 6px 12px !important;
}

[data-theme="dark"] .dataTables_wrapper .dataTables_paginate .paginate_button {
    background-color: var(--bg-card) !important;
    border: 1px solid var(--border-color) !important;
    color: var(--text-secondary) !important;
    border-radius: 6px !important;
}

[data-theme="dark"] .dataTables_wrapper .dataTables_paginate .paginate_button.current,
[data-theme="dark"] .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
    background: var(--primary) !important;
    border-color: var(--primary) !important;
    color: #ffffff !important;
}

[data-theme="dark"] .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
    background-color: var(--bg-card-hover) !important;
    border-color: var(--primary) !important;
    color: #ffffff !important;
}

/* 8. STATUS BADGES HARMONIZATION */
[data-theme="dark"] .badge-secondary,
[data-theme="dark"] .badge-light {
    background-color: rgba(148, 163, 184, 0.16) !important;
    color: #cbd5e1 !important;
    border: 1px solid rgba(148, 163, 184, 0.25) !important;
}

[data-theme="dark"] .badge-primary,
[data-theme="dark"] .badge-light-primary {
    background-color: rgba(59, 130, 246, 0.16) !important;
    color: #60a5fa !important;
    border: 1px solid rgba(59, 130, 246, 0.25) !important;
}

[data-theme="dark"] .badge-success,
[data-theme="dark"] .badge-light-success {
    background-color: rgba(52, 211, 153, 0.16) !important;
    color: #34d399 !important;
    border: 1px solid rgba(52, 211, 153, 0.25) !important;
}

[data-theme="dark"] .badge-warning,
[data-theme="dark"] .badge-light-warning {
    background-color: rgba(251, 191, 36, 0.16) !important;
    color: #fbbf24 !important;
    border: 1px solid rgba(251, 191, 36, 0.25) !important;
}

[data-theme="dark"] .badge-danger,
[data-theme="dark"] .badge-light-danger {
    background-color: rgba(251, 113, 133, 0.16) !important;
    color: #fb7185 !important;
    border: 1px solid rgba(251, 113, 133, 0.25) !important;
}

[data-theme="dark"] .badge-info,
[data-theme="dark"] .badge-light-info {
    background-color: rgba(56, 189, 248, 0.16) !important;
    color: #38bdf8 !important;
    border: 1px solid rgba(56, 189, 248, 0.25) !important;
}

/* 9. MODALS & DROPDOWNS */
[data-theme="dark"] .dropdown-menu {
    background-color: var(--bg-card) !important;
    border: 1px solid var(--border-color) !important;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.5) !important;
    border-radius: var(--radius-sm) !important;
}

[data-theme="dark"] .dropdown-item {
    color: var(--text-primary) !important;
}

[data-theme="dark"] .dropdown-item:hover,
[data-theme="dark"] .dropdown-item:focus {
    background-color: var(--bg-card-hover) !important;
    color: var(--primary-hover) !important;
}

[data-theme="dark"] .dropdown-divider {
    border-top: 1px solid var(--border-subtle) !important;
}

[data-theme="dark"] .modal-content {
    background-color: var(--bg-card) !important;
    border: 1px solid var(--border-color) !important;
    color: var(--text-primary) !important;
    border-radius: var(--radius) !important;
}

[data-theme="dark"] .modal-header {
    border-bottom: 1px solid var(--border-subtle) !important;
}

[data-theme="dark"] .modal-footer {
    border-top: 1px solid var(--border-subtle) !important;
    background-color: var(--bg-card-hover) !important;
}

/* 10. SELECT2 & BOOTSTRAP-SELECT */
[data-theme="dark"] .bootstrap-select > .dropdown-toggle.btn-light,
[data-theme="dark"] .bootstrap-select > .dropdown-toggle {
    background-color: var(--bg-input) !important;
    border: 1px solid var(--border-color) !important;
    color: var(--text-primary) !important;
    height: 42px !important;
    border-radius: var(--radius-sm) !important;
}

[data-theme="dark"] .bootstrap-select .dropdown-menu {
    background-color: var(--bg-card) !important;
    border: 1px solid var(--border-color) !important;
}

[data-theme="dark"] .bootstrap-select .dropdown-menu li a {
    color: var(--text-primary) !important;
}

[data-theme="dark"] .bootstrap-select .dropdown-menu li a:hover,
[data-theme="dark"] .bootstrap-select .dropdown-menu li.selected a {
    background-color: var(--bg-card-hover) !important;
    color: var(--primary) !important;
}

/* 11. FOOTER */
[data-theme="dark"] .footer,
[data-theme="dark"] #kt_footer {
    background-color: var(--bg-sidebar) !important;
    border-color: var(--border-color) !important;
    color: var(--text-muted) !important;
}

[data-theme="dark"] .footer a {
    color: var(--text-secondary) !important;
}
CSS;

if (strpos($css, 'PHASE 4B — COMPREHENSIVE DARK MODE REFINEMENTS') === false) {
    $css .= "\n" . $comprehensiveRefinements;
    file_put_contents($cssFile, $css);
    echo "admin-ui.css updated with Comprehensive Dark Mode Refinements!\n";
} else {
    // Replace the section if already exists
    $parts = explode('/* ==========================================================================\n   PHASE 4B — COMPREHENSIVE DARK MODE REFINEMENTS', $css);
    file_put_contents($cssFile, $parts[0] . "\n" . $comprehensiveRefinements);
    echo "admin-ui.css refined section refreshed!\n";
}
