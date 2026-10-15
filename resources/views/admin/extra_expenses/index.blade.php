@extends('layouts.admin')
@section('content')
    <div class="container">
        @include('layouts.includes.breadcrumb', ['page' => __('main.extra_expense')])
        <!--begin::Card-->
        <div class="card card-custom">
            <div class="card-header flex-wrap py-5">
                <div class="card-toolbar">
                    <!--begin::Button-->
                    <a href="{{ route('booking_contrainer_extra_costs_create', $booking_container->id) }}"
                        class="btn btn-primary font-weight-bolder">
                        <i class="fas fa-plus" aria-hidden="true"></i>{{ __('admin.add') }}
                    </a>
                    <!--end::Button-->
                </div>
                <div class="mt-3">
                    <a href="{{ route('bookings.show', $booking_container->id) }}" class="btn btn-secondary float-right">
                        {{ __('main.back') }}
                    </a>
                </div>
            </div>
            <div class="card-body">
                <table class="table table-responsive-xl" id="table">
                    <thead class="thead-light">
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">{{ __('admin.name') }}</th>
                            <th scope="col">{{ __('admin.value') }}</th>
                            <th scope="col">{{ __('admin.created_at') }}</th>
                            <th scope="col"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($expenses as $expense)
                            <tr>
                                <th scope="row">{{ $expense->id }}</th>
                                <td>
                                    {{ $expense->name }}
                                </td>

                                <td>
                                    {{ $expense->value }}
                                </td>
                                <td>{{ $expense->created_at }}</td>
                                <td>
                                    <div class="row">
                                        <div class="col-md-3 mr-3">
                                            <a href="{{ route('booking_contrainer_extra_costs_edit', $expense->id) }}"
                                                class="btn btn-icon btn-light btn-hover-primary btn-sm mx-3 ">
                                                <i class="fas fa-edit text-primary"></i>
                                            </a>
                                        </div>
                                        <div class="col-md-3">
                                            <button class="btn btn-icon btn-light btn-hover-danger btn-sm mx-3 delete"
                                                onclick="Delete('{{ $expense->id }}')">
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
                    var url = '{{ route('booking_contrainer_extra_costs_destroy', ':id') }}';
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
