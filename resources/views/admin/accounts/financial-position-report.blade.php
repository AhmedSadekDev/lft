@extends("layouts.admin")

@section("content")
<div class="container-fluid">
    @include("layouts.includes.breadcrumb", [ 'page' => 'تقرير الموقف المالي - الشركات المدينة' ])

    <!--begin::Card-->
    <div class="card card-custom shadow-sm">
        <div class="card-header border-0 py-4">
            <div class="card-title">
                <h3 class="card-label font-weight-bolder text-dark">
                    <i class="fas fa-chart-line text-primary mr-2"></i>
                    تقرير الموقف المالي - الشركات المدينة
                </h3>
            </div>
            <div class="card-toolbar">
                <a href="{{ route('accounts.financial-position.export.excel', ['date' => $reportDate]) }}"
                   class="btn btn-success font-weight-bold shadow-sm mr-2">
                    <i class="fas fa-file-excel"></i> تصدير Excel
                </a>
                <a href="{{ route('accounts.financial-position.export.pdf', ['date' => $reportDate]) }}"
                   class="btn btn-danger font-weight-bold shadow-sm mr-2">
                    <i class="fas fa-file-pdf"></i> تصدير PDF
                </a>
                <button type="button" class="btn btn-secondary font-weight-bold shadow-sm" data-toggle="modal" data-target="#filterModal">
                    <i class="fas fa-calendar-alt"></i> اختيار التاريخ
                </button>
            </div>
        </div>

        <!-- Modal الفلتر -->
        <div class="modal fade" id="filterModal" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title font-weight-bold">
                            <i class="fas fa-calendar-alt mr-2"></i>اختيار تاريخ التقرير
                        </h5>
                        <button type="button" class="close text-white" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <form action="{{ route('accounts.financial-position') }}" method="get">
                        <div class="modal-body">
                            <div class="form-group">
                                <label class="font-weight-bold">تاريخ التقرير</label>
                                <input type="date" name="date" value="{{ $reportDate }}" class="form-control">
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
            <!-- معلومات التقرير -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <h5 class="font-weight-bold">معلومات التقرير</h5>
                    <p><strong>تاريخ التقرير:</strong> {{ $reportDate }}</p>
                    <p><strong>عدد الشركات المدينة:</strong> {{ $companiesWithDebts?->count() ?? 0 }}</p>
                </div>
                <div class="col-md-6 text-right">
                    <h5 class="font-weight-bold">ملخص التقرير</h5>
                    <p><strong>إجمالي المبالغ المستحقة:</strong>
                        <span class="text-danger font-weight-bold" style="font-size: 18px;">
                            {{ number_format($totalDebts, 2) }} جنيه
                        </span>
                    </p>
                </div>
            </div>

            <!-- جدول الشركات المدينة -->
            <h5 class="font-weight-bold mt-4 mb-3">الشركات المدينة (التي عليها فلوس)</h5>
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead style="background: linear-gradient(135deg, #DC143C 0%, #B22222 100%); color: #fff;">
                        <tr class="text-center">
                            <th style="width: 5%;">#</th>
                            <th style="width: 30%;">اسم الشركة</th>
                            <th style="width: 25%;">البريد الإلكتروني</th>
                            <th style="width: 20%;">الهاتف</th>
                            <th style="width: 15%;">القيمة النهائية</th>
                            <th style="width: 10%;">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($companiesWithDebts ?? [] as $index => $company)
                        <tr>
                            <td class="text-center">{{ $index + 1 }}</td>
                            <td class="font-weight-bold">{{ $company['name'] }}</td>
                            <td>{{ $company['email'] }}</td>
                            <td>{{ $company['phone'] }}</td>
                            <td class="text-center text-danger font-weight-bold" style="font-size: 16px;">
                                {{ number_format($company['balance'], 2) }} ج.م
                            </td>
                            <td class="text-center">
                                <a href="{{ route('accounts.statement', $company['id']) }}?from={{ \Carbon\Carbon::parse($reportDate)->startOfYear()->format('Y-m-d') }}&to={{ $reportDate }}"
                                   class="btn btn-sm btn-primary" title="كشف حساب">
                                    <i class="fas fa-file-invoice"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <div class="text-muted">
                                    <i class="fas fa-check-circle fa-3x mb-3 text-success"></i>
                                    <p class="font-weight-bold">لا توجد شركات مدينة في هذا التاريخ</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                    @if($companiesWithDebts?->count() > 0)
                    <tfoot style="background: #f8f9fa;">
                        <tr class="font-weight-bold">
                            <td colspan="4" class="text-right" style="font-size: 16px;">الإجمالي:</td>
                            <td class="text-center text-danger" style="font-size: 18px;">
                                {{ number_format($totalDebts, 2) }} ج.م
                            </td>
                            <td></td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
