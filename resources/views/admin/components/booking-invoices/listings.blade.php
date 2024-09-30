{{-- ================== ================== TITLE ================== ================== --}}

<div class="card card-custom gutter-b">
    <div class="card-header">
        <div class="card-title">
            <h1>
                {{ __('admin.add_new_invoice') }}
            </h1>
        </div>
        <div class="mt-3">
            <a href="{{ route('bookings.index') }}" class="btn btn-secondary float-right">
                {{ __('main.back') }}
            </a>
        </div>
    </div>
</div>

{{-- ================== ================== END TITLE ================== ================== --}}



{{-- ================== ================== ============ ================== ================== --}}
{{-- ================== ================== GENERAL INFO ================== ================== --}}
{{-- ================== ================== ============ ================== ================== --}}
<div class="card card-custom gutter-b">
    <div class="card-header">
        <div class="card-title">
            <h2>
                {{ __('admin.general_details') }}
            </h2>
        </div>
    </div>
    <div class="card-body">
        <div class="row">

            <div class="col-md-12 col-sm-12 mt-2">
                <div class="form-group">
                    <div class="row">
                        <div class="col-sm-6 col-md-4">
                            <h4>
                                {{ __('admin.booking_number') . ': ' . $booking->booking_number ?? '' }}
                            </h4>
                        </div>
                        <div class="col-sm-6 col-md-4">
                            <h4 class="d-inline">
                                {{ __('admin.invoice_number') }}:
                            </h4>
                            <h4 class="d-inline">
                                {{ ($invoice->invoice_number ?? $invoice_number) ?? '' }}
                            </h4>
                        </div>
                        <div class="col-sm-6 col-md-4">
                            <h4>
                                {{ __('admin.company_name') . ': ' . $booking->company->name ?? "" }}
                            </h4>
                        </div>
                        <div class="col-sm-6 col-md-4">
                            <h4>
                                {{ __('admin.certificate_number') . ': ' . $booking->certificate_number ?? "" }}
                            </h4>
                        </div>
                        <div class="col-sm-6 col-md-4">
                            <h4>
                                {{ __('admin.booking_number') . ': ' . $booking->booking_number ?? "" }}
                            </h4>
                        </div>
                        <div class="col-sm-6 col-md-4">
                            <h4>
                                {{ __('admin.arrival_date') . ': ' . $booking->arrival_date ?? "" }}
                            </h4>
                        </div>
                        <div class="col-sm-6 col-md-4">
                            <h4>
                                {{ __('admin.shipping_agent') . ': ' . $booking->shippingAgent ? $booking->shippingAgent->title : "" }}
                            </h4>
                        </div>
                    </div>
                </div>
            </div>
            {{-- <hr size="12" width="95%"> --}}
        </div>
    </div>
</div>

{{-- ================== ================== ================ ================== ================== --}}
{{-- ================== ================== END GENERAL INFO ================== ================== --}}
{{-- ================== ================== ================ ================== ================== --}}

<!-- For loop this div -->
{{-- <div class="col-md-12 col-sm-12 mt-2 ml-3">
        <div class="form-group">
            <div class="card-title">
                <h2>
                    {{ __('admin.invoice') }}
                </h2>
            </div>
            <div class="row" id="default_type">
                <div class="col-md-12 col-sm-12 mt-2">
                    <div class="form-group{{ $errors->has('is_taxed') ? ' has-error' : '' }}">
                        {!! Form::label('is_taxed', __('admin.taxed')) !!}
                        {!! Form::select(
                            'is_taxed',
                            [
                                'taxed' => __('admin.taxed'),
                                'untaxed' => __('admin.untaxed'),
                            ],
                            isset($invoice) && !is_null($invoice->taxed) ? ($invoice->taxed == 1 ? 'untaxed' : 'taxed') : ($booking->taxed == 1 ? 'untaxed' : 'taxed'),
                            [
                                'id' => 'is_taxed',
                                'class' => 'form-control',
                                'required' => 'required',
                            ],
                        ) !!}
                        <small class="text-danger">{{ $errors->first('is_taxed') }}</small>
                    </div>
                </div>
            </div>
            @error('taxed')
                <small class="aleart text-danger">{{ $message }}</small>
            @enderror
        </div>
    </div> --}}
<!-- For loop this div -->


{{-- ================== ================== ======= ================== ================== --}}
{{-- ================== ================== INVOICE ================== ================== --}}
{{-- ================== ================== ======= ================== ================== --}}

<div class="card card-custom gutter-b">
    <div class="card-header">
        <div class="card-title">
            <h2>
                {{ __('admin.bill_type_invoice') }}
            </h2>
        </div>
    </div>
    <div class="card-body">

        {{-- ================== ================== TRANSPORTATION ================== ================== --}}
        <div class="row">
            <div class="col-md-12 col-sm-12 mt-2">
                <div class="card-title">
                    <h4>
                        {{ __('admin.transportation_details') }}
                    </h4>
                </div>
                <div>
                    <div class="col-md-12">
                        @include('admin.components.booking-containers.table')
                    </div>
                </div>
            </div>
        </div>
        {{-- ================== ================== END TRANSPORTATION ================== ================== --}}

        <hr size="12" width="95%">
        <br>

        {{-- ================== ================== TAXED SERVICES ================== ================== --}}
        <div class="row">
            <div class="col-md-12 col-sm-12 mt-2">
                <div class="card-title">
                    <h4>
                        {{ __('admin.taxed_services') }}
                    </h4>
                </div>
                <div>
                    <div class="col-md-12">
                        @include('admin.components.booking-services.table', ['booking_services' => $booking->taxed_services])
                    </div>
                </div>
            </div>
        </div>
        {{-- ================== ================== END TAXED SERVICES ================== ================== --}}

    </div>
</div>

{{-- ================== ================== =========== ================== ================== --}}
{{-- ================== ================== END INVOICE ================== ================== --}}
{{-- ================== ================== =========== ================== ================== --}}


{{-- ================== ================== ============ ================== ================== --}}
{{-- ================== ================== ATTACHMENTS ================== ================== --}}
{{-- ================== ================== ============ ================== ================== --}}

<div class="card card-custom gutter-b">
    <div class="card-header">
        <div class="card-title">
            <h2>
                {{ __('admin.attachments') }}
            </h2>
        </div>
    </div>
    <div class="card-body">

        {{-- ================== ================== UNTAXED SERVICES ================== ================== --}}
        <div class="row">
            <div class="col-md-12 col-sm-12 mt-2">
                <div class="card-title">
                    <h4>
                        {{ __('admin.untaxed_services') }}
                    </h4>
                </div>
                <div>
                    <div class="col-md-12">
                        @include('admin.components.booking-services.table', ['booking_services' => $booking->untaxed_services, 'expensesServices' => $booking->expenses])
                        
                    </div>
                </div>
            </div>
        </div>
        {{-- ================== ================== END UNTAXED SERVICES ================== ================== --}}

    </div>
</div>

{{-- ================== ================== ================ ================== ================== --}}
{{-- ================== ================== END ATTACHMENTS ================== ================== --}}
{{-- ================== ================== ================ ================== ================== --}}
