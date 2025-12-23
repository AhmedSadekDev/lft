@extends('layouts.admin')
@section('content')
    <style>
        .bookings-table {
            font-size: 14px;
        }
        .bookings-table thead th {
            background-color: #f8f9fa;
            color: #495057;
            font-weight: 600;
            border-bottom: 2px solid #dee2e6;
            padding: 12px;
            white-space: nowrap;
        }
        .bookings-table tbody td {
            padding: 12px;
            vertical-align: middle;
        }
        .bookings-table tbody tr:hover {
            background-color: #f8f9fa;
        }
        .filter-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .badge-custom {
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 12px;
        }
        .action-buttons {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }
        .action-buttons .btn {
            padding: 5px 10px;
            font-size: 12px;
        }
        .search-input-group {
            position: relative;
        }
        .search-input-group .form-control {
            padding-right: 40px;
        }
        .search-input-group .search-icon {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }
        .filter-badge {
            display: inline-block;
            margin: 2px;
            padding: 4px 8px;
            background: #e9ecef;
            border-radius: 4px;
            font-size: 12px;
        }
        .pagination-wrapper {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 0;
            border-top: 1px solid #e9ecef;
            margin-top: 20px;
        }
        .pagination-info {
            color: #6c757d;
            font-size: 14px;
        }
        .pagination {
            margin: 0;
        }
        .pagination .page-link {
            color: #495057;
            border: 1px solid #dee2e6;
            padding: 8px 12px;
            margin: 0 2px;
            border-radius: 4px;
            transition: all 0.3s;
        }
        .pagination .page-link:hover {
            background-color: #e9ecef;
            border-color: #adb5bd;
            color: #495057;
        }
        .pagination .page-item.active .page-link {
            background-color: #007bff;
            border-color: #007bff;
            color: #fff;
            font-weight: 600;
        }
        .pagination .page-item.disabled .page-link {
            color: #6c757d;
            pointer-events: none;
            background-color: #fff;
            border-color: #dee2e6;
            opacity: 0.5;
        }
        .custom-pagination {
            display: flex;
            align-items: center;
        }
        .pagination-list {
            display: flex;
            list-style: none;
            padding: 0;
            margin: 0;
            gap: 5px;
            align-items: center;
        }
        .pagination-item {
            margin: 0;
        }
        .pagination-link {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 8px 14px;
            min-width: 40px;
            height: 40px;
            color: #495057;
            background-color: #fff;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .pagination-link:hover:not(.disabled) {
            background-color: #e9ecef;
            border-color: #adb5bd;
            color: #495057;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .pagination-item.active .pagination-link {
            background-color: #007bff;
            border-color: #007bff;
            color: #fff;
            font-weight: 600;
            box-shadow: 0 2px 6px rgba(0,123,255,0.3);
        }
        .pagination-item.active .pagination-link:hover {
            background-color: #0056b3;
            border-color: #0056b3;
            transform: translateY(-1px);
        }
        .pagination-link.disabled,
        .pagination-item.disabled .pagination-link {
            color: #6c757d;
            background-color: #f8f9fa;
            border-color: #e9ecef;
            cursor: not-allowed;
            opacity: 0.6;
        }
        .pagination-prev,
        .pagination-next {
            gap: 6px;
            font-weight: 500;
        }
        .pagination-prev i,
        .pagination-next i {
            font-size: 12px;
        }
        .pagination-item.disabled span {
            cursor: not-allowed;
        }
        .table-container {
            min-height: 400px;
        }
        @media (max-width: 768px) {
            .pagination-wrapper {
                flex-direction: column;
                gap: 15px;
            }
            .pagination-info {
                text-align: center;
            }
            .pagination-list {
                flex-wrap: wrap;
                justify-content: center;
            }
            .pagination-link {
                padding: 6px 10px;
                min-width: 36px;
                height: 36px;
                font-size: 13px;
            }
            .pagination-prev span,
            .pagination-next span {
                display: none;
            }
        }
    </style>

    <div class="container-fluid">
        @include('layouts.includes.breadcrumb', ['page' => __('main.bookings')])

        <!-- Filters Card -->
        <div class="card filter-card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-filter"></i> {{ __('admin.search') }} و {{ __('admin.filter') }}
                </h5>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('bookings.index') }}" id="filterForm">
                    <div class="row">
                        <!-- Search Input -->
                        <div class="col-md-3 mb-3">
                            <label class="form-label">{{ __('admin.search') }}</label>
                            <div class="search-input-group">
                                <input type="text" name="search" class="form-control"
                                        value="{{ request('search') }}"
                                        placeholder="{{ __('admin.search') }}...">
                                <i class="fas fa-search search-icon"></i>
                            </div>
                        </div>

                        <!-- Date From Filter -->
                        <div class="col-md-2 mb-3">
                            <label class="form-label">{{ __('admin.date_from') ?? 'تاريخ من' }}</label>
                            <input type="date" name="date_from" class="form-control"
                                    value="{{ request('date_from') }}">
                        </div>

                        <!-- Date To Filter -->
                        <div class="col-md-2 mb-3">
                            <label class="form-label">{{ __('admin.date_to') ?? 'تاريخ إلى' }}</label>
                            <input type="date" name="date_to" class="form-control"
                                    value="{{ request('date_to') }}">
                        </div>

                        <!-- Company Filter -->
                        <div class="col-md-2 mb-3">
                            <label class="form-label">{{ __('main.company') }}</label>
                            <select name="company" class="form-control">
                                <option value="">{{ __('admin.all') }}</option>
                                @foreach ($companies as $company)
                                    <option value="{{ $company->id }}"
                                            {{ request('company') == $company->id ? 'selected' : '' }}>
                                        {{ $company->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Tax Status Filter -->
                        <div class="col-md-2 mb-3">
                            <label class="form-label">{{ __('admin.taxed_status') }}</label>
                            <select name="tax_status" class="form-control">
                                <option value="">{{ __('admin.all') }}</option>
                                <option value="1" {{ request('tax_status') == '1' ? 'selected' : '' }}>
                                    {{ __('admin.taxed') }}
                                </option>
                                <option value="0" {{ request('tax_status') == '0' ? 'selected' : '' }}>
                                    {{ __('admin.not_taxed') }}
                                </option>
                            </select>
                        </div>

                        <!-- Invoice Status Filter -->
                        <div class="col-md-2 mb-3">
                            <label class="form-label">حالة الفاتورة</label>
                            <select name="invoice_status" class="form-control">
                                <option value="">{{ __('admin.all') }}</option>
                                <option value="1" {{ request('invoice_status') == '1' ? 'selected' : '' }}>
                                    تم إنشاء فاتورة
                                </option>
                                <option value="0" {{ request('invoice_status') == '0' ? 'selected' : '' }}>
                                    لم يتم إنشاء فاتورة
                                </option>
                            </select>
                        </div>

                        <!-- Action Buttons -->
                        <div class="col-md-1 mb-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search"></i> {{ __('admin.search') }}
                            </button>
                        </div>
                    </div>

                    <!-- Active Filters -->
                    @if(request()->hasAny(['search', 'arrival_date', 'company', 'tax_status', 'invoice_status']))
                        <div class="row mt-2">
                            <div class="col-12">
                                <small class="text-muted">الفلاتر النشطة:</small>
                                @if(request('search'))
                                    <span class="filter-badge">
                                        بحث: {{ request('search') }}
                                        <a href="{{ route('bookings.index', request()->except('search')) }}" class="ml-2 text-danger">
                                            <i class="fas fa-times"></i>
                                        </a>
                                    </span>
                                @endif
                                @if(request('arrival_date'))
                                    <span class="filter-badge">
                                        تاريخ: {{ request('arrival_date') }}
                                        <a href="{{ route('bookings.index', request()->except('arrival_date')) }}" class="ml-2 text-danger">
                                            <i class="fas fa-times"></i>
                                        </a>
                                    </span>
                                @endif
                                @if(request('company'))
                                    <span class="filter-badge">
                                        شركة: {{ $companies->find(request('company'))->name ?? '' }}
                                        <a href="{{ route('bookings.index', request()->except('company')) }}" class="ml-2 text-danger">
                                            <i class="fas fa-times"></i>
                                        </a>
                                    </span>
                                @endif
                                @if(request('tax_status') !== null && request('tax_status') !== '')
                                    <span class="filter-badge">
                                        ضريبة: {{ request('tax_status') == '1' ? 'معفى' : 'غير معفى' }}
                                        <a href="{{ route('bookings.index', request()->except('tax_status')) }}" class="ml-2 text-danger">
                                            <i class="fas fa-times"></i>
                                        </a>
                                    </span>
                                @endif
                                @if(request('invoice_status') !== null && request('invoice_status') !== '')
                                    <span class="filter-badge">
                                        فاتورة: {{ request('invoice_status') == '1' ? 'تم الإنشاء' : 'لم يتم الإنشاء' }}
                                        <a href="{{ route('bookings.index', request()->except('invoice_status')) }}" class="ml-2 text-danger">
                                            <i class="fas fa-times"></i>
                                        </a>
                                    </span>
                                @endif
                                <a href="{{ route('bookings.index') }}" class="btn btn-sm btn-outline-danger ml-2">
                                    <i class="fas fa-times"></i> إزالة جميع الفلاتر
                                </a>
                            </div>
                        </div>
                    @endif
                </form>
            </div>
        </div>

        <!-- Bookings Table Card -->
        <div class="card card-custom">
            <div class="card-header flex-wrap py-4">
                <div class="card-title">
                    <h3 class="card-label">
                        {{ __('main.bookings') }}
                        <span class="text-muted font-size-sm ml-2">
                            ({{ $bookings->total() }} {{ __('admin.result') }})
                        </span>
                    </h3>
                </div>
                <div class="card-toolbar">
                    @if (auth()->user()->hasPermissionTo('bookings.create'))
                        <a href="{{ route('bookings.create') }}" class="btn btn-primary font-weight-bolder">
                            <i class="fas fa-plus"></i> {{ __('admin.add_new_booking') }}
                        </a>
                    @endif
                    @if($bookings->count() > 0)
                        <a class="btn btn-success ml-2"
                            href="{{ route('booking_container.export', ['ids' => implode(',', $bookings->pluck('id')->toArray())]) }}"
                            title="{{ __('admin.export') }}">
                            <i class="fas fa-download"></i> {{ __('admin.export') }}
                        </a>
                    @endif
                </div>
            </div>

            <div class="card-body">
                @if($bookings->count() > 0)
                    <div class="table-container">
                        <div class="table-responsive">
                            <table class="table table-hover bookings-table no-datatable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>{{ __('admin.company_name') }}</th>
                                    <th>{{ __('admin.responsible_employee') }}</th>
                                    <th>{{ __('main.factory') }}</th>
                                    <th>{{ __('admin.booking_number') }}</th>
                                    <th>{{ __('admin.taxed_status') }}</th>
                                    <th>حالة الفاتورة</th>
                                    <th>{{ __('admin.created_at') }}</th>
                                    <th>الفاتورة</th>
                                    <th>الملاحظات</th>
                                    <th>{{ __('admin.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($bookings as $booking)
                                    <tr>
                                        <td><strong>{{ $booking->id }}</strong></td>
                                        <td>{{ $booking->company->name ?? '__' }}</td>
                                        <td>{{ $booking->employee_name ?? '__' }}</td>
                                        <td>{{ $booking?->factory?->name ?? '__' }}</td>
                                        <td>
                                            <span class="badge badge-info badge-custom">
                                                {{ $booking->booking_number }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge badge-{{ $booking->company && $booking->company->taxed == 0 ? 'danger' : 'success' }} badge-custom">
                                                <i class="fa fa-{{ $booking->company && $booking->company->taxed == 0 ? 'xmark' : 'check' }}"></i>
                                                {{ $booking->taxed_invoice }}
                                            </span>
                                        </td>
                                        <td>
                                            @if(!is_null($booking->invoice?->invoice_number))
                                                <span class="badge badge-success badge-custom">
                                                    <i class="fas fa-check"></i> تم إنشاء فاتورة
                                                </span>
                                            @else
                                                <span class="badge badge-warning badge-custom">
                                                    <i class="fas fa-clock"></i> لم يتم إنشاء فاتورة
                                                </span>
                                            @endif
                                        </td>
                                        <td>{{ $booking->created_at ? \Carbon\Carbon::parse($booking->created_at)->format('Y-m-d') : '__' }}</td>
                                        <td>
                                            @if (is_null($booking->invoice?->invoice_number))
                                                @if ($booking->type_of_action != 2)
                                                    <a class="btn btn-sm btn-primary" href="{{ route('booking-invoices.create', $booking->id) }}">
                                                        <i class="fas fa-plus"></i> إنشاء فاتورة
                                                    </a>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            @else
                                                @if ($booking->type_of_action != 2)
                                                    <div class="d-flex flex-column gap-1">
                                                        <a href="{{ route('booking-invoices.edit', $booking->invoice->id) }}" class="btn btn-sm btn-warning">
                                                            <i class="fas fa-edit"></i> تعديل
                                                        </a>
                                                        <a href="{{ route('booking-invoices.show', ['booking_invoice' => $booking->invoice->id]) }}" class="btn btn-sm btn-info">
                                                            <i class="fas fa-eye"></i> عرض
                                                        </a>
                                                    </div>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('bookings.booking_notes', ['booking' => $booking->id]) }}" class="btn btn-sm btn-secondary">
                                                <i class="fas fa-sticky-note"></i> الملاحظات
                                            </a>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                @if (auth()->user()->hasPermissionTo('bookings.index'))
                                                    <a href="{{ route('bookings.show', $booking->id) }}"
                                                        class="btn btn-sm btn-light btn-hover-success"
                                                        title="{{ __('admin.show') }}">
                                                        <i class="fas fa-eye text-success"></i>
                                                    </a>
                                                @endif
                                                @if (auth()->user()->hasPermissionTo('bookings.update'))
                                                    <a href="{{ route('bookings.edit', $booking->id) }}"
                                                        class="btn btn-sm btn-light btn-hover-primary"
                                                        title="{{ __('admin.edit') }}">
                                                        <i class="fas fa-edit text-primary"></i>
                                                    </a>
                                                @endif
                                                @if (auth()->user()->hasPermissionTo('bookings.delete'))
                                                    <button class="btn btn-sm btn-light btn-hover-danger delete"
                                                            onclick="Delete('{{ $booking->id }}')"
                                                            title="{{ __('admin.delete') }}">
                                                        <i class="fas fa-trash text-danger"></i>
                                                    </button>
                                                @endif
                                                <a href="{{ route('bookings.booking_papers', ['booking' => $booking->id]) }}"
                                                    class="btn btn-sm btn-light btn-hover-info"
                                                    title="{{ __('admin.papers') }}">
                                                    <i class="fas fa-file text-info"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        </div>

                        <!-- Custom Pagination -->
                        <div class="pagination-wrapper">
                            <div class="pagination-info">
                                <i class="fas fa-info-circle"></i>
                                عرض {{ $bookings->firstItem() ?? 0 }} إلى {{ $bookings->lastItem() ?? 0 }} من {{ $bookings->total() }} نتيجة
                            </div>
                            <div class="custom-pagination">
                                @php
                                    $currentPage = $bookings->currentPage();
                                    $lastPage = $bookings->lastPage();
                                    $queryParams = request()->query();
                                @endphp

                                <ul class="pagination-list">
                                    {{-- Previous Button --}}
                                    <li class="pagination-item {{ $currentPage == 1 ? 'disabled' : '' }}">
                                        @if($currentPage > 1)
                                            <a href="{{ $bookings->appends($queryParams)->url($currentPage - 1) }}" class="pagination-link pagination-prev">
                                                <i class="fas fa-chevron-right"></i>
                                                <span>السابق</span>
                                            </a>
                                        @else
                                            <span class="pagination-link pagination-prev disabled">
                                                <i class="fas fa-chevron-right"></i>
                                                <span>السابق</span>
                                            </span>
                                        @endif
                                    </li>

                                    {{-- Page Numbers --}}
                                    @if($lastPage <= 7)
                                        {{-- Show all pages if total pages <= 7 --}}
                                        @for($i = 1; $i <= $lastPage; $i++)
                                            <li class="pagination-item {{ $i == $currentPage ? 'active' : '' }}">
                                                <a href="{{ $bookings->appends($queryParams)->url($i) }}" class="pagination-link">
                                                    {{ $i }}
                                                </a>
                                            </li>
                                        @endfor
                                    @else
                                        {{-- Show first page --}}
                                        @if($currentPage > 3)
                                            <li class="pagination-item">
                                                <a href="{{ $bookings->appends($queryParams)->url(1) }}" class="pagination-link">1</a>
                                            </li>
                                            @if($currentPage > 4)
                                                <li class="pagination-item disabled">
                                                    <span class="pagination-link">...</span>
                                                </li>
                                            @endif
                                        @endif

                                        {{-- Show pages around current page --}}
                                        @for($i = max(1, $currentPage - 2); $i <= min($lastPage, $currentPage + 2); $i++)
                                            <li class="pagination-item {{ $i == $currentPage ? 'active' : '' }}">
                                                <a href="{{ $bookings->appends($queryParams)->url($i) }}" class="pagination-link">
                                                    {{ $i }}
                                                </a>
                                            </li>
                                        @endfor

                                        {{-- Show last page --}}
                                        @if($currentPage < $lastPage - 2)
                                            @if($currentPage < $lastPage - 3)
                                                <li class="pagination-item disabled">
                                                    <span class="pagination-link">...</span>
                                                </li>
                                            @endif
                                            <li class="pagination-item">
                                                <a href="{{ $bookings->appends($queryParams)->url($lastPage) }}" class="pagination-link">{{ $lastPage }}</a>
                                            </li>
                                        @endif
                                    @endif

                                    {{-- Next Button --}}
                                    <li class="pagination-item {{ $currentPage == $lastPage ? 'disabled' : '' }}">
                                        @if($currentPage < $lastPage)
                                            <a href="{{ $bookings->appends($queryParams)->url($currentPage + 1) }}" class="pagination-link pagination-next">
                                                <span>التالي</span>
                                                <i class="fas fa-chevron-left"></i>
                                            </a>
                                        @else
                                            <span class="pagination-link pagination-next disabled">
                                                <span>التالي</span>
                                                <i class="fas fa-chevron-left"></i>
                                            </span>
                                        @endif
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <p class="text-muted">{{ __('admin.no_data') }}</p>
                        @if (auth()->user()->hasPermissionTo('bookings.create'))
                            <a href="{{ route('bookings.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus"></i> {{ __('admin.add_new_booking') }}
                            </a>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('js')
    <script>
        (function($) {
            "use strict";

            // Auto submit form on filter change (optional)
            // $('select[name="company"], select[name="tax_status"], select[name="invoice_status"]').on('change', function() {
            //     $('#filterForm').submit();
            // });

            // Clear search on X click
            $('.search-input-group .form-control').on('input', function() {
                if ($(this).val() === '') {
                    // Optionally auto-submit when cleared
                }
            });
        })(jQuery);

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
                    var url = '{{ route('bookings.destroy', ':id') }}';
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
                        type: 'DELETE',
                        success: function(response) {
                            Swal.fire({
                                title: "{{ __('alerts.done') }}",
                                icon: 'success',
                                showConfirmButton: false,
                                timer: 2000,
                                timerProgressBar: true,
                            }).then(() => {
                                location.reload();
                            });
                        },
                        error: function() {
                            Swal.fire({
                                title: "{{ __('alerts.error') }}",
                                text: "حدث خطأ أثناء الحذف",
                                icon: 'error',
                            });
                        }
                    });
                }
            });
        }
    </script>
@endpush
