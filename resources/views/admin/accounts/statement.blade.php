@extends("layouts.admin")

@section("content")
<div class="container-fluid">

    @include("layouts.includes.breadcrumb", [
        'page' => 'كشف حساب - ' . $company->name
    ])

    <div class="card shadow-sm border-0">

        <!-- Header -->
        <div class="card-header bg-gradient-primary text-white d-flex justify-content-between align-items-center">
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
                   class="btn btn-success btn-sm">
                    <i class="fas fa-money-bill-wave"></i> سداد
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
                    <thead class="thead-dark">
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
                        @forelse($transactions as $index => $transaction)
                            @php
                                $date = $transaction['date'] instanceof \Carbon\Carbon ? $transaction['date'] : \Carbon\Carbon::parse($transaction['date']);
                                $paymentDetails = $transaction['payment_details'] ?? [];
                                $hasPaymentDetails = isset($transaction['payment_details']) && is_array($paymentDetails) && count($paymentDetails) > 0;
                            @endphp

                            <tr class="{{ $hasPaymentDetails ? 'payment-row cursor-pointer' : '' }}"
                                @if($hasPaymentDetails)
                                    data-toggle="modal"
                                    data-target="#paymentDetailsModal{{ $index }}"
                                    style="cursor: pointer;"
                                    title="اضغط لعرض تفاصيل السداد"
                                @endif>
                                <td>
                                    {{ $date->format('Y-m-d') }}
                                    <br>
                                    <small class="text-muted">
                                        {{ $date->format('H:i') }}
                                    </small>
                                </td>

                                <td>{{ $transaction['booking_number'] ?? '-' }}</td>

                                <td>
                                    @if($hasPaymentDetails)
                                        <span class="badge badge-info">
                                            <i class="fas fa-money-bill-wave mr-1"></i>
                                            {{ $transaction['type_label'] ?? 'سداد' }}
                                            @if(isset($transaction['payment_count']) && $transaction['payment_count'] > 1)
                                                ({{ $transaction['payment_count'] }} فواتير)
                                            @endif
                                        </span>
                                    @elseif($transaction['type'] == 'invoice')
                                        <span class="badge badge-danger">
                                            <i class="fas fa-file-invoice mr-1"></i>فاتورة
                                        </span>
                                    @elseif($transaction['type'] == 'payment')
                                        <span class="badge badge-success">
                                            <i class="fas fa-money-bill mr-1"></i>سداد
                                        </span>
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
                                    @if($hasPaymentDetails)
                                        <i class="fas fa-check-circle mr-1"></i>
                                        {{ number_format($transaction['paid'] ?? 0,2) }}
                                        @if(isset($transaction['payment_count']) && $transaction['payment_count'] > 1)
                                            <i class="fas fa-info-circle ml-1" title="اضغط لعرض التفاصيل"></i>
                                        @endif
                                    @else
                                        {{ number_format($transaction['paid'] ?? 0,2) }}
                                    @endif
                                </td>

                                <td>
                                    {{ number_format($transaction['current_debit'] ?? 0,2) }}
                                </td>

                                <td>
                                    @if($hasPaymentDetails)
                                        <i class="fas fa-arrow-down mr-1"></i>
                                        {{ number_format($transaction['current_credit'] ?? 0,2) }}
                                    @else
                                        {{ number_format($transaction['current_credit'] ?? 0,2) }}
                                    @endif
                                </td>

                                <td>
                                    @if(!empty($transaction['notes']))
                                        <span title="{{ $transaction['notes'] }}">
                                            {{ mb_strlen($transaction['notes']) > 30 ? mb_substr($transaction['notes'], 0, 30) . '...' : $transaction['notes'] }}
                                        </span>
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>

                            @if($hasPaymentDetails)
                                <!-- Modal تفاصيل السداد -->
                                <div class="modal fade" id="paymentDetailsModal{{ $index }}" tabindex="-1" role="dialog">
                                    <div class="modal-dialog modal-lg" role="document">
                                        <div class="modal-content">
                                            <div class="modal-header bg-success text-white">
                                                <h5 class="modal-title font-weight-bold">
                                                    <i class="fas fa-money-bill mr-2"></i>
                                                    تفاصيل السداد - {{ number_format($transaction['paid'] ?? 0, 2) }} ج.م
                                                </h5>
                                                <button type="button" class="close text-white" data-dismiss="modal">
                                                    <span>&times;</span>
                                                </button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="table-responsive">
                                                    <table class="table table-bordered table-hover">
                                                        <thead class="thead-light">
                                                            <tr>
                                                                <th class="text-center">#</th>
                                                                <th class="text-center">رقم الفاتورة</th>
                                                                <th class="text-center">رقم الطلب</th>
                                                                <th class="text-center">المبلغ</th>
                                                                <th class="text-center">نوع السداد</th>
                                                                <th class="text-center">البنك</th>
                                                                <th class="text-center">ملاحظات</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @if($hasPaymentDetails && is_array($paymentDetails) && count($paymentDetails) > 0)
                                                                @foreach($paymentDetails as $detailIndex => $detail)
                                                                    <tr>
                                                                        <td class="text-center">{{ $detailIndex + 1 }}</td>
                                                                        <td class="text-center">
                                                                            <span class="badge badge-info">{{ $detail['invoice_number'] ?? '-' }}</span>
                                                                        </td>
                                                                        <td class="text-center">
                                                                            <span class="badge badge-secondary">{{ $detail['booking_number'] ?? '-' }}</span>
                                                                        </td>
                                                                        <td class="text-center font-weight-bold text-success" style="font-size: 1.1em;">
                                                                            {{ number_format($detail['value'] ?? 0, 2) }} ج.م
                                                                        </td>
                                                                        <td class="text-center">
                                                                            @if(($detail['payment_type'] ?? '') == 'check')
                                                                                <span class="badge badge-warning badge-pill">
                                                                                    <i class="fas fa-money-check mr-1"></i>شيك
                                                                                </span>
                                                                            @else
                                                                                <span class="badge badge-primary badge-pill">
                                                                                    <i class="fas fa-university mr-1"></i>تحويل بنكي
                                                                                </span>
                                                                            @endif
                                                                        </td>
                                                                        <td class="text-center">{{ $detail['bank_name'] ?? '-' }}</td>
                                                                        <td class="text-center">{{ $detail['notes'] ?? '-' }}</td>
                                                                    </tr>
                                                                @endforeach
                                                            @else
                                                                <tr>
                                                                    <td colspan="7" class="text-center text-muted py-4">
                                                                        <i class="fas fa-exclamation-triangle fa-2x mb-2"></i><br>
                                                                        لا توجد تفاصيل متاحة
                                                                    </td>
                                                                </tr>
                                                            @endif
                                                        </tbody>
                                                        <tfoot>
                                                            <tr class="table-success font-weight-bold">
                                                                <td colspan="3" class="text-right">الإجمالي:</td>
                                                                <td class="text-center text-success">{{ number_format($transaction['paid'] ?? 0, 2) }} ج.م</td>
                                                                <td colspan="3"></td>
                                                            </tr>
                                                        </tfoot>
                                                    </table>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">إغلاق</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif

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
    .payment-row:hover {
        background-color: #e8f5e9 !important;
        transition: background-color 0.2s;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .cursor-pointer {
        cursor: pointer;
    }
</style>

@push('js')
<script>
    $(document).ready(function() {
        // التأكد من أن الـ modal لا يفتح تلقائياً
        $('.modal').removeClass('show');
    });
</script>
@endpush

@endsection
