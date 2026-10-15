@extends("layouts.admin")
@section("content")
<div class="container">
    @include("layouts.includes.breadcrumb", [ 'page' => __('main.services') ])
    <!--begin::Card-->
    <div class="card card-custom">
        <div class="card-header flex-wrap py-5">
            <div class="card-toolbar">
                <!--begin::Button-->
                @if(auth()->user()->hasPermissionTo('services.create'))
                <a href="{{$route_create}}" class="btn btn-primary font-weight-bolder">
                    <i class="fas fa-plus" aria-hidden="true"></i>{{ __('admin.add') }}
                </a>
                @endif
                <!--end::Button-->
                <!--begin::Button-->
                <div class="float-left ml-2">
                    <button type="button" class="btn btn-success " id="imports"  data-toggle="modal" data-target="#import_excels">
                        <i class="icon-share-alternitive"></i>
                        {{ __('admin.import') }}
                    </button>
                </div>
                <!--end::Button-->
            </div>
            <div class="mt-3">
                <a href="{{ route('companies.index') }}" class="btn btn-secondary float-right">
                    {{ __('main.back') }}
                </a>
            </div>
        </div>
        <div class="card-body">
            <table class="table table-responsive-xl" id="table">
                <thead class="thead-light">
                    <tr>
                        <th scope="col">#</th>
                        <th scope="col">{{ __('admin.serviceCategory') }}</th>
                        <th scope="col">{{ __('admin.name') }}</th>
                        <th scope="col">{{ __('admin.created_at') }}</th>
                        <th scope="col"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($companyServices as $service)
                        <tr>
                            <th scope="row">{{$service->id}}</th>
                            <td>{{$service->serviceCategory?->title }}</td>
                            <td>{{$service->name}}</td>
                            <td>{{$service->created_at}}</td>
                            <td>
                                @if(auth()->user()->hasPermissionTo('services.update'))
                                <a href="{{route('services.edit',$service->id)}}" class="btn btn-icon btn-light btn-hover-primary btn-sm mx-3">
                                    <i class="fas fa-edit text-primary"></i>
                                </a>
                                @endif
                                @if(auth()->user()->hasPermissionTo('services.delete'))
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
@include('admin.companyServices.modals.import')
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
                    var url = '{{ route("services.destroy", ":id") }}';
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
