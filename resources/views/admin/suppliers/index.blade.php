@extends('layouts.admin')
@section('content')
    <div class="container">
        @include('layouts.includes.breadcrumb', ['page' => __('main.suppliers')])

        <div class="card card-custom">
            <div class="card-header flex-wrap py-5">
                <div class="card-title">
                    <h3 class="card-label">{{ __('main.suppliers') }}</h3>
                </div>
                <div class="card-toolbar">
                    @if (auth()->user()->hasPermissionTo('suppliers.index'))
                        <a href="{{ route('receipts.index') }}" class="btn btn-light-info font-weight-bolder mr-2">
                            <i class="fas fa-receipt"></i> الإيصالات
                        </a>
                    @endif
                    @if (auth()->user()->hasPermissionTo('suppliers.create'))
                        <a href="{{ route('suppliers.create') }}" class="btn btn-primary font-weight-bolder">
                            <i class="fas fa-plus"></i> {{ __('admin.add') }}
                        </a>
                    @endif
                </div>
            </div>

            <div class="card-body">
                <form method="GET" action="{{ route('suppliers.index') }}" class="mb-4">
                    <div class="input-group" style="max-width: 420px;">
                        <input type="text" name="search" value="{{ request('search') }}" class="form-control"
                               placeholder="بحث باسم المورد...">
                        <div class="input-group-append">
                            <button class="btn btn-primary" type="submit">بحث</button>
                        </div>
                    </div>
                </form>

                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                @endif

                <div class="table-responsive">
                    <table class="table table-bordered table-hover text-center">
                        <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th>{{ __('admin.name') }}</th>
                            <th>الرصيد</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($suppliers as $supplier)
                            <tr>
                                <td>{{ $supplier->id }}</td>
                                <td>
                                    <a href="{{ route('suppliers.statement', $supplier) }}">
                                        {{ $supplier->name }}
                                    </a>
                                </td>
                                <td class="{{ (float) $supplier->balance > 0 ? 'text-danger' : 'text-success' }} font-weight-bold">
                                    {{ number_format((float) $supplier->balance, 2) }} ج.م
                                </td>
                                <td>
                                    <a href="{{ route('suppliers.statement', $supplier) }}"
                                       class="btn btn-sm btn-light-primary" title="كشف حساب">
                                        <i class="fas fa-file-invoice"></i>
                                    </a>
                                    @if (auth()->user()->hasPermissionTo('suppliers.create'))
                                        <a href="{{ route('suppliers.payment', $supplier) }}"
                                           class="btn btn-sm btn-light-success" title="سداد">
                                            <i class="fas fa-money-bill-wave"></i>
                                        </a>
                                    @endif
                                    @if (auth()->user()->hasPermissionTo('suppliers.udpate') || auth()->user()->hasPermissionTo('suppliers.update'))
                                        <a href="{{ route('suppliers.edit', $supplier) }}"
                                           class="btn btn-sm btn-light-warning" title="تعديل">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    @endif
                                    @if (auth()->user()->hasPermissionTo('suppliers.delete'))
                                        <button type="button" class="btn btn-sm btn-light-danger"
                                                onclick="DeleteSupplier('{{ $supplier->id }}')" title="حذف">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-muted py-4">لا يوجد موردون</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

                {{ $suppliers->withQueryString()->links() }}
            </div>
        </div>
    </div>
@endsection

@push('js')
<script>
    function DeleteSupplier(id) {
        if (!confirm('هل أنت متأكد من حذف المورد؟')) return;
        $.ajax({
            url: '{{ url('dashboard/suppliers') }}/' + id,
            type: 'POST',
            data: {_method: 'DELETE', _token: '{{ csrf_token() }}'},
            success: function (res) {
                toastr.success(res.msg || 'تم الحذف');
                location.reload();
            },
            error: function () {
                toastr.error('تعذر حذف المورد');
            }
        });
    }
</script>
@endpush
