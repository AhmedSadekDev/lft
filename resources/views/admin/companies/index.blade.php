@extends('layouts.admin')
@section('content')
    <div class="container">
        @include('layouts.includes.breadcrumb', ['page' => __('main.companies')])
        <!--begin::Card-->
        <div class="card card-custom">
            <div class="card-header flex-wrap py-5">
                <div class="card-toolbar">
                    <!--begin::Button-->
                    @if (auth()->user()->hasPermissionTo('companies.create'))
                        <a href="{{ route('companies.create') }}" class="btn btn-primary font-weight-bolder">
                            <span class="svg-icon svg-icon-md">
                                <!--begin::Svg Icon | path:assets/media/svg/icons/Design/Flatten.svg-->
                                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"
                                    width="24px" height="24px" viewBox="0 0 24 24" version="1.1">
                                    <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                                        <rect x="0" y="0" width="24" height="24" />
                                        <circle fill="#000000" cx="9" cy="15" r="6" />
                                        <path
                                            d="M8.8012943,7.00241953 C9.83837775,5.20768121 11.7781543,4 14,4 C17.3137085,4 20,6.6862915 20,10 C20,12.2218457 18.7923188,14.1616223 16.9975805,15.1987057 C16.9991904,15.1326658 17,15.0664274 17,15 C17,10.581722 13.418278,7 9,7 C8.93357256,7 8.86733422,7.00080962 8.8012943,7.00241953 Z"
                                            fill="#000000" opacity="0.3" />
                                    </g>
                                </svg>
                                <!--end::Svg Icon-->
                            </span>{{ __('admin.add') }}
                        </a>
                    @endif
                    <!--end::Button-->
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">

                    <table class="table" id="table">
                        <thead class="thead-light">
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">{{ __('admin.name') }}</th>
                                <th scope="col">{{ __('admin.email') }}</th>
                                <th scope="col">{{ __('admin.address') }}</th>
                                <th scope="col">{{ __('admin.phone') }}</th>
                                <th scope="col">{{ __('admin.tax_no') }}</th>
                                <th scope="col">{{ __('admin.taxed_status') }}</th>
                                <th scope="col">{{ __('admin.bill_type') }}</th>
                                <th scope="col">{{ __('admin.attachments') }}</th>
                                <th scope="col">{{ __('admin.created_at') }}</th>
                                <th scope="col"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($companies as $company)
                                <tr>
                                    <th scope="row">{{ $company->id }}</th>
                                    <td>
                                        {{ $company->name }}
                                    </td>
                                    <td>
                                        {{ $company->email }}
                                    </td>
                                    <td>
                                        {{ $company->address }}
                                    </td>
                                    <td>
                                        {{ $company->phone }}
                                    </td>
                                    <td>
                                        {{ $company->tax_no }}
                                    </td>
                                    <td>
                                        <span
                                            class="badge badge-{{ $company->taxed == 0 ? 'danger' : 'success' }} text-white">
                                            <i class="fa fa-{{ $company->taxed == 0 ? 'xmark' : 'check' }} text-white"></i>
                                            {{ $company->taxed_invoice }}
                                        </span>
                                        @if($company->invoices->count())
                                        <a class="mt-2" href="{{ route('bokkings.invoices', $company->id) }}">
                                            {{ __('main.Tax_invoices') }}
                                        </a>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $company->bill_type == 1 ? __('admin.bill_type_invoice') : __('admin.bill_type_statement') }}
                                    </td>
                                    <td>

                                        @if (!is_null($company->attachments))
                                            @if (is_array($company->attachments))
                                                @foreach ($company->attachments as $attachment)
                                                    <a href="{{ url($attachment) }}"
                                                        class="btn btn-icon btn-light btn-hover-primary btn-sm mx-3 ">
                                                        <i
                                                            class="fas fa-file-{{ pathinfo($attachment, PATHINFO_EXTENSION) == 'pdf' ? 'pdf text-danger' : 'image text-primary' }} "></i>
                                                    </a>
                                                @endforeach
                                            @else
                                                <a href="{{ url($company->attachments) }}"
                                                    class="btn btn-icon btn-light btn-hover-primary btn-sm mx-3 ">
                                                    <i class="fas fa-file-pdf text-danger"></i>
                                                </a>
                                            @endif
                                        @endif
                                    </td>
                                    <td>{{ $company->created_at }}</td>
                                    <td>
                                        <div class="row">
                                            <div class="col-md-3 mr-3">
                                                @if (auth()->user()->hasPermissionTo('companies.update'))
                                                    <a href="{{ route('companies.edit', $company->id) }}"
                                                        class="btn btn-icon btn-light btn-hover-primary btn-sm mx-3 ">
                                                        <i class="fas fa-edit text-primary"></i>
                                                    </a>
                                                @endif
                                            </div>
                                            <div class="col-md-3">
                                                @if (auth()->user()->hasPermissionTo('companies.delete'))
                                                    <button
                                                        class="btn btn-icon btn-light btn-hover-danger btn-sm mx-3 delete"
                                                        onclick="Delete('{{ $company->id }}')">
                                                        <i class="fas fa-trash text-danger"></i>
                                                    </button>
                                                @endif
                                            </div>
                                            <div class="col-md-12 mt-2">
                                                @if (auth()->user()->hasPermissionTo('transportations.create'))
                                                    <a href="{{ route('companyTransportations.index', ['company_id' => $company->id]) }}"
                                                        class="btn btn-primary btn-hover-light">
                                                        <i class="fas fa-plus text-white"></i>
                                                        {{ __('admin.add_quotation_price') }}
                                                    </a>
                                                @endif
                                            </div>
                                            <div class="col-md-12 mt-2">
                                                @if (auth()->user()->hasPermissionTo('services.create'))
                                                    <a href="{{ route('companyServices.index', ['company' => $company]) }}"
                                                        class="btn btn-primary btn-hover-light">
                                                        <i class="fas fa-plus text-white"></i>
                                                        {{ __('admin.services') }}
                                                    </a>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!--end::Card-->
    </div>

    @if (auth()->user()->hasPermissionTo('companies.index'))
        <!-- Creates the bootstrap modal where the Note Of Transaction For users will appear -->
        <div class="modal fade" id="attachmentModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h6>{{ __('admin.attachment') }}</h6>
                    </div>
                    <div class="modal-body" id="attachment_preview">

                    </div>
                </div>
            </div>
        </div>
    @endif
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
                    var url = '{{ route('companies.destroy', ':id') }}';
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

        function openFile(attach) {
            $('#attachment_preview').html(`<embed src="${attach}"  frameborder="0" width="100%" height="400px">`)
            $('#attachmentModal').modal('show');
        }
    </script>
@endpush
