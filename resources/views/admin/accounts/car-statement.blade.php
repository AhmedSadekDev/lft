@extends("layouts.admin")

@section("content")
<div class="container-fluid">
    @include("layouts.includes.breadcrumb", [ 'page' => 'كشف حساب سيارة - ' . $car->car_number ])

    <!--begin::Card-->
    <div class="card card-custom shadow-sm">
        <div class="card-header border-0 py-4">
            <div class="card-title">
                <h3 class="card-label font-weight-bolder text-dark">
                    <i class="fas fa-car text-primary mr-2"></i>
                    كشف حساب سيارة - {{ $car->car_number }}
                </h3>
            </div>
            <div class="card-toolbar">
                <a href="{{ route('accounts.car.statement.export.excel', ['carId' => $car->id, 'from' => $fromDate, 'to' => $toDate]) }}"
                   class="btn btn-primary font-weight-bold shadow-sm mr-2">
                    <i class="fas fa-file-excel"></i> تصدير Excel
                </a>
                <a href="{{ route('accounts.car.statement.export.pdf', ['carId' => $car->id, 'from' => $fromDate, 'to' => $toDate]) }}"
                   class="btn btn-danger font-weight-bold shadow-sm mr-2">
                    <i class="fas fa-file-pdf"></i> تصدير PDF
                </a>
                <button type="button" class="btn btn-secondary font-weight-bold shadow-sm" data-toggle="modal" data-target="#filterModal">
                    <i class="fas fa-filter"></i> فلتر
                </button>
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
                    <form action="{{ route('accounts.car.statement', $car->id) }}" method="get">
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
            <!-- معلومات السيارة -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <h5 class="font-weight-bold">معلومات السيارة</h5>
                    <p><strong>رقم السيارة:</strong> {{ $car->car_number }}</p>
                    <p><strong>النوع:</strong> {{ $car->type ?? '-' }}</p>
                </div>
                <div class="col-md-6 text-right">
                    <h5 class="font-weight-bold">ملخص الحساب</h5>
                    <p><strong>الرصيد المرحّل:</strong>
                        <span class="text-{{ $carriedForwardBalance >= 0 ? 'danger' : 'success' }}">
                            {{ number_format($carriedForwardBalance, 2) }}
                        </span>
                    </p>
                    <p><strong>إجمالي القيمة:</strong>
                        <span class="text-info">{{ number_format($totalValue, 2) }}</span>
                    </p>
                    <p><strong>إجمالي العهدة:</strong>
                        <span class="text-info">{{ number_format($totalCustody, 2) }}</span>
                    </p>
                    <p><strong>إجمالي الدفعات:</strong>
                        <span class="text-success">{{ number_format($totalPayments, 2) }}</span>
                    </p>
                    <p><strong>الرصيد النهائي المستحق:</strong>
                        <span class="text-{{ $finalBalance >= 0 ? 'danger' : 'success' }} font-weight-bold">
                            {{ number_format($finalBalance, 2) }}
                        </span>
                    </p>
                </div>
            </div>

            <!-- جدول كشف الحساب -->
            <h5 class="font-weight-bold mt-4 mb-3">الحساب في الفترة من {{ $fromDate }} الى {{ $toDate }}</h5>
            <div class="table-responsive">
                <table class="table table-bordered table-hover" style="font-size: 12px;">
                    <thead class="thead-dark">
                        <tr class="text-center">
                            <th rowspan="2" style="vertical-align: middle; width: 7%;">التاريخ</th>
                            <th rowspan="2" style="vertical-align: middle; width: 6%;">حساب سابق</th>
                            <th rowspan="2" style="vertical-align: middle; width: 7%;">الخدمة</th>
                            <th rowspan="2" style="vertical-align: middle; width: 10%;">الوصف</th>
                            <th rowspan="2" style="vertical-align: middle; width: 7%;">رقم الحاوية</th>
                            <th rowspan="2" style="vertical-align: middle; width: 7%;">خروج</th>
                            <th rowspan="2" style="vertical-align: middle; width: 7%;">الوجهة</th>
                            <th rowspan="2" style="vertical-align: middle; width: 7%;">تعتيق</th>
                            <th rowspan="2" style="vertical-align: middle; width: 6%;">القيمة</th>
                            <th rowspan="2" style="vertical-align: middle; width: 6%;">العهدة</th>
                            <th rowspan="2" style="vertical-align: middle; width: 6%;">الإجمالي</th>
                            <th rowspan="2" style="vertical-align: middle; width: 6%;">الإجمالي</th>
                            <th rowspan="2" style="vertical-align: middle; width: 6%;">مدين أو دائن</th>
                            <th rowspan="2" style="vertical-align: middle; width: 10%;">اجمالي النقلة + حساب سابق</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($transactions as $transaction)
                            @php
                                $date = $transaction['date'] instanceof \Carbon\Carbon ? $transaction['date'] : \Carbon\Carbon::parse($transaction['date']);
                            @endphp
                            <tr>
                                <td class="text-center" style="font-size: 10px;">{{ $date->format('Y-m-d') }}<br><small>{{ $date->format('H:i') }}</small></td>
                                <td class="text-center">{{ $transaction['previous_balance'] > 0 ? number_format($transaction['previous_balance'], 2) : '-' }}</td>
                                <td class="text-center">{{ $transaction['service'] ?: '-' }}</td>
                                <td class="text-center" style="font-size: 10px;">{{ $transaction['description'] ?: '-' }}</td>
                                <td class="text-center">{{ $transaction['container_no'] ?: '-' }}</td>
                                <td class="text-center" style="font-size: 10px;">{{ $transaction['departure'] ?: '-' }}</td>
                                <td class="text-center" style="font-size: 10px;">{{ $transaction['destination'] ?: '-' }}</td>
                                <td class="text-center" style="font-size: 10px;">{{ $transaction['aging'] ?: '-' }}</td>
                                <td class="text-center">{{ $transaction['value'] > 0 ? number_format($transaction['value'], 2) : '-' }}</td>
                                <td class="text-center">{{ $transaction['custody'] > 0 ? number_format($transaction['custody'], 2) : '-' }}</td>
                                <td class="text-center">{{ $transaction['total1'] > 0 ? number_format($transaction['total1'], 2) : '-' }}</td>
                                <td class="text-center">{{ $transaction['total2'] > 0 ? number_format($transaction['total2'], 2) : '-' }}</td>
                                <td class="text-center {{ $transaction['debit_credit'] == 'مدين' ? 'text-danger font-weight-bold' : 'text-success font-weight-bold' }}">
                                    {{ $transaction['debit_credit'] }}
                                </td>
                                <td class="text-center {{ $transaction['running_balance'] >= 0 ? 'text-danger' : 'text-success' }} font-weight-bold">
                                    {{ number_format($transaction['running_balance'], 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="14" class="text-center py-5">لا توجد حركات في هذه الفترة</td>
                            </tr>
                        @endforelse

                        <!-- صف الإجمالي النهائي -->
                        @if($transactions->count() > 0)
                            @php
                                $totalPreviousBalance = $transactions->sum('previous_balance');
                                $totalValue = $transactions->sum('value');
                                $totalCustody = $transactions->sum('custody');
                                $total1 = $transactions->sum('total1');
                                $total2 = $transactions->sum('total2');
                                $finalRunningBalance = $transactions->last()['running_balance'] ?? $finalBalance;
                            @endphp
                            <tr class="table-info font-weight-bold">
                                <td colspan="2" class="text-center">الحساب النهائي يوم {{ $toDate }}</td>
                                <td colspan="10"></td>
                                <td class="text-center">{{ number_format($total1, 2) }}</td>
                                <td class="text-center">{{ number_format($total2, 2) }}</td>
                                <td class="text-center {{ $finalRunningBalance >= 0 ? 'text-danger' : 'text-success' }}">
                                    {{ number_format(abs($finalRunningBalance), 2) }}
                                </td>
                            </tr>
                            <tr class="table-warning font-weight-bold">
                                <td colspan="13" class="text-center">الرصيد النهائي المستحق</td>
                                <td class="text-center {{ $finalRunningBalance >= 0 ? 'text-danger' : 'text-success' }}">
                                    {{ number_format(abs($finalRunningBalance), 2) }}
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
