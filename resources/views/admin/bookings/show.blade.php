@extends('layouts.admin')
@section('content')
    <style>
        body {
            text-align: start;
        }

        .card-title {
            font-family: "bold";
            color: #144d99;
            font-size: 1.5rem;
            margin-bottom: 0;
            display: flex;
            align-items: center;
        }

        .title_name {
            font-family: "semibold";
        }

        .booking-info-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
            color: white;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .booking-info-card h2 {
            color: white;
            margin-bottom: 1.5rem;
            font-size: 1.75rem;
        }

        .data {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .data li {
            background: white;
            padding: 1.25rem;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border-right: 4px solid #144d99;
            transition: all 0.3s ease;
        }

        .data li:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.12);
        }

        .data li h4 {
            font-family: "semibold";
            color: #6c757d;
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .data li p {
            font-family: "regular";
            color: #212529;
            font-size: 1.1rem;
            font-weight: 600;
            margin: 0;
        }

        .data li .value {
            color: #144d99;
            font-size: 1.25rem;
        }

        h3 {
            font-family: "bold";
            margin-bottom: 1rem;
            color: #144d99;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem;
            border-bottom: 2px solid #e4e6ef;
            background: #f8f9fa;
        }

        .card-body {
            padding: 1.5rem;
        }

        .delivery-policies-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 1rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border-radius: 8px;
            overflow: hidden;
        }

        .delivery-policies-table thead {
            background: linear-gradient(135deg, #144d99 0%, #1e5ba8 100%);
            color: white;
        }

        .delivery-policies-table thead th {
            padding: 1.25rem 1rem;
            text-align: right;
            font-weight: 600;
            font-size: 0.95rem;
            border: none;
            white-space: nowrap;
        }

        .delivery-policies-table thead th:first-child {
            border-top-right-radius: 8px;
        }

        .delivery-policies-table thead th:last-child {
            border-top-left-radius: 8px;
        }

        .delivery-policies-table tbody td {
            padding: 1rem;
            border-bottom: 1px solid #e4e6ef;
            background-color: white;
            vertical-align: middle;
        }

        .delivery-policies-table tbody tr:last-child td:first-child {
            border-bottom-right-radius: 8px;
        }

        .delivery-policies-table tbody tr:last-child td:last-child {
            border-bottom-left-radius: 8px;
        }

        .delivery-policies-table tbody tr:hover {
            background-color: #f8f9fa;
        }

        .badge {
            font-size: 0.85rem;
            padding: 0.5rem 0.75rem;
            font-weight: 600;
        }

        .info-item {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }

        .info-item i {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255,255,255,0.2);
            border-radius: 8px;
            margin-left: 1rem;
            font-size: 1.1rem;
        }

        .section-card {
            border: none;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 2rem;
        }

        .section-card .card-header {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        }

        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 4rem;
            color: #dee2e6;
            margin-bottom: 1rem;
        }

        @media (max-width: 768px) {
            .data {
                grid-template-columns: 1fr;
            }
            
            .card-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            .delivery-policies-table {
                font-size: 0.875rem;
            }

            .delivery-policies-table thead th,
            .delivery-policies-table tbody td {
                padding: 0.75rem 0.5rem;
            }
        }
    </style>

    <div class="container">
        @include('layouts.includes.breadcrumb', ['page' => __('main.transportations')])
        <!--begin::Card-->

        <div class="content d-flex flex-column flex-column-fluid" id="kt_content">
            <!-- Booking Header Card -->
            <div class="booking-info-card" style="direction:rtl">
                <div class="d-flex justify-content-between align-items-start flex-wrap">
                    <div>
                        <h2 class="mb-3">
                            <i class="fas fa-building mr-2"></i>
                            {{ __('main.company') }}: {{ $booking->company->name }}
                        </h2>
                        <div class="d-flex flex-wrap gap-3">
                            <div class="info-item">
                                <i class="fas fa-hashtag"></i>
                                <div>
                                    <small style="opacity: 0.9;">{{ __('admin.booking_number') }}</small>
                                    <div style="font-size: 1.1rem; font-weight: 600;">
                                        {{ $booking->booking_number ?? __('main.not_found') }}
                                    </div>
                                </div>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-file-alt"></i>
                                <div>
                                    <small style="opacity: 0.9;">{{ __('admin.certificate_number') }}</small>
                                    <div style="font-size: 1.1rem; font-weight: 600;">
                                        {{ $booking->certificate_number ?? __('main.not_found') }}
                                    </div>
                                </div>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-boxes"></i>
                                <div>
                                    <small style="opacity: 0.9;">{{ __('admin.containers_number') }}</small>
                                    <div style="font-size: 1.1rem; font-weight: 600;">
                                        {{ $booking->bookingContainers->count() }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <a href="{{ route('bookings.index') }}" class="btn btn-light btn-sm">
                        <i class="fas fa-arrow-right mr-2"></i>
                        {{ __('main.back') }}
                    </a>
                </div>
            </div>

            <!-- Booking Details Card -->
            <div class="card section-card">
                <div class="card-header">
                    <h1 class="card-title">
                        <i class="fas fa-info-circle text-primary mr-2"></i>
                        {{ __('admin.booking_inforamtion') }}
                    </h1>
                </div>
                <div class="card-body" style="direction:rtl">
                    <ul class="data">
                        <li>
                            <h4><i class="fas fa-ship mr-2 text-primary"></i>{{ __('admin.shipping_agent') }}</h4>
                            <p class="value">{{ $booking->shippingAgent?->title ?? __('main.not_found') }}</p>
                        </li>
                        <li>
                            <h4><i class="fas fa-user-tie mr-2 text-primary"></i>{{ __('admin.responsible_employee') }}</h4>
                            <p class="value">{{ $booking->employee_name ?? __('main.not_found') }}</p>
                        </li>
                        <li>
                            <h4><i class="fas fa-tasks mr-2 text-primary"></i>{{ __('admin.type_of_action') }}</h4>
                            <p class="value">{{ __('actions.' . TypeOfAction($booking->type_of_action)) ?? __('main.not_found') }}</p>
                        </li>
                        <li>
                            <h4><i class="fas fa-building mr-2 text-primary"></i>{{ __('main.company') }}</h4>
                            <p class="value">{{ $booking->company?->name ?? __('main.not_found') }}</p>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="card section-card">
                <div class="card-header">
                    <h1 class="card-title">
                        <i class="fas fa-boxes text-primary mr-2"></i>
                        {{ __('main.containers') }}
                        <span class="badge badge-primary ml-2">
                            {{ $booking->bookingContainers->count() }}
                        </span>
                    </h1>
                </div>
                <div class="card-body p-0" style="direction:rtl">
                    <div class="form-group p-3">
                        @include('admin.components.booking-containers.table')
                    </div>
                </div>
            </div>


            <div class="card section-card">
                <div class="card-header">
                    <h1 class="card-title">
                        <i class="fas fa-concierge-bell text-primary mr-2"></i>
                        {{ __('main.services') }}
                        <span class="badge badge-primary ml-2">
                            {{ $booking->bookingServices?->count() ?? 0 }}
                        </span>
                    </h1>
                </div>
                <div class="card-body p-0" style="direction:rtl">
                    <div class="form-group p-3">
                        <div class="col-md-12">
                            @include('admin.components.booking-services.table', [
                                'booking_services' => $booking->bookingServices ?? collect(),
                                'expensesServices' => $booking->expenses ?? collect(),
                                'booking' => $booking,
                            ])
                        </div>
                    </div>
                </div>
            </div>



            <div class="card section-card">
                <div class="card-header">
                    <h1 class="card-title">
                        <i class="fas fa-file-invoice-dollar text-primary mr-2"></i>
                        {{ __('main.delivery_policies') }}
                        <span class="badge badge-primary ml-2">
                            {{ $deliveryPolices->count() ?? 0 }}
                        </span>
                    </h1>
                </div>
                <div class="card-body" style="direction:rtl">
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
                            <h5 class="mt-3 mb-2">{{ __('main.no_data_available') }}</h5>
                            <p class="text-muted">{{ __('alerts.no_data_found') }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        <!--end::Card-->
    </div>
@endsection

@push('js')
    <script>
        $(document).ready(function() {
            if ($('#deliveryPoliciesTable').length) {
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
