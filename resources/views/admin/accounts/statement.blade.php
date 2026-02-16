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
            <h5 class="font-weight-bold mt-4 mb-3">الحساب في الفترة من {{ $fromDate }} الى {{ $toDate }}</h5>
            <div class="table-responsive">
                <table class="table table-bordered table-hover" style="font-size: 12px;">
                    <thead class="thead-dark">
                        <tr class="text-center">
                            <th rowspan="2" style="vertical-align: middle;">التاريخ</th>
                            <th colspan="2" style="border-bottom: 2px solid #fff;">حساب سابق</th>
                            <th rowspan="2" style="vertical-align: middle;">رقم الطلب</th>
                            <th rowspan="2" style="vertical-align: middle;">نوع العملية</th>
                            <th rowspan="2" style="vertical-align: middle;">خصم على الفاتورة</th>
                            <th rowspan="2" style="vertical-align: middle;">الضريبة</th>
                            <th rowspan="2" style="vertical-align: middle;">بيان ملحق</th>
                            <th rowspan="2" style="vertical-align: middle;">فاتورة النقل</th>
                            <th rowspan="2" style="vertical-align: middle;">القيمة الاجمالية</th>
                            <th rowspan="2" style="vertical-align: middle;">تم دفع</th>
                            <th rowspan="2" style="vertical-align: middle;">ملاحظات</th>
                            <th colspan="2" style="border-bottom: 2px solid #fff;">الحساب الحالي</th>
                        </tr>
                        <tr class="text-center">
                            <th>مدين</th>
                            <th>دائن</th>
                            <th>مدين</th>
                            <th>دائن</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($transactions as $index => $transaction)
                            @php
                                $date = $transaction['date'] instanceof \Carbon\Carbon ? $transaction['date'] : \Carbon\Carbon::parse($transaction['date']);
                                $hasPaymentDetails = isset($transaction['payment_details']) && $transaction['payment_details']->count() > 0;
                            @endphp
                            <tr class="{{ $hasPaymentDetails ? 'payment-row cursor-pointer' : '' }}"
                                @if($hasPaymentDetails)
                                    data-toggle="modal"
                                    data-target="#paymentDetailsModal{{ $index }}"
                                    style="cursor: pointer;"
                                    title="اضغط لعرض تفاصيل السداد"
                                @endif>
                                <td class="text-center">{{ $date->format('Y-m-d') }}<br><small>{{ $date->format('H:i') }}</small></td>
                                <td class="text-center">{{ $transaction['previous_debit'] > 0 ? number_format($transaction['previous_debit'], 2) : '-' }}</td>
                                <td class="text-center">{{ $transaction['previous_credit'] > 0 ? number_format($transaction['previous_credit'], 2) : '-' }}</td>
                                <td class="text-center">{{ $transaction['booking_number'] ?: '-' }}</td>
                                <td class="text-center">
                                    @if($hasPaymentDetails)
                                        <span class="badge badge-info">
                                            {{ $transaction['type_label'] }}
                                            @if($transaction['payment_count'] > 1)
                                                ({{ $transaction['payment_count'] }} فواتير)
                                            @endif
                                        </span>
                                    @else
                                        {{ $transaction['type_label'] }}
                                    @endif
                                </td>
                                <td class="text-center">{{ $transaction['discount'] > 0 ? number_format($transaction['discount'], 2) : '-' }}</td>
                                <td class="text-center">{{ $transaction['tax'] > 0 ? number_format($transaction['tax'], 2) : '-' }}</td>
                                <td class="text-center">{{ $transaction['attachment_statement'] ?: '-' }}</td>
                                <td class="text-center">{{ $transaction['transportation'] > 0 ? number_format($transaction['transportation'], 2) : '-' }}</td>
                                <td class="text-center">{{ $transaction['total'] > 0 ? number_format($transaction['total'], 2) : '-' }}</td>
                                <td class="text-center">
                                    @if($hasPaymentDetails)
                                        <span class="font-weight-bold text-success">
                                            {{ number_format($transaction['paid'], 2) }}
                                            @if($transaction['payment_count'] > 1)
                                                <i class="fas fa-info-circle ml-1" title="اضغط لعرض التفاصيل"></i>
                                            @endif
                                        </span>
                                    @else
                                        {{ $transaction['paid'] > 0 ? number_format($transaction['paid'], 2) : '-' }}
                                    @endif
                                </td>
                                <td class="text-center">{{ $transaction['notes'] ?: '-' }}</td>
                                <td class="text-center {{ $transaction['current_debit'] > 0 ? 'text-danger font-weight-bold' : '' }}">
                                    {{ $transaction['current_debit'] > 0 ? number_format($transaction['current_debit'], 2) : '-' }}
                                </td>
                                <td class="text-center {{ $transaction['current_credit'] > 0 ? 'text-success font-weight-bold' : '' }}">
                                    @if($hasPaymentDetails)
                                        <span class="font-weight-bold text-success">
                                            {{ number_format($transaction['current_credit'], 2) }}
                                            @if($transaction['payment_count'] > 1)
                                                <i class="fas fa-info-circle ml-1" title="اضغط لعرض التفاصيل"></i>
                                            @endif
                                        </span>
                                    @else
                                        {{ $transaction['current_credit'] > 0 ? number_format($transaction['current_credit'], 2) : '-' }}
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
                                                            @foreach($transaction['payment_details'] as $detailIndex => $detail)
                                                                <tr>
                                                                    <td>{{ $detailIndex + 1 }}</td>
                                                                    <td>{{ $detail['invoice_number'] }}</td>
                                                                    <td>{{ $detail['booking_number'] }}</td>
                                                                    <td class="font-weight-bold text-success">{{ number_format($detail['value'], 2) }} ج.م</td>
                                                                    <td>
                                                                        @if($detail['payment_type'] == 'check')
                                                                            <span class="badge badge-warning">شيك</span>
                                                                        @else
                                                                            <span class="badge badge-primary">تحويل بنكي</span>
                                                                        @endif
                                                                    </td>
                                                                    <td>{{ $detail['bank_name'] ?: '-' }}</td>
                                                                    <td>{{ $detail['notes'] ?: '-' }}</td>
                                                                </tr>
                                                            @endforeach
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
        background-color: #f0f8ff !important;
        transition: background-color 0.2s;
    }
    .cursor-pointer {
        cursor: pointer;
    }
</style>
@endsection
