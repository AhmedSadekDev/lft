@extends("layouts.admin")

@section("content")
<div class="container-fluid">
    @include("layouts.includes.breadcrumb", [ 'page' => 'الشيكات' ])

    <!--begin::Card-->
    <div class="card card-custom shadow-sm">
        <div class="card-header border-0 py-4">
            <div class="card-title">
                <h3 class="card-label font-weight-bolder text-dark">
                    <i class="fas fa-money-check text-primary mr-2"></i>
                    الشيكات
                </h3>
            </div>
            <div class="card-toolbar">
                <form method="GET" action="{{ route('accounts.checks.index') }}" class="d-flex">
                    <input type="text"
                           name="search"
                           class="form-control mr-2"
                           placeholder="بحث برقم الشيك..."
                           value="{{ request('search') }}">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> بحث
                    </button>
                    @if(request('search'))
                        <a href="{{ route('accounts.checks.index') }}" class="btn btn-secondary mr-2">
                            <i class="fas fa-times"></i> إلغاء
                        </a>
                    @endif
                </form>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show m-3">
                {{ session('success') }}
                <button type="button" class="close" data-dismiss="alert">
                    <span>&times;</span>
                </button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show m-3">
                {{ session('error') }}
                <button type="button" class="close" data-dismiss="alert">
                    <span>&times;</span>
                </button>
            </div>
        @endif

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead style="background: linear-gradient(135deg, #DC143C 0%, #B22222 100%); color: #fff;">
                        <tr>
                            <th>#</th>
                            <th>رقم الشيك</th>
                            <th>اسم البنك</th>
                            <th>الشركة</th>
                            <th>رقم الفاتورة</th>
                            <th>القيمة</th>
                            <th>تاريخ الاستحقاق</th>
                            <th>الحالة</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($checks as $check)
                            <tr class="{{ $check->check_due_date < now() && !$check->check_paid_at ? 'table-danger' : '' }}">
                                <td>{{ $loop->iteration + ($checks->currentPage() - 1) * $checks->perPage() }}</td>
                                <td><strong>{{ $check->check_number }}</strong></td>
                                <td>{{ $check->check_bank_name }}</td>
                                <td>{{ $check->company->name ?? ($check->invoice->booking->company->name ?? '-') }}</td>
                                <td>{{ $check->invoice->invoice_number ?? '-' }}</td>
                                <td class="font-weight-bold">{{ number_format($check->value, 2) }} ج.م</td>
                                <td>
                                    <span class="{{ $check->check_due_date < now() && !$check->check_paid_at ? 'text-danger font-weight-bold' : '' }}">
                                        {{ $check->check_due_date ? \Carbon\Carbon::parse($check->check_due_date)->format('Y-m-d') : '-' }}
                                    </span>
                                </td>
                                <td>
                                    @if($check->check_paid_at)
                                        <span class="badge badge-success">تم الاستحقاق</span>
                                    @elseif($check->check_due_date < now())
                                        <span class="badge badge-danger">متأخر</span>
                                    @else
                                        <span class="badge badge-warning">قيد الانتظار</span>
                                    @endif
                                </td>
                                <td>
                                    @if(!$check->check_paid_at)
                                        <form action="{{ route('accounts.checks.mark-paid', $check->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('هل أنت متأكد من استحقاق هذا الشيك؟ سيتم خصم القيمة من حساب الشركة.')">
                                                <i class="fas fa-check"></i> تم الاستحقاق
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-muted">تم الاستحقاق في: {{ \Carbon\Carbon::parse($check->check_paid_at)->format('Y-m-d H:i') }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center">لا توجد شيكات</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $checks->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
