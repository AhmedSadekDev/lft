@extends("layouts.admin")
@section("content")
<div class="container-fluid">
    @include("layouts.includes.breadcrumb", [ 'page' => 'لوحة التحكم' ])

    <!-- إحصائيات سريعة -->
    <div class="row mb-6">
        <!-- الحجوزات -->
        <div class="col-lg-3 col-md-6 col-sm-6 mb-4">
            <div class="card card-custom shadow-sm border-left-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted font-weight-bold mb-2">إجمالي الحجوزات</h6>
                            <h2 class="font-weight-bolder text-primary mb-0">{{ number_format($stats['total_bookings']) }}</h2>
                        </div>
                        <div class="icon-circle bg-primary text-white">
                            <i class="fas fa-clipboard-list fa-2x"></i>
                        </div>
                    </div>
                    <div class="mt-3">
                        <small class="text-muted">
                            <i class="fas fa-calendar-day mr-1"></i>
                            اليوم: {{ $stats['today_bookings'] }} |
                            هذا الأسبوع: {{ $stats['week_bookings'] }}
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <!-- الحاويات -->
        <div class="col-lg-3 col-md-6 col-sm-6 mb-4">
            <div class="card card-custom shadow-sm border-left-info">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted font-weight-bold mb-2">إجمالي الحاويات</h6>
                            <h2 class="font-weight-bolder text-info mb-0">{{ number_format($stats['total_containers']) }}</h2>
                        </div>
                        <div class="icon-circle bg-info text-white">
                            <i class="fas fa-box fa-2x"></i>
                        </div>
                    </div>
                    <div class="mt-3">
                        <small class="text-muted">
                            <i class="fas fa-calendar-day mr-1"></i>
                            اليوم: {{ $stats['today_containers'] }}
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <!-- المندوبين -->
        <div class="col-lg-3 col-md-6 col-sm-6 mb-4">
            <div class="card card-custom shadow-sm border-left-success">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted font-weight-bold mb-2">المندوبين</h6>
                            <h2 class="font-weight-bolder text-success mb-0">{{ number_format($stats['total_agents']) }}</h2>
                        </div>
                        <div class="icon-circle bg-success text-white">
                            <i class="fas fa-users fa-2x"></i>
                        </div>
                    </div>
                    <div class="mt-3">
                        <small class="text-muted">
                            <i class="fas fa-user-shield mr-1"></i>
                            المندوبين الرئيسيين: {{ $stats['total_superagents'] }}
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <!-- الخزنة -->
        <div class="col-lg-3 col-md-6 col-sm-6 mb-4">
            <div class="card card-custom shadow-sm border-left-warning">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted font-weight-bold mb-2">رصيد الخزنة</h6>
                            <h2 class="font-weight-bolder text-warning mb-0">{{ number_format($stats['vault_amount'], 2) }} ج.م</h2>
                        </div>
                        <div class="icon-circle bg-warning text-white">
                            <i class="fas fa-wallet fa-2x"></i>
                        </div>
                    </div>
                    <div class="mt-3">
                        <small class="text-muted">
                            <i class="fas fa-chart-line mr-1"></i>
                            الصافي: {{ number_format($stats['month_income'] - $stats['month_expenses'], 2) }} ج.م
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- إحصائيات إضافية -->
    <div class="row mb-6">
        <div class="col-lg-3 col-md-6 col-sm-6 mb-4">
            <div class="card card-custom shadow-sm">
                <div class="card-body text-center">
                    <i class="fas fa-building fa-3x text-primary mb-3"></i>
                    <h4 class="font-weight-bolder">{{ number_format($stats['total_companies']) }}</h4>
                    <p class="text-muted mb-0">الشركات</p>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 col-sm-6 mb-4">
            <div class="card card-custom shadow-sm">
                <div class="card-body text-center">
                    <i class="fas fa-car fa-3x text-info mb-3"></i>
                    <h4 class="font-weight-bolder">{{ number_format($stats['total_cars']) }}</h4>
                    <p class="text-muted mb-0">السيارات</p>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 col-sm-6 mb-4">
            <div class="card card-custom shadow-sm">
                <div class="card-body text-center">
                    <i class="fas fa-user-tie fa-3x text-success mb-3"></i>
                    <h4 class="font-weight-bolder">{{ number_format($stats['total_drivers']) }}</h4>
                    <p class="text-muted mb-0">السائقين</p>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 col-sm-6 mb-4">
            <div class="card card-custom shadow-sm">
                <div class="card-body text-center">
                    <i class="fas fa-file-invoice fa-3x text-warning mb-3"></i>
                    <h4 class="font-weight-bolder">{{ number_format($stats['total_delivery_policies']) }}</h4>
                    <p class="text-muted mb-0">البوليصات</p>
                </div>
            </div>
        </div>
    </div>

    <!-- إحصائيات الفواتير -->
    <div class="row mb-6">
        <div class="col-lg-4 col-md-6 col-sm-6 mb-4">
            <div class="card card-custom shadow-sm border-left-danger">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted font-weight-bold mb-2">إجمالي الفواتير</h6>
                            <h2 class="font-weight-bolder text-danger mb-0">{{ number_format($stats['total_invoices']) }}</h2>
                        </div>
                        <div class="icon-circle bg-danger text-white">
                            <i class="fas fa-file-invoice-dollar fa-2x"></i>
                        </div>
                    </div>
                    <div class="mt-3">
                        <small class="text-muted">
                            <i class="fas fa-calendar-day mr-1"></i>
                            اليوم: {{ $stats['today_invoices'] }} |
                            هذا الشهر: {{ $stats['month_invoices'] }}
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- إحصائيات مالية -->
    <div class="row mb-6">
        <div class="col-lg-6 col-md-6 mb-4">
            <div class="card card-custom shadow-sm">
                <div class="card-header border-0 py-4">
                    <h3 class="card-label font-weight-bolder text-dark">
                        <i class="fas fa-chart-pie text-primary mr-2"></i>
                        الإحصائيات المالية - اليوم
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6 text-center">
                            <div class="p-3 bg-danger-light rounded">
                                <i class="fas fa-arrow-down fa-2x text-danger mb-2"></i>
                                <h4 class="font-weight-bolder text-danger">{{ number_format($stats['today_expenses'], 2) }} ج.م</h4>
                                <p class="text-muted mb-0">المصروفات</p>
                            </div>
                        </div>
                        <div class="col-6 text-center">
                            <div class="p-3 bg-success-light rounded">
                                <i class="fas fa-arrow-up fa-2x text-success mb-2"></i>
                                <h4 class="font-weight-bolder text-success">{{ number_format($stats['today_income'], 2) }} ج.م</h4>
                                <p class="text-muted mb-0">الواردات</p>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4 text-center">
                        <h5 class="font-weight-bold {{ ($stats['today_income'] - $stats['today_expenses']) >= 0 ? 'text-success' : 'text-danger' }}">
                            الصافي: {{ number_format($stats['today_income'] - $stats['today_expenses'], 2) }} ج.م
                        </h5>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6 col-md-6 mb-4">
            <div class="card card-custom shadow-sm">
                <div class="card-header border-0 py-4">
                    <h3 class="card-label font-weight-bolder text-dark">
                        <i class="fas fa-chart-bar text-primary mr-2"></i>
                        الإحصائيات المالية - هذا الشهر
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6 text-center">
                            <div class="p-3 bg-danger-light rounded">
                                <i class="fas fa-arrow-down fa-2x text-danger mb-2"></i>
                                <h4 class="font-weight-bolder text-danger">{{ number_format($stats['month_expenses'], 2) }} ج.م</h4>
                                <p class="text-muted mb-0">المصروفات</p>
                            </div>
                        </div>
                        <div class="col-6 text-center">
                            <div class="p-3 bg-success-light rounded">
                                <i class="fas fa-arrow-up fa-2x text-success mb-2"></i>
                                <h4 class="font-weight-bolder text-success">{{ number_format($stats['month_income'], 2) }} ج.م</h4>
                                <p class="text-muted mb-0">الواردات</p>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4 text-center">
                        <h5 class="font-weight-bold {{ ($stats['month_income'] - $stats['month_expenses']) >= 0 ? 'text-success' : 'text-danger' }}">
                            الصافي: {{ number_format($stats['month_income'] - $stats['month_expenses'], 2) }} ج.م
                        </h5>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- الرسوم البيانية -->
    <div class="row mb-6">
        <!-- رسم بياني للحجوزات -->
        <div class="col-lg-6 mb-4">
            <div class="card card-custom shadow-sm">
                <div class="card-header border-0 py-4">
                    <h3 class="card-label font-weight-bolder text-dark">
                        <i class="fas fa-chart-line text-primary mr-2"></i>
                        الحجوزات حسب الشهر (آخر 6 أشهر)
                    </h3>
                </div>
                <div class="card-body">
                    <canvas id="bookingsChart" height="100"></canvas>
                </div>
            </div>
        </div>

        <!-- رسم بياني للمصروفات والواردات -->
        <div class="col-lg-6 mb-4">
            <div class="card card-custom shadow-sm">
                <div class="card-header border-0 py-4">
                    <h3 class="card-label font-weight-bolder text-dark">
                        <i class="fas fa-chart-area text-primary mr-2"></i>
                        المصروفات والواردات (آخر 6 أشهر)
                    </h3>
                </div>
                <div class="card-body">
                    <canvas id="financialChart" height="100"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('css')
