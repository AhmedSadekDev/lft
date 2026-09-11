@extends('layouts.admin')

@section('css')
<style>
    .table-separate {
        border-collapse: separate;
        border-spacing: 0 10px;
    }

    .table-separate thead th {
        border: none;
        background-color: #f3f6f9;
        color: #464e5f;
        font-weight: 600;
        padding: 15px 10px;
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .table-separate tbody tr {
        background-color: #fff;
        border: 1px solid #ebedf3;
        border-radius: 8px;
        transition: all 0.3s ease;
    }

    .table-separate tbody tr:hover {
        box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        transform: translateY(-2px);
    }

    .table-separate tbody td {
        border: none;
        padding: 20px 10px;
        vertical-align: middle;
    }

    .badge-lg {
        padding: 8px 16px;
        font-size: 14px;
    }

    .separator {
        height: 2px;
        background: linear-gradient(90deg, transparent, #ebedf3 50%, transparent);
    }
</style>
@endsection

@section('content')
    <div class="container">
        @include('layouts.includes.breadcrumb', ['page' => __('main.Tax_invoices')])
        <!--begin::Card-->
        <div class="card card-custom">
            <div class="card-header flex-wrap  align-items-center py-5">
                <div class="card-toolbar">
                    <div class="">
                        <!--begin::Button-->
                        <a href="{{ route('bookings.create', request()->id) }}" class="btn btn-primary font-weight-bolder">
                            <span class="svg-icon svg-icon-md">
                                <!--begin::Svg Icon | path:assets/media/svg/icons/Design/Flatten.svg-->
                                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"
                                    width="24px" height="24px" viewBox="0 0 24 24" version="1.1">
                                    <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                                        <rect x="0" y="0" width="24" height="24" />
                                        <circle fill="#000000" cx="9" cy="15" r="6" />
                                        <path
                                            d="M8.8012943,7.00241953 C9.83837775,5.20768121 11.7781543,4 14,4 C17.3137085,4 20,6.6862915 20,10 C20,12.2218457 18.7923188,14.1616223 16.9975805,15.1987057 C16.9991904,15.1326658 17,15.0664274 17,15 C17,10.581722 13.418278,7 9,7 C8.93357256,7 8.86733422,7.00080962 8.8012943,7.00241953 Z"
                                            fill="#000000" opacity="0.3" />
                                    </g>
                                </svg>
                                <!--end::Svg Icon-->
                            </span>{{ __('admin.add') }}
                        </a>
                    </div>

                    <!--end::Button-->
                </div>
                <div class="">
                    <!-- Button trigger modal -->
                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#exampleModal">
                        {{ __('admin.filter') }}
                    </button>

                    <a href="{{ route('invoice_payments.excel', request()->id) }}"
                        class="btn btn-primary">{{ __('admin.export') }}</a>

                    <a href="{{ route('invoice_payments.pdf', request()->id) }}"
                        class="btn btn-primary">{{ __('main.download_all_invoices') }}</a>


                    <!-- Modal -->
                    <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel"
                        aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="exampleModalLabel">{{ __('admin.filter') }}</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        X
                                    </button>
                                </div>
                                <form action="{{ route('bokkings.invoices', request()->id) }}" method="get">
                                    <div class="modal-body">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label for="idSelect">{{ __('main.companies') }}</label>
                                                    <select class="form-control" name="id" id="idSelect">
                                                        @foreach ($companies as $company)
                                                            <option value="{{ $company->id }}">{{ $company->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="dateFrom">{{ __('admin.from') }}</label>
                                                    <input id="dateFrom" class="form-control" type="date"
                                                        name="date_from">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="dateTo">{{ __('admin.to') }}</label>
                                                    <input id="dateTo" class="form-control" type="date"
                                                        name="date_to">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                        <button type="submit" class="btn btn-primary">{{ __('admin.filter') }}</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <!-- Invoices Section -->
                @if($bookings->count() > 0)
                <div class="mb-10">
                    <h4 class="mb-4 font-weight-bold text-dark">الفواتير</h4>
                    <div class="table-responsive">
                        <table class="table table-separate table-head-custom no-datatable" id="invoices-table" style="width: 100%">
                            <thead>
                                <tr>
                                    <th style="min-width: 120px">التاريخ</th>
                                    <th style="min-width: 120px">رقم الفاتورة</th>
                                    <th class="text-center" style="min-width: 100px">نوع العملية</th>
                                    <th class="text-center" style="min-width: 100px">الضريبة</th>
                                    <th class="text-center" style="min-width: 100px">الخصم</th>
                                    <th class="text-center" style="min-width: 120px">المبالغ</th>
                                    <th class="text-center" style="min-width: 120px">الإجمالي</th>
                                    <th class="text-center" style="min-width: 120px">المدفوع</th>
                                    <th class="text-center" style="min-width: 120px">المتبقي</th>
                                    <th class="text-center" style="min-width: 100px">الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($bookings as $booking)
                                    @php
                                        $invoice = $booking->invoice ?? null;

                                        if ($invoice) {
                                            $invoiceTotalBeforeTax = $invoice->invoice_total_before_tax;
                                            $vatValue = $invoice->value_added_tax_amount;
                                            $saleValue = $invoice->sales_tax_amount;
                                            $discountValue = $invoice->discount_amount;

                                            $taxedServicesTotal = $invoice->taxed_services_total_before_vat ?? 0;
                                            $untaxedServicesTotal = $invoice->untaxed_services_total_before_vat ?? 0;
                                            $transportationTotal = $invoice->transportation_total_before_vat ?? 0;

                                            $finalValue = $invoiceTotalBeforeTax + $taxedServicesTotal + $untaxedServicesTotal + $vatValue - $saleValue - $discountValue;
                                            $paidAmount = $booking->invoice->invoicePayments->sum('value') ?? 0;
                                            $remainingAmount = $finalValue - $paidAmount;
                                        } else {
                                            $vatValue = $saleValue = $discountValue = $taxedServicesTotal = $untaxedServicesTotal = $transportationTotal = $finalValue = $paidAmount = $remainingAmount = 0;
                                        }
                                    @endphp
                                    <tr>
                                        <td>
                                            <span class="text-dark-75 font-weight-bold">{{ $booking->created_at->format('Y-m-d') }}</span>
                                            <div class="text-muted font-size-sm">{{ $booking->created_at->format('H:i') }}</div>
                                        </td>
                                        <td>
                                            <span class="badge badge-light-primary font-weight-bold">{{ $booking->invoice->invoice_number ?? '-' }}</span>
                                        </td>
                                        <td class="text-center">
                                            @if ($booking->type_of_action == 0)
                                                <span class="badge badge-info">{{ __('actions.Outbound') }}</span>
                                            @elseif($booking->type_of_action == 1)
                                                <span class="badge badge-success">{{ __('actions.Inbound') }}</span>
                                            @else
                                                <span class="badge badge-warning">{{ __('actions.Clearance') }}</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <span class="text-dark-75 font-weight-bold">{{ number_format($vatValue, 2) }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="text-danger font-weight-bold">{{ number_format($discountValue, 2) }}</span>
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex flex-column">
                                                <small class="text-muted">ضريبي: <span class="text-dark">{{ number_format($taxedServicesTotal, 2) }}</span></small>
                                                <small class="text-muted">غير ضريبي: <span class="text-dark">{{ number_format($untaxedServicesTotal, 2) }}</span></small>
                                                <small class="text-muted">نقل: <span class="text-dark">{{ number_format($transportationTotal, 2) }}</span></small>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge badge-lg badge-primary font-weight-bold">{{ number_format($finalValue, 2) }} ر.س</span>
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ route('invoice_payments.index', $booking->invoice->id) }}" class="text-primary font-weight-bold">
                                                {{ number_format($paidAmount, 2) }} ر.س
                                            </a>
                                        </td>
                                        <td class="text-center">
                                            @if($remainingAmount > 0)
                                                <span class="badge badge-lg badge-warning font-weight-bold">{{ number_format($remainingAmount, 2) }} ر.س</span>
                                                <div class="text-danger font-size-sm">مستحق</div>
                                            @elseif($remainingAmount < 0)
                                                <span class="badge badge-lg badge-info font-weight-bold">{{ number_format(abs($remainingAmount), 2) }} ر.س</span>
                                                <div class="text-info font-size-sm">زائد</div>
                                            @else
                                                <span class="badge badge-lg badge-success font-weight-bold">0.00 ر.س</span>
                                                <div class="text-success font-size-sm">مسدد</div>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown dropdown-inline">
                                                <a href="javascript:;" class="btn btn-sm btn-light-primary btn-icon" data-toggle="dropdown">
                                                    <i class="fas fa-ellipsis-v"></i>
                                                </a>
                                                <div class="dropdown-menu dropdown-menu-sm dropdown-menu-right">
                                                    <a class="dropdown-item" href="{{ route('booking-invoices.show', $booking->invoice->id) }}">
                                                        <i class="fas fa-eye text-primary mr-2"></i> عرض التفاصيل
                                                    </a>
                                                    @if ($finalValue > ($booking->invoice->invoicePayments->sum('value') ?? 0))
                                                        <a class="dropdown-item" href="#" data-toggle="modal" data-target="#createModal" data-invoice_id="{{ $invoice->id }}" onclick="$('#invoiceIdInput').val('{{ $invoice->id }}')">
                                                            <i class="fas fa-money-bill-wave text-success mr-2"></i> سداد
                                                        </a>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif

                <!-- Payments Section -->
                @if($allPayments->count() > 0)
                <div class="separator separator-dashed my-10"></div>
                <div>
                    <h4 class="mb-4 font-weight-bold text-dark">المدفوعات</h4>
                    <div class="table-responsive">
                        <table class="table table-separate table-head-custom no-datatable" id="payments-table" style="width: 100%">
                            <thead>
                                <tr>
                                    <th style="min-width: 80px">#</th>
                                    <th style="min-width: 120px">رقم الفاتورة</th>
                                    <th class="text-center" style="min-width: 120px">المبلغ</th>
                                    <th class="text-center" style="min-width: 100px">صورة التحويل</th>
                                    <th style="min-width: 120px">تاريخ السداد</th>
                                    <th class="text-center" style="min-width: 100px">الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($allPayments as $payment)
                                    <tr>
                                        <td>
                                            <span class="text-muted font-weight-bold">{{ $payment->id }}</span>
                                        </td>
                                        <td>
                                            <span class="badge badge-light-info font-weight-bold">{{ $payment->invoice->invoice_number ?? '-' }}</span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge badge-lg badge-success font-weight-bold">{{ number_format($payment->value, 2) }} ر.س</span>
                                        </td>
                                        <td class="text-center">
                                            @if($payment->image)
                                                <a href="{{ asset($payment->image) }}" download class="btn btn-icon btn-light btn-hover-primary btn-sm">
                                                    <img src="{{ asset($payment->image) }}" alt="تحويل" style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px;">
                                                </a>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="text-dark-75 font-weight-bold">{{ $payment->created_at->format('Y-m-d') }}</span>
                                            <div class="text-muted font-size-sm">{{ $payment->created_at->format('H:i') }}</div>
                                        </td>
                                        <td class="text-center">
                                            <div class="dropdown dropdown-inline">
                                                <a href="javascript:;" class="btn btn-sm btn-light-primary btn-icon" data-toggle="dropdown">
                                                    <i class="fas fa-ellipsis-v"></i>
                                                </a>
                                                <div class="dropdown-menu dropdown-menu-sm dropdown-menu-right">
                                                    <a class="dropdown-item" href="#" data-toggle="modal" data-target="#updateModal" data-url="{{ route('invoice_payments.update', $payment->id) }}" data-id="{{ $payment->id }}" data-value="{{ $payment->value }}" data-bank_id="{{ $payment->bank_id }}">
                                                        <i class="fas fa-edit text-primary mr-2"></i> تعديل
                                                    </a>
                                                    <div class="dropdown-divider"></div>
                                                    <a class="dropdown-item text-danger" href="javascript:;" onclick="Delete('{{ $payment->id }}')">
                                                        <i class="fas fa-trash mr-2"></i> حذف
                                                    </a>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif

                @if($bookings->count() == 0 && $allPayments->count() == 0)
                    <div class="text-center py-10">
                        <i class="fas fa-file-invoice fa-3x text-muted mb-4"></i>
                        <h4 class="text-muted">لا توجد فواتير أو مدفوعات</h4>
                    </div>
                @endif
                </div>
            </div>


        </div>
        <!-- Modal -->
        <div class="modal fade" id="createModal" tabindex="-1" aria-labelledby="createModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="createModalLabel">{{ __('Pay') }}</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form action="{{ route('invoice_payments.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="invoice_id" id="invoiceIdInput">

                        <div class="modal-body">
                            <div class="form-group">
                                <label for="bank_id">{{ __('admin.bank') }}</label>
                                <select name="bank_id" id="bank_id" class="form-control">
                                    @foreach ($banks as $bank)
                                        <option value="{{ $bank->id }}">{{ $bank->name }}</option>
                                    @endforeach
                                </select>
                                @error('bank_id')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group">
                                <label for="value">{{ __('admin.value') }}</label>
                                <input type="text" name="value" id="value" class="form-control">
                                @error('value')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group">
                                <label for="image">{{ __('admin.image') }}</label>
                                <input type="file" name="image" id="image" class="form-control">
                                @error('image')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary"
                                data-dismiss="modal">{{ __('admin.close') }}</button>
                            <button type="submit" class="btn btn-primary">{{ __('admin.save') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Update Modal -->
        <div class="modal fade" id="updateModal" tabindex="-1" aria-labelledby="updateModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="updateModalLabel">تعديل المدفوع</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form id="updatePaymentForm" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('POST')
                        <div class="modal-body">
                            <div class="form-group">
                                <label for="update_bank_id">{{ __('admin.bank') }}</label>
                                <select name="bank_id" id="update_bank_id" class="form-control">
                                    @foreach ($banks as $bank)
                                        <option value="{{ $bank->id }}">{{ $bank->name }}</option>
                                    @endforeach
                                </select>
                                @error('bank_id')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group">
                                <label for="update_value">{{ __('admin.value') }}</label>
                                <input type="text" name="value" id="update_value" class="form-control" required>
                                @error('value')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group">
                                <label for="update_image">{{ __('admin.image') }}</label>
                                <input type="file" name="image" id="update_image" class="form-control">
                                <small class="text-muted">اتركه فارغاً إذا لم ترد تغيير الصورة</small>
                                @error('image')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('admin.close') }}</button>
                            <button type="submit" class="btn btn-primary">{{ __('admin.save') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!--end::Card-->
    </div>
@endsection
@push('js')
<script>
    $(document).on('click', '.create-btn', function() {
      $('#invoiceIdInput').val($(this).data('invoice_id'));
    });

    // Handle update modal
    $(document).on('click', '[data-target="#updateModal"]', function(e) {
        e.preventDefault();
        var url = $(this).data('url');
        var id = $(this).data('id');
        var value = $(this).data('value');
        var bankId = $(this).data('bank_id') || null;

        $('#updatePaymentForm').attr('action', url);
        $('#update_value').val(value);
        if (bankId) {
            $('#update_bank_id').val(bankId);
        }
    });

    // Handle delete
    function Delete(id) {
        Swal.fire({
            title: "{{ __('alerts.are_you_sure') }}",
            text: "{{ __('alerts.not_revert_information') }}",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: "{{ __('alerts.confirm') }}",
            cancelButtonText: "{{ __('alerts.cancel') }}",
        }).then((result) => {
            if (result.isConfirmed) {
                var url = '{{ route('invoice_payments.destroy', ':id') }}';
                url = url.replace(':id', id);
                var token = '{{ csrf_token() }}';
                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': token,
                        'X-Requested-With': 'XMLHttpRequest',
                    }
                });
                $.ajax({
                    url: url,
                    type: 'POST',
                    success: function(response, textStatus, xhr) {
                        if (xhr.status == 200) {
                            Swal.fire({
                                title: "{{ __('alerts.done') }}",
                                icon: 'success',
                                showConfirmButton: false,
                                timer: 3000,
                                timerProgressBar: true,
                            });
                            location.reload();
                        }
                    },
                    error: function(xhr) {
                        Swal.fire({
                            title: "{{ __('alerts.error') }}",
                            text: xhr.responseJSON?.msg || 'حدث خطأ',
                            icon: 'error',
                        });
                    }
                });
            }
        });
    }
</script>
@endpush
