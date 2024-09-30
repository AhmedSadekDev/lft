@extends("layouts.admin")
@section("content")
<div class="container">
    @include("layouts.includes.breadcrumb", [ 'page' => __('main.daily_reports') ])
    <!--begin::Card-->
    <div class="card card-custom">
        <div class="card-header flex-wrap py-5">
            <div class="card-toolbar">
                <!--begin::Button-->
               
                <!--end::Button-->
            </div>
        </div>
        <div class="card-body">
            <table class="table table-responsive-xl" id="table">
                <thead class="thead-light">
                    <tr>
                        <th scope="col">#</th>
                        <th scope="col">{{ __('admin.agent') }}</th>
                        <th scope="col">{{ __('admin.action') }}</th>
                        <th scope="col">{{ __('main.date') }}</th>
                        <th scope="col">{{ __('main.time') }}</th>
                        {{-- <th scope="col"></th> --}}
                    </tr>
                </thead>
                <tbody>
                    @foreach ($log_activities as $log_activity)
                    <tr>
                            <th scope="row">{{$log_activity->id}}</th>
                            <td>
                                {{$log_activity?->attacher?->name ?? ""}}
                            </td>
                    
                            <td>{{ $log_activity->action ?? "" }}</td>
                            <td>{{ $log_activity->date ?? "" }}</td>
                            <td>{{ $log_activity->time ?? "" }}</td>
                          
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
                    var url = '{{ route("agents.destroy", ":id") }}';
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
                            if(xhr.status == 200){
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

