@if ($method == 'POST')
    {!! Form::open(['url' => $action, 'method' => $method, 'enctype' => 'multipart/form-data', 'files' => true]) !!}
@elseif ($method == 'PUT')
    {!! Form::model($invoice, [
        'url' => [$action],
        'method' => $method,
        'enctype' => 'multipart/form-data',
        'files' => true,
    ]) !!}
@endif


{{-- ================== ================== ====== ================== ================== --}}
{{-- ================== ================== TOTALS ================== ================== --}}
{{-- ================== ================== ====== ================== ================== --}}


<div class="card card-custom gutter-b">
    <div class="card-header">
        <div class="card-title">
            <h2>
                {{ __('admin.totals') }}
            </h2>
        </div>
    </div>
    <div class="card-body">
        {{-- ================== ================== INVOICE TOTALS ================== ================== --}}
        <div class="row">
            

            <div class="col-sm-12 col-md-6 col-lg-4">
                <h4>{{ __('admin.transportation_total') }}</h4>
                <h4>{{ $transportation_total }}</h4>
            </div>
            <div class="col-sm-12 col-md-6 col-lg-4">
                <h4>{{ __('admin.taxed_service_total') }}</h4>
                <h4>{{ $taxed_services_total }}</h4>
            </div>
            <div class="col-sm-12 col-md-6 col-lg-4">
                <h4>{{ __('admin.invoice_total') }}</h4>
                <h4>{{ $transportation_total + $taxed_services_total }}</h4>
            </div>
            <div class="col-sm-12 col-md-6 row">
                <div class="col-6">
                    <div class="form-group{{ $errors->has('value_added_tax') ? ' has-error' : '' }}">
                        {!! Form::label('value_added_tax', __('admin.value_added_tax')) !!}
                        {!! Form::number('value_added_tax', old('value_added_tax') ?? (isset($invoice) ? $invoice->value_added_tax : ""), [
                            'class' => 'form-control',
                            'required' => 'required',
                        ]) !!}
                        <small class="text-danger">{{ $errors->first('value_added_tax') }}</small>
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group mb-0">
                        <input class="form-control" style="margin-top: 25px;" type="number" id="taxAmount" placeholder="فيمة الضريبة المضافه">
                    </div>
                </div>

            </div>
            
          
            
            <div class="col-sm-12 col-md-6">
                <div class="form-group{{ $errors->has('sales_tax') ? ' has-error' : '' }}">
                    {!! Form::label('sales_tax', __('admin.sales_tax')) !!}
                    {!! Form::number('sales_tax', old('sales_tax') ?? (isset($invoice) ? $invoice->sales_tax : ""), ['class' => 'form-control', 'required' => 'required']) !!}
                    <small class="text-danger">{{ $errors->first('sales_tax') }}</small>
                </div>
            </div>
            <div class="col-sm-12">
                <h4>{{ __('admin.invoice_total_after_tax') }}</h4>
                <h4 id="invoice_total_after_tax"></h4>
            </div>
        </div>
        {{-- ================== ================== END INVOICE TOTALS ================== ================== --}}

        <hr size="12" width="95%">

        {{-- ================== ================== ATTACHMENT TOTALS ================== ================== --}}
        <div class="row">
            <div class="col-sm-12 col-md-6 col-lg-4">
                <h4>{{ __('admin.attachments_total') }}</h4>
                <h4></h4>
            </div>
        </div>
        {{-- ================== ================== END ATTACHMENT TOTALS ================== ================== --}}

        <hr size="12" width="95%">

        {{-- ================== ================== REQUIRED TO BE PAID TOTAL ================== ================== --}}
        <div class="row">
            <div class="col-sm-12 col-md-6 col-lg-4">
                <h4>{{ __('admin.required_to_be_paid') }}</h4>
                <h4 id="required_to_be_paid"></h4>
            </div>
            <div class="col-sm-12 col-md-6 col-lg-4">
                <div class="form-group{{ $errors->has('discount') ? ' has-error' : '' }}">
                    {!! Form::label('discount', __('admin.discount')) !!}
                    {!! Form::number('discount', old('discount') ?? (isset($invoice) ? $invoice->discount : ''), ['class' => 'form-control', 'required' => 'required']) !!}
                    <small class="text-danger">{{ $errors->first('discount') }}</small>
                </div>
            </div>
            <div class="col-sm-12">
                <h4>{{ __('admin.required_to_be_paid_after_discount') }}</h4>
                <h4 id="required_to_be_paid_after_discount"></h4>
            </div>
            <div class="col-sm-12 col-md-6 col-lg-4">
                <div class="form-group">
                    {!! Form::label('invoice_number', __('admin.invoice_number')) !!}
                    {!! Form::text('invoice_number', old('invoice_number') ?? ($invoice_number ?? ''), [
                        'class' => 'form-control',
                    ]) !!}
                </div>
            </div>
        </div>
    </div>
    {{-- ================== ================== END BILL TOTAL ================== ================== --}}
