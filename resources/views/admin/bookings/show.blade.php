@extends('layouts.admin')
@section('content')
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <style>
        body {
            background: #f5f7fa;
        }

        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            padding: 2.5rem;
            margin-bottom: 2rem;
            color: white;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
            position: relative;
            overflow: hidden;
        }

        .page-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: pulse 15s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 0.5; }
            50% { transform: scale(1.1); opacity: 0.8; }
        }

        .page-header-content {
            position: relative;
            z-index: 1;
        }

        .page-header h1 {
            color: white;
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .page-header h1 i {
            font-size: 2.5rem;
            background: rgba(255,255,255,0.2);
            padding: 1rem;
            border-radius: 12px;
            backdrop-filter: blur(10px);
            display: inline-flex !important;
            align-items: center;
            justify-content: center;
            font-style: normal;
            font-weight: 900;
        }

        .info-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .info-card {
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            padding: 1.5rem;
            border: 1px solid rgba(255,255,255,0.2);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .info-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 4px;
            height: 100%;
            background: white;
            opacity: 0.8;
        }

        .info-card:hover {
            transform: translateY(-5px);
            background: rgba(255,255,255,0.25);
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }

        .info-card-icon {
            width: 50px;
            height: 50px;
            background: rgba(255,255,255,0.25);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: white;
        }

        .info-card-icon i {
            display: block !important;
            line-height: 1 !important;
            font-style: normal;
            font-weight: 900;
        }

        .info-card-label {
            color: rgba(255,255,255,0.9);
            font-size: 0.875rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-card-value {
            color: rgba(255,255,255,0.9);
            font-size: 1.25rem;
            font-weight: bold;
        }

        .section-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
            overflow: hidden;
            transition: all 0.3s ease;
            border: 1px solid #e4e6ef;
        }

        .section-card:hover {
            box-shadow: 0 10px 30px rgba(0,0,0,0.12);
            transform: translateY(-2px);
        }

        .section-header {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 1.5rem 2rem;
            border-bottom: 2px solid #e4e6ef;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .section-title {
            color: #144d99;
            font-size: 1.5rem;
            font-weight: bold;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .section-title i {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            display: inline-flex !important;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
            line-height: 1 !important;
            font-style: normal;
            font-weight: 900;
        }

        .section-badge {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: bold;
            font-size: 0.9rem;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .section-body {
            padding: 2rem;
        }

        .delivery-policies-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .delivery-policies-table thead {
            background: linear-gradient(135deg, #144d99 0%, #1e5ba8 100%);
            color: white;
        }

        .delivery-policies-table thead th {
            padding: 1rem 1.25rem;
            text-align: right;
            font-weight: 600;
            font-size: 0.95rem;
            border: none;
            white-space: nowrap;
        }

        .delivery-policies-table thead th:first-child {
            border-top-right-radius: 10px;
        }

        .delivery-policies-table thead th:last-child {
            border-top-left-radius: 10px;
        }

        .delivery-policies-table tbody td {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid #e4e6ef;
            vertical-align: middle;
        }

        .delivery-policies-table tbody tr {
            transition: all 0.2s ease;
        }

        .delivery-policies-table tbody tr:hover {
            background: #f8f9fa;
            transform: scale(1.01);
        }

        .delivery-policies-table tbody tr:last-child td:first-child {
            border-bottom-right-radius: 10px;
        }

        .delivery-policies-table tbody tr:last-child td:last-child {
            border-bottom-left-radius: 10px;
        }

        .back-btn {
            background: rgba(255,255,255,0.2);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.3);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .back-btn:hover {
            background: rgba(255,255,255,0.3);
            transform: translateX(-5px);
            color: white;
            text-decoration: none;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 5rem;
            color: #dee2e6;
            margin-bottom: 1.5rem;
            opacity: 0.5;
        }

        .empty-state h5 {
            color: #495057;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .empty-state p {
            color: #6c757d;
            margin: 0;
        }

        @media (max-width: 768px) {
            .info-cards {
                grid-template-columns: 1fr;
            }

            .page-header {
                padding: 1.5rem;
            }

            .page-header h1 {
                font-size: 1.5rem;
            }

            .section-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
        }
    </style>

    <div class="container">
        @include('layouts.includes.breadcrumb', ['page' => __('main.transportations')])

        <div class="content d-flex flex-column flex-column-fluid" id="kt_content">
            <!-- Page Header -->
            <div class="page-header" style="direction:rtl">
                <div class="page-header-content">
                    <div class="d-flex justify-content-between align-items-start flex-wrap">
                        <div style="flex: 1;">
                            <h1>
                                <i class="fas fa-building"></i>
                                <span>{{ __('main.company') }}: {{ $booking->company->name }}</span>
                            </h1>

                            <div class="info-cards">
                                <div class="info-card">
                                    <div class="info-card-icon">
                                        <i class="fas fa-hashtag"></i>
                                    </div>
                                    <div class="info-card-label">{{ __('admin.booking_number') }}</div>
                                    <div class="info-card-value">{{ $booking->booking_number ?? __('main.not_found') }}</div>
                                </div>

                                <div class="info-card">
                                    <div class="info-card-icon">
                                        <i class="fas fa-file-alt"></i>
                                    </div>
                                    <div class="info-card-label">{{ __('admin.certificate_number') }}</div>
                                    <div class="info-card-value">{{ $booking->certificate_number ?? __('main.not_found') }}</div>
                                </div>

                                <div class="info-card">
                                    <div class="info-card-icon">
                                        <i class="fas fa-ship"></i>
                                    </div>
                                    <div class="info-card-label">{{ __('admin.shipping_agent') }}</div>
                                    <div class="info-card-value">{{ $booking->shippingAgent?->title ?? __('main.not_found') }}</div>
                                </div>

                                <div class="info-card">
                                    <div class="info-card-icon">
                                        <i class="fas fa-user-tie"></i>
                                    </div>
                                    <div class="info-card-label">{{ __('admin.responsible_employee') }}</div>
                                    <div class="info-card-value">{{ $booking->employee_name ?? __('main.not_found') }}</div>
                                </div>

                                <div class="info-card">
                                    <div class="info-card-icon">
                                        <i class="fas fa-tasks"></i>
                                    </div>
                                    <div class="info-card-label">{{ __('admin.type_of_action') }}</div>
                                    <div class="info-card-value">{{ __('actions.' . TypeOfAction($booking->type_of_action)) ?? __('main.not_found') }}</div>
                                </div>

                                <div class="info-card">
                                    <div class="info-card-icon">
                                        <i class="fas fa-boxes"></i>
                                    </div>
                                    <div class="info-card-label">{{ __('admin.containers_number') }}</div>
                                    <div class="info-card-value">{{ $booking->bookingContainers->count() }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3 mt-md-0">
                            <a href="{{ route('bookings.index') }}" class="back-btn">
                                <i class="fas fa-arrow-right"></i>
                                <span>{{ __('main.back') }}</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Containers Section -->
            <div class="section-card">
                <div class="section-header">
                    <h2 class="section-title">
                        <i class="fas fa-boxes"></i>
                        <span>{{ __('main.containers') }}</span>
                    </h2>
                    <span class="section-badge">{{ $booking->bookingContainers->count() }}</span>
                </div>
                <div class="section-body" style="direction:rtl">
                    @include('admin.components.booking-containers.table')
                </div>
            </div>

            <!-- Services Section -->
            <div class="section-card">
                <div class="section-header">
                    <h2 class="section-title">
                        <i class="fas fa-concierge-bell"></i>
                        <span>{{ __('main.services') }}</span>
                    </h2>
                    <span class="section-badge">{{ $booking->bookingServices?->count() ?? 0 }}</span>
                </div>
                <div class="section-body" style="direction:rtl">
                    @include('admin.components.booking-services.table', [
                        'booking_services' => $booking->bookingServices ?? collect(),
                        'expensesServices' => $booking->expenses ?? collect(),
                        'booking' => $booking,
                    ])
                </div>
            </div>

            <!-- Delivery Policies Section -->
            <div class="section-card">
                <div class="section-header">
                    <h2 class="section-title">
                        <i class="fas fa-file-invoice-dollar"></i>
                        <span>{{ __('main.delivery_policies') }}</span>
                    </h2>
                    <span class="section-badge">{{ $deliveryPolices->count() ?? 0 }}</span>
                </div>
                <div class="section-body" style="direction:rtl">
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
                        <div class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <h5>{{ __('main.no_data_available') }}</h5>
                            <p>{{ __('alerts.no_data_found') }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
    <script>
        $(document).ready(function() {
            if ($('#deliveryPoliciesTable').length) {
                // Destroy existing DataTable if it exists
                if ($.fn.DataTable.isDataTable('#deliveryPoliciesTable')) {
                    $('#deliveryPoliciesTable').DataTable().destroy();
                }

                // Initialize DataTable
                $('#deliveryPoliciesTable').DataTable({
                    "language": {
                        "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Arabic.json"
                    },
                    "order": [[0, "desc"]],
                    "pageLength": 10,
                    "responsive": true,
                    "dom": '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
                });
            }
        });
    </script>
@endpush
