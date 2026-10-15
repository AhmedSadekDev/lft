@extends("layouts.admin")
@section("content")
<div class="container-fluid lft-simple-page">
    @include("layouts.includes.breadcrumb", [ 'page' => __('main.branches') ])
    <!--begin::Card-->
    <div class="card card-custom gutter-b lft-simple-card lft-simple-card--wide">
        <div class="card-header border-0">
            <div class="card-toolbar">
                <!--begin::Button-->
                @if(auth()->user()->hasPermissionTo('branches.create'))
                <a href="{{route('branches.create')}}" class="btn btn-primary font-weight-bolder">
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
                                <a href="mailto:{{$branch->email}}" class="lft-contact-chip">
                                    <i class="fas fa-envelope" aria-hidden="true"></i>
                                    <span>{{$branch->email}}</span>
                                </a>
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

function DeleteBranch(id) {
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
