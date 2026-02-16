@extends("layouts.admin")

@section("content")
<div class="container-fluid">

    @include("layouts.includes.breadcrumb", [
        'page' => 'كشف حساب - ' . $company->name
    ])

    <div class="card shadow-sm border-0">

        <!-- Header -->
        <div class="card-header text-white d-flex justify-content-between align-items-center" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
            <h4 class="mb-0">
                <i class="fas fa-file-invoice mr-2"></i>
                كشف حساب - {{ $company->name }}
            </h4>

            <div>
                <a href="{{ route('accounts.statement.export.excel', ['companyId'=>$company->id,'from'=>$fromDate,'to'=>$toDate]) }}"
                   class="btn btn-light btn-sm mr-2">
                    <i class="fas fa-file-excel text-success"></i> Excel
                </a>

                <a href="{{ route('accounts.statement.export.pdf', ['companyId'=>$company->id,'from'=>$fromDate,'to'=>$toDate]) }}"
                   class="btn btn-light btn-sm mr-2">
                    <i class="fas fa-file-pdf text-danger"></i> PDF
                </a>

                <a href="{{ route('accounts.payment',$company->id) }}"
                   class="btn btn-light btn-sm">
                    <i class="fas fa-money-bill-wave text-success"></i> سداد
                </a>
            </div>
        </div>

        <div class="card-body">

            <!-- معلومات الشركة + الملخص -->
            <div class="row mb-4">

                <div class="col-md-6">
                    <div class="card border shadow-sm h-100">
                        <div class="card-body">
                            <h5 class="text-primary mb-3">
                                <i class="fas fa-building"></i> معلومات الشركة
                            </h5>
                            <p><strong>الاسم:</strong> {{ $company->name }}</p>
                            <p><strong>البريد:</strong> {{ $company->email ?? '-' }}</p>
                            <p><strong>الهاتف:</strong> {{ $company->phone ?? '-' }}</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card bg-light border shadow-sm h-100">
                        <div class="card-body">
                            <h5 class="text-primary mb-3">
                                <i class="fas fa-chart-line"></i> ملخص الحساب
                            </h5>

                            <p>الرصيد الافتتاحي:
                                <span class="float-left font-weight-bold">
                                    {{ number_format($company->opening_balance ?? 0,2) }} ج.م
                                </span>
                            </p>

                            <p>الرصيد المرحل:
                                <span class="float-left font-weight-bold">
                                    {{ number_format($carriedForwardBalance,2) }} ج.م
                                </span>
                            </p>

                            <p>إجمالي الفواتير:
                                <span class="float-left text-danger font-weight-bold">
                                    {{ number_format($totalInvoices,2) }} ج.م
                                </span>
                            </p>

                            <p>إجمالي السداد:
                                <span class="float-left text-success font-weight-bold">
                                    {{ number_format($totalPayments,2) }} ج.م
                                </span>
                            </p>

                            <hr>

                            <h5>
                                الرصيد النهائي:
                                <span class="{{ $finalBalance >= 0 ? 'text-danger' : 'text-success' }}">
                                    {{ number_format(abs($finalBalance),2) }} ج.م
                                </span>
                            </h5>
                        </div>
                    </div>
                </div>
            </div>

            <!-- فلتر -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#filterModal">
                    <i class="fas fa-filter"></i> فلتر
                </button>

                <strong>
                    من {{ $fromDate }} إلى {{ $toDate }}
                </strong>
            </div>

            <!-- جدول -->
            <div class="table-responsive">
                <table class="table table-bordered table-hover table-striped text-center">
                    <thead style="background:#1f2a44; color:#fff;">
                        <tr>
                            <th>التاريخ</th>
                            <th>رقم الطلب</th>
                            <th>نوع العملية</th>
                            <th>الإجمالي</th>
                            <th>تم دفع</th>
                            <th>مدين</th>
                            <th>دائن</th>
                            <th>ملاحظات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $transaction)

                            @php
                                $date = \Carbon\Carbon::parse($transaction['date']);
                            @endphp

                            <tr>
                                <td>
                                    {{ $date->format('Y-m-d') }}
                                    <br>
                                    <small class="text-muted">
                                        {{ $date->format('H:i') }}
                                    </small>
                                </td>

                                <td>{{ $transaction['booking_number'] ?? '-' }}</td>

                                <td>
                                    @if($transaction['type'] == 'invoice')
                                        <span class="badge badge-danger">فاتورة</span>
                                    @elseif($transaction['type'] == 'payment')
                                        <span class="badge badge-success">سداد</span>
                                    @else
                                        <span class="badge badge-secondary">
                                            {{ $transaction['type_label'] ?? '-' }}
                                        </span>
                                    @endif
                                </td>

                                <td class="text-danger font-weight-bold">
                                    {{ number_format($transaction['total'] ?? 0,2) }}
                                </td>

                                <td class="text-success font-weight-bold">
                                    {{ number_format($transaction['paid'] ?? 0,2) }}
                                </td>

                                <td>
                                    {{ number_format($transaction['current_debit'] ?? 0,2) }}
                                </td>

                                <td>
                                    {{ number_format($transaction['current_credit'] ?? 0,2) }}
                                </td>

                                <td>
                                    {{ $transaction['notes'] ?? '-' }}
                                </td>
                            </tr>

                        @empty
                            <tr>
                                <td colspan="8" class="py-4 text-muted">
                                    لا توجد بيانات
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</div>


<!-- Modal الفلتر -->
<div class="modal fade" id="filterModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="GET" action="{{ route('accounts.statement',$company->id) }}">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">فلترة حسب التاريخ</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <label>من</label>
                    <input type="date" name="from" value="{{ $fromDate }}" class="form-control mb-3">

                    <label>إلى</label>
                    <input type="date" name="to" value="{{ $toDate }}" class="form-control">
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary">تطبيق</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .card-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        padding: 1rem 1.5rem;
    }
    .card-header h4 {
        color: white !important;
        font-weight: bold;
    }
    .card-header .btn-light {
        background-color: rgba(255, 255, 255, 0.95) !important;
        border: 1px solid rgba(255, 255, 255, 0.3) !important;
        color: #333 !important;
    }
    .card-header .btn-light:hover {
        background-color: #fff !important;
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }
    /* تعطيل DataTable */
    .dataTables_wrapper {
        display: none !important;
    }
    .dataTables_filter,
    .dataTables_length,
    .dataTables_info,
    .dataTables_paginate {
        display: none !important;
    }
</style>

@push('js')
<script>
    $(document).ready(function() {
        // منع تهيئة DataTable على أي جدول في هذه الصفحة
        if ($.fn.DataTable) {
            // تعطيل DataTable على جميع الجداول في هذه الصفحة
            $('.table').each(function() {
                if ($.fn.DataTable.isDataTable(this)) {
                    $(this).DataTable().destroy();
                }
            });
        }
    });
</script>
@endpush

@endsection
