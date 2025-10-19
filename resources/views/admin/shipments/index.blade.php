<?php 
use App\Models\BookingContainer;
?>
@extends('layouts.admin')

@section('css')
@endsection

@section('content')
    <div class="container">
        @include('layouts.includes.breadcrumb', ['page' => __('main.car_shipments')])
        <!--begin::Card-->
        <div class="card card-custom">
            <div class="card-header flex-wrap align-items-center py-5">
                <div class="card-toolbar">
                    <div class="">
                        <!--begin::Button-->
                        {{-- @if (auth()->user()->hasPermissionTo('cars.create'))
                            <a href="{{ route('shipments.create', request()->id) }}"
                                class="btn btn-primary font-weight-bolder">
                                <span class="svg-icon svg-icon-md">
                                    <!--begin::Svg Icon-->
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24px" height="24px"
                                        viewBox="0 0 24 24">
                                        <g fill="none" fill-rule="evenodd">
                                            <rect width="24" height="24" />
                                            <circle fill="#000000" cx="9" cy="15" r="6" />
                                            <path
                                                d="M8.8012943,7.00241953 C9.83837775,5.20768121 11.7781543,4 14,4 C17.3137085,4 20,6.6862915 20,10 C20,12.2218457 18.7923188,14.1616223 16.9975805,15.1987057 C16.9991904,15.1326658 17,15.0664274 17,15 C17,10.581722 13.418278,7 9,7"
                                                fill="#000000" opacity="0.3" />
                                        </g>
                                    </svg>
                                    <!--end::Svg Icon-->
                                </span>{{ __('admin.add') }}
                            </a>
                        @endif --}}
                    </div>
                </div>

                <div class="">
                    <!-- Button trigger modal -->
                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#exampleModal">
                        {{ __('admin.filter') }}
                    </button>
                    <button type="button" class="btn btn-secondary" data-toggle="modal" data-target="#filterModalExport">
                        {{ __('admin.export_shipments') }}
                    </button>

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
                                <form action="{{ route('shipments.index', $car->id) }}" method="get">
                                    <div class="modal-body">
                                        <div class="row">
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
                    <div class="modal fade" id="filterModalExport" tabindex="-1" aria-labelledby="exampleModalLabel"
                        aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="exampleModalLabel">{{ __('admin.export_shipments') }}</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        X
                                    </button>
                                </div>
                                <form action="{{ route('shipments.export') }}" method="get">
                                    <input type="hidden" name="car_id" value="{{ $car->id }}" />
                                    <div class="modal-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="dateFrom">{{ __('admin.from') }}</label>
                                                    <input id="dateFrom" class="form-control" type="date"
                                                        name="from">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="dateTo">{{ __('admin.to') }}</label>
                                                    <input id="dateTo" class="form-control" type="date"
                                                        name="to">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                        <button type="submit" class="btn btn-primary">{{ __('admin.export') }}</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Create Payment -->
                    <div class="modal fade" id="createModal" tabindex="-1" aria-labelledby="createModalLabel"
                        aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="createModalLabel">{{ __('Pay') }}</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        X
                                    </button>
                                </div>
                                <form action="{{ route('car_payments.store') }}" method="POST"
                                    enctype="multipart/form-data">
                                    @csrf
                                    <input type="hidden" name="delivery_policy_id" id="deliveryPolicyInput">
                                    <div class="modal-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="value">{{ __('admin.value') }}</label>
                                                    <input id="value" class="form-control" type="number"
                                                        name="value">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="image">{{ __('admin.image') }}</label>
                                                    <input id="image" class="form-control" type="file"
                                                        name="image">
                                                </div>
                                            </div>
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

                    <!-- Modal Edit Payment -->
                    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel"
                        aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="editModalLabel">{{ __('Pay') }}</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        X
                                    </button>
                                </div>
                                <form id="editForm" method="POST" enctype="multipart/form-data">
                                    @csrf
                                    @method('PUT')
                                    <div class="modal-body">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label for="cost">{{ __('admin.cost') }}</label>
                                                    <input id="cost" class="form-control" type="number"
                                                        name="cost">
                                                </div>
                                            </div>
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

                </div>
            </div>
            <div class="card-body">
                <!-- عرض رقم السيارة فوق الجدول -->
                <div class="mb-4 text-center">
                    <h4>{{ __('admin.car_number') }}: <span class="text-primary">{{ $car->car_number }}</span></h4>
                </div>
                <table class="table table-responsive-xl" id="table">
                    <thead class="thead-light">
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">{{ __('admin.container_no') }}</th>
                            <th scope="col">تاريخ النقلة</th>
                            <th scope="col">{{ __('admin.costing') }}</th>
                            <th scope="col">{{ __('admin.financial_custody') }}</th>
                            <th scope="col">{{ __('main.extra_expense') }}</th>
                            <th scope="col">{{ __('the_payer') }}</th>
                            <th scope="col">{{ __('the_rest') }}</th>
                            <th scope="col">{{ __('admin.departure') }}</th>
                            <th scope="col">{{ __('admin.loading') }}</th>
                            <th scope="col">{{ __('admin.aging') }}</th>
                            <th scope="col"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($shipments as $shipment)
                            @php
                                $bookingContainerIds = $shipment->booking_containers
                                    ->pluck('id')
                                    ->sort()
                                    ->values()
                                    ->toJson();
                                $containerNumbers = $shipment->booking_containers
                                    ? implode(', ', $shipment->booking_containers->pluck('container_no')->toArray() ?? [])
                                    : '';
                                $cost = $shipment->cost ?? 0;
                                $financialCustody = $shipment->money_transfer->value ?? 0;
                                $extraExpenses = $shipment->extraExpenses->sum('value') ?? 0;
                                $payerValue = ($shipment->payingCars->sum('value') ?? 0) + $financialCustody;

                                $remain = $cost
                                    ? $cost - $financialCustody + $extraExpenses - $shipment->payingCars->sum('value')
                                    : $financialCustody + $extraExpenses - $shipment->payingCars->sum('value');
                                $booking_id = $containerNumbers ? BookingContainer::where('container_no', $containerNumbers)->first() ? BookingContainer::where('container_no', $containerNumbers)->first()->booking_id : "" : "";
                            @endphp

                            <tr>
                                <th scope="row">{{ $shipment->id }}</th>
                                <td><a href="{{ route('bookings.show', $booking_id ?? 1) }}">{{ $containerNumbers }}</a></td>
                                <td>{{ $shipment->date ?? '' }}</td>
                                <td>{{ $cost }}</td>
                                <td>
                                    @if($shipment->booking_containers->first() && $shipment->booking_containers->first()->id)
                                        <a target="_blank"
                                           href="{{ route('bookings.booking_container_policies', $shipment->booking_containers->first()->id) }}">
                                            {{ $financialCustody }}
                                        </a>
                                    @else
                                        0
                                    @endif
                                </td>
                                <td>
                                    @if($shipment->booking_containers->first() && $shipment->booking_containers->first()->id)
                                        <a target="_blank"
                                           href="{{ route('booking_contrainer_extra_costs', $shipment->booking_containers->first()->id) }}">
                                            {{ $extraExpenses }}
                                        </a>
                                    @else
                                        0
                                    @endif
                                </td>
                                <td>
                                    <a target="_blank" href="{{ route('car_payments.index', $shipment->id) }}">
                                        {{ $payerValue }}
                                    </a>
                                </td>
                                <td>{{ $remain }}</td>
                                <td>{{ $shipment->booking_containers->first()->departure->title ?? '' }}</td>
                                <td>{{ $shipment->booking_containers->first()->loading->title ?? '' }}</td>
                                <td>{{ $shipment->booking_containers->first()->aging->title ?? '' }}</td>
                                <td>
                                    <div class="row">
                                        @if ($remain)
                                            <div class="col-md-3">
                                                <a class="btn btn-icon btn-light btn-sm mx-3 pay-btn"
                                                   data-id="{{ $shipment->id }}" href="#" data-toggle="modal"
                                                   data-target="#createModal">
                                                    {{ __('Pay') }}
                                                </a>
                                            </div>
                                        @endif
                                        <div class="col-md-3">
                                            <button data-toggle="modal" data-target="#editModal"
                                                    data-url="{{ route('shipments.update', $shipment->id) }}"
                                                    data-cost="{{ $shipment->cost }}" data-id="{{ $shipment->id }}"
                                                    class="btn btn-icon btn-light btn-hover-secondary btn-sm mx-3 edit-btn">
                                                {{ __('admin.edit') }}
                                            </button>
                                        </div>
                                        <div class="col-md-3">
                                            <button class="btn btn-icon btn-light btn-hover-danger btn-sm mx-3 delete"
                                                    onclick="Delete('{{ $shipment->id }}')">
                                                <i class="fas fa-trash text-danger"></i>
                                            </button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <!--end::Card-->
    </div>
@endsection

@push('js')
    <script>
        $(document).on('click', '.pay-btn', function() {
            $('#deliveryPolicyInput').val($(this).data('id'));
        });

        $(document).on('click', '.edit-btn', function() {
            $('#cost').val($(this).data('cost'));
            $('#editForm').attr('action', $(this).data('url'));
        });
    </script>

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
                    var url = '{{ route('shipments.destroy', ':id') }}';
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
                        }
                    });
                }
            });
        }
    </script>
@endpush
