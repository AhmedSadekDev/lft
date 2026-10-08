@extends("layouts.admin")
@section("content")
<div class="container-fluid">
    @include("layouts.includes.breadcrumb", [ 'page' => 'لوحة التحكم' ])

    @php
        $user = auth()->user();
        $hasDashboardAccess = false;
        if ($user) {
            try {
                $hasDashboardAccess = $user->hasRole('Admin') || 
                                     $user->can('dashboard.index') || 
                                     ($user->hasPermissionTo('dashboard.index') ?? false);
            } catch (\Throwable $e) {
                $hasDashboardAccess = $user->hasRole('Admin') || $user->can('dashboard.index');
            }
        }
    @endphp

    @if($hasDashboardAccess)
        <section class="card db-hero-card mb-8" aria-label="ملخص الإدارة">
            <div class="card-body">
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between">
                    <div>
                        <span class="lft-eyebrow">LEADER FOR TRANS</span>
                        <h2 class="mb-2">أهلاً بك، {{ $user->name }}</h2>
                        <p class="mb-0">حركة التشغيل والحسابات، في مكان واحد.</p>
                    </div>
                    <div class="mt-4 mt-md-0">
                        <time datetime="{{ now()->toDateString() }}">{{ now()->translatedFormat('l، d F Y') }}</time>
                        <div class="d-flex flex-wrap mt-3">
                            @if($user->hasPermissionTo('bookings.index'))
                                <a class="btn btn-primary" href="{{ route('bookings.index') }}">إدارة الحجوزات <i class="fas fa-arrow-left ml-2" aria-hidden="true"></i></a>
                            @endif
                            @if($user->hasPermissionTo('bookings.create'))
                                <a class="btn btn-secondary" href="{{ route('bookings.create') }}"><i class="fas fa-plus" aria-hidden="true"></i> حجز جديد</a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- إحصائيات سريعة رئيسية -->
        <div class="row mb-6">
            <!-- الحجوزات -->
            <div class="col-xl-3 col-lg-6 col-md-6 mb-5">
                <div class="card db-stat-card shadow-sm h-100">
                    <div class="card-accent-bar bg-accent-primary"></div>
                    <div class="card-body p-6">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <span class="text-muted font-weight-bold d-block mb-1">إجمالي الحجوزات</span>
                                <div class="db-metric-value text-primary">{{ number_format($stats['total_bookings']) }}</div>
                            </div>
                            <div class="db-icon-box icon-box-primary">
                                <i class="fas fa-clipboard-list"></i>
                            </div>
                        </div>
                        <div class="pt-3 border-top d-flex flex-wrap gap-2 align-items-center">
                            <span class="db-pill mr-2 mb-1">
                                <i class="fas fa-sun text-warning mr-1"></i> اليوم: <strong class="ml-1 text-dark">{{ $stats['today_bookings'] }}</strong>
                            </span>
                            <span class="db-pill mb-1">
                                <i class="fas fa-calendar-week text-info mr-1"></i> الأسبوع: <strong class="ml-1 text-dark">{{ $stats['week_bookings'] }}</strong>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- الحاويات -->
            <div class="col-xl-3 col-lg-6 col-md-6 mb-5">
                <div class="card db-stat-card shadow-sm h-100">
                    <div class="card-accent-bar bg-accent-info"></div>
                    <div class="card-body p-6">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <span class="text-muted font-weight-bold d-block mb-1">إجمالي الحاويات</span>
                                <div class="db-metric-value text-info">{{ number_format($stats['total_containers']) }}</div>
                            </div>
                            <div class="db-icon-box icon-box-info">
                                <i class="fas fa-box"></i>
                            </div>
                        </div>
                        <div class="pt-3 border-top">
                            <span class="db-pill">
                                <i class="fas fa-calendar-day text-info mr-1"></i> مضافة اليوم: <strong class="ml-1 text-dark">{{ $stats['today_containers'] }}</strong>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- المندوبين -->
            <div class="col-xl-3 col-lg-6 col-md-6 mb-5">
                <div class="card db-stat-card shadow-sm h-100">
                    <div class="card-accent-bar bg-accent-success"></div>
                    <div class="card-body p-6">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <span class="text-muted font-weight-bold d-block mb-1">المندوبين</span>
                                <div class="db-metric-value text-success">{{ number_format($stats['total_agents']) }}</div>
                            </div>
                            <div class="db-icon-box icon-box-success">
                                <i class="fas fa-users"></i>
                            </div>
                        </div>
                        <div class="pt-3 border-top">
                            <span class="db-pill">
                                <i class="fas fa-user-shield text-success mr-1"></i> رئيسيين: <strong class="ml-1 text-dark">{{ $stats['total_superagents'] }}</strong>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- الخزنة -->
            <div class="col-xl-3 col-lg-6 col-md-6 mb-5">
                <div class="card db-stat-card shadow-sm h-100">
                    <div class="card-accent-bar bg-accent-warning"></div>
                    <div class="card-body p-6">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <span class="text-muted font-weight-bold d-block mb-1">رصيد الخزنة</span>
                                <div class="db-metric-value text-warning" style="font-size: 1.65rem;">
                                    {{ number_format($stats['vault_amount'], 2) }} <small class="font-size-xs">ج.م</small>
                                </div>
                            </div>
                            <div class="db-icon-box icon-box-warning">
                                <i class="fas fa-wallet"></i>
                            </div>
                        </div>
                        <div class="pt-3 border-top">
                            <span class="db-pill">
                                <i class="fas fa-chart-line text-warning mr-1"></i> الصافي الشهر:
                                <strong class="ml-1 {{ ($stats['month_income'] - $stats['month_expenses']) >= 0 ? 'text-success' : 'text-danger' }}">
                                    {{ number_format($stats['month_income'] - $stats['month_expenses'], 2) }} ج.م
                                </strong>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- إحصائيات التشغيل والجهات -->
        <div class="row mb-6">
            <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
                <div class="card db-stat-card shadow-sm">
                    <div class="card-body p-5 text-center">
                        <div class="db-icon-box icon-box-primary mx-auto mb-3">
                            <i class="fas fa-building"></i>
                        </div>
                        <h3 class="font-weight-bolder text-dark mb-1">{{ number_format($stats['total_companies']) }}</h3>
                        <span class="text-muted font-weight-bold">الشركات</span>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
                <div class="card db-stat-card shadow-sm">
                    <div class="card-body p-5 text-center">
                        <div class="db-icon-box icon-box-info mx-auto mb-3">
                            <i class="fas fa-truck-moving"></i>
                        </div>
                        <h3 class="font-weight-bolder text-dark mb-1">{{ number_format($stats['total_cars']) }}</h3>
                        <span class="text-muted font-weight-bold">السيارات</span>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
                <div class="card db-stat-card shadow-sm">
                    <div class="card-body p-5 text-center">
                        <div class="db-icon-box icon-box-success mx-auto mb-3">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <h3 class="font-weight-bolder text-dark mb-1">{{ number_format($stats['total_drivers']) }}</h3>
                        <span class="text-muted font-weight-bold">السائقين</span>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
                <div class="card db-stat-card shadow-sm">
                    <div class="card-body p-5 text-center">
                        <div class="db-icon-box icon-box-warning mx-auto mb-3">
                            <i class="fas fa-file-contract"></i>
                        </div>
                        <h3 class="font-weight-bolder text-dark mb-1">{{ number_format($stats['total_delivery_policies']) }}</h3>
                        <span class="text-muted font-weight-bold">البوليصات</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- إحصائيات الفواتير والشيكات -->
        <div class="row mb-6">
            <div class="col-lg-{{ auth()->user()->hasPermissionTo('accounts.index') ? '6' : '12' }} mb-4">
                <div class="card db-stat-card shadow-sm h-100">
                    <div class="card-accent-bar bg-accent-danger"></div>
                    <div class="card-body p-6">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <span class="text-muted font-weight-bold d-block mb-1">إجمالي الفواتير</span>
                                <div class="db-metric-value text-danger">{{ number_format($stats['total_invoices']) }}</div>
                            </div>
                            <div class="db-icon-box icon-box-danger">
                                <i class="fas fa-file-invoice-dollar"></i>
                            </div>
                        </div>
                        <div class="pt-3 border-top d-flex gap-3">
                            <span class="db-pill mr-2">
                                <i class="fas fa-calendar-day text-danger mr-1"></i> فواتير اليوم: <strong class="ml-1 text-dark">{{ $stats['today_invoices'] }}</strong>
                            </span>
                            <span class="db-pill">
                                <i class="fas fa-calendar-alt text-danger mr-1"></i> فواتير هذا الشهر: <strong class="ml-1 text-dark">{{ $stats['month_invoices'] }}</strong>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            @if(auth()->user()->hasPermissionTo('accounts.index'))
            <div class="col-lg-6 mb-4">
                <a href="{{ route('accounts.checks.index') }}" class="text-decoration-none">
                    <div class="card db-stat-card shadow-sm h-100">
                        <div class="card-accent-bar bg-accent-purple"></div>
                        <div class="card-body p-6">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <span class="text-muted font-weight-bold d-block mb-1">شيكات مستحقة خلال 3 أيام</span>
                                    <div class="db-metric-value text-dark">{{ number_format($stats['checks_due_within_3_days'] ?? 0) }}</div>
                                </div>
                                <div class="db-icon-box icon-box-purple">
                                    <i class="fas fa-money-check-alt"></i>
                                </div>
                            </div>
                            <div class="pt-3 border-top d-flex justify-content-between align-items-center">
                                <span class="text-muted font-weight-bold font-size-sm">شيكات بحاجة إلى التحصيل الفوري</span>
                                <span class="btn btn-sm btn-light-primary font-weight-bolder px-4 py-2">
                                    عرض التفاصيل <i class="fas fa-arrow-left mr-1 font-size-xs"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            @endif
        </div>

        <!-- الإحصائيات المالية التفصيلية -->
        <div class="row mb-6">
            <!-- حركة اليوم -->
            <div class="col-lg-6 mb-5">
                <div class="card card-custom shadow-sm h-100 border-0" style="border-radius: 16px;">
                    <div class="card-header border-0 pt-6 pb-2 bg-transparent">
                        <h3 class="card-title font-weight-bolder text-dark mb-0">
                            <i class="fas fa-chart-pie text-primary mr-2"></i>
                            الموقف المالي اليومي
                        </h3>
                        <span class="text-muted font-weight-bold font-size-sm">حركة الوارد والمصروف اليوم</span>
                    </div>
                    <div class="card-body pt-4">
                        <div class="row mb-4">
                            <div class="col-6">
                                <div class="db-financial-box expenses text-center">
                                    <div class="d-inline-flex align-items-center justify-content-center w-40px h-40px rounded-circle bg-white text-danger shadow-xs mb-2">
                                        <i class="fas fa-arrow-down"></i>
                                    </div>
                                    <h4 class="font-weight-bolder text-danger mb-1">{{ number_format($stats['today_expenses'], 2) }} <small class="font-size-xs">ج.م</small></h4>
                                    <span class="text-muted font-weight-bold font-size-sm">المصروفات</span>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="db-financial-box income text-center">
                                    <div class="d-inline-flex align-items-center justify-content-center w-40px h-40px rounded-circle bg-white text-success shadow-xs mb-2">
                                        <i class="fas fa-arrow-up"></i>
                                    </div>
                                    <h4 class="font-weight-bolder text-success mb-1">{{ number_format($stats['today_income'], 2) }} <small class="font-size-xs">ج.م</small></h4>
                                    <span class="text-muted font-weight-bold font-size-sm">الواردات</span>
                                </div>
                            </div>
                        </div>
                        <div class="p-4 rounded-xl text-center {{ ($stats['today_income'] - $stats['today_expenses']) >= 0 ? 'bg-light-success text-success' : 'bg-light-danger text-danger' }}">
                            <span class="font-weight-bold d-block mb-1">صافي حركة اليوم</span>
                            <h4 class="font-weight-bolder mb-0">
                                {{ number_format($stats['today_income'] - $stats['today_expenses'], 2) }} ج.م
                            </h4>
                        </div>
                    </div>
                </div>
            </div>

            <!-- حركة الشهر -->
            <div class="col-lg-6 mb-5">
                <div class="card card-custom shadow-sm h-100 border-0" style="border-radius: 16px;">
                    <div class="card-header border-0 pt-6 pb-2 bg-transparent">
                        <h3 class="card-title font-weight-bolder text-dark mb-0">
                            <i class="fas fa-chart-bar text-info mr-2"></i>
                            الموقف المالي الشهري
                        </h3>
                        <span class="text-muted font-weight-bold font-size-sm">حركة الوارد والمصروف هذا الشهر</span>
                    </div>
                    <div class="card-body pt-4">
                        <div class="row mb-4">
                            <div class="col-6">
                                <div class="db-financial-box expenses text-center">
                                    <div class="d-inline-flex align-items-center justify-content-center w-40px h-40px rounded-circle bg-white text-danger shadow-xs mb-2">
                                        <i class="fas fa-arrow-down"></i>
                                    </div>
                                    <h4 class="font-weight-bolder text-danger mb-1">{{ number_format($stats['month_expenses'], 2) }} <small class="font-size-xs">ج.م</small></h4>
                                    <span class="text-muted font-weight-bold font-size-sm">المصروفات</span>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="db-financial-box income text-center">
                                    <div class="d-inline-flex align-items-center justify-content-center w-40px h-40px rounded-circle bg-white text-success shadow-xs mb-2">
                                        <i class="fas fa-arrow-up"></i>
                                    </div>
                                    <h4 class="font-weight-bolder text-success mb-1">{{ number_format($stats['month_income'], 2) }} <small class="font-size-xs">ج.م</small></h4>
                                    <span class="text-muted font-weight-bold font-size-sm">الواردات</span>
                                </div>
                            </div>
                        </div>
                        <div class="p-4 rounded-xl text-center {{ ($stats['month_income'] - $stats['month_expenses']) >= 0 ? 'bg-light-success text-success' : 'bg-light-danger text-danger' }}">
                            <span class="font-weight-bold d-block mb-1">صافي حركة الشهر الحالي</span>
                            <h4 class="font-weight-bolder mb-0">
                                {{ number_format($stats['month_income'] - $stats['month_expenses'], 2) }} ج.م
                            </h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- الرسوم البيانية التفاعلية -->
        <div class="row mb-6">
            <!-- رسم بياني للحجوزات -->
            <div class="col-lg-6 mb-5">
                <div class="card card-custom shadow-sm border-0" style="border-radius: 16px;">
                    <div class="card-header border-0 pt-6 pb-2 bg-transparent">
                        <h3 class="card-title font-weight-bolder text-dark mb-0">
                            <i class="fas fa-chart-line text-primary mr-2"></i>
                            نمو الحجوزات (آخر 6 أشهر)
                        </h3>
                    </div>
                    <div class="card-body p-5">
                        <x-admin.chart-panel id="bookingsChart" title="نمو الحجوزات خلال آخر ستة أشهر" :labels="$bookingsChart['labels']" :series="['عدد الحجوزات' => $bookingsChart['data']]" />
                    </div>
                </div>
            </div>

            <!-- رسم بياني للمصروفات والواردات -->
            <div class="col-lg-6 mb-5">
                <div class="card card-custom shadow-sm border-0" style="border-radius: 16px;">
                    <div class="card-header border-0 pt-6 pb-2 bg-transparent">
                        <h3 class="card-title font-weight-bolder text-dark mb-0">
                            <i class="fas fa-chart-area text-success mr-2"></i>
                            مقارنة المالية (آخر 6 أشهر)
                        </h3>
                    </div>
                    <div class="card-body p-5">
                        <x-admin.chart-panel id="financialChart" title="المصروفات والواردات خلال آخر ستة أشهر" :labels="$financialChart['labels']" :series="['المصروفات' => $financialChart['expenses'], 'الواردات' => $financialChart['income']]" />
                    </div>
                </div>
            </div>
        </div>
    @else
        <!-- Restricted Access / No Permission Design -->
        <div class="row justify-content-center my-8">
            <div class="col-xl-8 col-lg-10">
                <div class="db-restricted-card">
                    <div class="db-lock-halo">
                        <i class="fas fa-lock"></i>
                    </div>
                    
                    <span class="badge badge-light-danger font-weight-bolder px-4 py-2 mb-3 text-uppercase tracking-wide" style="border-radius: 20px; font-size: 0.85rem;">
                        <i class="fas fa-shield-alt mr-1 text-danger"></i> وصول مقيد
                    </span>

                    <h2 class="font-weight-bolder text-dark mb-3">
                        عفواً، لا تملك صلاحية عرض إحصائيات لوحة التحكم
                    </h2>
                    
                    <p class="text-muted font-size-lg mb-6 max-w-500px mx-auto leading-relaxed">
                        تم تقييد عرض مؤشرات وتقارير لوحة التحكم لحسابك بناءً على صلاحيات النظام الحالية.
                        يمكنك الانتقال المباشر لأي من الأقسام المصرح لك بها أدناه، أو التواصل مع مدير النظام لتفعيل الصلاحية المطلوبة.
                    </p>

                    <!-- Shortcut Links for Authorized Pages -->
                    <div class="border-top pt-6 mt-4">
                        <h6 class="font-weight-bolder text-muted mb-4">اختصارات الأقسام المتاحة لك:</h6>
                        <div class="row justify-content-center">
                            @if($user->can('bookings.index') || ($user->hasPermissionTo('bookings.index') ?? false))
                                <div class="col-md-4 col-sm-6 mb-3">
                                    <a href="{{ route('bookings.index') }}" class="db-shortcut-card">
                                        <div class="db-icon-box icon-box-primary" style="width:40px; height:40px; font-size:1.1rem;">
                                            <i class="fas fa-clipboard-list"></i>
                                        </div>
                                        <span class="font-weight-bolder">إدارة الحجوزات</span>
                                    </a>
                                </div>
                            @endif

                            @if($user->can('containers.index') || ($user->hasPermissionTo('containers.index') ?? false))
                                <div class="col-md-4 col-sm-6 mb-3">
                                    <a href="{{ route('containers.index') }}" class="db-shortcut-card">
                                        <div class="db-icon-box icon-box-info" style="width:40px; height:40px; font-size:1.1rem;">
                                            <i class="fas fa-box"></i>
                                        </div>
                                        <span class="font-weight-bolder">إدارة الحاويات</span>
                                    </a>
                                </div>
                            @endif

                            @if($user->can('companies.index') || ($user->hasPermissionTo('companies.index') ?? false))
                                <div class="col-md-4 col-sm-6 mb-3">
                                    <a href="{{ route('companies.index') }}" class="db-shortcut-card">
                                        <div class="db-icon-box icon-box-success" style="width:40px; height:40px; font-size:1.1rem;">
                                            <i class="fas fa-building"></i>
                                        </div>
                                        <span class="font-weight-bolder">إدارة الشركات</span>
                                    </a>
                                </div>
                            @endif

                            @if($user->can('drivers.index') || ($user->hasPermissionTo('drivers.index') ?? false))
                                <div class="col-md-4 col-sm-6 mb-3">
                                    <a href="{{ route('drivers.index') }}" class="db-shortcut-card">
                                        <div class="db-icon-box icon-box-warning" style="width:40px; height:40px; font-size:1.1rem;">
                                            <i class="fas fa-user-tie"></i>
                                        </div>
                                        <span class="font-weight-bolder">إدارة السائقين</span>
                                    </a>
                                </div>
                            @endif

                            @if($user->can('cars.index') || ($user->hasPermissionTo('cars.index') ?? false))
                                <div class="col-md-4 col-sm-6 mb-3">
                                    <a href="{{ route('cars.index') }}" class="db-shortcut-card">
                                        <div class="db-icon-box icon-box-purple" style="width:40px; height:40px; font-size:1.1rem;">
                                            <i class="fas fa-truck-moving"></i>
                                        </div>
                                        <span class="font-weight-bolder">إدارة السيارات</span>
                                    </a>
                                </div>
                            @endif

                            @if($user->can('permissions.index') || ($user->hasPermissionTo('permissions.index') ?? false))
                                <div class="col-md-4 col-sm-6 mb-3">
                                    <a href="{{ route('permissions.index') }}" class="db-shortcut-card">
                                        <div class="db-icon-box icon-box-danger" style="width:40px; height:40px; font-size:1.1rem;">
                                            <i class="fas fa-user-shield"></i>
                                        </div>
                                        <span class="font-weight-bolder">إدارة الصلاحيات</span>
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection

