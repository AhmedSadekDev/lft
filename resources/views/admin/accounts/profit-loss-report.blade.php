@extends("layouts.admin")

@section("content")
<div class="container-fluid">
    @include("layouts.includes.breadcrumb", [ 'page' => 'تقرير الأرباح والخسائر' ])

    <!--begin::Card-->
    <div class="card card-custom shadow-sm">
        <div class="card-header border-0 py-4">
            <div class="card-title">
                <h3 class="card-label font-weight-bolder text-dark">
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

        <!-- Modal الفلتر -->
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
                                        <label class="font-weight-bold">الشركة (اختياري - اتركه فارغاً لجميع الشركات)</label>
                                        <select name="company_id" class="form-control">
                                            <option value="">جميع الشركات</option>
                                            @foreach($companies as $company)
                                                <option value="{{ $company->id }}" {{ $companyId == $company->id ? 'selected' : '' }}>
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
            <!-- ملخص التقرير -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="alert alert-info">
                        <h5 class="font-weight-bold mb-3">
                            <i class="fas fa-info-circle mr-2"></i>
                            ملخص التقرير للفترة من {{ $fromDate }} إلى {{ $toDate }}
                        </h5>
                        <div class="row">
                            <div class="col-md-4">
                                <p><strong>إجمالي التكلفة:</strong>
                                    <span class="text-danger font-weight-bold">{{ number_format($totalCost, 2) }} ج.م</span>
                                </p>
                            </div>
                            <div class="col-md-4">
                                <p><strong>إجمالي الإيرادات:</strong>
                                    <span class="text-primary font-weight-bold">{{ number_format($totalRevenue, 2) }} ج.م</span>
                                </p>
                            </div>
                            <div class="col-md-4">
                                <p><strong>صافي الربح/الخسارة:</strong>
                                    <span class="font-weight-bold {{ $totalProfitLoss >= 0 ? 'text-success' : 'text-danger' }}">
                                        {{ number_format($totalProfitLoss, 2) }} ج.م
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- جدول التقرير -->
            <div class="table-responsive">
                <table class="table table-bordered table-hover" style="font-size: 12px;">
                    <thead class="thead-dark">
                        <tr class="text-center">
                            <th style="width: 5%;">#</th>
                            <th style="width: 8%;">رقم الطلب</th>
                            <th style="width: 10%;">رقم الفاتورة</th>
                            <th style="width: 12%;">اسم الشركة</th>
                            <th style="width: 8%;">تاريخ الفاتورة</th>
                            <th style="width: 25%;">وصف المصروفات</th>
                            <th style="width: 10%;" class="text-danger">التكلفة الفعلية</th>
                            <th style="width: 10%;" class="text-primary">سعر الفاتورة</th>
                            <th style="width: 12%;" class="{{ $totalProfitLoss >= 0 ? 'text-success' : 'text-danger' }}">الربح/الخسارة</th>
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
                                <td style="font-size: 11px;">
                                    @if($row['expenses_details']->count() > 0)
                                        <ul class="mb-0 pl-3" style="list-style: none;">
                                            @foreach($row['expenses_details'] as $expense)
                                                <li>• {{ $expense['description'] }}: {{ number_format($expense['value'], 2) }} ج.م</li>
                                            @endforeach
                                        </ul>
                                    @else
                                        <span class="text-muted">لا توجد مصروفات</span>
                                    @endif
                                </td>
                                <td class="text-center text-danger font-weight-bold">{{ number_format($row['total_cost'], 2) }}</td>
                                <td class="text-center text-primary font-weight-bold">{{ number_format($row['invoice_total'], 2) }}</td>
                                <td class="text-center font-weight-bold {{ $row['profit_loss'] >= 0 ? 'text-success' : 'text-danger' }}">
                                    {{ number_format($row['profit_loss'], 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-5">لا توجد بيانات في هذه الفترة</td>
                            </tr>
                        @endforelse

                        <!-- صف الإجمالي -->
                        @if($reportData->count() > 0)
                            <tr class="table-info font-weight-bold">
                                <td colspan="6" class="text-center">الإجمالي</td>
                                <td class="text-center text-danger">{{ number_format($totalCost, 2) }}</td>
                                <td class="text-center text-primary">{{ number_format($totalRevenue, 2) }}</td>
                                <td class="text-center {{ $totalProfitLoss >= 0 ? 'text-success' : 'text-danger' }}">
                                    {{ number_format($totalProfitLoss, 2) }}
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