<style>
    .border-left-primary {
        border-left: 4px solid #007bff !important;
    }
    .border-left-info {
        border-left: 4px solid #17a2b8 !important;
    }
    .border-left-success {
        border-left: 4px solid #28a745 !important;
    }
    .border-left-warning {
        border-left: 4px solid #ffc107 !important;
    }
    .border-left-danger {
        border-left: 4px solid #dc3545 !important;
    }
    .icon-circle {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .bg-danger-light {
        background-color: #f8d7da;
    }
    .bg-success-light {
        background-color: #d4edda;
    }
    .card-custom {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .card-custom:hover {
        transform: translateY(-5px);
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
    }
</style>
@endpush

@push('js')
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
    // رسم بياني للحجوزات
    const bookingsCtx = document.getElementById('bookingsChart').getContext('2d');
    const bookingsChart = new Chart(bookingsCtx, {
        type: 'line',
        data: {
            labels: @json($bookingsChart['labels']),
            datasets: [{
                label: 'عدد الحجوزات',
                data: @json($bookingsChart['data']),
                borderColor: '#007bff',
                backgroundColor: 'rgba(0, 123, 255, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointRadius: 5,
                pointHoverRadius: 7
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });

    // رسم بياني للمصروفات والواردات
    const financialCtx = document.getElementById('financialChart').getContext('2d');
    const financialChart = new Chart(financialCtx, {
        type: 'bar',
        data: {
            labels: @json($financialChart['labels']),
            datasets: [
                {
                    label: 'المصروفات',
                    data: @json($financialChart['expenses']),
                    backgroundColor: 'rgba(220, 53, 69, 0.8)',
                    borderColor: '#dc3545',
                    borderWidth: 2
                },
                {
                    label: 'الواردات',
                    data: @json($financialChart['income']),
                    backgroundColor: 'rgba(40, 167, 69, 0.8)',
                    borderColor: '#28a745',
                    borderWidth: 2
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
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
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
                y: {
                    beginAtZero: true,
                    ticks: {
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
</script>
@endpush
