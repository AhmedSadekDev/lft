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
        <!-- Hero Welcome Header -->
        <div class="card db-hero-card mb-8">
            <div class="card-body p-6 p-lg-8">
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between">
                    <div class="d-flex align-items-center mb-4 mb-md-0">
                        <div class="mr-4 d-flex align-items-center justify-content-center text-white font-weight-bold font-size-h3 bg-primary" 
                             style="width: 60px; height: 60px; border-radius: 50%; box-shadow: 0 4px 12px rgba(54, 153, 255, 0.4); flex-shrink: 0;">
                            {{ mb_substr($user->name ?? 'A', 0, 1) }}
                        </div>
                        <div>
                            <h2 class="font-weight-bolder mb-1" style="color: #000000 !important; font-size: 1.6rem;">
                                أهلاً بك، {{ $user->name ?? 'المستخدم' }} 👋
                            </h2>
                            <p class="mb-0 font-size-lg" style="color: #000000 !important;">
                                إليك نظرة عامة ومؤشرات أداء النظام حتى اليوم
                            </p>
                        </div>
                    </div>
                    <div class="d-flex align-items-center">
                        <div class="px-4 py-3 rounded-xl d-flex align-items-center" 
                             style="background: rgba(255, 255, 255, 0.12); border: 1px solid rgba(255, 255, 255, 0.2); backdrop-filter: blur(5px);">
                            <i class="far fa-calendar-alt mr-3 fa-lg" style="color: #ffffff !important;"></i>
                            <div class="text-right">
                                <span class="d-block font-weight-bolder font-size-sm" style="color: #ffffff !important;">
                                    {{ \Carbon\Carbon::now()->translatedFormat('l, d F Y') }}
                                </span>
                                <span class="d-block font-size-xs" style="color: rgba(255, 255, 255, 0.7) !important;">التاريخ الحالي</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

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
                        <canvas id="bookingsChart" height="130"></canvas>
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
                        <canvas id="financialChart" height="130"></canvas>
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

