@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    @include('layouts.includes.breadcrumb', ['page' => 'كشف حساب مورد - ' . $supplier->name])

    <div class="card shadow-sm border-0">
        <div class="card-header text-white d-flex justify-content-between align-items-center flex-wrap"
             style="background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);">
            <h4 class="mb-0">
                <i class="fas fa-file-invoice mr-2"></i>
                كشف حساب - {{ $supplier->name }}
            </h4>
            <div class="mt-2 mt-md-0">
                @if (auth()->user()->hasPermissionTo('suppliers.create'))
                    <a href="{{ route('suppliers.payment', $supplier) }}" class="btn btn-light btn-sm mr-2">
                        <i class="fas fa-money-bill-wave text-success"></i> سداد
                    </a>
                    <a href="{{ route('receipts.create', ['supplier_id' => $supplier->id]) }}" class="btn btn-light btn-sm mr-2">
                        <i class="fas fa-plus text-primary"></i> إضافة إيصال
                    </a>
                @endif
                <a href="{{ route('suppliers.index') }}" class="btn btn-light btn-sm">
                    رجوع
                </a>
            </div>
        </div>

        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card border h-100">
                        <div class="card-body">
                            <h5 class="text-primary mb-3">معلومات المورد</h5>
                            <p><strong>الاسم:</strong> {{ $supplier->name }}</p>
                            <p><strong>الرصيد الحالي:</strong>
                                <span class="font-weight-bold {{ (float) $supplier->balance > 0 ? 'text-danger' : 'text-success' }}">
                                    {{ number_format((float) $supplier->balance, 2) }} ج.م
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card bg-light border h-100">
                        <div class="card-body">
                            <h5 class="text-primary mb-3">ملخص الفترة</h5>
                            <p>إجمالي فواتير المورد:
                                <span class="float-left text-danger font-weight-bold">{{ number_format($totalInvoices, 2) }} ج.م</span>
                            </p>
                            <p>إجمالي السداد:
                                <span class="float-left text-success font-weight-bold">{{ number_format($totalPayments, 2) }} ج.م</span>
                            </p>
                            <hr>
                            <h5>
                                صافي الفترة:
                                <span class="{{ ($totalInvoices - $totalPayments) >= 0 ? 'text-danger' : 'text-success' }}">
                                    {{ number_format(abs($totalInvoices - $totalPayments), 2) }} ج.م
                                </span>
                            </h5>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
                <button class="btn btn-primary btn-sm mb-2" data-toggle="modal" data-target="#supplierFilterModal">
                    <i class="fas fa-filter"></i> فلتر
                </button>
                <strong class="mb-2">
                    @if($fromDate || $toDate)
                        من {{ $fromDate ?: '—' }} إلى {{ $toDate ?: '—' }}
                    @else
                        كل الفترات
                    @endif
                </strong>
            </div>

            <h5 class="mb-3">فواتير المورد (مجمّعة برقم فاتورة المورد)</h5>
            <div class="table-responsive mb-5">
                <table class="table table-bordered table-hover text-center">
                    <thead style="background:#1f2a44;color:#fff;">
                    <tr>
                        <th>رقم فاتورة المورد</th>
                        <th>عدد الإيصالات</th>
                        <th>أول إيصال</th>
                        <th>آخر إيصال</th>
                        <th>الإجمالي</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($groupedInvoices as $row)
                        <tr>
                            <td class="font-weight-bold">{{ $row->supplier_invoice_number }}</td>
                            <td>{{ $row->receipts_count }}</td>
                            <td>{{ optional(\Carbon\Carbon::parse($row->first_receipt_at))->format('Y-m-d') }}</td>
                            <td>{{ optional(\Carbon\Carbon::parse($row->last_receipt_at))->format('Y-m-d') }}</td>
                            <td class="text-danger font-weight-bold">{{ number_format((float) $row->total_cost, 2) }} ج.م</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-muted py-3">لا توجد فواتير مجمّعة</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            @if($ungroupedReceipts->count())
                <h5 class="mb-3">إيصالات بدون رقم فاتورة مورد</h5>
                <div class="table-responsive mb-5">
                    <table class="table table-bordered table-hover text-center">
                        <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th>التاريخ</th>
                            <th>رقم الطلب</th>
                            <th>التكلفة</th>
                            <th>ملاحظات</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($ungroupedReceipts as $receipt)
                            <tr>
                                <td>{{ $receipt->id }}</td>
                                <td>{{ optional($receipt->created_at)->format('Y-m-d') }}</td>
                                <td>{{ $receipt->booking->booking_number ?? $receipt->booking_id ?? '-' }}</td>
                                <td class="text-danger font-weight-bold">{{ number_format((float) $receipt->cost, 2) }} ج.م</td>
                                <td>{{ $receipt->notes ?: '-' }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <h5 class="mb-3">عمليات السداد</h5>
            <div class="table-responsive">
                <table class="table table-bordered table-hover text-center">
                    <thead class="thead-light">
                    <tr>
                        <th>#</th>
                        <th>التاريخ</th>
                        <th>المبلغ</th>
                        <th>المصدر</th>
                        <th>المندوب</th>
                        <th>ملاحظات</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($payments as $payment)
                        <tr>
                            <td>{{ $payment->id }}</td>
                            <td>{{ optional($payment->created_at)->format('Y-m-d H:i') }}</td>
                            <td class="text-success font-weight-bold">{{ number_format((float) $payment->amount, 2) }} ج.م</td>
                            <td>
                                {{ $payment->source_type === \App\Models\SupplierPayment::SOURCE_SAFE ? 'الخزنة' : 'مندوب' }}
                            </td>
                            <td>{{ $payment->agent->name ?? '-' }}</td>
                            <td>{{ $payment->notes ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-muted py-3">لا توجد عمليات سداد</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="supplierFilterModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="GET" action="{{ route('suppliers.statement', $supplier) }}">
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
@endsection
