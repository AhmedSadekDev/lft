@extends('layouts.admin')
@section('content')
    <div class="container">
        @include('layouts.includes.breadcrumb', ['page' => __('main.payments')])
        <!--begin::paymentd-->
        <div class="paymentd paymentd-custom">
            <div class="paymentd-header flex-wrap py-5">
                <div class="paymentd-toolbar">
                    <!--begin::Button-->
                    <a href="#" data-toggle="modal" data-target="#createModal" class="btn btn-primary font-weight-bolder">
                        <i class="fas fa-plus" aria-hidden="true"></i>{{ __('admin.add') }}
                    </a>
                    <!--end::Button-->
                </div>
                <div class="mt-3">
                    <a href="{{ route('bokkings.invoices', $invoice->booking->company_id) }}" class="btn btn-secondary float-right">
                        {{ __('main.back') }}
                    </a>
                </div>
            </div>
            <div class="paymentd-body">
                <table class="table table-responsive-xl" id="table">
                    <thead class="thead-light">
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">{{ __('admin.value') }}</th>
                            <th scope="col">{{ __('admin.image') }}</th>
                            <th scope="col">{{ __('admin.created_at') }}</th>
                            <th scope="col"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($payments as $payment)
                            <tr>
                                <th scope="row">{{ $payment->id }}</th>
                                <td> {{ $payment->value }} </td>
                                <td> <a href="{{ asset($payment->image) }}" download> <img src="{{ asset($payment->image) }}" width="50" alt=""></a></td>
                                <td>{{ $payment->created_at }}</td>
                                <td>
                                    <div class="row">
                                        <div class="col-md-3 mr-3">

                                            <a href="#" data-toggle="modal" data-target="#updateModal" data-url="{{ route('invoice_payments.update', $payment->id) }}" data-id="{{ $payment->id }}" data-value="{{ $payment->value }}" class="btn btn-icon btn-light btn-hover-primary btn-sm mx-3 edit-btn ">
                                                <i class="fas fa-edit text-primary"></i>
                                            </a>

                                        </div>
                                        <div class="col-md-3">

                                            <button class="btn btn-icon btn-light btn-hover-danger btn-sm mx-3 delete"
                                                onclick="Delete('{{ $payment->id }}')">
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



            <!-- Modal -->
            <div class="modal fade" id="createModal" tabindex="-1" aria-labelledby="createModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="createModalLabel">{{ __('Pay') }}</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <form action="{{ route('invoice_payments.store') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="invoice_id" value="{{ $invoice->id }}">

                            <div class="modal-body">
                                <div class="form-group">
                                    <label for="value">{{ __('admin.value') }}</label>
                                    <input type="text" name="value" id="value" class="form-control">
                                    @error('value')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <label for="image">{{ __('admin.image') }}</label>
                                    <input type="file" name="image" id="image" class="form-control">
                                    @error('image')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary"
                                    data-dismiss="modal">{{ __('admin.close') }}</button>
                                <button type="submit" class="btn btn-primary">{{ __('admin.save') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>


            <!-- Modal -->
            <div class="modal fade" id="updateModal" tabindex="-1" aria-labelledby="updateModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="updateModalLabel">{{ __('Pay') }}</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <form id="updateForm" method="POST" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="invoice_id" value="{{ $invoice->id }}">

                            <div class="modal-body">
                                <div class="form-group">
                                    <label for="updateValueINput">{{ __('admin.value') }}</label>
                                    <input type="text" name="value" id="updateValueINput" class="form-control">
                                    @error('value')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="form-group">
                                    <label for="image">{{ __('admin.image') }}</label>
                                    <input type="file" name="image" id="image" class="form-control">
                                    @error('image')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary"
                                    data-dismiss="modal">{{ __('admin.close') }}</button>
                                <button type="submit" class="btn btn-primary">{{ __('admin.save') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <!--end::paymentd-->
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
                    var url = '{{ route('invoice_payments.destroy', ':id') }}';
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


<script>
    $(document).on('click', '.edit-btn', function()
    {
        let url = $(this).data('url');
        let value = $(this).data('value');


        $('#updateValueINput').val(value);
        $('#updateForm').attr('action', url);

    });
</script>
@endpush
