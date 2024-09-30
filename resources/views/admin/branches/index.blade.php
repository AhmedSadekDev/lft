@extends("layouts.admin")
@section("content")
<div class="container">
    @include("layouts.includes.breadcrumb", [ 'page' => __('main.branches') ])
    <!--begin::Card-->
    <div class="card card-custom">
        <div class="card-header flex-wrap py-5">
            <div class="card-toolbar">
                <!--begin::Button-->
                @if(auth()->user()->hasPermissionTo('branches.create'))
                <a href="{{route('branches.create')}}" class="btn btn-primary font-weight-bolder">
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
                        <th scope="col">{{ __('main.factory') }}</th>
                        <th scope="col">{{ __('admin.name') }}</th>
{{--                        <th scope="col">{{ __('admin.country') }}</th>--}}
                        <th scope="col">{{ __('admin.city') }}</th>
                        <th scope="col">{{ __('admin.address') }}</th>
                        <th scope="col">{{ __('admin.email') }}</th>
                        <th scope="col">{{ __('admin.branch_number') }}</th>
                        <th scope="col">{{ __('admin.created_at') }}</th>
                        <th scope="col"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($branches as $branch)
                    <tr>
                            <th scope="row">{{$branch->id}}</th>
                            <td>{{$branch->getFactory()->name}}</td>
                            <td>{{$branch->name}}</td>
{{--                            <td>{{$branch->country}}</td>--}}
                            <td>{{$branch->city}}</td>
                            <td>{{$branch->address}}</td>
                            <td>
                                <div class="row">
                                    <div class="col-12">
                                        <a href="mailto:{{$branch->email}}" class="badge badge-pill badge-dark">
                                            <i class="fas fa-at"></i>
                                            :{{$branch->email}}
                                        </a>
                                    </div>
                                </div>
                            </td>
                            <td>{{$branch->number}}</td>
                            <td>{{$branch->created_at}}</td>
                            <td>
                                @if(auth()->user()->hasPermissionTo('branches.update'))
                                <a href="{{route('branches.edit',$branch->id)}}" class="btn btn-icon btn-light btn-hover-primary btn-sm mx-3">
                                    <i class="fas fa-edit text-primary"></i>
                                </a>
                                @endif
                                @if(auth()->user()->hasPermissionTo('branches.delete'))
                                    <button class="btn btn-icon btn-light btn-hover-danger btn-sm delete" onclick="DeleteBranch( '{{ $branch->id }}' )">
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
            "use strict";
        })(jQuery);

        function DeleteBranch(id) {
            Swal.fire({
                title: "{{ __('alerts.are_you_sure') }}",
                text: "{{ __('alerts.not_revert_information') }}",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: "{{ __('alerts.confirm') }}",
                cancelButtonText: "{{ __('alerts.cancel') }}",
            }).then((result) => {
                if (result.isConfirmed) {
                    var url = '{{ route("branches.destroy", ":id") }}';
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