@push('js')
@if($hasDashboardAccess)
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Chart Config - Common styling
        if (typeof Chart === 'undefined') return; // Exact values remain available in the accessible data tables.
        Chart.defaults.font.family = 'regular, Tahoma, sans-serif';
        Chart.defaults.animation = window.matchMedia('(prefers-reduced-motion: reduce)').matches ? false : { duration: 300 };

        // 1. Line Chart - Bookings
        const bookingsElem = document.getElementById('bookingsChart');
        if (bookingsElem) {
            const bookingsCtx = bookingsElem.getContext('2d');
            
            // Gradient fill
            const blueGradient = bookingsCtx.createLinearGradient(0, 0, 0, 300);
            blueGradient.addColorStop(0, 'rgba(8, 127, 140, 0.35)');
            blueGradient.addColorStop(1, 'rgba(8, 127, 140, 0.0)');

            new Chart(bookingsCtx, {
                type: 'line',
                data: {
                    labels: @json($bookingsChart['labels']),
                    datasets: [{
                        label: 'عدد الحجوزات',
                        data: @json($bookingsChart['data']),
                        borderColor: '#087f8c',
                        backgroundColor: blueGradient,
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 4,
                        pointBackgroundColor: '#087f8c',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        pointHoverRadius: 7
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#1e1e2d',
                            padding: 12,
                            titleFont: { size: 14, weight: 'bold' },
                            bodyFont: { size: 13 },
                            cornerRadius: 8,
                            displayColors: false
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: { color: '#617489', font: { weight: '600' } }
                        },
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(0, 0, 0, 0.04)' },
                            ticks: { stepSize: 1, color: '#617489' }
                        }
                    }
                }
            });
        }

        // 2. Bar Chart - Financials
        const financialElem = document.getElementById('financialChart');
        if (financialElem) {
            const financialCtx = financialElem.getContext('2d');

            new Chart(financialCtx, {
                type: 'bar',
                data: {
                    labels: @json($financialChart['labels']),
                    datasets: [
                        {
                            label: 'المصروفات',
                            data: @json($financialChart['expenses']),
                            backgroundColor: 'rgba(189, 56, 72, 0.85)',
                            borderColor: '#bd3848',
                            borderWidth: 1,
                            borderRadius: 6,
                        },
                        {
                            label: 'الواردات',
                            data: @json($financialChart['income']),
                            backgroundColor: 'rgba(19, 118, 83, 0.85)',
                            borderColor: '#137653',
                            borderWidth: 1,
                            borderRadius: 6,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                            labels: {
                                usePointStyle: true,
                                padding: 15,
                                font: { weight: 'bold' }
                            }
                        },
                        tooltip: {
                            backgroundColor: '#1e1e2d',
                            padding: 12,
                            titleFont: { size: 14, weight: 'bold' },
                            bodyFont: { size: 13 },
                            cornerRadius: 8,
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': ' + new Intl.NumberFormat('ar-EG', {
                                        style: 'currency',
                                        currency: 'EGP',
                                        minimumFractionDigits: 2
                                    }).format(context.parsed.y);
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: { color: '#617489', font: { weight: '600' } }
                        },
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(0, 0, 0, 0.04)' },
                            ticks: {
                                color: '#617489',
                                callback: function(value) {
                                    return new Intl.NumberFormat('ar-EG', {
                                        style: 'currency',
                                        currency: 'EGP',
                                        minimumFractionDigits: 0
                                    }).format(value);
                                }
                            }
                        }
                    }
                }
            });
        }
    });
</script>
@endif
@endpush
