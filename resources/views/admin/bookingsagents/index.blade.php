@extends('layouts.admin')
@section('content')
    <div class="container">
        @include('layouts.includes.breadcrumb', ['page' => __('main.cars')])
        <!--begin::Card-->
        <div class="card card-custom">
            <div class="card-header flex-wrap py-5">
                <div class="card-toolbar">
                </div>
            </div>
            <div class="card-body">
                <table class="table table-responsive-xl" id="table">
                    <thead class="thead-light">
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">{{ __('admin.container_number') }}</th>
                            <th scope="col">{{ __('main.agents') }}</th>
                            <th scope="col">{{ __('admin.created_at') }}</th>
                            <th scope="col"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($containers as $container)
                            <tr>
                                <th scope="row">{{ $container->id }}</th>
                                <td>
                                    {{ $container->container_no }}
                                </td>
                                <td>
                                    {{ $container->agents->count() }}
                                </td>

                                <td>{{ $container->created_at }}</td>

                                <td>
                                    <div class="row">
                                        <div class="col-md-3 mr-3">
                                                <a href="{{ route('booking_containers_agents.edit', $container->id) }}"
                                                    class="btn btn-icon btn-light btn-hover-primary btn-sm mx-3 ">
                                                    <i class="fas fa-edit text-primary"></i>
                                                </a>
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
                    var url = '{{ route('cars.destroy', ':id') }}';
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
