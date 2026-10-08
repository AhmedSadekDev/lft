<?php
$sidebarFile = __DIR__ . '/../resources/views/layouts/includes/sidebar.blade.php';
$content = file_get_contents($sidebarFile);

// 1. Update Brand logo section
$oldBrand = '            <!--begin::Logo-->
            <a href="{{ route(\'main\') }}" class="brand-logo">
                <img alt="Leader for Trans" src="{{ asset(\'assets/media/logo.png\') }}" />
                <span class="lft-brand-name" dir="ltr">LEADER<span><small>TRANSPORT & LOGISTICS</small></span></span>
            </a>
            <!--end::Logo-->';

$newBrand = '            <!--begin::Logo-->
            <a href="{{ route(\'main\') }}" class="lft-brand-container">
                <div class="lft-brand-logo">
                    <img src="{{ asset(\'assets/media/logo.png\') }}" alt="Leader for Trans" style="max-height: 28px; filter: brightness(0) invert(1);" onerror="this.onerror=null; this.parentNode.innerHTML=\'<i class=\"fas fa-truck-moving\"></i>\';" />
                </div>
                <div class="lft-brand-titles">
                    <span class="lft-brand-title">Leader for Trans</span>
                    <span class="lft-brand-subtitle">منظومة التجارة والأعمال الرقمية</span>
                </div>
            </a>
            <!--end::Logo-->';

if (strpos($content, 'lft-brand-container') === false && strpos($content, $oldBrand) !== false) {
    $content = str_replace($oldBrand, $newBrand, $content);
}

