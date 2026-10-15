@extends('layouts.admin')
@section('content')
    <div class="container">
        @include('layouts.includes.breadcrumb', ['page' => __('main.agents')])
        <!--begin::Card-->
        <div class="card card-custom">
            <div class="card-header flex-wrap py-5">
                <div class="card-toolbar">
                    <!--begin::Button-->
                    @if (auth()->user()->hasPermissionTo('agents.create'))
                        <a href="{{ route('agents.create') }}" class="btn btn-primary font-weight-bolder">
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
                            <th scope="col">{{ __('admin.name') }}</th>
                            <th scope="col">{{ __('admin.email') }}</th>
                            <th scope="col">{{ __('admin.phone') }}</th>
                            <th scope="col">{{ __('admin.financial_custody') }}</th>
                            <th scope="col"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($agents as $agent)
                            <tr>
                                <th scope="row">{{ $agent->id }}</th>
                                <td>
                                    <a href="{{ route('agent_car_tranfer.index', $agent->id) }}">
                                        {{ $agent->name }}
                                    </a>
                                </td>
                                <td>
                                    {{ $agent->email }}
                                </td>

                                <td>
                                    {{ $agent->phone }}
                                </td>

                                <td>
                                    {{ $agent->wallet }}
                                </td>


                                <td>
                                    <div class="row">
                                        <div class="col-md-3 mr-3">
                                            @if (auth()->user()->hasPermissionTo('agents.update'))
                                                <a href="{{ route('agents.edit', $agent->id) }}"
                                                    class="btn btn-icon btn-light btn-hover-primary btn-sm mx-3 ">
                                                    <i class="fas fa-edit text-primary"></i>
                                                </a>
                                            @endif
                                        </div>
                                        <div class="col-md-3">
                                            @if (auth()->user()->hasPermissionTo('agents.delete'))
                                                <button class="btn btn-icon btn-light btn-hover-danger btn-sm mx-3 delete"
                                                    onclick="Delete('{{ $agent->id }}')">
                                                    <i class="fas fa-trash text-danger"></i>
                                                </button>
                                            @endif
                                        </div>

                                        <a class="btn btn-primary btn-sm"
                                            href="{{ route('reports.agent_reports', $agent->id) }}">{{ __('main.reports') }}</a>
                                        <a class="btn btn-primary btn-sm ml-4 "
                                            href="{{ route('expenses.agent_expenses', $agent->id) }}">{{ __('main.expenses') }}</a>

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
                    var url = '{{ route('agents.destroy', ':id') }}';
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
