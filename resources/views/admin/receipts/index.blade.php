@extends('layouts.admin')
@section('content')
    <div class="container-fluid lft-simple-page">
        @include('layouts.includes.breadcrumb', ['page' => 'الإيصالات'])

        <div class="card card-custom gutter-b lft-simple-card lft-simple-card--wide">
            <div class="card-header border-0">
                <div class="card-title">
                    <h3 class="card-label">الإيصالات</h3>
                </div>
                <div class="card-toolbar">
                    <a href="{{ route('suppliers.index') }}" class="btn btn-secondary font-weight-bolder mr-2">الموردين</a>
                    @if (auth()->user()->hasPermissionTo('suppliers.create'))
                        <a href="{{ route('receipts.create') }}" class="btn btn-primary font-weight-bolder">
                            <i class="fas fa-plus"></i> إضافة إيصال
                        </a>
                    @endif
                </div>
            </div>

            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                <form method="GET" class="mb-4 row">
                    <div class="col-md-3 mb-2">
                        <select name="payment_source" class="form-control">
                            <option value="">كل مصادر الدفع</option>
                            <option value="safe" {{ request('payment_source') === 'safe' ? 'selected' : '' }}>الخزنة</option>
                            <option value="representative" {{ request('payment_source') === 'representative' ? 'selected' : '' }}>مندوب</option>
                            <option value="supplier" {{ request('payment_source') === 'supplier' ? 'selected' : '' }}>مورد</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-2">
                        <select name="supplier_id" class="form-control">
                            <option value="">كل الموردين</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" {{ (string) request('supplier_id') === (string) $supplier->id ? 'selected' : '' }}>
                                    {{ $supplier->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 mb-2">
                        <button class="btn btn-primary btn-block">فلترة</button>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover text-center">
                        <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th>التاريخ</th>
                            <th>التكلفة</th>
                            <th>مصدر الدفع</th>
                            <th>المورد</th>
                            <th>رقم فاتورة المورد</th>
                            <th>الخدمة</th>
                            <th>المندوب / المشرف</th>
                            <th>رقم الطلب</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($receipts as $receipt)
                            <tr>
                                <td>{{ $receipt->id }}</td>
                                <td>{{ optional($receipt->created_at)->format('Y-m-d') }}</td>
                                <td class="font-weight-bold">{{ number_format((float) $receipt->cost, 2) }}</td>
                                <td>
                                    @if($receipt->payment_source === 'supplier') مورد
                                    @elseif($receipt->payment_source === 'safe') الخزنة
                                    @elseif($receipt->payment_source === 'representative') مندوب
                                    @else -
                                    @endif
                                </td>
                                <td>{{ $receipt->supplier->name ?? '-' }}</td>
                                <td>{{ $receipt->supplier_invoice_number ?: '-' }}</td>
                                <td>{{ $receipt->bookingService?->full_name ?? '-' }}</td>
                                <td>{{ $receipt->responsible_name ?: '—' }}</td>
                                <td>
                                    @if($receipt->booking)
                                        <a href="{{ route('bookings.show', $receipt->booking_id) }}">
                                            {{ $receipt->booking->booking_number ?? ('#'.$receipt->booking_id) }}
                                        </a>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if (auth()->user()->hasPermissionTo('suppliers.udpate') || auth()->user()->hasPermissionTo('suppliers.update'))
                                        <a href="{{ route('receipts.edit', $receipt) }}" class="btn btn-sm btn-light-warning">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    @endif
                                    @if (auth()->user()->hasPermissionTo('suppliers.delete'))
                                        <button type="button" class="btn btn-sm btn-light-danger"
                                                onclick="DeleteReceipt('{{ $receipt->id }}')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-muted py-4">لا توجد إيصالات</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

                {{ $receipts->withQueryString()->links() }}
            </div>
        </div>
    </div>
@endsection

@push('js')
<script>
    function DeleteReceipt(id) {
        Swal.fire({
            title: "{{ __('alerts.are_you_sure') }}",
            text: "{{ __('alerts.not_revert_information') }}",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: "{{ __('alerts.confirm') }}",
            cancelButtonText: "{{ __('alerts.cancel') }}",
            buttonsStyling: false,
            customClass: {
                confirmButton: 'btn btn-primary mx-1',
                cancelButton: 'btn btn-secondary mx-1',
            },
        }).then(function (result) {
            if (!result.isConfirmed) return;
            $.ajax({
                url: '{{ url('dashboard/receipts') }}/' + id,
                type: 'POST',
                data: {_method: 'DELETE', _token: '{{ csrf_token() }}'},
                success: function (res) {
                    Swal.fire({
                        title: "{{ __('alerts.done') }}",
                        icon: 'success',
                        showConfirmButton: false,
                        timer: 1500,
                        timerProgressBar: true,
                    }).then(function () { location.reload(); });
                },
                error: function (xhr) {
                    Swal.fire({
                        title: "{{ __('alerts.error') }}",
                        text: (xhr.responseJSON && xhr.responseJSON.msg) || 'تعذر الحذف',
                        icon: 'error',
                    });
                }
            });
        });
    }
</script>
@endpush
