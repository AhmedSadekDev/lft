@extends('layouts.admin')

@section('css')
@endsection

@section('content')
    <div class="container">
        @include('layouts.includes.breadcrumb', ['page' => __('main.Tax_invoices')])
        <!--begin::Card-->
        <div class="card card-custom">
            <div class="card-header flex-wrap  align-items-center py-5">
                <div class="card-toolbar">
                    <div class="">
                        <!--begin::Button-->
                        <a href="{{ route('bookings.create', request()->id) }}" class="btn btn-primary font-weight-bolder">
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
                    </div>

                    <!--end::Button-->
                </div>
                <div class="">
                    <!-- Button trigger modal -->
                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#exampleModal">
                        {{ __('admin.filter') }}
                    </button>

                    <a href="{{ route('invoice_payments.excel', request()->id) }}"
                        class="btn btn-primary">{{ __('admin.export') }}</a>

                    <a href="{{ route('invoice_payments.pdf', request()->id) }}"
                        class="btn btn-primary">{{ __('main.download_all_invoices') }}</a>


                    <!-- Modal -->
                    <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel"
                        aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="exampleModalLabel">{{ __('admin.filter') }}</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        X
                                    </button>
                                </div>
                                <form action="{{ route('bokkings.invoices', request()->id) }}" method="get">
                                    <div class="modal-body">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label for="idSelect">{{ __('main.companies') }}</label>
                                                    <select class="form-control" name="id" id="idSelect">
                                                        @foreach ($companies as $company)
                                                            <option value="{{ $company->id }}">{{ $company->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="dateFrom">{{ __('admin.from') }}</label>
                                                    <input id="dateFrom" class="form-control" type="date"
                                                        name="date_from">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="dateTo">{{ __('admin.to') }}</label>
                                                    <input id="dateTo" class="form-control" type="date"
                                                        name="date_to">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                        <button type="submit" class="btn btn-primary">{{ __('admin.filter') }}</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">

                    <table class="table table-bordered table-responsive-xl" id="table">
                        <thead class="thead-light">
                            <tr>
                                <th scope="col">{{ __('main.date') }}</th>
                                <th scope="col">{{ __('main.booking_no') }}</th>
                                <th scope="col">{{ __('admin.type_of_action') }}</th>
                                <th scope="col">{{ __('admin.value_added_tax') }}</th>
                                <th scope="col">{{ __('admin.discount') }}</th>
                                <th scope="col">{{ __('admin.taxed') }}</th>
                                <th scope="col">{{ __('admin.untaxed') }}</th>
                                <th scope="col">{{ __('admin.transportation_total') }}</th>
                                <th scope="col">{{ __('admin.total') }}</th>
                                <th scope="col">{{ __('the_payer') }}</th>
                                <th scope="col">{{ __('the_rest') }}</th>

                                <th scope="col"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($bookings as $booking)
                                @php
                                    $invoice = $booking->invoice ?? null;
                                    
                                    if ($invoice) {
                                        $invoiceTotalBeforeTax = $invoice->invoice_total_before_tax;
                                        $vatValue = $invoice->value_added_tax_amount; // Already calculated in the model
                                        $saleValue = $invoice->sales_tax_amount; // Already calculated in the model
                                        $discountValue = $invoice->discount_amount; // Fixed discount, not a percentage
                                    
                                        $taxedServicesTotal = $invoice->taxed_services_total_before_vat ?? 0;
                                        $untaxedServicesTotal = $invoice->untaxed_services_total_before_vat ?? 0;
                                        $transportationTotal = $invoice->transportation_total_before_vat ?? 0;
                                    
                                        $finalValue = $invoiceTotalBeforeTax + $taxedServicesTotal + $untaxedServicesTotal + $vatValue - $saleValue - $discountValue;
                                    } else {
                                        $vatValue = $saleValue = $discountValue = $taxedServicesTotal = $untaxedServicesTotal = $transportationTotal = $finalValue = 0;
                                    }
                                @endphp


                                <tr>
                                    <td>{{ $booking->created_at }}</td>
                                    <td>{{ $booking->invoice->invoice_number  }}</td>

                                    <td>
                                        @if ($booking->type_of_action == 0)
                                            {{ __('actions.Outbound') }}
                                        @elseif($booking->type_of_action == 1)
                                            {{ __('actions.Inbound') }}
                                        @else
                                            {{ __('actions.Clearance') }}
                                        @endif
                                    </td>


                                    <td>{{ $vatValue }}</td>
                                    <td>{{ $discountValue }}</td>
                                    <td>{{ $taxedServicesTotal }}</td>
                                    <td>{{ $untaxedServicesTotal }}</td>
                                    <td>{{ $transportationTotal }}</td>
                                    <td>{{ $finalValue }}</td>
                                    <td>
                                        <a href="{{ route('invoice_payments.index', $booking->invoice->id) }}">
                                            {{ $booking->invoice->invoicePayments->sum('value') ?? 0 }}
                                        </a>
                                    </td>
                                    <td>{{ $finalValue - ($booking->invoice->invoicePayments->sum('value') ?? 0) }}</td>



                                    <td>
                                        <div class="row">
                                            <div class="col-md-3 mr-3">
                                                <a href="{{ route('booking-invoices.show', $booking->invoice->id) }}"
                                                    class="btn btn-icon btn-light btn-hover-primary btn-sm mx-3 ">
                                                    <i class="fas fa-eye text-primary"></i>
                                                </a>
                                            </div>
                                            @if ($finalValue > ($booking->invoice->invoicePayments->sum('value') ?? 0))
                                                <div class="col-md-3 mr-3">
                                                    <a href="#" data-toggle="modal" data-target="#createModal"
                                                        class="btn btn-icon btn-light btn-hover-primary btn-sm mx-3 create-btn" data-invoice_id="{{ $invoice->id }}">
                                                        {{ __('Pay') }}
                                                    </a>
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    
                    
                    <table class="table table-responsive-xl mt-5" id="table">
                    <thead class="thead-light">
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">{{ __('main.booking_no') }}</th>
                            <th scope="col">{{ __('admin.value') }}</th>
                            <th scope="col">{{ __('admin.image') }}</th>
                            <th scope="col">{{ __('admin.created_at') }}</th>
                            <th scope="col"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($allPayments as $payment)
                            <tr>
                                <th scope="row">{{ $payment->id }}</th>
                                <td> {{ $payment->invoice->invoice_number ?? '' }} </td>
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
            </div>


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
                        <input type="hidden" name="invoice_id" id="invoiceIdInput">

                        <div class="modal-body">
                            <div class="form-group">
                                <label for="bank_id">{{ __('admin.bank') }}</label>
                                <select name="bank_id" id="bank_id" class="form-control">
                                    @foreach ($banks as $bank)
                                        <option value="{{ $bank->id }}">{{ $bank->name }}</option>
                                    @endforeach
                                </select>
                                @error('bank_id')
                                    <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
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
        <!--end::Card-->
    </div>
@endsection
@push('js')
<script>
    $(document).on('click', '.create-btn', function() {
      $('#invoiceIdInput').val($(this).data('invoice_id'));
    });
</script>
@endpush
