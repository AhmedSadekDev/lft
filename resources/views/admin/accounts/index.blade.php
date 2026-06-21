@extends("layouts.admin")

@section("content")
<div class="container-fluid">
    @include("layouts.includes.breadcrumb", [ 'page' => 'الحسابات' ])

    <!--begin::Card-->
    <div class="card card-custom shadow-sm">
        <div class="card-header border-0 py-4">
            <div class="card-title">
                <h3 class="card-label font-weight-bolder text-dark">
                    <i class="fas fa-calculator text-primary mr-2"></i>
                    إدارة الحسابات
                </h3>
            </div>
            <div class="card-toolbar">
                <a href="{{ route('accounts.checks.index') }}"
                   class="btn btn-warning font-weight-bold shadow-sm mr-2">
                    <i class="fas fa-money-check"></i> الشيكات
                </a>
                <a href="{{ route('accounts.financial-position') }}"
                   class="btn btn-info font-weight-bold shadow-sm">
                    <i class="fas fa-chart-line"></i> تقرير الموقف المالي
                </a>
            </div>
        </div>

        <div class="card-body">
            <!-- شريط البحث -->
            <form action="{{ route('accounts.index') }}" method="get" class="mb-4">
                <div class="row">
                    <div class="col-md-4">
                        <div class="input-group input-group-solid">
                            <div class="input-group-prepend">
                                <span class="input-group-text bg-light">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                            </div>
                            <input type="text" name="search" class="form-control form-control-solid"
                                   placeholder="ابحث عن الشركة..."
                                   value="{{ request('search') }}">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary font-weight-bold w-100">
                            <i class="fas fa-search mr-1"></i> بحث
                        </button>
                    </div>
                    @if(request('search'))
                        <div class="col-md-2">
                            <a href="{{ route('accounts.index') }}"
                               class="btn btn-secondary font-weight-bold w-100">
                                <i class="fas fa-times mr-1"></i> إلغاء
                            </a>
                        </div>
                    @endif
                </div>
            </form>

            <!-- الجدول -->
            <div class="table-responsive">
                <table class="table table-hover table-bordered">
                    <thead class="thead-light">
                        <tr class="text-center">
                            <th>#</th>
                            <th>اسم الشركة</th>
                            <th>البريد الإلكتروني</th>
                            <th>الهاتف</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($companies as $company)
                        <tr>
                            <td class="text-center">{{ $company->id }}</td>
                            <td>{{ $company->name }}</td>
                            <td>{{ $company->email }}</td>
                            <td>{{ $company->phone }}</td>
                            <td class="text-center">
                                <a href="{{ route('accounts.statement', $company->id) }}"
                                   class="btn btn-sm btn-primary">
                                    <i class="fas fa-file-invoice"></i> كشف حساب
                                </a>
                                <a href="{{ route('accounts.payment', $company->id) }}"
                                   class="btn btn-sm btn-success">
                                    <i class="fas fa-money-bill"></i> سداد
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-5">
                                <div class="text-muted">
                                    <i class="fas fa-inbox fa-3x mb-3"></i>
                                    <p class="font-weight-bold">لا توجد شركات</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($companies->hasPages())
                <div class="d-flex justify-content-center mt-4">
                    {{ $companies->links('pagination::bootstrap-4') }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
