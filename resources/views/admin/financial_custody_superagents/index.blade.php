@extends("layouts.admin")
@section("content")
<div class="container">
    @include("layouts.includes.breadcrumb", [ 'page' => __('main.financial_custody_superagents') ])
    <!--begin::Card-->
    <div class="card card-custom">
        <div class="card-header flex-wrap py-5">
            <div class="card-toolbar">
                <!--begin::Button-->

                <a href="{{$route_create}}" class="btn btn-primary font-weight-bolder">
                    <i class="fas fa-plus" aria-hidden="true"></i>{{ __('admin.add') }}
                </a>

                <!--end::Button-->
            </div>
        </div>
        <div class="card-body">
            <table class="table table-responsive-xl" id="table">
                <thead class="thead-light">
                    <tr>
                        <th scope="col">#</th>
                        <th scope="col">{{ __('admin.transferer') }}</th>
                        <th scope="col">{{ __('admin.agent') }}</th>
                        <th scope="col">{{ __('admin.financial_custody') }}</th>
                        <th scope="col">{{ __('admin.created_at') }}</th>
                        <th scope="col"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($financial_custody_superagents as $financial_custody_agent)
                        <tr>
                            <th scope="row">{{$financial_custody_agent->id}}</th>
                            <td>{{$financial_custody_agent->transferer?->name }}</td>
                            <td>{{$financial_custody_agent->transfered?->name }}</td>
                            <td>{{$financial_custody_agent->value}}</td>
                            <td>{{$financial_custody_agent->created_at}}</td>
                            <td>

                                    <button class="btn btn-icon btn-light btn-hover-danger btn-sm delete" onclick="Delete('{{ $financial_custody_agent->id }}', '{{ $financial_custody_agent->transfered_id }}')">
                                        <i class="fas fa-trash text-danger"></i>
                                    </button>
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

        function Delete(id, agent_id) {
            Swal.fire({
                title: "{{ __('alerts.are_you_sure') }}",
                text: "{{ __('alerts.not_revert_information') }}",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: "{{ __('alerts.confirm') }}",
                cancelButtonText: "{{ __('alerts.cancel') }}",
            }).then((result) => {
                if (result.isConfirmed) {
                    var url = '{{ route("financial_custody_superagents.destroy", ":id") }}' + '?agent_id=' + agent_id;
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
