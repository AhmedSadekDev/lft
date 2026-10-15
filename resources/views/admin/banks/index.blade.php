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
                            <i class="fas fa-plus" aria-hidden="true"></i>{{ __('admin.add') }}
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
                            <th scope="col">اسم البنك</th>
                            <th scope="col">{{ __('main.balance') }}</th>
                            <th scope="col">الاجراءات</th>
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
