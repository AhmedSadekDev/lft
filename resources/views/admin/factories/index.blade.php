@extends("layouts.admin")
@section("content")
<div class="container-fluid lft-simple-page">
    @include("layouts.includes.breadcrumb", [ 'page' => __('main.factories') ])
    <!--begin::Card-->
    <div class="card card-custom gutter-b lft-simple-card lft-simple-card--wide">
        <div class="card-header border-0">
            <div class="card-toolbar">
                <!--begin::Button-->
                @if(auth()->user()->hasPermissionTo('factories.create'))
                <a href="{{route('factories.create')}}" class="btn btn-primary font-weight-bolder">
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
                        <th scope="col">{{ __('admin.contacts') }}</th>
                        <th scope="col">{{ __('admin.attachments') }}</th>
                        <th scope="col">{{ __('admin.created_at') }}</th>
                        <th scope="col"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($factories as $factory)
                        <tr>
                            <th scope="row">{{$factory->id}}</th>
                            <td>{{$factory->name}}</td>
                            <td>
                                <a href="mailto:{{$factory->email}}" class="lft-contact-chip">
                                    <i class="fas fa-envelope" aria-hidden="true"></i>
                                    <span>{{$factory->email}}</span>
                                </a>
                            </td>
                            <td>
                                @if (!is_null($factory->attachments) && is_array($factory->attachments))
                                    @foreach ($factory->attachments as $attachment)
                                        <a href="{{ url($attachment)}}" target="_blank" class="btn btn-icon btn-light btn-hover-primary btn-sm mx-3 ">
                                            <i class="fas fa-file-pdf text-danger"></i>
                                        </a>
                                    @endforeach
                                @else
                                    @if(!is_null($factory->attachments))
                                        <a href="{{ url($factory->attachments)}}" target="_blank" class="btn btn-icon btn-light btn-hover-primary btn-sm mx-3 ">
                                            <i class="fas fa-file-pdf text-danger"></i>
                                        </a>
                                    @else
                                        __
                                    @endif
                                @endif
                            </td>
                            <td>{{$factory->created_at}}</td>
                            <td>
                                @if(auth()->user()->hasPermissionTo('factories.update'))
                                <a href="{{route('factories.edit',$factory->id)}}" class="btn btn-icon btn-light btn-hover-primary btn-sm mx-3">
                                    <i class="fas fa-edit text-primary"></i>
                                </a>
                                @endif
                                @if(auth()->user()->hasPermissionTo('factories.delete'))
                                    <button class="btn btn-icon btn-light btn-hover-danger btn-sm delete" onclick="Delete('{{ $factory->id }}')">
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

function Delete(id) {
            Swal.fire({
                title: "{{ __('alerts.are_you_sure') }}",
                text: "{{ __('alerts.not_revert_information') }}",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: "{{ __('alerts.confirm') }}",
                cancelButtonText: "{{ __('alerts.cancel') }}",
                buttonsStyling: false,
                customClass: {
                    confirmButton: 'btn btn-primary mx-1',
                    cancelButton: 'btn btn-secondary mx-1',
                },
            }).then((result) => {
                if (result.isConfirmed) {
                    var url = '{{ route("factories.destroy", ":id") }}';
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
