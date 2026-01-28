@extends('layouts.admin')
@section('content')
    <div class="container">
        @include('layouts.includes.breadcrumb', ['page' => __('main.companies')])
        <!--begin::Card-->
        <div class="card card-custom">
            <div class="card-header flex-wrap py-5">
                <div class="card-title">
                    <h3 class="card-label">{{ __('main.companies') }}</h3>
                </div>
                <div class="card-toolbar">
                    <!--begin::Search-->
                    <form method="GET" action="{{ route('companies.index') }}" class="d-flex align-items-center mr-4">
                        <input type="text" name="search" class="form-control form-control-solid w-250px mr-3"
                               placeholder="بحث بالاسم، البريد، الهاتف..."
                               value="{{ request('search') }}">
                        <button type="submit" class="btn btn-light-primary">
                            <i class="fas fa-search"></i>
                        </button>
                        @if(request('search'))
                            <a href="{{ route('companies.index') }}" class="btn btn-light ml-2">
                                <i class="fas fa-times"></i>
                            </a>
                        @endif
                    </form>
                    <!--end::Search-->
                    <!--begin::Button-->
                    @if (auth()->user()->hasPermissionTo('companies.create'))
                        <a href="{{ route('companies.create') }}" class="btn btn-primary font-weight-bolder mr-2">
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
                    @endif
                    <!--end::Button-->
                    <div class="p-2">
                        <a href="{{ route('companies.export-excel', ['search' => request('search')]) }}"
                           class="btn btn-primary">
                            <i class="fas fa-file-excel"></i> تصدير إلى Excel
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                @if($companies->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover table-separate table-head-custom no-datatable" id="table" style="width: 100%">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 60px">#</th>
                                <th style="min-width: 180px">معلومات الشركة</th>
                                <th style="min-width: 150px">التواصل</th>
                                <th style="min-width: 120px">الضريبي</th>
                                <th class="text-center" style="min-width: 120px">الحالة الضريبية</th>
                                <th class="text-center" style="min-width: 100px">نوع الفاتورة</th>
                                <th class="text-center" style="min-width: 120px">آخر فاتورة</th>
                                <th class="text-center" style="min-width: 150px">الرصيد المتبقي</th>
                                <th class="text-center" style="min-width: 100px">الملحقات</th>
                                <th class="text-center" style="min-width: 120px">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($companies as $company)
                                @php
                                    // حساب الرصيد المتبقي من جميع الفواتير
                                    $totalInvoices = 0;
                                    $totalPayments = 0;
                                    foreach ($company->bookings as $booking) {
                                        if ($booking->invoice) {
                                            $totalInvoices += $booking->invoice->invoice_total_after_discount ?? 0;
                                            $totalPayments += $booking->invoice->invoicePayments->sum('value') ?? 0;
                                        }
                                    }
                                    $remainingBalance = $totalInvoices - $totalPayments;
                                @endphp
                                <tr>
                                    <td class="text-center align-middle">
                                        <span class="text-muted font-weight-bold">{{ $company->id }}</span>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <span class="text-dark font-weight-bold mb-1">{{ $company->name }}</span>
                                            @if($company->address)
                                                <span class="text-muted font-size-sm">
                                                    <i class="fas fa-map-marker-alt text-muted mr-1"></i>
                                                    {{ $company->address }}
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            @if($company->email)
                                                <span class="text-dark-75 font-size-sm mb-1">
                                                    <i class="fas fa-envelope text-primary mr-1"></i>
                                                    <a href="mailto:{{ $company->email }}" class="text-primary">{{ $company->email }}</a>
                                                </span>
                                            @endif
                                            @if($company->phone)
                                                <span class="text-dark-75 font-size-sm">
                                                    <i class="fas fa-phone text-success mr-1"></i>
                                                    <a href="tel:{{ $company->phone }}" class="text-dark-75">{{ $company->phone }}</a>
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        @if($company->tax_no)
                                            <span class="badge badge-light-primary font-weight-bold">
                                                {{ $company->tax_no }}
                                            </span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center align-middle">
                                        <span class="badge badge-{{ $company->taxed == 0 ? 'danger' : 'success' }} font-weight-bold">
                                            <i class="fa fa-{{ $company->taxed == 0 ? 'times' : 'check' }} mr-1"></i>
                                            {{ $company->taxed_invoice }}
                                        </span>
                                        @if($company->invoices->count())
                                            <div class="mt-1">
                                                <a href="{{ route('bokkings.invoices', $company->id) }}" class="btn btn-sm btn-light-primary">
                                                    <i class="fas fa-file-invoice mr-1"></i>
                                                    {{ __('main.Tax_invoices') }}
                                                </a>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="text-center align-middle">
                                        <span class="badge badge-light-info font-weight-bold">
                                            {{ $company->bill_type == 1 ? __('admin.bill_type_invoice') : __('admin.bill_type_statement') }}
                                        </span>
                                    </td>
                                    <td class="text-center align-middle">
                                        @php
                                            $lastInvoice = $company->companyInvoices()->latest()->first();
                                        @endphp
                                        @if($lastInvoice && $lastInvoice->created_at)
                                            <span class="text-dark-75 font-weight-bold">
                                                {{ $lastInvoice->created_at->format('Y-m-d') }}
                                            </span>
                                            <div class="text-muted font-size-sm">
                                                {{ $lastInvoice->created_at->diffForHumans() }}
                                            </div>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center align-middle">
                                        <div class="d-flex flex-column align-items-center">
                                            <span class="badge badge-{{ $remainingBalance > 0 ? 'warning' : ($remainingBalance < 0 ? 'info' : 'success') }} badge-lg font-weight-bold mb-2">
                                                {{ number_format(abs($remainingBalance), 2) }} ج
                                            </span>
                                            @if($remainingBalance > 0)
                                                <span class="text-muted font-size-sm mb-2">مستحق</span>
                                            @elseif($remainingBalance < 0)
                                                <span class="text-muted font-size-sm mb-2">رصيد زائد</span>
                                            @else
                                                <span class="text-muted font-size-sm mb-2">مسدد</span>
                                            @endif
                                            @if(auth()->user()->hasPermissionTo('accounts.index'))
                                                <a href="{{ route('accounts.statement', $company->id) }}" class="btn btn-sm btn-light-primary">
                                                    <i class="fas fa-file-invoice mr-1"></i>
                                                    كشف حساب
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="text-center align-middle">
                                        @if (!is_null($company->attachments))
                                            <div class="d-flex justify-content-center">
                                                @if (is_array($company->attachments))
                                                    @foreach ($company->attachments as $attachment)
                                                        <a href="{{ url($attachment) }}" target="_blank" class="btn btn-icon btn-light btn-hover-primary btn-sm mr-1" title="عرض الملف">
                                                            <i class="fas fa-file-{{ pathinfo($attachment, PATHINFO_EXTENSION) == 'pdf' ? 'pdf text-danger' : 'image text-primary' }}"></i>
                                                        </a>
                                                    @endforeach
                                                @else
                                                    <a href="{{ url($company->attachments) }}" target="_blank" class="btn btn-icon btn-light btn-hover-primary btn-sm" title="عرض الملف">
                                                        <i class="fas fa-file-pdf text-danger"></i>
                                                    </a>
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center align-middle">
                                        <div class="dropdown dropdown-inline">
                                            <a href="javascript:;" class="btn btn-sm btn-light-primary btn-icon" data-toggle="dropdown">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </a>
                                            <div class="dropdown-menu dropdown-menu-sm dropdown-menu-right">
                                                @if (auth()->user()->hasPermissionTo('companies.update'))
                                                    <a class="dropdown-item" href="{{ route('companies.edit', $company->id) }}">
                                                        <i class="fas fa-edit text-primary mr-2"></i> تعديل
                                                    </a>
                                                @endif
                                                @if (auth()->user()->hasPermissionTo('transportations.create'))
                                                    <a class="dropdown-item" href="{{ route('companyTransportations.index', ['company_id' => $company->id]) }}">
                                                        <i class="fas fa-plus text-success mr-2"></i> إضافة عرض سعر
                                                    </a>
                                                @endif
                                                @if (auth()->user()->hasPermissionTo('services.create'))
                                                    <a class="dropdown-item" href="{{ route('companyServices.index', ['company' => $company]) }}">
                                                        <i class="fas fa-cog text-info mr-2"></i> الخدمات
                                                    </a>
                                                @endif
                                                @if(auth()->user()->hasPermissionTo('accounts.index'))
                                                    <a class="dropdown-item" href="{{ route('accounts.statement', $company->id) }}">
                                                        <i class="fas fa-file-invoice text-warning mr-2"></i> كشف حساب
                                                    </a>
                                                @endif
                                                <div class="dropdown-divider"></div>
                                                @if (auth()->user()->hasPermissionTo('companies.delete'))
                                                    <a class="dropdown-item text-danger" href="javascript:;" onclick="Delete('{{ $company->id }}')">
                                                        <i class="fas fa-trash mr-2"></i> حذف
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
                @else
                    <div class="text-center py-10">
                        <i class="fas fa-building fa-3x text-muted mb-4"></i>
                        <h4 class="text-muted">لا توجد شركات</h4>
                        @if (auth()->user()->hasPermissionTo('companies.create'))
                            <a href="{{ route('companies.create') }}" class="btn btn-primary mt-3">
                                <i class="fas fa-plus mr-2"></i> إضافة شركة جديدة
                            </a>
                        @endif
                    </div>
                @endif
                @if($companies->hasPages())
                <!--begin::Pagination-->
                <div class="card-footer">
                    <div class="d-flex justify-content-between align-items-center flex-wrap">
                        <div class="mr-3">
                            <span class="text-muted font-weight-bold">
                                عرض <span class="text-dark">{{ $companies->firstItem() ?? 0 }}</span>
                                إلى <span class="text-dark">{{ $companies->lastItem() ?? 0 }}</span>
                                من <span class="text-dark">{{ $companies->total() }}</span> نتيجة
                            </span>
                        </div>
                        <div>
                            {{ $companies->links() }}
                        </div>
                    </div>
                </div>
                <!--end::Pagination-->
                @endif
            </div>
        </div>
        <!--end::Card-->
    </div>

    @if (auth()->user()->hasPermissionTo('companies.index'))
        <!-- Creates the bootstrap modal where the Note Of Transaction For users will appear -->
        <div class="modal fade" id="attachmentModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h6>{{ __('admin.attachment') }}</h6>
                    </div>
                    <div class="modal-body" id="attachment_preview">

                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection
@push('css')
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

        .table-separate tbody tr:first-child td:first-child {
            border-top-right-radius: 8px;
            border-bottom-right-radius: 8px;
        }

        .table-separate tbody tr:first-child td:last-child {
            border-top-left-radius: 8px;
            border-bottom-left-radius: 8px;
        }

        .badge-lg {
            padding: 8px 16px;
            font-size: 14px;
        }

        .dropdown-menu {
            min-width: 200px;
        }

        .dropdown-item {
            padding: 10px 20px;
            transition: all 0.2s;
        }

        .dropdown-item:hover {
            background-color: #f3f6f9;
            padding-right: 25px;
        }

        .dropdown-item i {
            width: 20px;
        }
    </style>
@endpush
@push('js')
    <script>
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
                    var url = '{{ route('companies.destroy', ':id') }}';
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
                        type: 'delete',
                        success: function(response, textStatus, xhr) {
                            console.log(response, xhr.status);
                            if (xhr.status == 200) {
                                Swal.fire({
                                    title: "{{ __('alerts.done') }}",
                                    icon: 'success',
                                    showConfirmButton: false,
                                    timer: 3000,
                                    timerProgressBar: true,
                                });
                                location.reload();
                                //getNotify();
                            }
                        }
                    });
                }
            });
        }

        function openFile(attach) {
            $('#attachment_preview').html(`<embed src="${attach}"  frameborder="0" width="100%" height="400px">`)
            $('#attachmentModal').modal('show');
        }
    </script>
@endpush

