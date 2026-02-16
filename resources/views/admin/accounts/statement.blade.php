@extends("layouts.admin")

@section("content")
<div class="container-fluid">
    @include("layouts.includes.breadcrumb", [ 'page' => 'كشف حساب - ' . $company->name ])

    <!--begin::Card-->
    <div class="card card-custom shadow-sm">
        <div class="card-header border-0 py-4">
            <div class="card-title">
                <h3 class="card-label font-weight-bolder text-dark">
                    <i class="fas fa-file-invoice text-primary mr-2"></i>
                    كشف حساب - {{ $company->name }}
                </h3>
            </div>
            <div class="card-toolbar">
                <a href="{{ route('accounts.statement.export.excel', ['companyId' => $company->id, 'from' => $fromDate, 'to' => $toDate]) }}"
                   class="btn btn-primary font-weight-bold shadow-sm mr-2">
                    <i class="fas fa-file-excel"></i> تصدير Excel
                </a>
                <a href="{{ route('accounts.statement.export.pdf', ['companyId' => $company->id, 'from' => $fromDate, 'to' => $toDate]) }}"
                   class="btn btn-danger font-weight-bold shadow-sm mr-2">
                    <i class="fas fa-file-pdf"></i> تصدير PDF
                </a>
                <a href="{{ route('accounts.payment', $company->id) }}"
                   class="btn btn-success font-weight-bold shadow-sm">
                    <i class="fas fa-money-bill"></i> سداد
                </a>
            </div>
        </div>

        <!-- Modal الفلتر -->
        <div class="modal fade" id="filterModal" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title font-weight-bold">
                            <i class="fas fa-calendar-alt mr-2"></i>فلترة حسب التاريخ
                        </h5>
                        <button type="button" class="close text-white" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <form action="{{ route('accounts.statement', $company->id) }}" method="get">
                        <div class="modal-body">
                            <div class="form-group">
                                <label class="font-weight-bold">من تاريخ</label>
                                <input type="date" name="from" value="{{ $fromDate }}" class="form-control">
                            </div>
                            <div class="form-group">
                                <label class="font-weight-bold">إلى تاريخ</label>
                                <input type="date" name="to" value="{{ $toDate }}" class="form-control">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">إلغاء</button>
                            <button type="submit" class="btn btn-primary">تطبيق</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="card-body">
            <!-- معلومات الشركة -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <h5 class="font-weight-bold">معلومات الشركة</h5>
                    <p><strong>الاسم:</strong> {{ $company->name }}</p>
                    <p><strong>البريد:</strong> {{ $company->email }}</p>
                    <p><strong>الهاتف:</strong> {{ $company->phone }}</p>
                </div>
                <div class="col-md-6 text-right">
                    <h5 class="font-weight-bold">ملخص الحساب</h5>
                    @if($company->opening_balance && $company->opening_balance != 0)
                    <p><strong>الرصيد الافتتاحي:</strong>
                        <span class="text-{{ $company->opening_balance >= 0 ? 'danger' : 'success' }}">
                            {{ number_format($company->opening_balance, 2) }}
                        </span>
                    </p>
                    @endif
                    <p><strong>الرصيد المرحّل:</strong>
                        <span class="text-{{ $carriedForwardBalance >= 0 ? 'danger' : 'success' }}">
                            {{ number_format($carriedForwardBalance, 2) }}
                        </span>
                    </p>
                    <p><strong>إجمالي الفواتير:</strong>
                        <span class="text-danger">{{ number_format($totalInvoices, 2) }}</span>
                    </p>
                    <p><strong>إجمالي السداد:</strong>
                        <span class="text-success">{{ number_format($totalPayments, 2) }}</span>
                    </p>
                    <p><strong>الرصيد النهائي المستحق:</strong>
                        <span class="text-{{ $finalBalance >= 0 ? 'danger' : 'success' }} font-weight-bold">
                            {{ number_format($finalBalance, 2) }}
                        </span>
                    </p>
                </div>
            </div>

            <button type="button" class="btn btn-primary mb-3" data-toggle="modal" data-target="#filterModal">
                <i class="fas fa-filter"></i> فلتر
            </button>

            <!-- جدول كشف الحساب الموحد -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="font-weight-bold mb-0">
                    <i class="fas fa-calendar-alt text-primary mr-2"></i>
                    الحساب في الفترة من {{ $fromDate }} الى {{ $toDate }}
                </h5>
            </div>
            <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                <table class="table table-bordered table-hover table-striped" id="statementTable" style="font-size: 13px; margin-bottom: 0;">
                    <thead class="thead-dark" style="position: sticky; top: 0; z-index: 10;">
                        <tr class="text-center">
                            <th rowspan="2" style="vertical-align: middle; background-color: #343a40; color: #fff; min-width: 120px;">التاريخ</th>
                            <th colspan="2" style="border-bottom: 2px solid #fff; background-color: #343a40; color: #fff;">حساب سابق</th>
                            <th rowspan="2" style="vertical-align: middle; background-color: #343a40; color: #fff; min-width: 100px;">رقم الطلب</th>
                            <th rowspan="2" style="vertical-align: middle; background-color: #343a40; color: #fff; min-width: 120px;">نوع العملية</th>
                            <th rowspan="2" style="vertical-align: middle; background-color: #343a40; color: #fff; min-width: 100px;">خصم</th>
                            <th rowspan="2" style="vertical-align: middle; background-color: #343a40; color: #fff; min-width: 100px;">الضريبة</th>
                            <th rowspan="2" style="vertical-align: middle; background-color: #343a40; color: #fff; min-width: 100px;">بيان ملحق</th>
                            <th rowspan="2" style="vertical-align: middle; background-color: #343a40; color: #fff; min-width: 100px;">فاتورة النقل</th>
                            <th rowspan="2" style="vertical-align: middle; background-color: #343a40; color: #fff; min-width: 120px;">القيمة الاجمالية</th>
                            <th rowspan="2" style="vertical-align: middle; background-color: #343a40; color: #fff; min-width: 120px;">تم دفع</th>
                            <th rowspan="2" style="vertical-align: middle; background-color: #343a40; color: #fff; min-width: 150px;">ملاحظات</th>
                            <th colspan="2" style="border-bottom: 2px solid #fff; background-color: #343a40; color: #fff;">الحساب الحالي</th>
                        </tr>
                        <tr class="text-center" style="background-color: #343a40; color: #fff;">
                            <th style="background-color: #495057; min-width: 100px;">مدين</th>
                            <th style="background-color: #495057; min-width: 100px;">دائن</th>
                            <th style="background-color: #495057; min-width: 100px;">مدين</th>
                            <th style="background-color: #495057; min-width: 100px;">دائن</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($transactions as $index => $transaction)
                            @php
                                $date = $transaction['date'] instanceof \Carbon\Carbon ? $transaction['date'] : \Carbon\Carbon::parse($transaction['date']);
                                $paymentDetails = $transaction['payment_details'] ?? collect();
                                $hasPaymentDetails = isset($transaction['payment_details']) && (
                                    (is_array($paymentDetails) && count($paymentDetails) > 0) ||
                                    (is_object($paymentDetails) && method_exists($paymentDetails, 'count') && $paymentDetails->count() > 0)
                                );
                            @endphp
                            <tr class="{{ $hasPaymentDetails ? 'payment-row cursor-pointer' : '' }}"
                                @if($hasPaymentDetails)
                                    data-toggle="modal"
                                    data-target="#paymentDetailsModal{{ $index }}"
                                    style="cursor: pointer;"
                                    title="اضغط لعرض تفاصيل السداد"
                                @endif>
                                <td class="text-center" style="white-space: nowrap;">
                                    <strong>{{ $date->format('Y-m-d') }}</strong><br>
                                    <small class="text-muted">{{ $date->format('H:i') }}</small>
                                </td>
                                <td class="text-center">
                                    @if($transaction['previous_debit'] > 0)
                                        <span class="text-danger font-weight-bold">{{ number_format($transaction['previous_debit'], 2) }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($transaction['previous_credit'] > 0)
                                        <span class="text-success font-weight-bold">{{ number_format($transaction['previous_credit'], 2) }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($transaction['booking_number'])
                                        <span class="badge badge-secondary">{{ $transaction['booking_number'] }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($hasPaymentDetails)
                                        <span class="badge badge-info badge-pill">
                                            <i class="fas fa-money-bill-wave mr-1"></i>
                                            {{ $transaction['type_label'] }}
                                            @if($transaction['payment_count'] > 1)
                                                ({{ $transaction['payment_count'] }} فواتير)
                                            @endif
                                        </span>
                                    @elseif($transaction['type'] == 'invoice')
                                        <span class="badge badge-danger badge-pill">
                                            <i class="fas fa-file-invoice mr-1"></i>
                                            {{ $transaction['type_label'] }}
                                        </span>
                                    @elseif($transaction['type'] == 'opening_balance')
                                        <span class="badge badge-warning badge-pill">
                                            <i class="fas fa-wallet mr-1"></i>
                                            {{ $transaction['type_label'] }}
                                        </span>
                                    @elseif($transaction['type'] == 'carried_forward')
                                        <span class="badge badge-secondary badge-pill">
                                            <i class="fas fa-arrow-right mr-1"></i>
                                            {{ $transaction['type_label'] }}
                                        </span>
                                    @else
                                        <span class="badge badge-light">{{ $transaction['type_label'] }}</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($transaction['discount'] > 0)
                                        <span class="text-warning font-weight-bold">{{ number_format($transaction['discount'], 2) }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($transaction['tax'] > 0)
                                        <span class="text-info font-weight-bold">{{ number_format($transaction['tax'], 2) }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($transaction['attachment_statement'])
                                        <span class="text-primary">{{ $transaction['attachment_statement'] }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($transaction['transportation'] > 0)
                                        <span class="font-weight-bold">{{ number_format($transaction['transportation'], 2) }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($transaction['total'] > 0)
                                        <span class="text-danger font-weight-bold" style="font-size: 1.1em;">{{ number_format($transaction['total'], 2) }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($hasPaymentDetails)
                                        <span class="font-weight-bold text-success" style="font-size: 1.1em;">
                                            <i class="fas fa-check-circle mr-1"></i>
                                            {{ number_format($transaction['paid'], 2) }}
                                            @if($transaction['payment_count'] > 1)
                                                <i class="fas fa-info-circle ml-1" title="اضغط لعرض التفاصيل"></i>
                                            @endif
                                        </span>
                                    @elseif($transaction['paid'] > 0)
                                        <span class="text-success font-weight-bold">{{ number_format($transaction['paid'], 2) }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-center" style="max-width: 150px; overflow: hidden; text-overflow: ellipsis;">
                                    @if($transaction['notes'])
                                        <span class="text-muted" title="{{ $transaction['notes'] }}">
                                            {{ mb_strlen($transaction['notes']) > 30 ? mb_substr($transaction['notes'], 0, 30) . '...' : $transaction['notes'] }}
                                        </span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($transaction['current_debit'] > 0)
                                        <span class="text-danger font-weight-bold" style="font-size: 1.1em;">{{ number_format($transaction['current_debit'], 2) }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($hasPaymentDetails)
                                        <span class="font-weight-bold text-success" style="font-size: 1.1em;">
                                            <i class="fas fa-arrow-down mr-1"></i>
                                            {{ number_format($transaction['current_credit'], 2) }}
                                            @if($transaction['payment_count'] > 1)
                                                <i class="fas fa-info-circle ml-1" title="اضغط لعرض التفاصيل"></i>
                                            @endif
                                        </span>
                                    @elseif($transaction['current_credit'] > 0)
                                        <span class="text-success font-weight-bold" style="font-size: 1.1em;">{{ number_format($transaction['current_credit'], 2) }}</span>
                                    @else
                                        <span class="text-muted">-</span>
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
                                                    تفاصيل السداد - {{ number_format($transaction['paid'], 2) }} ج.م
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
                                                                <th>#</th>
                                                                <th>رقم الفاتورة</th>
                                                                <th>رقم الطلب</th>
                                                                <th>المبلغ</th>
                                                                <th>نوع السداد</th>
                                                                <th>البنك</th>
                                                                <th>ملاحظات</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @if($hasPaymentDetails)
                                                                @php
                                                                    $details = is_array($paymentDetails) ? $paymentDetails : $paymentDetails->toArray();
                                                                @endphp
                                                                @foreach($details as $detailIndex => $detail)
                                                                    <tr>
                                                                        <td>{{ $detailIndex + 1 }}</td>
                                                                        <td>{{ $detail['invoice_number'] ?? '-' }}</td>
                                                                        <td>{{ $detail['booking_number'] ?? '-' }}</td>
                                                                        <td class="font-weight-bold text-success">{{ number_format($detail['value'] ?? 0, 2) }} ج.م</td>
                                                                        <td>
                                                                            @if(($detail['payment_type'] ?? '') == 'check')
                                                                                <span class="badge badge-warning">شيك</span>
                                                                            @else
                                                                                <span class="badge badge-primary">تحويل بنكي</span>
                                                                            @endif
                                                                        </td>
                                                                        <td>{{ $detail['bank_name'] ?? '-' }}</td>
                                                                        <td>{{ $detail['notes'] ?? '-' }}</td>
                                                                    </tr>
                                                                @endforeach
                                                            @else
                                                                <tr>
                                                                    <td colspan="7" class="text-center text-muted">لا توجد تفاصيل متاحة</td>
                                                                </tr>
                                                            @endif
                                                        </tbody>
                                                        <tfoot>
                                                            <tr class="table-success font-weight-bold">
                                                                <td colspan="3" class="text-right">الإجمالي:</td>
                                                                <td class="text-success">{{ number_format($transaction['paid'], 2) }} ج.م</td>
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
                                <td colspan="15" class="text-center py-5">لا توجد حركات في هذه الفترة</td>
                            </tr>
                        @endforelse

                        <!-- صف الإجمالي النهائي -->
                        @if($transactions->count() > 0)
                            @php
                                $totalPreviousDebit = $transactions->sum('previous_debit');
                                $totalPreviousCredit = $transactions->sum('previous_credit');
                                $totalCurrentDebit = $transactions->sum('current_debit');
                                $totalCurrentCredit = $transactions->sum('current_credit');
                                // استخدام $finalBalance المحسوب بشكل صحيح من الكنترولر
                                // الرصيد النهائي = الرصيد المرحّل + إجمالي الفواتير - إجمالي المدفوعات
                                $finalRunningBalance = $finalBalance;
                            @endphp
                            <tr class="table-info font-weight-bold">
                                <td class="text-center" colspan="2">الحساب النهائي يوم {{ $toDate }}</td>
                                <td class="text-center">{{ number_format($totalPreviousDebit, 2) }}</td>
                                <td class="text-center">{{ number_format($totalPreviousCredit, 2) }}</td>
                                <td colspan="8"></td>
                                <td class="text-center">{{ number_format($totalCurrentDebit, 2) }}</td>
                                <td class="text-center">{{ number_format($totalCurrentCredit, 2) }}</td>
                            </tr>
                            <tr class="table-warning font-weight-bold">
                                <td class="text-center" colspan="13">الرصيد النهائي المستحق</td>
                                <td class="text-center {{ $finalRunningBalance >= 0 ? 'text-danger' : 'text-success' }}">
                                    {{ number_format(abs($finalRunningBalance), 2) }}
                                </td>
                                <td></td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
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
    #statementTable {
        direction: rtl;
    }
    #statementTable thead th {
        font-weight: 600;
        text-align: center;
        white-space: nowrap;
    }
    #statementTable tbody tr {
        transition: all 0.2s;
    }
    #statementTable tbody tr:hover:not(.table-info):not(.table-warning) {
        background-color: #f8f9fa !important;
    }
    #statementTable tbody td {
        text-align: center;
        vertical-align: middle;
        padding: 8px 4px;
    }
    .badge {
        font-size: 11px;
        padding: 5px 10px;
    }
    .table-info {
        background-color: #d1ecf1 !important;
    }
    .table-warning {
        background-color: #fff3cd !important;
    }
    .card-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    .card-header .card-title h3 {
        color: white;
    }
    .card-header .btn {
        background-color: rgba(255, 255, 255, 0.2);
        border: 1px solid rgba(255, 255, 255, 0.3);
        color: white;
    }
    .card-header .btn:hover {
        background-color: rgba(255, 255, 255, 0.3);
        color: white;
    }
</style>

@push('js')
<script>
    $(document).ready(function() {
        // إضافة scroll للجدول
        $('.table-responsive').on('scroll', function() {
            $(this).find('thead').css('transform', 'translateY(' + this.scrollTop + 'px)');
        });
    });
</script>
@endpush
@endsection
