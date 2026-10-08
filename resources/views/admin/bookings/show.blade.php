@extends('layouts.admin')
@section('content')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <style>
        .booking-show-page { direction: rtl; }

        /* Page Header */
        .booking-page-header {
            background: linear-gradient(135deg, #1e3a5f 0%, #2d5a87 50%, #3d7ab5 100%);
            border-radius: var(--radius);
            padding: 1.75rem 2rem;
            margin-bottom: 1.75rem;
            box-shadow: 0 6px 24px rgba(30, 58, 95, 0.25);
            border: 1px solid transparent;
        }

        [data-theme="dark"] .booking-page-header {
            background: linear-gradient(135deg, #0d1e38 0%, #14284b 50%, #1e3a66 100%);
            border-color: var(--border-color);
            box-shadow: 0 6px 24px rgba(0, 0, 0, 0.4);
        }

        .booking-page-header-inner {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            position: relative;
            z-index: 1;
        }
        .booking-header-icon {
            width: 56px;
            height: 56px;
            background: rgba(255,255,255,0.18);
            border: 2px solid rgba(255,255,255,0.35);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .booking-header-icon i { font-size: 1.75rem; color: #fff; }
        .booking-page-title {
            color: #fff;
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0 0 0.25rem 0;
        }
        .booking-page-subtitle {
            color: rgba(255,255,255,0.85);
            font-size: 0.95rem;
            margin: 0;
        }
        .booking-header-actions { display: flex; flex-wrap: wrap; align-items: center; gap: 0.6rem; }
        .booking-btn-invoice {
            padding: 0.6rem 1.25rem;
            border-radius: 10px;
            font-weight: 700;
            font-size: 0.95rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: transform 0.2s, box-shadow 0.2s;
            border: 1px solid transparent;
        }
        .booking-btn-invoice--view {
            background: var(--success);
            color: #fff;
        }
        .booking-btn-invoice--view:hover { color: #fff; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(16,185,129,0.4); text-decoration: none; }
        .booking-btn-invoice--create {
            background: var(--primary);
            color: #fff;
        }
        .booking-btn-invoice--create:hover { color: #fff; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(59,130,246,0.4); text-decoration: none; }
        .booking-back-btn {
            background: var(--bg-card);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
            padding: 0.6rem 1.25rem;
            border-radius: 10px;
            font-weight: 700;
            font-size: 0.95rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .booking-back-btn:hover { color: var(--primary); text-decoration: none; transform: translateX(-3px); box-shadow: 0 4px 12px rgba(0,0,0,0.15); }

        /* Info Cards */
        .booking-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 1rem;
            margin-top: 1.5rem;
        }
        .booking-info-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 1.25rem;
            background: #ffffff;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-sm);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        [data-theme="dark"] .booking-info-item {
            background: var(--bg-card) !important;
            border-color: var(--border-color) !important;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.25) !important;
        }
        .booking-info-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 14px rgba(0,0,0,0.08);
        }
        .booking-info-icon {
            width: 46px;
            height: 46px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            color: #fff;
            flex-shrink: 0;
        }
        .booking-info-icon--blue { background: #0d6efd; }
        .booking-info-icon--green { background: #198754; }
        .booking-info-icon--orange { background: #fd7e14; }
        .booking-info-icon--teal { background: #0dcaf0; }
        .booking-info-icon--purple { background: #6f42c1; }
        .booking-info-icon--indigo { background: #6610f2; }
        .booking-info-text { min-width: 0; }
        .booking-info-label {
            display: block;
            font-size: 0.78rem;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin-bottom: 0.25rem;
        }
        .booking-info-value {
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        /* Section Cards */
        .booking-section-card {
            background: var(--bg-card);
            border-radius: 14px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-color);
            margin-bottom: 1.75rem;
            overflow: hidden;
            transition: background-color 0.25s ease, border-color 0.25s ease;
        }
        [data-theme="dark"] .booking-section-card {
            background: var(--bg-card) !important;
            border-color: var(--border-color) !important;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.25) !important;
        }
        .booking-section-head {
            padding: 1.1rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 0.75rem;
            border-bottom: 1px solid var(--border-color);
            transition: background-color 0.25s ease;
        }
        .booking-section-head--blue {
            background: #eff6ff;
            border-bottom: 2px solid #bfdbfe;
        }
        [data-theme="dark"] .booking-section-head--blue {
            background: rgba(59, 130, 246, 0.12) !important;
            border-bottom: 2px solid var(--border-color) !important;
        }
        .booking-section-head--teal {
            background: #f0fdf4;
            border-bottom: 2px solid #bbf7d0;
        }
        [data-theme="dark"] .booking-section-head--teal {
            background: rgba(14, 165, 233, 0.12) !important;
            border-bottom: 2px solid var(--border-color) !important;
        }
        .booking-section-head--green {
            background: #ecfdf5;
            border-bottom: 2px solid #a7f3d0;
        }
        [data-theme="dark"] .booking-section-head--green {
            background: rgba(52, 211, 153, 0.12) !important;
            border-bottom: 2px solid var(--border-color) !important;
        }
        .booking-section-title {
            margin: 0;
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .booking-section-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            color: #fff;
        }
        .booking-section-icon--blue { background: #0d6efd; }
        .booking-section-icon--teal { background: #0dcaf0; }
        .booking-section-icon--green { background: #198754; }
        .booking-section-badge {
            background: var(--primary);
            color: #fff;
            padding: 0.4rem 0.95rem;
            border-radius: 20px;
            font-weight: 700;
            font-size: 0.85rem;
        }
        .booking-section-body {
            padding: 1.5rem;
            background: var(--bg-card);
            color: var(--text-primary);
        }
        [data-theme="dark"] .booking-section-body {
            background: var(--bg-card) !important;
            color: var(--text-primary) !important;
        }

        .delivery-policies-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        .delivery-policies-table thead {
            background: linear-gradient(135deg, #1e3a5f 0%, #2d5a87 100%);
            color: #fff;
        }
        [data-theme="dark"] .delivery-policies-table thead {
            background: var(--bg-card-hover) !important;
            color: var(--text-secondary) !important;
        }
        .delivery-policies-table thead th {
            padding: 1rem 1.25rem;
            text-align: right;
            font-weight: 600;
            font-size: 0.9rem;
            border: none;
            color: #fff;
        }
        [data-theme="dark"] .delivery-policies-table thead th {
            color: var(--text-secondary) !important;
            border-bottom: 1px solid var(--border-color) !important;
        }
        .delivery-policies-table thead th:first-child { border-top-right-radius: 10px; }
        .delivery-policies-table thead th:last-child { border-top-left-radius: 10px; }
        .delivery-policies-table tbody td {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid var(--border-subtle);
            vertical-align: middle;
            color: var(--text-primary);
        }
        .delivery-policies-table tbody tr:hover { background: var(--bg-card-hover); }

        .booking-empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: var(--text-muted);
        }
        .booking-empty-state i {
            font-size: 3.5rem;
            color: var(--border-color);
            margin-bottom: 1rem;
        }
        .booking-empty-state h5 { font-weight: 700; color: var(--text-primary); margin-bottom: 0.5rem; }
        .booking-empty-state p { margin: 0; font-size: 0.95rem; color: var(--text-muted); }

        @media (max-width: 768px) {
            .booking-header-icon { width: 48px; height: 48px; }
            .booking-header-icon i { font-size: 1.4rem; }
            .booking-page-title { font-size: 1.25rem; }
            .booking-info-grid { grid-template-columns: 1fr; }
        }
</style>

    <div class="container booking-show-page">
        @include('layouts.includes.breadcrumb', ['page' => __('main.transportations')])

        <!-- Page Header -->
        <div class="booking-page-header">
            <div class="booking-page-header-inner">
                <div class="d-flex align-items-center">
                    
                    <div class="mr-3">
                        <h1 class="booking-page-title">{{ __('main.company') }}: {{ $booking->company->name ?? '-' }}</h1>
                        <p class="booking-page-subtitle">{{ __('admin.booking_number') }}: {{ $booking->booking_number ?? '-' }}</p>
                    </div>
                </div>
                <div class="booking-header-actions">
                    @if($booking->invoice)
                        <a href="{{ route('booking-invoices.show', $booking->invoice) }}" class="booking-btn-invoice booking-btn-invoice--view">
                            <i class="fas fa-file-invoice"></i>
                            عرض الفاتورة
                        </a>
                    @else
                        <a href="{{ route('booking-invoices.create', $booking) }}" class="booking-btn-invoice booking-btn-invoice--create">
                            <i class="fas fa-file-invoice-dollar"></i>
                            انشاء فاتورة
                        </a>
                    @endif
                    @if (auth()->user()->hasPermissionTo('bookings.update'))
                        <a href="{{ route('bookings.edit', $booking->id) }}" class="booking-btn-invoice booking-btn-invoice--create">
                            <i class="fas fa-edit"></i>
                            تعديل الطلب
                        </a>
                    @endif
                    <a href="{{ route('bookings.index') }}" class="booking-back-btn">
                        <i class="fas fa-arrow-right"></i>
                        {{ __('main.back') }}
                    </a>
                </div>
            </div>

            <div class="booking-info-grid">
                <div class="booking-info-item">
                    <span class="booking-info-icon booking-info-icon--blue"><i class="fas fa-hashtag"></i></span>
                    <div class="booking-info-text">
                        <span class="booking-info-label">{{ __('admin.booking_number') }}</span>
                        <span class="booking-info-value">{{ $booking->booking_number ?? __('main.not_found') }}</span>
                    </div>
                </div>
                <div class="booking-info-item">
                    <span class="booking-info-icon booking-info-icon--green"><i class="fas fa-certificate"></i></span>
                    <div class="booking-info-text">
                        <span class="booking-info-label">{{ __('admin.certificate_number') }}</span>
                        <span class="booking-info-value">{{ $booking->certificate_number ?? __('main.not_found') }}</span>
                    </div>
                </div>
                <div class="booking-info-item">
                    <span class="booking-info-icon booking-info-icon--orange"><i class="fas fa-ship"></i></span>
                    <div class="booking-info-text">
                        <span class="booking-info-label">{{ __('admin.shipping_agent') }}</span>
                        <span class="booking-info-value">{{ $booking->shippingAgent?->title ?? __('main.not_found') }}</span>
                    </div>
                </div>
                <div class="booking-info-item">
                    <span class="booking-info-icon booking-info-icon--teal"><i class="fas fa-user-tie"></i></span>
                    <div class="booking-info-text">
                        <span class="booking-info-label">{{ __('admin.responsible_employee') }}</span>
                        <span class="booking-info-value">{{ $booking->employee_name ?? __('main.not_found') }}</span>
                    </div>
                </div>
                <div class="booking-info-item">
                    <span class="booking-info-icon booking-info-icon--purple"><i class="fas fa-tasks"></i></span>
                    <div class="booking-info-text">
                        <span class="booking-info-label">{{ __('admin.type_of_action') }}</span>
                        <span class="booking-info-value">{{ __('actions.' . TypeOfAction($booking->type_of_action)) ?? __('main.not_found') }}</span>
                    </div>
                </div>
                <div class="booking-info-item">
                    <span class="booking-info-icon booking-info-icon--indigo"><i class="fas fa-boxes"></i></span>
                    <div class="booking-info-text">
                        <span class="booking-info-label">{{ __('admin.containers_number') }}</span>
                        <span class="booking-info-value">{{ $booking->bookingContainers->count() }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Containers Section -->
        <div class="booking-section-card">
            <div class="booking-section-head booking-section-head--blue">
                <h2 class="booking-section-title">
                    <span class="booking-section-icon booking-section-icon--blue"><i class="fas fa-boxes"></i></span>
                    {{ __('main.containers') }}
                </h2>
                <span class="booking-section-badge">{{ $booking->bookingContainers->count() }}</span>
            </div>
            <div class="booking-section-body" style="direction:rtl">
                @include('admin.components.booking-containers.table')
            </div>
        </div>

        <!-- Services Section -->
        <div class="booking-section-card">
            <div class="booking-section-head booking-section-head--teal">
                <h2 class="booking-section-title">
                    <span class="booking-section-icon booking-section-icon--teal"><i class="fas fa-concierge-bell"></i></span>
                    {{ __('main.services') }}
                </h2>
                <span class="booking-section-badge">{{ $booking->booking_services_count + $booking->expenses_count + ($booking->receipts?->whereNull('booking_service_id')->count() ?? 0) }}</span>
            </div>
            <div class="booking-section-body" style="direction:rtl">
                @include('admin.components.booking-services.table', [
                    'booking_services' => $booking->bookingServices ?? collect(),
                    'expensesServices' => $booking->expenses ?? collect(),
                    'supplierReceipts' => ($booking->receipts ?? collect())
                        ->whereNull('booking_service_id')
                        ->values(),
                    'booking' => $booking,
                ])
            </div>
        </div>

        <!-- Delivery Policies Section -->
        <div class="booking-section-card">
            <div class="booking-section-head booking-section-head--green">
                <h2 class="booking-section-title">
                    <span class="booking-section-icon booking-section-icon--green"><i class="fas fa-file-invoice-dollar"></i></span>
                    {{ __('main.delivery_policies') }}
                </h2>
                <span class="booking-section-badge">{{ $deliveryPolices->count() ?? 0 }}</span>
            </div>
            <div class="booking-section-body" style="direction:rtl">
                @if($deliveryPolices && $deliveryPolices->count() > 0)
                    <div class="table-responsive">
                        <table class="delivery-policies-table" id="deliveryPoliciesTable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>{{ __('main.container') }}</th>
                                    <th>{{ __('admin.departure') }}</th>
                                    <th>{{ __('admin.loading') }}</th>
                                    <th>{{ __('admin.aging') }}</th>
                                    <th>{{ __('admin.value') }}</th>
                                    <th>{{ __('main.date') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($deliveryPolices as $policy)
                                    <tr>
                                        <td><strong>{{ $policy->id }}</strong></td>
                                        <td>
                                            <span class="badge badge-info">
                                                {{ $policy->booking_containers->first()->container_no ?? __('main.container_not_written_yet') }}
                                            </span>
                                        </td>
                                        <td>{{ $policy->booking_containers->first()->departure->title ?? __('main.not_found') }}</td>
                                        <td>{{ $policy->booking_containers->first()->loading->title ?? __('main.not_found') }}</td>
                                        <td>{{ $policy->booking_containers->first()->aging->title ?? __('main.not_found') }}</td>
                                        <td>
                                            <span class="font-weight-bold text-success" style="font-size: 1.1rem;">
                                                {{ number_format($policy->money_transfer->value ?? 0, 2) }} {{ __('main.currency') }}
                                            </span>
                                        </td>
                                        <td>{{ $policy->money_transfer->date ?? __('main.not_found') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="booking-empty-state">
                        <i class="fas fa-inbox"></i>
                        <h5>{{ __('main.no_data_available') }}</h5>
                        <p>{{ __('alerts.no_data_found') }}</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('js')
    <script>
        $(document).ready(function() {
            if ($('#deliveryPoliciesTable').length) {
                if ($.fn.DataTable.isDataTable('#deliveryPoliciesTable')) {
                    $('#deliveryPoliciesTable').DataTable().destroy();
                }
                $('#deliveryPoliciesTable').DataTable({
                    "language": { "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Arabic.json" },
                    "order": [[0, "desc"]],
                    "pageLength": 10,
                    "responsive": true,
                    "dom": '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
                });
            }
        });
    </script>
@endpush
