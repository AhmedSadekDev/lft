@extends('layouts.admin')
@section('content')
    <style>
        body {
            text-align: start;
        }

        .card-title {
            font-family: "bold";
            color: #144d99;
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
            padding: 0;
            list-style: none;
        }

        .data li {
            width: 33.3%;
            margin-bottom: 1rem;
        }

        .data li h4 {
            display: inline-block;
            font-family: "semibold";
        }

        .data li p {
            display: inline-block;
            font-family: "regular";
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
    </style>

    <div class="container">
        @include('layouts.includes.breadcrumb', ['page' => __('main.transportations')])
        <!--begin::Card-->

        <div class="content d-flex flex-column flex-column-fluid" id="kt_content">
            <div class="card card-custom gutter-b">
                <div class="card-header">
                    <h1 class="card-title">
                        {{ __('main.company') . ' ' . $booking->company->name }}
                    </h1>
                    <div class="mt-3">
                        <a href="{{ route('bookings.index') }}" class="btn btn-secondary float-right">
                            {{ __('main.back') }}
                        </a>
                    </div>
                    {{-- <h5 class="title_name">
                    {{ $booking->company->name }}
                </h5> --}}
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
                    {{-- <h1 class="card-title">
                    {{ __('main.company').' '. $booking->company->name }}
                </h1> --}}
                    <h1 class="card-title">
                        {{ __('main.containers') }}
                        <span class="badge badge-light">
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
                    {{-- <h1 class="card-title">
                    {{ __('main.company').' '. $booking->company->name }}
                </h1> --}}
                    <h1 class="card-title">
                        {{ __('main.services') }}
                        <span class="badge badge-light">
                            {{ $booking->bookingServices?->count() ?? 0 }}
                        </span>
                    </h1>
                </div>
                <div class="card-body p-0" style="direction:rtl">
                    <!-- For loop this container -->
                    <div class="form-group">
                        <div class="col-md-12">
                            @include('admin.components.booking-services.table', [
                                'booking_services' => $booking->bookingServices,
                                'expensesServices' => $booking->expenses,
                            ])
                        </div>
                    </div>
                    <!-- For loop this container -->
                </div>

            </div>



            <div class="card card-custom gutter-b">
                <div class="card-header">
                    {{-- <h1 class="card-title">
                    {{ __('main.company').' '. $booking->company->name }}
                </h1> --}}
                    <h1 class="card-title">
                        {{ __('main.delivery_policies') }}
                        <span class="badge badge-light">
                            {{ $booking->bookingServices?->count() ?? 0 }}
                        </span>
                    </h1>
                </div>
                <div class="card-body p-0" style="direction:rtl">
                    <!-- For loop this container -->
                    <div class="form-group">
                        <div class="col-md-12">
                            <table>
                                <thead>
                                    <th>#</th>
                                    <th>{{ __('main.container') }}</th>
                                    <th>{{ __('admin.departure') }}</th>
                                    <th>{{ __('admin.loading') }}</th>
                                    <th>{{ __('admin.aging') }}</th>
                                    <th>{{ __('admin.value') }}</th>
                                    <th>التاريخ</th>
                                </thead>


                                <tbody>
                                    @foreach ($deliveryPolices as $policy)
                                        <tr>
                                            <td>{{ $policy->id }}</td>
                                            <td>{{ $policy->booking_containers->first()->container_no ?? 'لم يتم كتابه رقم الحاويه بعد' }}
                                            </td>
                                            <td>{{ $policy->booking_containers->first()->departure->title ?? '' }}</td>
                                            <td>{{ $policy->booking_containers->first()->loading->title ?? '' }}</td>
                                            <td>{{ $policy->booking_containers->first()->aging->title ?? '' }}</td>
                                            <td>{{ $policy->money_transfer->value ?? '' }}</td>
                                            <td>{{ $policy->money_transfer->date ?? '' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <!-- For loop this container -->
                </div>

            </div>
        </div>
        <!--end::Card-->
    </div>
@endsection