</div>

{{-- ================== ================== ================ ================== ================== --}}
{{-- ================== ================== END TOTALS ================== ================== --}}
{{-- ================== ================== ================ ================== ================== --}}


{{-- ================== ================== SUBMIT BUTTON ================== ================== --}}
<div class="card-footer">
    @if ($method == 'POST')
        {!! Form::submit(__('admin.save'), [
            'class' => 'btn btn-primary btn-md text-uppercase font-weight-bold chat-send py-2 px-6',
        ]) !!}
    @elseif ($method == 'PUT')
        <div class="d-flex">
            {!! Form::submit(__('admin.update'), ['class' => 'btn btn-primary']) !!}
            
                
            <a href="{{ route('booking-invoices.show', ['booking_invoice' => $invoice->id]) }}"
                class="btn btn-primary mx-3">
                {{ __('admin.show') . ' ' . __('admin.bill_type_invoice') }}
            </a>
        </div>
    @endif
</div>
{{-- ================== ================== END SUBMIT BUTTON ================== ================== --}}

</form>
{!! Form::close() !!}
<!-- /.card-body -->
@push('js')
    <script>
        // Function to calculate the invoice total after tax
        function getInvoiceTotalAfterTax() {
            var invoiceTotalBeforeTax = {{ $transportation_total + $taxed_services_total }};
            var valueAddedTax = parseFloat($('#value_added_tax').val()) || 0;
            var salesTax = parseFloat($('#sales_tax').val()) || 0;
        
            var taxAmount = invoiceTotalBeforeTax * (valueAddedTax / 100 - salesTax / 100);
            return invoiceTotalBeforeTax + taxAmount;
        }
        
        // Function to update all fields based on current inputs
        function updateInvoiceAndBillableAmounts() {
            var valueAddedTax = parseFloat($('#value_added_tax').val()) || 0;
            var salesTax = parseFloat($('#sales_tax').val()) || 0;
        
            var invoiceTotalBeforeTax = {{ $transportation_total + $taxed_services_total }};
            var taxAmount = invoiceTotalBeforeTax * (valueAddedTax / 100 - salesTax / 100);
            var invoiceTotalAfterTax = invoiceTotalBeforeTax + taxAmount;
        
            $('#invoice_total_after_tax').text(
                invoiceTotalAfterTax.toLocaleString('en-US', { minimumFractionDigits: 2 })
            );
        
            var untaxedServicesTotal = {{ $untaxed_services_total }};
            var billableAmount = invoiceTotalAfterTax + untaxedServicesTotal;
            $('#required_to_be_paid').text(
                billableAmount.toLocaleString('en-US', { minimumFractionDigits: 2 })
            );
        
            // Update Required To Be Paid After Discount
            var discount = parseFloat($('#discount').val()) || 0;
            var billableAfterDiscount = billableAmount - discount;
            $('#required_to_be_paid_after_discount').text(
                billableAfterDiscount.toLocaleString('en-US', { minimumFractionDigits: 2 })
            );
        
            // Update Tax Amount Input
            $('#taxAmount').val(
                taxAmount.toFixed(2)
            );
        }
        
        // Function to update tax percentages based on the tax amount input
        function updateTaxPercentagesBasedOnAmount() {
            var invoiceTotalBeforeTax = {{ $transportation_total + $taxed_services_total }};
            var taxAmount = parseFloat($('#taxAmount').val()) || 0;
        
            var valueAddedTax = ((taxAmount / invoiceTotalBeforeTax) * 100).toFixed(2);
            var salesTax = 0; // Assuming no sales tax or set it as needed
        
            $('#value_added_tax').val(valueAddedTax);
            $('#sales_tax').val(salesTax);
        
            updateInvoiceAndBillableAmounts();
        }
        
        // Initial call to set values
        updateInvoiceAndBillableAmounts();
        
        // Event listeners
        $('#value_added_tax').on('input', updateInvoiceAndBillableAmounts);
        $('#sales_tax').on('input', updateInvoiceAndBillableAmounts);
        $('#discount').on('input', updateInvoiceAndBillableAmounts);
        $('#taxAmount').on('input', updateTaxPercentagesBasedOnAmount);


    </script>
@endpush
