@extends('layouts.admin')
@section('content')
    <div class="container">
        @include('layouts.includes.breadcrumb', ['page' => __('main.manage_users')])
        <!--begin::Card-->
        <div class="card card-custom">
            <div class="card-header flex-wrap py-5">
                <div class="card-toolbar">
                    <!--begin::Button-->

                    <a href="{{ request()->has('role') ? route('users.create', ['role' => request()->role]) : route('users.create') }}" class="btn btn-primary font-weight-bolder">
                        <i class="fas fa-plus" aria-hidden="true"></i>{{ __('admin.add') }}
                    </a>

                    <!--end::Button-->
                </div>
            </div>
            <div class="card-body">
                <table class="table table-responsive-xl">
                    <thead class="thead-light">
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">{{ __('admin.name') }}</th>
                            <th scope="col">{{ __('admin.email') }}</th>
                            <th scope="col">{{ __('admin.created_at') }}</th>
                            <th scope="col"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($users as $user)
                            <tr>
                                <th scope="row">{{ $user->id }}</th>
                                <td>{{ $user->name }}</td>
                                <td>{{ $user->email }}</td>
                                <td>{{ $user->created_at }}</td>
                                <td>
                                    @if ($user->id != auth()->user()->id)
                                    <a href="{{ route('users.edit', $user->id) }}{{ request()->has('role') ? '?role=' . request()->role : '' }}"
                                            class="btn btn-icon btn-sm btn-secondary">
                                            <i class="fas fa-pen"></i>
                                        </a>

                                        <button class="btn btn-icon btn-light btn-hover-danger btn-sm delete"
                                            onclick="Delete('{{ $user->id }}')">
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
            s
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
                    var url = '{{ route('users.destroy', ':id') }}';
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


    <script>
        $(docement).ready(function() {
            $('.dataTables_paginate').addClass('d-none');
        });
    </script>
@endpush
