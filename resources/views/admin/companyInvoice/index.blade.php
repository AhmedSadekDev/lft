@extends('layouts.admin')
@php
    $from = isset($_GET['from']) ? $_GET['from'] : '';
    $to = isset($_GET['to']) ? $_GET['to'] : '';
@endphp

@section('content')
    <div class="container-fluid lft-simple-page">
        @include('layouts.includes.breadcrumb', ['page' => $company->name])
        <!--begin::Card-->
        <div class="card card-custom gutter-b lft-simple-card lft-simple-card--wide">
            <div class="card-header border-0">
                <div class="card-toolbar">
                    <a href="{{ route('companyInvoice.create', $company->id) }}" class="btn btn-primary font-weight-bolder">
                        <i class="fas fa-plus" aria-hidden="true"></i>{{ __('admin.add') }}
                    </a>

                </div>
                <!--begin::Button-->
            </div>
        </div>
        <div class="">
            <br>
            <!-- Button trigger modal -->
            <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#exampleModal">
                فلتر
            </button>
            @if ($from)
                <a href="{{ route('companyInvoice.export', [$from, $to, $company->id]) }}" class="btn btn-secondary">
                    تحميل الملف
                </a>
            @endif

            <!-- Modal -->
            <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-md">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">فلتر بالتاريخ</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                X
                            </button>
                        </div>
                        <form action="{{ route('companyInvoice.index', $company->id) }}" method="get">
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="dateFrom">من </label>
                                            <input id="dateFrom" class="form-control" type="date" name="from">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="dateTo">الي</label>
                                            <input id="dateTo" class="form-control" type="date" name="to">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">غلق</button>
                                <button type="submit" class="btn btn-primary">فلتر</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">

            <table class="table" id="table">
                <thead class="thead-light">
                    <tr>
                        <th scope="col">#</th>
                        <th scope="col">صورة التحويل</th>
                        <th scope="col">قيمة التحويل</th>
                        <th scope="col">من قام بالاضافة</th>
                        <th scope="col">{{ __('admin.status') }}</th>

                        <th scope="col">{{ __('admin.created_at') }}</th>
                        <th scope="col"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($invoices as $invoice)
                        @php
                            $imagePath = Str::startsWith($invoice->image, 'admin')
                                ? asset($invoice->image)
                                : asset('Admin/images/companyInvoice/' . $invoice->image);
                        @endphp

                        <tr>
                            <th scope="row">{{ $invoice->id }}</th>
                            <td>
                                <a href="{{ $imagePath }}" download><img
                                        class="bg-soft-primary rounded img-fluid avatar-40 me-3"
                                        style="width:100px;height:100px"
                                        src="{{ $imagePath }}"
                                        alt="Ads"></a>
                            </td>
                            <td>
                                {{ $invoice->total }}
                            </td>
                            <td>
                                {{ $invoice->user->name }}
                            </td>

                            <td>
                                @if ($invoice->type)
                                    {{ __('main.credit') }}
                                @else
                                    {{ __('main.debit') }}
                                @endif
                            </td>

                            <td>{{ $invoice->created_at }}</td>
                            <td>
                                <div class="row">
                                    <div class="col-md-3 mr-3">
                                        <a href="{{ route('companyInvoice.edit', $invoice->id) }}"
                                            class="btn btn-icon btn-light btn-hover-primary btn-sm mx-3 ">
                                            <i class="fas fa-edit text-primary"></i>
                                        </a>
                                    </div>
                                    <div class="col-md-3">
                                        <button class="btn btn-icon btn-light btn-hover-danger btn-sm mx-3 delete"
                                            onclick="Delete('{{ $invoice->id }}')">
                                            <i class="fas fa-trash text-danger"></i>
                                        </button>
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
                    var url = '{{ route('companyInvoice.destroy', ':id') }}';
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
