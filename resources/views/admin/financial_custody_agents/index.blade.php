@extends("layouts.admin")
@section("content")
<div class="container">
    @include("layouts.includes.breadcrumb", [ 'page' => __('main.financial_custody_agents') ])
    <!--begin::Card-->
    <div class="card card-custom">
        <div class="card-header flex-wrap py-5">
            <div class="card-toolbar">
                <!--begin::Button-->

                <a href="{{$route_create}}" class="btn btn-primary font-weight-bolder">
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

                <!--end::Button-->
            </div>
        </div>
        <div class="card-body">
            <table class="table table-responsive-xl" id="table">
                <thead class="thead-light">
                    <tr>
                        <th scope="col">#</th>
                        <th scope="col">{{ __('admin.type') }}</th>
                        <th scope="col">{{ __('admin.transferer') }}</th>
                        <th scope="col">{{ __('admin.agent') }}</th>
                        <th scope="col">{{ __('admin.direction') }}</th>
                        <th scope="col">{{ __('admin.financial_custody') }}</th>
                        <th scope="col">{{ __('admin.created_at') }}</th>
                        <th scope="col"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($financial_custody_agents as $financial_custody_agent)
                        @php
                            $isAgentReceiver = $financial_custody_agent->transfered_type == 'App\Models\Agent';
                        @endphp
                        <tr>
                            <th scope="row">{{$financial_custody_agent->id}}</th>
                            <td>
                                @if($financial_custody_agent->type == 1)
                                    <span class="badge badge-success">{{ __('main.from_dashboard') }}</span>
                                @elseif($financial_custody_agent->type == 2)
                                    <span class="badge badge-info">{{ __('main.transfer_to_agent') }}</span>
                                @elseif($financial_custody_agent->type == 3)
                                    <span class="badge badge-warning">{{ __('main.custody_transfer') }}</span>
                                @elseif($financial_custody_agent->type == 4)
                                    <span class="badge badge-primary">{{ __('main.settle_delivery_policy') }}</span>
                                @elseif($financial_custody_agent->type == 5)
                                    <span class="badge badge-secondary">{{ __('main.office_commission') }}</span>
                                @else
                                    <span class="badge badge-light">-</span>
                                @endif
                            </td>
                            <td>{{$financial_custody_agent->transferer?->name ?? '-' }}</td>
                            <td>
                                @if($isAgentReceiver)
                                    {{$financial_custody_agent->transfered?->name ?? '-'}}
                                @else
                                    {{$financial_custody_agent->transferer?->name ?? '-'}}
                                @endif
                            </td>
                            <td>
                                @if($isAgentReceiver)
                                    <span class="badge badge-success">
                                        <i class="fas fa-arrow-down"></i> {{ __('admin.deposit') }}
                                    </span>
                                @else
                                    <span class="badge badge-danger">
                                        <i class="fas fa-arrow-up"></i> {{ __('admin.withdrawal') }}
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if($isAgentReceiver)
                                    <span class="text-success">+ {{$financial_custody_agent->value}}</span>
                                @else
                                    <span class="text-danger">- {{$financial_custody_agent->value}}</span>
                                @endif
                            </td>
                            <td>{{$financial_custody_agent->created_at}}</td>
                            <td>
                                @if($financial_custody_agent->type == 1)
                                    <button class="btn btn-icon btn-light btn-hover-danger btn-sm delete" onclick="Delete('{{ $financial_custody_agent->id }}', '{{ $financial_custody_agent->transfered_id }}')">
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
                    var url = '{{ route("financial_custody_agents.destroy", ":id") }}' + '?agent_id=' + agent_id;
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
                                title: "{{ __('alerts.done') }}",
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
