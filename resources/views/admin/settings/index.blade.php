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
                    <span class="svg-icon svg-icon-md">
                        <!--begin::Svg Icon | path:assets/media/svg/icons/Design/Flatten.svg-->
                        <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="24px" height="24px" viewBox="0 0 24 24" version="1.1">
                            <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                                <rect x="0" y="0" width="24" height="24" />
                                <circle fill="#000000" cx="9" cy="15" r="6" />
                                <path d="M8.8012943,7.00241953 C9.83837775,5.20768121 11.7781543,4 14,4 C17.3137085,4 20,6.6862915 20,10 C20,12.2218457 18.7923188,14.1616223 16.9975805,15.1987057 C16.9991904,15.1326658 17,15.0664274 17,15 C17,10.581722 13.418278,7 9,7 C8.93357256,7 8.86733422,7.00080962 8.8012943,7.00241953 Z" fill="#000000" opacity="0.3" />
                            </g>
                        </svg>
                        <!--end::Svg Icon-->
                    </span>{{ __('admin.add') }}
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
