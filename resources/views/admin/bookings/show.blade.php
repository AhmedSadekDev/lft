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
        }

        .title_name {
            font-family: "semibold";
        }

        .data {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            margin-top: 2rem;
            padding: 1.5rem;
            list-style: none;
            background-color: #f8f9fa;
            border-radius: 8px;
        }

        .data li {
            width: 33.3%;
            margin-bottom: 1.5rem;
            padding: 0.75rem;
            background-color: white;
            border-radius: 6px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .data li h4 {
            display: inline-block;
            font-family: "semibold";
            color: #495057;
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
        }

        .data li p {
            display: inline-block;
            font-family: "regular";
            color: #212529;
            font-size: 1rem;
            margin-right: 0.5rem;
        }

        h3 {
            font-family: "bold";
            margin-bottom: 1rem;
            color: #144d99;
        }

        .data_container {
            display: flex;
            list-style: none;
            padding: 0;
        }

        .data_container li {
            width: 33.3%;
            margin-bottom: 1rem;
        }

        .data_container li h4 {
            display: inline-block;
            font-family: "semibold";
        }

        .data_container li p {
            display: inline-block;
            font-family: "regular";
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem;
            border-bottom: 1px solid #e4e6ef;
        }

        .card-body {
            padding: 1.5rem;
        }

        .delivery-policies-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        .delivery-policies-table thead {
            background-color: #144d99;
            color: white;
        }

        .delivery-policies-table thead th {
            padding: 1rem;
            text-align: right;
            font-weight: 600;
            border: 1px solid #e4e6ef;
        }

        .delivery-policies-table tbody td {
            padding: 1rem;
            border: 1px solid #e4e6ef;
            background-color: white;
        }

        .delivery-policies-table tbody tr:hover {
            background-color: #f8f9fa;
        }

        .badge {
            font-size: 0.9rem;
            padding: 0.5rem 0.75rem;
        }

        @media (max-width: 768px) {
            .data li {
                width: 100%;
            }
        }
    </style>

    <div class="container">
        @include('layouts.includes.breadcrumb', ['page' => __('main.transportations')])
        <!--begin::Card-->

        <div class="content d-flex flex-column flex-column-fluid" id="kt_content">
            <div class="card card-custom gutter-b">
                <div class="card-header">
                    <h1 class="card-title">
                        <i class="fas fa-building text-primary mr-2"></i>
                        {{ __('main.company') . ' ' . $booking->company->name }}
                    </h1>
                    <div>
                        <a href="{{ route('bookings.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-right mr-2"></i>
                            {{ __('main.back') }}
                        </a>
                    </div>
                </div>
                <div class="card-body p-0" style="direction:rtl">
                    <ul class="data">
                        <li>
                            <h4>{{ __('admin.shipping_agent') }} : </h4>
                            <p>
                                {{ $booking->shippingAgent?->title }}
                            </p>
                        </li>
                        <li>
                            <h4>{{ __('admin.responsible_employee') }} : </h4>
                            <p>
                                {{ $booking->employee_name ?? '__' }}
                            </p>
                        </li>
                        <li>
                            <h4>{{ __('admin.booking_number') }} : </h4>
                            <p>
                                {{ $booking->booking_number ?? '__' }}
                            </p>
                        </li>
                        <li>
                            <h4>{{ __('admin.certificate_number') }} : </h4>
                            <p>
                                {{ $booking->certificate_number ?? '__' }}
                            </p>
                        </li>
                        <li>
                            <h4>{{ __('admin.type_of_action') }} : </h4>
                            <p>
                                {{ __('actions.' . TypeOfAction($booking->type_of_action)) ?? '__' }}
                            </p>
                        </li>
                        <li>
                            <h4>{{ __('main.company') }} : </h4>
                            <p>
                                {{ $booking->company?->name ?? '__' }}
                            </p>
                        </li>
                        <li>
                            <h4>{{ __('admin.containers_number') }} : </h4>
                            <p>
                                {{ $booking->bookingContainers->count() }}

                            </p>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="card card-custom gutter-b">
                <div class="card-header">
                    <h1 class="card-title">
                        <i class="fas fa-boxes text-primary mr-2"></i>
                        {{ __('main.containers') }}
                        <span class="badge badge-primary">
                            {{ $booking->bookingContainers->count() }}
                        </span>
                    </h1>
                </div>
                <div class="card-body p-0" style="direction:rtl">
                    <!-- For loop this container -->
                    <!-- For loop this container -->
                    <div class="form-group">
                        @include('admin.components.booking-containers.table')
                    </div>
                    <!-- For loop this container -->
                    <!-- For loop this container -->
                </div>
            </div>


            <div class="card card-custom gutter-b">
                <div class="card-header">
                    <h1 class="card-title">
                        <i class="fas fa-concierge-bell text-primary mr-2"></i>
                        {{ __('main.services') }}
                        <span class="badge badge-primary">
                            {{ $booking->bookingServices?->count() ?? 0 }}
                        </span>
                    </h1>
                </div>
                <div class="card-body p-0" style="direction:rtl">
                    <!-- For loop this container -->
                    <div class="form-group">
                        <div class="col-md-12">
                            @include('admin.components.booking-services.table', [
                                'booking_services' => $booking->bookingServices ?? collect(),
                                'expensesServices' => $booking->expenses ?? collect(),
                                'booking' => $booking,
                            ])
                        </div>
                    </div>
                    <!-- For loop this container -->
                </div>

            </div>



            <div class="card card-custom gutter-b">
                <div class="card-header">
                    <h1 class="card-title">
                        <i class="fas fa-file-invoice-dollar text-primary mr-2"></i>
                        {{ __('main.delivery_policies') }}
                        <span class="badge badge-primary">
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
                                            <td>{{ $policy->id }}</td>
                                            <td>
                                                <span class="badge badge-info">
                                                    {{ $policy->booking_containers->first()->container_no ?? 'لم يتم كتابه رقم الحاويه بعد' }}
                                                </span>
                                            </td>
                                            <td>{{ $policy->booking_containers->first()->departure->title ?? '-' }}</td>
                                            <td>{{ $policy->booking_containers->first()->loading->title ?? '-' }}</td>
                                            <td>{{ $policy->booking_containers->first()->aging->title ?? '-' }}</td>
                                            <td>
                                                <span class="font-weight-bold text-success">
                                                    {{ number_format($policy->money_transfer->value ?? 0, 2) }} {{ __('main.currency') ?? 'ج.م' }}
                                                </span>
                                            </td>
                                            <td>{{ $policy->money_transfer->date ?? '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-info text-center">
                            <i class="fas fa-info-circle mr-2"></i>
                            {{ __('main.no_data_available') ?? 'لا توجد بيانات متاحة' }}
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
