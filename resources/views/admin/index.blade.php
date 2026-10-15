@extends("layouts.admin")

@section("admin-page-title", "لوحة التحكم")

@section("content")
<div class="container-fluid px-6 py-6">

    @php
        $user = auth()->user();
        $hasDashboardAccess = true;
        $canBookings = true;
        $canAccounts = true;
        $canContainers = true;

        if ($user) {
            try {
                $hasDashboardAccess = $user->hasRole('Admin') ||
                                     $user->can('dashboard.index') ||
                                     ($user->hasPermissionTo('dashboard.index') ?? false);
            } catch (\Throwable $e) {
                $hasDashboardAccess = true;
            }

            try {
                $canBookings = $user->can('bookings.index');
            } catch (\Throwable $e) {
                $canBookings = true;
            }

            try {
                $canAccounts = $user->can('accounts.index');
            } catch (\Throwable $e) {
                $canAccounts = true;
            }

            try {
                $canContainers = $user->can('containers.index');
            } catch (\Throwable $e) {
                $canContainers = true;
            }
        }

        $recentBookings = $recentBookings ?? collect();
        $containerStages = $containerStages ?? ['assigned' => 0, 'waiting' => 0, 'loading' => 0, 'unloading' => 0, 'invoiced' => 0];
        $totalStagesCount = array_sum($containerStages);
        $stageLegend = [
            'assigned'  => ['label' => 'التخصيص', 'color' => '#06b6d4'],
            'waiting'   => ['label' => 'الانتظار', 'color' => '#3b82f6'],
            'loading'   => ['label' => 'التحميل', 'color' => '#f59e0b'],
            'unloading' => ['label' => 'التعتيق', 'color' => '#10b981'],
            'invoiced'  => ['label' => 'فواتير منتهية', 'color' => '#8b5cf6'],
        ];
    @endphp

    @if($hasDashboardAccess)
        <!-- KPI STATISTICS CARDS -->
        <div class="row g-4 mb-6">
            <div class="col-xl-3 col-sm-6 mb-4">
                <div class="lft-kpi-card h-100">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="lft-kpi-info">
                            <span class="lft-kpi-title">إجمالي الحجوزات</span>
                            <div class="lft-kpi-val">{{ number_format($stats['total_bookings'] ?? 0) }}</div>
                        </div>
                        <div class="lft-kpi-icon-box purple" aria-hidden="true">
                            <i class="fas fa-boxes"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-center justify-content-between mt-auto pt-2 border-top-subtle">
                        <span class="lft-trend-text">هذا الشهر: <strong>{{ number_format($stats['month_bookings'] ?? 0) }}</strong> حجز</span>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-sm-6 mb-4">
                <div class="lft-kpi-card h-100">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="lft-kpi-info">
                            <span class="lft-kpi-title">الحاويات النشطة</span>
                            <div class="lft-kpi-val">{{ number_format($stats['active_containers'] ?? 0) }}</div>
                        </div>
                        <div class="lft-kpi-icon-box blue" aria-hidden="true">
                            <i class="fas fa-ship"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-center justify-content-between mt-auto pt-2 border-top-subtle">
                        <span class="lft-trend-text">من إجمالي <strong>{{ number_format($stats['total_containers'] ?? 0) }}</strong> حاوية · اليوم: {{ number_format($stats['today_containers'] ?? 0) }}</span>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-sm-6 mb-4">
                <div class="lft-kpi-card h-100">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="lft-kpi-info">
                            <span class="lft-kpi-title">الأسطول</span>
                            <div class="lft-kpi-val">{{ number_format($stats['total_cars'] ?? 0) }}</div>
                        </div>
                        <div class="lft-kpi-icon-box green" aria-hidden="true">
                            <i class="fas fa-truck-moving"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-center justify-content-between mt-auto pt-2 border-top-subtle">
                        <span class="lft-trend-text"><strong>{{ number_format($stats['total_drivers'] ?? 0) }}</strong> سائق مسجل</span>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-sm-6 mb-4">
                <div class="lft-kpi-card h-100">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="lft-kpi-info">
                            <span class="lft-kpi-title">الشركات</span>
                            <div class="lft-kpi-val">{{ number_format($stats['total_companies'] ?? 0) }}</div>
                        </div>
                        <div class="lft-kpi-icon-box orange" aria-hidden="true">
                            <i class="fas fa-building"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-center justify-content-between mt-auto pt-2 border-top-subtle">
                        <span class="lft-trend-text">شركة مسجلة في النظام</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- CHARTS & ANALYTICS -->
        <div class="row g-4 mb-6">
            <div class="col-xl-8 col-lg-7 mb-4">
                <div class="lft-card h-100">
                    <div class="lft-card-header d-flex align-items-center justify-content-between pb-2">
                        <h5 class="lft-card-title mb-0">الحجوزات خلال الأشهر الستة الماضية</h5>
                        <span class="d-flex align-items-center font-size-sm lft-chart-legend">
                            <span class="d-inline-block rounded-circle ml-1" style="width: 10px; height: 10px; background-color: #06b6d4;" aria-hidden="true"></span>
                            الحجوزات الجديدة
                        </span>
                    </div>
                    <div class="lft-card-body p-4">
                        <div id="lftBookingsAreaChart" style="min-height: 290px;" role="img" aria-label="رسم بياني لعدد الحجوزات الجديدة شهرياً"></div>
                    </div>
                </div>
            </div>

            <div class="col-xl-4 col-lg-5 mb-4">
                <div class="lft-card h-100">
                    <div class="lft-card-header pb-2">
                        <h5 class="lft-card-title mb-0">توزيع الحاويات حسب المرحلة</h5>
                    </div>
                    <div class="lft-card-body p-4">
                        <div class="row align-items-center">
                            <div class="col-sm-7 position-relative">
                                <div id="lftStagesDonutChart" style="min-height: 220px;" role="img" aria-label="توزيع الحاويات حسب المرحلة"></div>
                            </div>
                            <div class="col-sm-5 mt-3 mt-sm-0">
                                <div class="lft-stage-legend-list">
                                    @foreach($stageLegend as $key => $stage)
                                        <div class="lft-stage-item {{ $loop->last ? '' : 'mb-2' }} d-flex align-items-center justify-content-between">
                                            <span class="d-flex align-items-center font-size-sm">
                                                <span class="legend-dot" style="background-color: {{ $stage['color'] }};" aria-hidden="true"></span>
                                                {{ $stage['label'] }}
                                            </span>
                                            <strong class="font-size-sm lft-stage-count">{{ number_format($containerStages[$key] ?? 0) }}</strong>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- RECENT BOOKINGS, FINANCIAL STATS & TODAY'S ACTIVITY -->
        <div class="row g-4 mb-6">
            <div class="col-xl-5 col-lg-6 mb-4">
                <div class="lft-card h-100">
                    <div class="lft-card-header d-flex align-items-center justify-content-between pb-3">
                        <h5 class="lft-card-title mb-0">أحدث الحجوزات</h5>
                        @if($canBookings)
                            <a href="{{ route('bookings.index') }}" class="lft-view-all-btn">عرض الكل</a>
                        @endif
                    </div>
                    <div class="lft-card-body p-0">
                        <div class="table-responsive">
                            <table class="table lft-table mb-0 no-datatable">
                                <thead>
                                    <tr>
                                        <th scope="col">رقم الحجز</th>
                                        <th scope="col">الشركة</th>
                                        <th scope="col">المرحلة</th>
                                        <th scope="col">التاريخ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($recentBookings as $b)
                                        @php $stageInfo = $b->stage_info; @endphp
                                        <tr>
                                            <td class="font-weight-bold">
                                                @if($canBookings)
                                                    <a href="{{ route('bookings.show', $b->id) }}">{{ $b->booking_number ?: '#' . $b->id }}</a>
                                                @else
                                                    {{ $b->booking_number ?: '#' . $b->id }}
                                                @endif
                                            </td>
                                            <td>{{ $b->company->name ?? '—' }}</td>
                                            <td>
                                                <span class="badge {{ $stageInfo['badge'] }} font-weight-bold px-2 py-1">{{ $stageInfo['label'] }}</span>
                                            </td>
                                            <td class="lft-text-secondary font-size-sm">{{ $b->created_at ? $b->created_at->format('Y-m-d') : '—' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center py-4 lft-text-secondary">لا توجد حجوزات مسجلة</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-4 col-lg-6 mb-4">
                <div class="lft-card h-100">
                    <div class="lft-card-header d-flex align-items-center justify-content-between pb-3">
                        <h5 class="lft-card-title mb-0">إحصائيات مالية (الشهر الحالي)</h5>
                        @if($canAccounts)
                            <a href="{{ route('accounts.index') }}" class="lft-view-all-btn">عرض الكل</a>
                        @endif
                    </div>
                    <div class="lft-card-body p-4 d-flex flex-column justify-content-center">
                        <div class="row g-3">
                            <div class="col-6">
                                <div class="lft-fin-summary-box income h-100">
                                    <div class="lft-fin-icon green" aria-hidden="true">
                                        <i class="fas fa-wallet"></i>
                                    </div>
                                    <span class="lft-fin-label">إجمالي الإيرادات</span>
                                    <h4 class="lft-fin-value">{{ number_format($stats['month_income'] ?? 0) }}</h4>
                                    <span class="lft-trend-text font-size-xs">اليوم: {{ number_format($stats['today_income'] ?? 0) }} ج.م</span>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="lft-fin-summary-box expenses h-100">
                                    <div class="lft-fin-icon red" aria-hidden="true">
                                        <i class="fas fa-receipt"></i>
                                    </div>
                                    <span class="lft-fin-label">إجمالي المصروفات</span>
                                    <h4 class="lft-fin-value">{{ number_format($stats['month_expenses'] ?? 0) }}</h4>
                                    <span class="lft-trend-text font-size-xs">اليوم: {{ number_format($stats['today_expenses'] ?? 0) }} ج.م</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-lg-12 mb-4">
                <div class="lft-card h-100">
                    <div class="lft-card-header pb-3">
                        <h5 class="lft-card-title mb-0">نشاط اليوم</h5>
                    </div>
                    <div class="lft-card-body p-4">
                        <div class="lft-activity-timeline lft-activity-timeline--stats">
                            <div class="lft-activity-item">
                                <div class="lft-activity-dot green"></div>
                                <div class="lft-activity-content">
                                    <span class="lft-activity-title">حجوزات جديدة</span>
                                    <span class="lft-activity-time">{{ number_format($stats['today_bookings'] ?? 0) }}</span>
                                </div>
                            </div>
                            <div class="lft-activity-item">
                                <div class="lft-activity-dot blue"></div>
                                <div class="lft-activity-content">
                                    <span class="lft-activity-title">حاويات مضافة</span>
                                    <span class="lft-activity-time">{{ number_format($stats['today_containers'] ?? 0) }}</span>
                                </div>
                            </div>
                            <div class="lft-activity-item">
                                <div class="lft-activity-dot orange"></div>
                                <div class="lft-activity-content">
                                    <span class="lft-activity-title">بوالص توصيل</span>
                                    <span class="lft-activity-time">{{ number_format($stats['today_delivery_policies'] ?? 0) }}</span>
                                </div>
                            </div>
                            <div class="lft-activity-item">
                                <div class="lft-activity-dot green"></div>
                                <div class="lft-activity-content">
                                    <span class="lft-activity-title">فواتير صادرة</span>
                                    <span class="lft-activity-time">{{ number_format($stats['today_invoices'] ?? 0) }}</span>
                                </div>
                            </div>
                            <div class="lft-activity-item">
                                <div class="lft-activity-dot orange"></div>
                                <div class="lft-activity-content">
                                    <span class="lft-activity-title">شيكات مستحقة خلال 3 أيام</span>
                                    <span class="lft-activity-time">{{ number_format($stats['checks_due_within_3_days'] ?? 0) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    @else
        <div class="row justify-content-center my-8">
            <div class="col-xl-8 col-lg-10">
                <div class="lft-card text-center p-8">
                    <div class="d-inline-flex align-items-center justify-content-center w-60px h-60px rounded-circle bg-light-primary text-primary mb-4 mx-auto" style="font-size: 24px;" aria-hidden="true">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3 class="font-weight-bolder mb-2">مساحة عمل إدارة النقل والحاويات</h3>
                    <p class="lft-text-secondary max-w-500px mx-auto mb-6">
                        أهلاً بك في نظام Leader for Trans. يمكنك متابعة العمليات المصرح لك بها من خلال القائمة الجانبية.
                    </p>
                    <div class="d-flex justify-content-center gap-3">
                        @if($canBookings)
                            <a href="{{ route('bookings.index') }}" class="btn btn-primary px-5">إدارة الحجوزات</a>
                        @endif
                        @if($canContainers)
                            <a href="{{ route('containers.index') }}" class="btn btn-secondary px-5">إدارة الحاويات</a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif

</div>
@endsection

@push("js")
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof ApexCharts === 'undefined') {
        console.warn('ApexCharts not found. Waiting for scripts bundle.');
        return;
    }

    var palette = function (dark) {
        return {
            grid: dark ? '#24354e' : '#e2e8f0',
            axis: dark ? '#b4c3d8' : '#475569',
            strong: dark ? '#f1f5f9' : '#0f172a',
            surface: dark ? '#14243c' : '#ffffff',
            tooltip: dark ? 'dark' : 'light'
        };
    };
    var p = palette(document.documentElement.getAttribute('data-theme') === 'dark');

    var areaOptions = {
        series: [{ name: 'الحجوزات الجديدة', data: {!! json_encode($bookingsChart['data'] ?? []) !!} }],
        chart: { type: 'area', height: 290, toolbar: { show: false }, fontFamily: 'Cairo, sans-serif', background: 'transparent' },
        colors: ['#06b6d4'],
        dataLabels: { enabled: false },
        stroke: { curve: 'smooth', width: 3 },
        fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.05, stops: [0, 95, 100] } },
        grid: { borderColor: p.grid, strokeDashArray: 4 },
        xaxis: {
            categories: {!! json_encode($bookingsChart['labels'] ?? []) !!},
            labels: { style: { colors: p.axis, fontSize: '12px' } },
            axisBorder: { show: false },
            axisTicks: { show: false }
        },
        yaxis: { labels: { style: { colors: p.axis, fontSize: '12px' }, formatter: function (v) { return Math.round(v); } } },
        legend: { show: false },
        noData: { text: 'لا توجد بيانات', style: { color: p.axis } },
        tooltip: { theme: p.tooltip, y: { formatter: function (val) { return val + ' حجز'; } } }
    };

    var areaChartEl = document.querySelector('#lftBookingsAreaChart');
    var areaChart;
    if (areaChartEl) {
        areaChart = new ApexCharts(areaChartEl, areaOptions);
        areaChart.render();
    }

    var stageValues = {!! json_encode(array_values($containerStages)) !!};
    var stageTotal = {{ (int) $totalStagesCount }};

    var donutOptions = {
        series: stageValues,
        labels: {!! json_encode(array_values(array_column($stageLegend, 'label')), JSON_UNESCAPED_UNICODE) !!},
        chart: { type: 'donut', height: 240, fontFamily: 'Cairo, sans-serif', background: 'transparent' },
        colors: {!! json_encode(array_values(array_column($stageLegend, 'color'))) !!},
        plotOptions: {
            pie: {
                donut: {
                    size: '72%',
                    labels: {
                        show: true,
                        name: { show: true, fontSize: '12px', color: p.axis, offsetY: -8 },
                        value: { show: true, fontSize: '22px', fontWeight: 700, color: p.strong, offsetY: 8 },
                        total: {
                            show: true,
                            label: 'إجمالي الحاويات',
                            color: p.axis,
                            formatter: function () { return stageTotal.toLocaleString('en-US'); }
                        }
                    }
                }
            }
        },
        dataLabels: { enabled: false },
        legend: { show: false },
        stroke: { colors: [p.surface], width: 2 },
        noData: { text: 'لا توجد بيانات', style: { color: p.axis } },
        tooltip: { theme: p.tooltip }
    };

    var donutChartEl = document.querySelector('#lftStagesDonutChart');
    var donutChart;
    if (donutChartEl) {
        donutChart = new ApexCharts(donutChartEl, donutOptions);
        donutChart.render();
    }

    window.addEventListener('lftThemeChanged', function (e) {
        var t = palette(e.detail.theme === 'dark');
        if (areaChart) {
            areaChart.updateOptions({
                grid: { borderColor: t.grid },
                xaxis: { labels: { style: { colors: t.axis } } },
                yaxis: { labels: { style: { colors: t.axis } } },
                tooltip: { theme: t.tooltip }
            });
        }
        if (donutChart) {
            donutChart.updateOptions({
                stroke: { colors: [t.surface] },
                tooltip: { theme: t.tooltip },
                plotOptions: { pie: { donut: { labels: {
                    name: { color: t.axis },
                    value: { color: t.strong },
                    total: { color: t.axis }
                } } } }
            });
        }
    });
});
</script>
@endpush
