@extends('layouts.admin')
@section('content')
    <style>
        .booking-header {
            background: #fff;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .booking-header h1 {
            color: #144d99;
            font-size: 1.75rem;
            margin-bottom: 1rem;
            font-weight: bold;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .info-item {
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 6px;
            border-right: 3px solid #144d99;
        }

        .info-item label {
            display: block;
            color: #6c757d;
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        .info-item .value {
            color: #212529;
            font-size: 1rem;
            font-weight: 500;
        }

        .section-title {
            color: #144d99;
            font-size: 1.25rem;
            font-weight: bold;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .section-title i {
            font-size: 1.1rem;
        }

        .card-custom {
            border: 1px solid #e4e6ef;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            background: #fff;
        }

        .card-header-custom {
            padding: 1rem 1.5rem;
            background: #f8f9fa;
            border-bottom: 1px solid #e4e6ef;
            border-radius: 8px 8px 0 0;
        }

        .card-body-custom {
            padding: 1.5rem;
        }

        .delivery-policies-table {
            width: 100%;
            border-collapse: collapse;
        }

        .delivery-policies-table thead {
            background: #144d99;
            color: white;
        }

        .delivery-policies-table thead th {
            padding: 0.75rem 1rem;
            text-align: right;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .delivery-policies-table tbody td {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #e4e6ef;
        }

        .delivery-policies-table tbody tr:hover {
            background: #f8f9fa;
        }

        @media (max-width: 768px) {
            .info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="container">
        @include('layouts.includes.breadcrumb', ['page' => __('main.transportations')])

        <div class="content d-flex flex-column flex-column-fluid" id="kt_content">
            <!-- Booking Header -->
            <div class="booking-header" style="direction:rtl">
                <div class="d-flex justify-content-between align-items-center flex-wrap">
                    <div>
                        <h1>
                            <i class="fas fa-building text-primary mr-2"></i>
                            {{ __('main.company') }}: {{ $booking->company->name }}
                        </h1>
                        <div class="info-grid">
                            <div class="info-item">
                                <label>{{ __('admin.booking_number') }}</label>
                                <div class="value">{{ $booking->booking_number ?? __('main.not_found') }}</div>
                            </div>
                            <div class="info-item">
                                <label>{{ __('admin.certificate_number') }}</label>
                                <div class="value">{{ $booking->certificate_number ?? __('main.not_found') }}</div>
                            </div>
                            <div class="info-item">
                                <label>{{ __('admin.shipping_agent') }}</label>
                                <div class="value">{{ $booking->shippingAgent?->title ?? __('main.not_found') }}</div>
                            </div>
                            <div class="info-item">
                                <label>{{ __('admin.responsible_employee') }}</label>
                                <div class="value">{{ $booking->employee_name ?? __('main.not_found') }}</div>
                            </div>
                            <div class="info-item">
                                <label>{{ __('admin.type_of_action') }}</label>
                                <div class="value">{{ __('actions.' . TypeOfAction($booking->type_of_action)) ?? __('main.not_found') }}</div>
                            </div>
                            <div class="info-item">
                                <label>{{ __('admin.containers_number') }}</label>
                                <div class="value">{{ $booking->bookingContainers->count() }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3 mt-md-0">
                        <a href="{{ route('bookings.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-right mr-2"></i>
                            {{ __('main.back') }}
                        </a>
                    </div>
                </div>
            </div>

            <!-- Containers Section -->
            <div class="card card-custom">
                <div class="card-header-custom">
                    <h2 class="section-title">
                        <i class="fas fa-boxes text-primary"></i>
                        {{ __('main.containers') }}
                        <span class="badge badge-primary">{{ $booking->bookingContainers->count() }}</span>
                    </h2>
                </div>
                <div class="card-body-custom" style="direction:rtl">
                    @include('admin.components.booking-containers.table')
                </div>
            </div>

            <!-- Services Section -->
            <div class="card card-custom">
                <div class="card-header-custom">
                    <h2 class="section-title">
                        <i class="fas fa-concierge-bell text-primary"></i>
                        {{ __('main.services') }}
                        <span class="badge badge-primary">{{ $booking->bookingServices?->count() ?? 0 }}</span>
                    </h2>
                </div>
                <div class="card-body-custom" style="direction:rtl">
                    @include('admin.components.booking-services.table', [
                        'booking_services' => $booking->bookingServices ?? collect(),
                        'expensesServices' => $booking->expenses ?? collect(),
                        'booking' => $booking,
                    ])
                </div>
            </div>

            <!-- Delivery Policies Section -->
            <div class="card card-custom">
                <div class="card-header-custom">
                    <h2 class="section-title">
                        <i class="fas fa-file-invoice-dollar text-primary"></i>
                        {{ __('main.delivery_policies') }}
                        <span class="badge badge-primary">{{ $deliveryPolices->count() ?? 0 }}</span>
                    </h2>
                </div>
                <div class="card-body-custom" style="direction:rtl">
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
                                                <span class="font-weight-bold text-success">
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
                        <div class="alert alert-info text-center">
                            <i class="fas fa-info-circle mr-2"></i>
                            {{ __('main.no_data_available') }}
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
