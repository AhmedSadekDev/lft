@extends("layouts.admin")
@section("content")
<div class="container">
    @include("layouts.includes.breadcrumb", [ 'page' => __('main.ourServices') ])
    <!--begin::Card-->
    <div class="card card-custom">
        <div class="card-header flex-wrap py-5">
            <div class="card-toolbar">
                <!--begin::Button-->
                @if(auth()->user()->hasPermissionTo('ourServices.create'))
                <a href="{{route('ourServices.create')}}" class="btn btn-primary font-weight-bolder">
                    <i class="fas fa-plus" aria-hidden="true"></i>{{ __('admin.add') }}
                </a>
                @endif
                <!--end::Button-->
            </div>
        </div>
        <div class="card-body">
            <table class="table table-responsive-xl" id="table">
                <thead class="thead-light">
                    <tr>
                        <th scope="col">#</th>
                        <th scope="col">{{ __('admin.title') }}</th>
                        <th scope="col">{{ __('admin.description') }}</th>
                        <th scope="col">{{ __('admin.image') }}</th>
                        <th scope="col">{{ __('admin.created_at') }}</th>
                        <th scope="col"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($services as $service)
                        <tr>
                            <th scope="row">{{ $service->id }}</th>
                            <td>{{ $service->title }}</td>
                            <td>{{ substr($service->description, 0, 150) }}</td>
                            <td>
                                <a href="{{ url($service->image) ?? ''}}" target="_blank" class="btn btn-icon btn-light btn-hover-primary btn-sm mx-3 ">
                                    <i class="fas fa-image text-danger"></i>
                                </a>
                            </td>
                            <td>{{ $service->created_at }}</td>
                            <td>
                                @if(auth()->user()->hasPermissionTo('ourServices.update'))
                                <a href="{{ route('ourServices.edit',$service->id) }}" class="btn btn-icon btn-light btn-hover-primary btn-sm mx-3">
                                    <i class="fas fa-edit text-primary"></i>
                                </a>
                                @endif
                                @if(auth()->user()->hasPermissionTo('ourServices.delete'))
                                    <button class="btn btn-icon btn-light btn-hover-danger btn-sm delete" onclick="Delete('{{ $service->id }}')">
                                        <i class="fas fa-trash text-danger"></i>
                                    </button>
                                @endif
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
        (function($) {
            "use strict";s
        })(jQuery);

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
                    var url = '{{ route("ourServices.destroy", ":id") }}';
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
                        type: 'DELETE',
                        success: function(response) {
                            console.log(response);
                            location.reload();
                            Swal.fire({
                                title: {{ __('alerts.done') }},
                                icon: 'success',
                                showConfirmButton: false,
                                timer: 3000,
                                timerProgressBar: true,
                            });
                        }
                    });
                }
            });
        }
    </script>
@endpush
