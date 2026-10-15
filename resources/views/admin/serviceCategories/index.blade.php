@extends('layouts.admin')
@section('content')
    <div class="container-fluid lft-simple-page">
        @include('layouts.includes.breadcrumb', ['page' => __('main.serviceCategories')])

        <div class="card card-custom gutter-b lft-simple-card lft-simple-card--wide">
            <div class="card-header border-0">
                <div class="card-title mb-0">
                    <h3 class="card-label mb-0">
                        <i class="fas fa-tags text-primary ml-2"></i>
                        {{ __('main.serviceCategories') }}
                    </h3>
                </div>
                <div class="card-toolbar">
                    @if (auth()->user()->hasPermissionTo('serviceCategories.create'))
                        <a href="{{ route('serviceCategories.create') }}" class="btn btn-primary font-weight-bolder">
                            <i class="fas fa-plus" aria-hidden="true"></i>
                            {{ __('admin.add') }}
                        </a>
                    @endif
                </div>
            </div>
            <div class="card-body">
                <table class="table table-responsive-xl" id="table">
                    <thead class="thead-light">
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">{{ __('admin.title') }}</th>
                            <th scope="col">{{ __('admin.service_status') }}</th>
                            <th scope="col">{{ __('admin.created_at') }}</th>
                            <th scope="col">{{ __('admin.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($serviceCategories as $serviceCategory)
                            <tr>
                                <th scope="row">{{ $serviceCategory->id }}</th>
                                <td>{{ $serviceCategory->title }}</td>
                                <td>{{ serviceStatus($serviceCategory->service_status) }}</td>
                                <td>{{ $serviceCategory->created_at }}</td>
                                <td>
                                    @if (auth()->user()->hasPermissionTo('serviceCategories.update'))
                                        <a href="{{ route('serviceCategories.edit', $serviceCategory->id) }}"
                                            class="btn btn-icon btn-light btn-hover-primary btn-sm mx-1"
                                            title="{{ __('admin.edit') }}">
                                            <i class="fas fa-edit text-primary"></i>
                                        </a>
                                    @endif
                                    @if (auth()->user()->hasPermissionTo('serviceCategories.delete'))
                                        <button type="button"
                                            class="btn btn-icon btn-light btn-hover-danger btn-sm mx-1"
                                            onclick="Delete('{{ $serviceCategory->id }}')"
                                            title="{{ __('admin.delete') }}">
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
            }).then(function (result) {
                if (!result.isConfirmed) return;

                var url = '{{ route('serviceCategories.destroy', ':id') }}'.replace(':id', id);

                $.ajax({
                    url: url,
                    type: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    success: function () {
                        Swal.fire({
                            title: "{{ __('alerts.done') }}",
                            icon: 'success',
                            showConfirmButton: false,
                            timer: 1500,
                            timerProgressBar: true,
                        }).then(function () {
                            location.reload();
                        });
                    },
                    error: function (xhr) {
                        Swal.fire({
                            title: "{{ __('alerts.error') }}",
                            text: (xhr.responseJSON && xhr.responseJSON.msg) ? xhr.responseJSON.msg : "{{ __('alerts.error') }}",
                            icon: 'error',
                        });
                    },
                });
            });
        }
    </script>
@endpush