// 2. Mark dashboard menu-item active properly
$oldDashItem = '<li class="menu-item menu-item-submenu" aria-haspopup="true" data-menu-toggle="hover">
                        <a href="{{ route(\'main\') }}" class="menu-link">
                            <span class="svg-icon menu-icon">
                                <i class="fas fa-home"></i>
                            </span>
                            <span class="menu-text">{{ __(\'main.home\') }}</span>
                        </a>
                    </li>';

$newDashItem = '<li class="menu-item {{ request()->routeIs(\'main\') || request()->is(\'admin\') ? \'menu-item-active\' : \'\' }}" aria-haspopup="true">
                        <a href="{{ route(\'main\') }}" class="menu-link">
                            <span class="menu-icon">
                                <i class="fas fa-home"></i>
                            </span>
                            <span class="menu-text">لوحة التحكم</span>
                        </a>
                    </li>';

if (strpos($content, $oldDashItem) !== false) {
    $content = str_replace($oldDashItem, $newDashItem, $content);
}

// 3. Update Topbar section (#kt_header)
$oldHeaderRegex = '/<!--begin::Header-->\s*<div id="kt_header" class="header header-fixed">.*?<!--end::Header-->/s';

$newHeader = '<!--begin::Header-->
        <div id="kt_header" class="header header-fixed">
            <!--begin::Container-->
            <div class="container-fluid d-flex align-items-stretch justify-content-between px-6">
                <!--begin::Page Title & Breadcrumb-->
                <div class="d-flex align-items-center flex-wrap mr-4 py-3">
                    <div class="d-flex flex-column">
                        <h4 class="text-dark font-weight-bolder my-1 mr-3 font-size-h4" style="color: var(--text-primary) !important;">
                            @yield(\'admin-page-title\', \'لوحة التحكم\')
                        </h4>
                        <ul class="breadcrumb breadcrumb-transparent breadcrumb-dot font-weight-bold p-0 my-1 font-size-sm">
                            <li class="breadcrumb-item text-muted">
                                <a href="{{ route(\'main\') }}" class="text-muted text-hover-primary">الرئيسية</a>
                            </li>
                            <li class="breadcrumb-item text-muted">
                                <span>@yield(\'admin-page-title\', \'لوحة التحكم\')</span>
                            </li>
                        </ul>
                    </div>
                </div>
                <!--end::Page Title & Breadcrumb-->

                <!--begin::Topbar Actions-->
                <div class="d-flex align-items-center">
                    <!-- Global Search -->
                    <div class="lft-topbar-search d-none d-lg-block mr-4">
                        <i class="fas fa-search lft-topbar-search-icon"></i>
                        <input type="text" placeholder="البحث في النظام..." aria-label="البحث في النظام">
                    </div>

                    <div class="lft-topbar-actions">
                        <!-- Light / Dark Mode Toggle Pill -->
                        <div class="lft-theme-toggle mr-2" role="group" aria-label="تبديل المظهر">
                            <button type="button" class="lft-theme-toggle-btn active" data-theme-val="light" title="المظهر الفاتح">
                                <i class="fas fa-sun"></i>
                            </button>
                            <button type="button" class="lft-theme-toggle-btn" data-theme-val="dark" title="المظهر الداكن">
                                <i class="fas fa-moon"></i>
                            </button>
                        </div>

                        <!-- Notifications Button -->
                        <div class="dropdown mr-2">
                            <button type="button" class="lft-icon-btn" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="الإشعارات">
                                <i class="far fa-bell"></i>
                                <span class="lft-badge-counter">5</span>
                            </button>
                            <div class="dropdown-menu dropdown-menu-right p-0 m-0 dropdown-menu-md">
                                <div class="p-4 border-bottom d-flex justify-content-between align-items-center">
                                    <h6 class="font-weight-bolder mb-0">التنبيهات</h6>
                                    <span class="badge badge-light-primary">5 جديدة</span>
                                </div>
                                <div class="p-3 font-size-sm">
                                    <div class="d-flex align-items-center py-2 border-bottom">
                                        <div class="w-8px h-8px rounded-circle bg-success mr-2"></div>
                                        <span>تم إنشاء حجز جديد #509</span>
                                    </div>
                                    <div class="d-flex align-items-center py-2 border-bottom">
                                        <div class="w-8px h-8px rounded-circle bg-primary mr-2"></div>
                                        <span>تم اعتماد مرحلة تحميل لحاوية</span>
                                    </div>
                                    <div class="d-flex align-items-center py-2">
                                        <div class="w-8px h-8px rounded-circle bg-warning mr-2"></div>
                                        <span>شيكات مستحقة خلال 3 أيام</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Fullscreen Toggle Button -->
                        <button type="button" class="lft-icon-btn mr-3 d-none d-sm-inline-flex" onclick="if(!document.fullscreenElement){document.documentElement.requestFullscreen();}else{document.exitFullscreen();}" title="ملء الشاشة">
                            <i class="fas fa-expand-arrows-alt"></i>
                        </button>

                        <!-- User Profile Dropdown -->
                        <div class="dropdown">
                            <div class="lft-user-profile" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" role="button">
                                <div class="lft-user-avatar">
                                    {{ mb_substr(auth()->user()->name ?? \'م\', 0, 1) }}
                                </div>
                                <div class="lft-user-info d-none d-md-flex">
                                    <span class="lft-user-name">{{ auth()->user()->name ?? \'المدير العام\' }}</span>
                                    <span class="lft-user-role">مدير النظام</span>
                                </div>
                                <i class="fas fa-chevron-down font-size-xs text-muted mr-1"></i>
                            </div>
                            <div class="dropdown-menu dropdown-menu-right py-2 mt-2 shadow-sm border-0" style="border-radius: 12px; min-width: 200px;">
                                <div class="px-4 py-2 border-bottom mb-2">
                                    <div class="font-weight-bold text-dark">{{ auth()->user()->name ?? \'المدير العام\' }}</div>
                                    <small class="text-muted">{{ auth()->user()->email ?? \'admin@leaderfortrans.com\' }}</small>
                                </div>
                                <a class="dropdown-item py-2" href="{{ route(\'profile.edit\') }}">
                                    <i class="far fa-user ml-2 text-primary"></i> {{ __(\'profile.edit\') }}
                                </a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item py-2 text-danger" href="{{ route(\'logout\') }}" onclick="event.preventDefault(); document.getElementById(\'logout-form\').submit();">
                                    <i class="fas fa-sign-out-alt ml-2 text-danger"></i> {{ __(\'Logout\') }}
                                </a>
                                <form id="logout-form" action="{{ route(\'logout\') }}" method="POST" class="d-none">
                                    @csrf
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <!--end::Topbar Actions-->
            </div>
            <!--end::Container-->
        </div>
        <!--end::Header-->';

$content = preg_replace($oldHeaderRegex, $newHeader, $content);
file_put_contents($sidebarFile, $content);
echo "sidebar.blade.php updated successfully!\n";
