@extends('layouts.admin')
@section('content')
    <div class="container">
        @include('layouts.includes.breadcrumb', ['page' => __('main.banks')])
        <!--begin::Bankd-->
        <div class="bankd bankd-custom">
            <div class="bankd-header row justify-content-between align-items-center flex-wrap py-5">
                <div class="bankd-toolbar">
                    <!--begin::Button-->
                    @if (auth()->user()->hasPermissionTo('banks.create'))
                        <a href="{{ route('banks.create') }}" class="btn btn-primary font-weight-bolder">
                            <span class="svg-icon svg-icon-md">
                                <!--begin::Svg Icon | path:assets/media/svg/icons/Design/Flatten.svg-->
                                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"
                                    width="24px" height="24px" viewBox="0 0 24 24" version="1.1">
                                    <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                                        <rect x="0" y="0" width="24" height="24" />
                                        <circle fill="#000000" cx="9" cy="15" r="6" />
                                        <path
                                            d="M8.8012943,7.00241953 C9.83837775,5.20768121 11.7781543,4 14,4 C17.3137085,4 20,6.6862915 20,10 C20,12.2218457 18.7923188,14.1616223 16.9975805,15.1987057 C16.9991904,15.1326658 17,15.0664274 17,15 C17,10.581722 13.418278,7 9,7 C8.93357256,7 8.86733422,7.00080962 8.8012943,7.00241953 Z"
                                            fill="#000000" opacity="0.3" />
                                    </g>
                                </svg>
                                <!--end::Svg Icon-->
                            </span>{{ __('admin.add') }}
                        </a>
                    @endif
                    <!--end::Button-->
                </div>
                <div class="">

                </div>
            </div>
            <div class="bankd-body">
                <table class="table table-responsive-xl" id="table">
                    <thead class="thead-light">
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">{{ __('main.operation_name') }}</th>
                            <th scope="col">{{ __('main.balance') }}</th>
                            <th scope="col">{{ __('admin.created_at') }}</th>
                            <th scope="col"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($banks as $bank)
                            <tr>
                                <th scope="row">{{ $bank->id }}</th>
                                <td>
                                    <a href="{{ route('banktransactions.index', $bank->id) }}">
                                        {{ $bank->name }}
                                    </a>
                                </td>

                                <td>
                                    {{ $bank->amount }}
                                </td>

                                <td>{{ $bank->created_at }}</td>

                                <td>
                                    <div class="row">
                                        <div class="col-md-3 mr-3">
                                            @if (auth()->user()->hasPermissionTo('banks.update'))
                                                <a href="{{ route('banks.edit', $bank->id) }}"
                                                    class="btn btn-icon btn-light btn-hover-primary btn-sm mx-3 ">
                                                    <i class="fas fa-edit text-primary"></i>
                                                </a>
                                            @endif
                                        </div>
                                        <div class="col-md-3">
                                            @if (auth()->user()->hasPermissionTo('banks.delete'))
                                                <button class="btn btn-icon btn-light btn-hover-danger btn-sm mx-3 delete"
                                                    onclick="Delete('{{ $bank->id }}')">
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
        <!--end::Bankd-->
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
                    var url = '{{ route('banks.destroy', ':id') }}';
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
