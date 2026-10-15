@extends('layouts.admin')
@section('content')
    
    <div class="container-fluid lft-simple-page">
<style>
        #transportationsTable {
            direction: rtl;
        }
        #transportationsTable thead th {
            background-color: var(--surface-secondary) !important;
            color: var(--text-secondary) !important;
            font-weight: 700;
            border-bottom: 1px solid var(--border) !important;
        }
        #transportationsTable tbody tr:hover td {
            background-color: var(--surface-secondary) !important;
            transition: background-color 0.2s;
        }
        .empty-state {
            padding: 40px 20px;
        }
        .empty-state i {
            opacity: 0.5;
        }
    </style>
    <div class="container">
        @include('layouts.includes.breadcrumb', ['page' => __('main.transportations')])
        <!--begin::Card-->
        <div class="card card-custom gutter-b lft-simple-card lft-simple-card--wide">
            <div class="card-header border-0">
                <div class="card-toolbar">
                    <!--begin::Button-->
                    @if (auth()->user()->hasPermissionTo('transportations.create'))
                        <a href="{{ $route_create }}" class="btn btn-primary font-weight-bolder">
                            <i class="fas fa-plus" aria-hidden="true"></i>{{ __('admin.add') }}
                        </a>
                    @endif

                    <div class="float-left ml-2">
                        <button type="button" class="btn btn-success " id="imports" data-toggle="modal"
                            data-target="#import_excels">
                            <i class="icon-share-alternitive"></i>
                            {{ __('admin.import') }}
                        </button>
                    </div>
                    <div class="float-left ml-2">
                        <a href="{{ route('companyTransportations.export', request()->all()) }}" class="btn btn-info">
                            <i class="fas fa-file-excel"></i>
                            {{ __('admin.export') }}
                        </a>
                    </div>
                    <!--end::Button-->
                </div>
                <div class="mt-3">
                    <a href="{{ route('companies.index') }}" class="btn btn-secondary float-right">
                        {{ __('main.back') }}
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-striped" id="transportationsTable">
                        <thead class="thead-light">
                            <tr>
                                <th scope="col" style="width: 50px;">#</th>
                                <th scope="col">{{ __('main.company') }}</th>
                                <th scope="col">{{ __('main.container') }}</th>
                                <th scope="col">{{ __('admin.departure_location') }}</th>
                                <th scope="col">{{ __('admin.loading_location') }}</th>
                                <th scope="col">{{ __('admin.aging_location') }}</th>
                                <th scope="col" style="text-align: left;">{{ __('admin.price') }}</th>
                                <th scope="col" style="width: 120px; text-align: center;">{{ __('admin.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($transportations as $transportation)
                                <tr>
                                    <th scope="row" class="font-weight-bold">
                                        {{ $transportation->id }}
                                    </th>
                                    <td>
                                        <span class="font-weight-bold text-primary">
                                            {{ $transportation->company_name ?? __('main.not_found') }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-info badge-pill">
                                            {{ $transportation->container_type ?? __('main.not_found') }}
                                        </span>
                                    </td>
                                    <td>
                                        <i class="fas fa-map-marker-alt text-danger mr-1"></i>
                                        {{ $transportation->Departure->title ?? __('main.not_found') }}
                                    </td>
                                    <td>
                                        <i class="fas fa-map-marker-alt text-success mr-1"></i>
                                        {{ $transportation->Loading->title ?? __('main.not_found') }}
                                    </td>
                                    <td>
                                        <i class="fas fa-map-marker-alt text-warning mr-1"></i>
                                        {{ $transportation->Aging->title ?? __('main.not_found') }}
                                    </td>
                                    <td>
                                        <span class="font-weight-bold text-success" style="font-size: 1.1rem;">
                                            {{ number_format($transportation->price ?? 0, 2) }} {{ __('main.currency') }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex justify-content-center align-items-center">
                                            @if (auth()->user()->hasPermissionTo('transportations.update'))
                                                <a href="{{ route('companyTransportations.edit', ['companyTransportation' => $transportation->id, 'company_id' => isset(request()->company_id) && !is_null(request()->company_id) ? request()->company_id : null]) }}"
                                                    class="btn btn-icon btn-light btn-hover-primary btn-sm mr-2"
                                                    title="{{ __('admin.edit') }}">
                                                    <i class="fas fa-edit text-primary"></i>
                                                </a>
                                            @endif
                                            @if (auth()->user()->hasPermissionTo('transportations.delete'))
                                                <button class="btn btn-icon btn-light btn-hover-danger btn-sm delete"
                                                    onclick="Delete('{{ $transportation->id }}')"
                                                    title="{{ __('admin.delete') }}">
                                                    <i class="fas fa-trash text-danger"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-5">
                                        <div class="empty-state">
                                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                            <h5 class="text-muted">{{ __('main.no_data_available') }}</h5>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!--end::Card-->
    </div>
    @include('admin.transportations.modals.import')
    </div>
@endsection
@push('js')
    <script>
        $(document).ready(function() {
            if ($('#transportationsTable').length) {
                // Destroy existing DataTable if it exists
                if ($.fn.DataTable.isDataTable('#transportationsTable')) {
                    $('#transportationsTable').DataTable().destroy();
                }

                // Initialize DataTable
                $('#transportationsTable').DataTable({
                    "language": {
                        "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Arabic.json"
                    },
                    "order": [[0, "desc"]],
                    "pageLength": 10,
                    "responsive": true,
                    "dom": '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
                    "columnDefs": [
                        { "orderable": false, "targets": [7] } // Disable sorting on actions column
                    ]
                });
            }
        });

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
                    var url = '{{ route('companyTransportations.destroy', ':id') }}';
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
