@extends("layouts.admin")
@section("content")
<div class="container">
    @include("layouts.includes.breadcrumb", [ 'page' => __('main.employees') ])
    <!--begin::Card-->
    <div class="card card-custom">
        <div class="card-header flex-wrap py-5">
            <div class="card-toolbar">
                <!--begin::Button-->
                @if(auth()->user()->hasPermissionTo('employees.create'))
                <a href="{{route('employees.create')}}" class="btn btn-primary font-weight-bolder">
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
                        <th scope="col">{{ __('main.company') }}</th>
                        <th scope="col">{{ __('admin.name') }}</th>
                        <th scope="col">{{ __('admin.job') }}</th>
                        <th scope="col">{{ __('admin.contacts') }}</th>
                        <th scope="col">{{ __('admin.note') }}</th>
                        <th scope="col">{{ __('admin.created_at') }}</th>
                        <th scope="col"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($employees as $employee)
                    <tr>
                            <th scope="row">{{$employee->id}}</th>
                            <td>{{$employee->getCompany()->name}}</td>
                            <td>{{$employee->name}}</td>
                            <td>{{$employee->job}}</td>
                            <td>
                                <div class="lft-contact-list">
                                    <a href="mailto:{{$employee->email}}" class="lft-contact-chip">
                                        <i class="fas fa-envelope" aria-hidden="true"></i>
                                        <span>{{$employee->email}}</span>
                                    </a>
                                    <a href="tel:+{{$employee->phone}}" class="lft-contact-chip">
                                        <i class="fas fa-phone" aria-hidden="true"></i>
                                        <span>{{$employee->phone}}</span>
                                    </a>
                                </div>
                            </td>
                            <td>{{$employee->note}}</td>
                            <td>{{$employee->created_at}}</td>
                            <td>
                                @if(auth()->user()->hasPermissionTo('employees.update'))
                                <a href="{{route('employees.edit',$employee->id)}}" class="btn btn-icon btn-light btn-hover-primary btn-sm mx-3">
                                    <i class="fas fa-edit text-primary"></i>
                                </a>
                                @endif
                                @if(auth()->user()->hasPermissionTo('employees.delete'))
                                    <button class="btn btn-icon btn-light btn-hover-danger btn-sm delete" onclick="Delete('{{ $employee->id }}')">
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
            {{--  $(document).ready(function() {
                $('#table').DataTable({
                    "language": {
                        "url": "{{asset('assets/js/datatable/Arabic.json')}}"
                    },
                    "columnDefs": [{
                        "targets": [0, 7],
                        "orderable": false
                    }],
                    "order": [
                        [0, "desc"]
                    ]
                });
            });  --}}
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
                    var url = '{{ route("employees.destroy", ":id") }}';
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
