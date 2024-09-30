@extends('layouts.admin')

@section('css')
@endsection

@section('content')
    <div class="container">
        @include('layouts.includes.breadcrumb', ['page' => __('main.car_shipments')])
        <!--begin::Card-->
        <div class="card card-custom">
            <div class="card-header flex-wrap  align-items-center py-5">
                <div class="card-toolbar">
                    <div class="">
                       
                    </div>


                    <!--end::Button-->
                </div>

                <div class="">
                    <!-- Button trigger modal -->
                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#exampleModal">
                        {{ __('admin.filter') }}
                    </button>

                    {{-- <a href="{{ route('car_payments.export', ['car' => $payment->car_id, 'container' => $payment->booking_container_id, 'ids' => implode(',', $payments->pluck('id')->toArray())]) }}"
                        class="btn btn-secondary">
                        {{ __('admin.export_shipments') }}
                    </a> --}}


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
                                <form
                                    action="{{ route('car_payments.index', ['id' => $policy->id]) }}"
                                    method="get">
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
                </div>
            </div>
            <div class="card-body">
                <table class="table table-responsive-xl" id="table">
                    <thead class="thead-light">
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">{{ __('admin.value') }}</th>
                            <th scope="col">{{ __('admin.image') }}</th>
                            <th scope="col">{{ __('main.date') }}</th>

                            <th scope="col"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($payments as $shipment)
                           
                            <tr>
                                <th scope="row">{{ $shipment->id }}</th>
                                <td>{{ $shipment->value }}</td>
                                <td>
                                    <img width="50" src="{{ asset($shipment->image) }}" alt="">
                                </td>
                                <td>{{ $shipment->created_at }}</td>
                                

                                <td>
                                    <div class="row">

                                        <div class="col-md-3">
                                            @if (auth()->user()->hasPermissionTo('cars.delete'))
                                                <button class="btn btn-icon btn-light btn-hover-danger btn-sm mx-3 delete"
                                                    onclick="Delete('{{ $shipment->id }}')">
                                                    <i class="fas fa-trash text-danger"></i>
                                                </button>
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
        <!--end::Card-->
    </div>
@endsection
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
    </script>
@endpush
