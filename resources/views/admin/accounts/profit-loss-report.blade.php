@extends("layouts.admin")

@section("content")
<div class="container-fluid lft-simple-page">
    @include("layouts.includes.breadcrumb", [ 'page' => 'تقرير الأرباح والخسائر' ])

    <div class="card card-custom gutter-b lft-simple-card lft-simple-card--wide shadow-sm">
        <div class="card-header border-0 py-4">
            <div class="card-title">
                <h3 class="card-label font-weight-bolder">
                    <i class="fas fa-chart-line text-primary mr-2"></i>
                    تقرير الأرباح والخسائر
                </h3>
            </div>
            <div class="card-toolbar">
                <a href="{{ route('accounts.profit-loss.export.excel', request()->all()) }}"
                   class="btn btn-primary font-weight-bold shadow-sm mr-2">
                    <i class="fas fa-file-excel"></i> تصدير Excel
                </a>
                <a href="{{ route('accounts.profit-loss.export.pdf', request()->all()) }}"
                   class="btn btn-danger font-weight-bold shadow-sm mr-2">
                    <i class="fas fa-file-pdf"></i> تصدير PDF
                </a>
                <button type="button" class="btn btn-secondary font-weight-bold shadow-sm" data-toggle="modal" data-target="#filterModal">
                    <i class="fas fa-filter"></i> فلتر
                </button>
            </div>
        </div>

        <div class="modal fade" id="filterModal" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title font-weight-bold">
                            <i class="fas fa-calendar-alt mr-2"></i>فلترة التقرير
                        </h5>
                        <button type="button" class="close text-white" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <form action="{{ route('accounts.profit-loss') }}" method="get">
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="font-weight-bold">من تاريخ</label>
                                        <input type="date" name="from" value="{{ $fromDate }}" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="font-weight-bold">إلى تاريخ</label>
                                        <input type="date" name="to" value="{{ $toDate }}" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="font-weight-bold">الشركة (اختياري)</label>
                                        <select name="company_id" class="form-control">
                                            <option value="">جميع الشركات</option>
                                            @foreach($companies as $company)
                                                <option value="{{ $company->id }}" {{ (string) $companyId === (string) $company->id ? 'selected' : '' }}>
                                                    {{ $company->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
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
            @php $summary = $summary ?? []; @endphp

            <div class="alert alert-light border mb-4">
                <h5 class="font-weight-bold mb-3">
                    ملخص الفترة من {{ $fromDate }} إلى {{ $toDate }}
                </h5>
                <div class="table-responsive">
                    <table class="table table-bordered mb-0">
                        <tbody>
                            <tr class="table-primary">
                                <th style="width: 28%;">1) إجمالي الفواتير</th>
                                <td>
                                    عدد الفواتير: <strong>{{ $summary['invoice_count'] ?? 0 }}</strong>
                                    &nbsp;|&nbsp; قيمة الفواتير: <strong>{{ number_format($summary['invoice_total'] ?? 0, 2) }} ج.م</strong>
                                    &nbsp;|&nbsp; المصروف الفعلي: <strong>{{ number_format($summary['invoice_cost'] ?? 0, 2) }} ج.م</strong>
                                    &nbsp;|&nbsp; صافي الربح: <strong>{{ number_format($summary['invoice_net_profit'] ?? 0, 2) }} ج.م</strong>
                                </td>
                            </tr>
                            <tr class="table-warning">
                                <th>2) إشعارات الخصم</th>
                                <td>
                                    الإجمالي: <strong>{{ number_format($summary['discount_notices_total'] ?? 0, 2) }} ج.م</strong>
                                    <small class="text-muted d-block">
                                        من الفواتير: {{ number_format($summary['invoice_discounts'] ?? 0, 2) }}
                                        — من الشركة: {{ number_format($summary['company_discounts'] ?? 0, 2) }}
                                    </small>
                                </td>
                            </tr>
                            <tr class="table-info">
                                <th>3) إشعارات الإضافة / المدين</th>
                                <td>
                                    الإجمالي: <strong>{{ number_format($summary['addition_notices_total'] ?? 0, 2) }} ج.م</strong>
                                    <small class="text-muted d-block">
                                        من الفواتير: {{ number_format($summary['invoice_additions'] ?? 0, 2) }}
                                        — من الشركة: {{ number_format($summary['company_additions'] ?? 0, 2) }}
                                    </small>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <h5 class="font-weight-bold mb-3">تفاصيل الفواتير</h5>
            <div class="table-responsive mb-5">
                <table class="table table-bordered table-hover" style="font-size: 12px;">
                    <thead class="thead-dark">
                        <tr class="text-center">
                            <th>#</th>
                            <th>رقم الطلب</th>
                            <th>رقم الفاتورة</th>
                            <th>الشركة</th>
                            <th>التاريخ</th>
                            <th>التكلفة</th>
                            <th>إجمالي الفاتورة</th>
                            <th>الربح/الخسارة</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($reportData as $index => $row)
                            @php
                                $date = $row['invoice_date'] instanceof \Carbon\Carbon ? $row['invoice_date'] : \Carbon\Carbon::parse($row['invoice_date']);
                            @endphp
                            <tr>
                                <td class="text-center">{{ $index + 1 }}</td>
                                <td class="text-center">{{ $row['booking_number'] }}</td>
                                <td class="text-center">{{ $row['invoice_number'] }}</td>
                                <td class="text-center">{{ $row['company_name'] }}</td>
                                <td class="text-center">{{ $date->format('Y-m-d') }}</td>
                                <td class="text-center text-danger font-weight-bold">{{ number_format($row['total_cost'], 2) }}</td>
                                <td class="text-center text-primary font-weight-bold">{{ number_format($row['invoice_total'], 2) }}</td>
                                <td class="text-center font-weight-bold {{ $row['profit_loss'] >= 0 ? 'text-success' : 'text-danger' }}">
                                    {{ number_format($row['profit_loss'], 2) }}
                                </td>
                                <td class="text-center">
                                    @if(!empty($row['invoice_id']))
                                        <a href="{{ route('booking-invoices.show', $row['invoice_id']) }}" class="btn btn-sm btn-light-primary">
                                            فتح الفاتورة
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-5">لا توجد فواتير في هذه الفترة</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <h5 class="font-weight-bold mb-3">إشعارات الشركة (بدون فاتورة)</h5>
            <div class="table-responsive">
                <table class="table table-bordered table-hover" style="font-size: 12px;">
                    <thead class="thead-light">
                        <tr class="text-center">
                            <th>رقم الإشعار</th>
                            <th>النوع</th>
                            <th>الشركة</th>
                            <th>التاريخ</th>
                            <th>المبلغ</th>
                            <th>السبب</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse(($companyAdjustments ?? collect()) as $adj)
                            <tr>
                                <td class="text-center">{{ $adj->note_number ?: ('ADJ-'.$adj->id) }}</td>
                                <td class="text-center">{{ $adj->typeLabel() }}</td>
                                <td class="text-center">{{ $adj->company->name ?? '-' }}</td>
                                <td class="text-center">{{ optional($adj->created_at)->format('Y-m-d') }}</td>
                                <td class="text-center font-weight-bold">{{ number_format(abs((float)$adj->amount), 2) }} {{ $adj->currency }}</td>
                                <td>{{ $adj->reason }}</td>
                                <td class="text-center">
                                    <a href="{{ route('accounts.adjustments.print', [$adj->company_id, $adj->id]) }}" class="btn btn-sm btn-light-primary" target="_blank">طباعة</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">لا توجد إشعارات شركة في الفترة</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
