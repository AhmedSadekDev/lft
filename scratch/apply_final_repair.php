<?php
// 1. Update resources/views/admin/bookings/show.blade.php
$showFile = __DIR__ . '/../resources/views/admin/bookings/show.blade.php';
$showContent = file_get_contents($showFile);

$oldStyleRegex = '/<style>.*?<\/style>/s';

$newStyle = <<<'STYLE'
<style>
        .booking-show-page { direction: rtl; }

        /* Page Header */
        .booking-page-header {
            background: linear-gradient(135deg, #1e3a5f 0%, #2d5a87 50%, #3d7ab5 100%);
            border-radius: var(--radius);
            padding: 1.75rem 2rem;
            margin-bottom: 1.75rem;
            box-shadow: 0 6px 24px rgba(30, 58, 95, 0.25);
            border: 1px solid transparent;
        }

        [data-theme="dark"] .booking-page-header {
            background: linear-gradient(135deg, #0d1e38 0%, #14284b 50%, #1e3a66 100%);
            border-color: var(--border-color);
            box-shadow: 0 6px 24px rgba(0, 0, 0, 0.4);
        }

        .booking-page-header-inner {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            position: relative;
            z-index: 1;
        }
        .booking-header-icon {
            width: 56px;
            height: 56px;
            background: rgba(255,255,255,0.18);
            border: 2px solid rgba(255,255,255,0.35);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .booking-header-icon i { font-size: 1.75rem; color: #fff; }
        .booking-page-title {
            color: #fff;
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0 0 0.25rem 0;
        }
        .booking-page-subtitle {
            color: rgba(255,255,255,0.85);
            font-size: 0.95rem;
            margin: 0;
        }
        .booking-header-actions { display: flex; flex-wrap: wrap; align-items: center; gap: 0.6rem; }
        .booking-btn-invoice {
            padding: 0.6rem 1.25rem;
            border-radius: 10px;
            font-weight: 700;
            font-size: 0.95rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: transform 0.2s, box-shadow 0.2s;
            border: 1px solid transparent;
        }
        .booking-btn-invoice--view {
            background: var(--success);
            color: #fff;
        }
        .booking-btn-invoice--view:hover { color: #fff; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(16,185,129,0.4); text-decoration: none; }
        .booking-btn-invoice--create {
            background: var(--primary);
            color: #fff;
        }
        .booking-btn-invoice--create:hover { color: #fff; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(59,130,246,0.4); text-decoration: none; }
        .booking-back-btn {
            background: var(--bg-card);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
            padding: 0.6rem 1.25rem;
            border-radius: 10px;
            font-weight: 700;
            font-size: 0.95rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .booking-back-btn:hover { color: var(--primary); text-decoration: none; transform: translateX(-3px); box-shadow: 0 4px 12px rgba(0,0,0,0.15); }

        /* Info Cards */
        .booking-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 1rem;
            margin-top: 1.5rem;
        }
        .booking-info-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 1.25rem;
            background: #ffffff;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-sm);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        [data-theme="dark"] .booking-info-item {
            background: var(--bg-card) !important;
            border-color: var(--border-color) !important;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.25) !important;
        }
        .booking-info-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 14px rgba(0,0,0,0.08);
        }
        .booking-info-icon {
            width: 46px;
            height: 46px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            color: #fff;
            flex-shrink: 0;
        }
        .booking-info-icon--blue { background: #0d6efd; }
        .booking-info-icon--green { background: #198754; }
        .booking-info-icon--orange { background: #fd7e14; }
        .booking-info-icon--teal { background: #0dcaf0; }
        .booking-info-icon--purple { background: #6f42c1; }
        .booking-info-icon--indigo { background: #6610f2; }
        .booking-info-text { min-width: 0; }
        .booking-info-label {
            display: block;
            font-size: 0.78rem;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin-bottom: 0.25rem;
        }
        .booking-info-value {
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        /* Section Cards */
        .booking-section-card {
            background: var(--bg-card);
            border-radius: 14px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-color);
            margin-bottom: 1.75rem;
            overflow: hidden;
            transition: background-color 0.25s ease, border-color 0.25s ease;
        }
        [data-theme="dark"] .booking-section-card {
            background: var(--bg-card) !important;
            border-color: var(--border-color) !important;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.25) !important;
        }
        .booking-section-head {
            padding: 1.1rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 0.75rem;
            border-bottom: 1px solid var(--border-color);
            transition: background-color 0.25s ease;
        }
        .booking-section-head--blue {
            background: #eff6ff;
            border-bottom: 2px solid #bfdbfe;
        }
        [data-theme="dark"] .booking-section-head--blue {
            background: rgba(59, 130, 246, 0.12) !important;
            border-bottom: 2px solid var(--border-color) !important;
        }
        .booking-section-head--teal {
            background: #f0fdf4;
            border-bottom: 2px solid #bbf7d0;
        }
        [data-theme="dark"] .booking-section-head--teal {
            background: rgba(14, 165, 233, 0.12) !important;
            border-bottom: 2px solid var(--border-color) !important;
        }
        .booking-section-head--green {
            background: #ecfdf5;
            border-bottom: 2px solid #a7f3d0;
        }
        [data-theme="dark"] .booking-section-head--green {
            background: rgba(52, 211, 153, 0.12) !important;
            border-bottom: 2px solid var(--border-color) !important;
        }
        .booking-section-title {
            margin: 0;
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .booking-section-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            color: #fff;
        }
        .booking-section-icon--blue { background: #0d6efd; }
        .booking-section-icon--teal { background: #0dcaf0; }
        .booking-section-icon--green { background: #198754; }
        .booking-section-badge {
            background: var(--primary);
            color: #fff;
            padding: 0.4rem 0.95rem;
            border-radius: 20px;
            font-weight: 700;
            font-size: 0.85rem;
        }
        .booking-section-body {
            padding: 1.5rem;
            background: var(--bg-card);
            color: var(--text-primary);
        }
        [data-theme="dark"] .booking-section-body {
            background: var(--bg-card) !important;
            color: var(--text-primary) !important;
        }

        .delivery-policies-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        .delivery-policies-table thead {
            background: linear-gradient(135deg, #1e3a5f 0%, #2d5a87 100%);
            color: #fff;
        }
        [data-theme="dark"] .delivery-policies-table thead {
            background: var(--bg-card-hover) !important;
            color: var(--text-secondary) !important;
        }
        .delivery-policies-table thead th {
            padding: 1rem 1.25rem;
            text-align: right;
            font-weight: 600;
            font-size: 0.9rem;
            border: none;
            color: #fff;
        }
        [data-theme="dark"] .delivery-policies-table thead th {
            color: var(--text-secondary) !important;
            border-bottom: 1px solid var(--border-color) !important;
        }
        .delivery-policies-table thead th:first-child { border-top-right-radius: 10px; }
        .delivery-policies-table thead th:last-child { border-top-left-radius: 10px; }
        .delivery-policies-table tbody td {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid var(--border-subtle);
            vertical-align: middle;
            color: var(--text-primary);
        }
        .delivery-policies-table tbody tr:hover { background: var(--bg-card-hover); }

        .booking-empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: var(--text-muted);
        }
        .booking-empty-state i {
            font-size: 3.5rem;
            color: var(--border-color);
            margin-bottom: 1rem;
        }
        .booking-empty-state h5 { font-weight: 700; color: var(--text-primary); margin-bottom: 0.5rem; }
        .booking-empty-state p { margin: 0; font-size: 0.95rem; color: var(--text-muted); }

        @media (max-width: 768px) {
            .booking-header-icon { width: 48px; height: 48px; }
            .booking-header-icon i { font-size: 1.4rem; }
            .booking-page-title { font-size: 1.25rem; }
            .booking-info-grid { grid-template-columns: 1fr; }
        }
</style>
STYLE;

$showContent = preg_replace($oldStyleRegex, $newStyle, $showContent, 1);
file_put_contents($showFile, $showContent);
echo "1. Updated show.blade.php with semantic styles\n";

// 2. Update resources/views/layouts/includes/sidebar.blade.php brand markup
$sidebarFile = __DIR__ . '/../resources/views/layouts/includes/sidebar.blade.php';
$sidebarContent = file_get_contents($sidebarFile);

$oldBrandSection = <<<'HTML'
        <!--begin::Brand-->
        <div class="brand flex-column-auto" id="kt_brand">
            <!--begin::Logo-->
            <a href="{{ route('main') }}" class="brand-logo">
                <img alt="Leader for Trans" src="{{ asset('assets/media/logo.png') }}" />
                <span class="lft-brand-name" dir="ltr">LEADER<span><small>TRANSPORT & LOGISTICS</small></span></span>
            </a>
            <!--end::Logo-->
HTML;

$newBrandSection = <<<'HTML'
        <!--begin::Brand-->
        <div class="brand flex-column-auto px-4" id="kt_brand">
            <!--begin::Logo-->
            <a href="{{ route('main') }}" class="brand-logo lft-sidebar-brand d-flex align-items-center">
                <img src="{{ asset('assets/media/logo.png') }}" alt="Leader for Trans" class="lft-sidebar-logo" />
                <div class="lft-brand-titles mr-3">
                    <span class="lft-brand-title">Leader for Trans</span>
                    <span class="lft-brand-subtitle">منظومة التجارة والأعمال</span>
                </div>
            </a>
            <!--end::Logo-->
HTML;

if (strpos($sidebarContent, $oldBrandSection) !== false) {
    $sidebarContent = str_replace($oldBrandSection, $newBrandSection, $sidebarContent);
    file_put_contents($sidebarFile, $sidebarContent);
    echo "2. Updated sidebar.blade.php brand section\n";
} else {
    echo "2. sidebar.blade.php brand section check: already updated or different markup\n";
}

// 3. Append logo sizing & booking details styles to public/assets/css/admin-ui.css
$cssFile = __DIR__ . '/../public/assets/css/admin-ui.css';
$css = file_get_contents($cssFile);

$extraRepairCss = <<<'CSS'

/* ==========================================================================
   FINAL BRANDING & BOOKING DETAILS DISPLAY REPAIR
   ========================================================================== */

/* Clean Sidebar Brand Header & Logo Alignment */
.brand, #kt_brand, .aside .brand {
    height: 76px !important;
    padding: 0 16px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
    background-color: var(--bg-sidebar) !important;
    border-bottom: 1px solid var(--border-color) !important;
    transition: background-color 0.25s ease, border-color 0.25s ease;
}

.lft-sidebar-brand {
    display: flex;
    align-items: center;
    gap: 10px;
    text-decoration: none !important;
    min-width: 0;
    flex: 1;
    overflow: hidden;
}

.lft-sidebar-logo {
    max-height: 44px !important;
    height: 44px !important;
    width: auto !important;
    object-fit: contain !important;
    flex-shrink: 0;
    transition: all 0.25s ease;
}

[data-theme="dark"] .lft-sidebar-logo {
    filter: drop-shadow(0 2px 6px rgba(0, 0, 0, 0.45));
}

.lft-brand-titles {
    display: flex;
    flex-direction: column;
    min-width: 0;
    overflow: hidden;
}

.lft-brand-title {
    font-size: 15px;
    font-weight: 800;
    color: var(--text-primary);
    line-height: 1.2;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

[data-theme="dark"] .lft-brand-title {
    color: #ffffff !important;
}

.lft-brand-subtitle {
    font-size: 11px;
    font-weight: 500;
    color: var(--text-muted);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-top: 2px;
}

/* Collapsed Sidebar */
.aside-minimize .lft-brand-titles {
    display: none !important;
}

.aside-minimize .brand,
.aside-minimize #kt_brand {
    padding: 0 8px !important;
    justify-content: center !important;
}

.aside-minimize .lft-sidebar-brand {
    justify-content: center !important;
    flex: none !important;
}

.aside-minimize .lft-sidebar-logo {
    max-height: 38px !important;
    height: 38px !important;
    margin: 0 auto !important;
}

.aside-minimize .brand-toggle,
.aside-minimize #kt_aside_toggle {
    display: none !important;
}

/* Global Table & Container Card Overrides for Dark Mode */
[data-theme="dark"] .booking-section-card {
    background-color: var(--bg-card) !important;
    border-color: var(--border-color) !important;
}

[data-theme="dark"] .booking-section-head {
    border-bottom-color: var(--border-color) !important;
}

[data-theme="dark"] .booking-section-body {
    background-color: var(--bg-card) !important;
    color: var(--text-primary) !important;
}

[data-theme="dark"] .booking-section-title {
    color: var(--text-primary) !important;
}

[data-theme="dark"] .booking-info-item {
    background-color: var(--bg-card) !important;
    border-color: var(--border-color) !important;
}

[data-theme="dark"] .booking-info-value {
    color: var(--text-primary) !important;
}

[data-theme="dark"] .booking-info-label {
    color: var(--text-muted) !important;
}

[data-theme="dark"] .table-striped tbody tr:nth-of-type(odd) {
    background-color: rgba(255, 255, 255, 0.02) !important;
}

[data-theme="dark"] .table-striped tbody tr:nth-of-type(even) {
    background-color: transparent !important;
}

/* Buttons disabled states */
.btn:disabled,
.btn.disabled,
fieldset:disabled .btn {
    opacity: 0.55 !important;
    cursor: not-allowed !important;
    box-shadow: none !important;
}

[data-theme="dark"] .btn:disabled,
[data-theme="dark"] .btn.disabled {
    background-color: var(--bg-card-hover) !important;
    border-color: var(--border-color) !important;
    color: var(--text-muted) !important;
}
CSS;

if (strpos($css, 'FINAL BRANDING & BOOKING DETAILS DISPLAY REPAIR') === false) {
    $css .= "\n" . $extraRepairCss;
    file_put_contents($cssFile, $css);
    echo "3. Appended final repairs to admin-ui.css\n";
} else {
    echo "3. admin-ui.css already contains final repairs\n";
}