@push('css')
<style>
    /* Dashboard Styling System */
    .db-hero-card {
        background: linear-gradient(135deg, #1b1b29 0%, #28293d 100%) !important;
        background-color: #1b1b29 !important;
        border: none !important;
        border-radius: 16px !important;
        color: #ffffff !important;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15) !important;
        position: relative;
        overflow: hidden;
    }
    .db-hero-card::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -10%;
        width: 300px;
        height: 300px;
        background: radial-gradient(circle, rgba(54, 153, 255, 0.25) 0%, rgba(0, 0, 0, 0) 70%);
        border-radius: 50%;
        pointer-events: none;
    }
    .db-hero-card h2,
    .db-hero-card h3,
    .db-hero-card h4,
    .db-hero-card h5,
    .db-hero-card span,
    .db-hero-card p,
    .db-hero-card i {
        color: #ffffff !important;
    }

    .db-stat-card {
        border: 1px solid rgba(0, 0, 0, 0.05);
        border-radius: 16px;
        background: #ffffff;
        transition: all 0.3s cubic-bezier(0.165, 0.84, 0.44, 1);
        position: relative;
        overflow: hidden;
    }
    .db-stat-card:hover {
        transform: translateY(-6px);
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.08) !important;
    }

    .db-stat-card .card-accent-bar {
        height: 4px;
        width: 100%;
        position: absolute;
        top: 0;
        left: 0;
    }

    .bg-accent-primary { background: linear-gradient(90deg, #3699FF 0%, #0062FF 100%); }
    .bg-accent-info { background: linear-gradient(90deg, #1BC5BD 0%, #0BB7AF 100%); }
    .bg-accent-success { background: linear-gradient(90deg, #28C76F 0%, #48DA89 100%); }
    .bg-accent-warning { background: linear-gradient(90deg, #FF9F43 0%, #FFB800 100%); }
    .bg-accent-danger { background: linear-gradient(90deg, #EA5455 0%, #F07067 100%); }
    .bg-accent-purple { background: linear-gradient(90deg, #7367F0 0%, #9E95F5 100%); }

    .db-icon-box {
        width: 58px;
        height: 58px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.4rem;
        transition: transform 0.3s ease;
    }
    .db-stat-card:hover .db-icon-box {
        transform: scale(1.08) rotate(-3deg);
    }

    .icon-box-primary { background: rgba(54, 153, 255, 0.12); color: #3699FF; }
    .icon-box-info { background: rgba(27, 197, 189, 0.12); color: #1BC5BD; }
    .icon-box-success { background: rgba(40, 199, 111, 0.12); color: #28C76F; }
    .icon-box-warning { background: rgba(255, 159, 67, 0.12); color: #FF9F43; }
    .icon-box-danger { background: rgba(234, 84, 85, 0.12); color: #EA5455; }
    .icon-box-purple { background: rgba(115, 103, 240, 0.12); color: #7367F0; }

    .db-metric-value {
        font-size: 1.95rem;
        font-weight: 800;
        letter-spacing: -0.5px;
        color: #181C32;
    }

    .db-pill {
        display: inline-flex;
        align-items: center;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 0.78rem;
        font-weight: 600;
        background: #F3F6F9;
        color: #5E6278;
    }

    .db-financial-box {
        border-radius: 14px;
        padding: 1.25rem;
        transition: all 0.2s ease;
    }
    .db-financial-box.expenses {
        background: rgba(234, 84, 85, 0.06);
        border: 1px dashed rgba(234, 84, 85, 0.2);
    }
    .db-financial-box.income {
        background: rgba(40, 199, 111, 0.06);
        border: 1px dashed rgba(40, 199, 111, 0.2);
    }

    /* Restricted Access Empty State */
    .db-restricted-card {
        background: #ffffff;
        border-radius: 20px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.06);
        border: 1px solid rgba(0, 0, 0, 0.06);
        padding: 3.5rem 2rem;
        text-align: center;
        position: relative;
        overflow: hidden;
    }
    .db-restricted-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 6px;
        background: linear-gradient(90deg, #FF9F43 0%, #EA5455 50%, #7367F0 100%);
    }
    .db-lock-halo {
        width: 90px;
        height: 90px;
        border-radius: 50%;
        background: rgba(234, 84, 85, 0.08);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1.5rem auto;
        color: #EA5455;
        font-size: 2.6rem;
        position: relative;
        animation: pulse-ring 2.5s infinite;
    }
    @keyframes pulse-ring {
        0% { box-shadow: 0 0 0 0 rgba(234, 84, 85, 0.2); }
        70% { box-shadow: 0 0 0 18px rgba(234, 84, 85, 0); }
        100% { box-shadow: 0 0 0 0 rgba(234, 84, 85, 0); }
    }

    .db-shortcut-card {
        background: #F9FAFB;
        border: 1px solid #E5E7EB;
        border-radius: 12px;
        padding: 0.9rem;
        transition: all 0.25s ease;
        text-decoration: none !important;
        display: flex;
        align-items: center;
        gap: 12px;
        color: #374151;
    }
    .db-shortcut-card:hover {
        background: #ffffff;
        border-color: #3699FF;
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(54, 153, 255, 0.12);
        color: #3699FF;
    }
</style>
@endpush

@push('js')
@if($hasDashboardAccess)
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Chart Config - Common styling
        Chart.defaults.font.family = 'inherit';

        // 1. Line Chart - Bookings
        const bookingsElem = document.getElementById('bookingsChart');
        if (bookingsElem) {
            const bookingsCtx = bookingsElem.getContext('2d');
            
            // Gradient fill
            const blueGradient = bookingsCtx.createLinearGradient(0, 0, 0, 300);
            blueGradient.addColorStop(0, 'rgba(54, 153, 255, 0.35)');
            blueGradient.addColorStop(1, 'rgba(54, 153, 255, 0.0)');

            new Chart(bookingsCtx, {
                type: 'line',
                data: {
                    labels: @json($bookingsChart['labels']),
                    datasets: [{
                        label: 'عدد الحجوزات',
                        data: @json($bookingsChart['data']),
                        borderColor: '#3699FF',
                        backgroundColor: blueGradient,
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 4,
                        pointBackgroundColor: '#3699FF',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        pointHoverRadius: 7
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
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
                            ticks: { color: '#B5B5C3', font: { weight: '600' } }
                        },
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(0, 0, 0, 0.04)' },
                            ticks: { stepSize: 1, color: '#B5B5C3' }
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
                            backgroundColor: 'rgba(234, 84, 85, 0.85)',
                            borderColor: '#EA5455',
                            borderWidth: 1,
                            borderRadius: 6,
                        },
                        {
                            label: 'الواردات',
                            data: @json($financialChart['income']),
                            backgroundColor: 'rgba(40, 199, 111, 0.85)',
                            borderColor: '#28C76F',
                            borderWidth: 1,
                            borderRadius: 6,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
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
                            ticks: { color: '#B5B5C3', font: { weight: '600' } }
                        },
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(0, 0, 0, 0.04)' },
                            ticks: {
                                color: '#B5B5C3',
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
