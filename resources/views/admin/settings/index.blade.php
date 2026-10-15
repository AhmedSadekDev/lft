@extends("layouts.admin")
@section("content")
<div class="container">
    @include("layouts.includes.breadcrumb", [ 'page' => __('main.settings') ])
    <!--begin::Card-->
    <div class="card card-custom">
        <div class="card-header flex-wrap py-5">
            <div class="card-toolbar">
                <!--begin::Button-->
                @if(auth()->user()->hasPermissionTo('settings.create'))
                <a href="{{route('settings.create')}}" class="btn btn-primary font-weight-bolder">
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
                        <th scope="col">{{ __('admin.phone') }}</th>
                        <th scope="col">{{ __('admin.email') }}</th>
                        <th scope="col">{{ 'Whatsapp' }}</th>
                        <th scope="col">{{ 'Facebook' }}</th>
                        <th scope="col">{{ 'Twitter' }}</th>
                        <th scope="col">{{ 'LinkedIn' }}</th>
                        <th scope="col">{{ 'Instagram' }}</th>
                        <th scope="col">{{ __('admin.logo') }}</th>
                        <th scope="col">{{ __('admin.created_at') }}</th>
                        <th scope="col"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        @if ($setting)
                            <th scope="row">
                                {{$setting->id ?? ''}}
                            </th>
                            <td scope="row">
                                <small class="badge badge-dark badge-sm">
                                    <i class="fa fa-phone"></i>
                                    {{$setting->phone ?? ''}}
                                </small>
                            </td>
                            <td scope="row">
                                <small class="badge badge-light badge-sm">
                                    <i class="fas fa-mail"></i>
                                    {{$setting->email ?? ''}}
                                </small>
                            </td>
                            <td scope="row">
                                <small class="badge badge-success badge-sm">
                                    <i class="fa fa-whatsapp"></i>
                                    {{ substr($setting->whatsapp, 0, 50) }}
                                </small>
                            </td>
                            <td scope="row">
                                <small class="badge badge-primary badge-sm">
                                    <i class="fa fa-facebook"></i>
                                    {{ substr($setting->facebook, 0, 50) }}
                                </small>
                            </td>
                            <td scope="row">
                                <small class="badge badge-info badge-sm">
                                    <i class="fa fa-twitter"></i>
                                    {{ substr($setting->twitter, 0, 50) }}
                                </small>
                            </td>
                            <td scope="row">
                                <small class="badge badge-info badge-sm">
                                    <i class="fa fa-linkedin"></i>
                                    {{ substr($setting->linkedin, 0, 50) }}
                                </small>
                            </td>
                            <td scope="row">
                                <small class="badge badge-info badge-sm">
                                    <i class="fa fa-linkedin"></i>
                                    {{ substr($setting->instagram, 0, 50) }}
                                </small>
                            </td>
                            <td>
                                <a href="{{ url($setting->logo) ?? ''}}" target="_blank" class="btn btn-icon btn-light btn-hover-primary btn-sm mx-3 ">
                                    <i class="fas fa-image text-danger"></i>
                                </a>
                            </td>
                            <td>{{$setting->created_at ?? ''}}</td>
                            <td>
                                @if(auth()->user()->hasPermissionTo('settings.update'))
                                <a href="{{route('settings.edit',$setting->id)}}" class="btn btn-icon btn-light btn-hover-primary btn-sm mx-3">
                                    <i class="fas fa-edit text-primary"></i>
                                </a>
                                @endif
                                @if(auth()->user()->hasPermissionTo('settings.delete'))
                                    <button class="btn btn-icon btn-light btn-hover-danger btn-sm delete" onclick="Delete('{{ $setting->id }}')">
                                        <i class="fas fa-trash text-danger"></i>
                                    </button>
                                @endif
                            </td>
                        @endif
                    </tr>
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
                    var url = '{{ route("settings.destroy", ":id") }}';
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
