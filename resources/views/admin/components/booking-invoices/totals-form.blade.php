@if ($method == 'POST')
    {!! Form::open(['url' => $action, 'method' => $method, 'enctype' => 'multipart/form-data', 'files' => true, 'class' => 'invoice-totals-form']) !!}
@elseif ($method == 'PUT')
    {!! Form::model($invoice, [
        'url' => [$action],
        'method' => $method,
        'enctype' => 'multipart/form-data',
        'files' => true,
        'class' => 'invoice-totals-form',
    ]) !!}
@endif

<div class="totals-card mb-4">
    <div class="totals-card-head">
        <span class="totals-card-icon"><i class="fas fa-calculator"></i></span>
        <h2 class="totals-card-title">{{ __('admin.totals') }}</h2>
    </div>
    <div class="totals-card-body">

        {{-- إجماليات الفاتورة --}}
        <div class="totals-block">
            <h3 class="totals-block-title">
                <span class="totals-block-icon totals-block-icon--blue"><i class="fas fa-coins"></i></span>
                إجماليات الفاتورة
            </h3>
            <div class="row">
                <div class="col-sm-6 col-lg-4 mb-3">
                    <div class="totals-value-box">
                        <span class="totals-value-label">{{ __('admin.transportation_total') }}</span>
                        <span class="totals-value-number">{{ number_format((float)$transportation_total, 2) }}</span>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-4 mb-3">
                    <div class="totals-value-box">
                        <span class="totals-value-label">{{ __('admin.taxed_service_total') }}</span>
                        <span class="totals-value-number">{{ number_format((float)$taxed_services_total, 2) }}</span>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-4 mb-3">
                    <div class="totals-value-box totals-value-box--primary">
                        <span class="totals-value-label">{{ __('admin.invoice_total') }}</span>
                        <span class="totals-value-number totals-value-number--primary">{{ number_format((float)($transportation_total + $taxed_services_total), 2) }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- الضرائب والمبلغ بعد الضريبة --}}
        <div class="totals-block">
            <h3 class="totals-block-title">
                <span class="totals-block-icon totals-block-icon--orange"><i class="fas fa-percent"></i></span>
                الضرائب والمبلغ بعد الضريبة
            </h3>
            <div class="row align-items-end">
                <div class="col-md-6 col-lg-3 mb-3">
                    <label class="totals-input-label">{{ __('admin.value_added_tax') }} %</label>
                    {!! Form::number('value_added_tax', old('value_added_tax') ?? (isset($invoice) ? $invoice->value_added_tax : ''), [
                        'class' => 'form-control form-control-solid totals-input',
                        'required' => 'required',
                        'step' => '0.01',
                        'min' => '0',
                        'max' => '100',
                        'id' => 'value_added_tax',
                    ]) !!}
                    @error('value_added_tax') <small class="text-danger d-block mt-1">{{ $message }}</small> @enderror
                </div>
                <div class="col-md-6 col-lg-3 mb-3">
                    <label class="totals-input-label">{{ __('admin.sales_tax') }} %</label>
                    {!! Form::number('sales_tax', old('sales_tax') ?? (isset($invoice) ? $invoice->sales_tax : ''), [
                        'class' => 'form-control form-control-solid totals-input',
                        'required' => 'required',
                        'step' => '0.01',
                        'min' => '0',
                        'max' => '100',
                        'id' => 'sales_tax',
                    ]) !!}
                    @error('sales_tax') <small class="text-danger d-block mt-1">{{ $message }}</small> @enderror
                </div>
                <div class="col-md-6 col-lg-3 mb-3">
                    <label class="totals-input-label">قيمة الضريبة المضافة (جنيه)</label>
                    <input class="form-control form-control-solid totals-input" type="number" id="taxAmount" placeholder="0.00" step="0.01" readonly>
                </div>
                <div class="col-md-6 col-lg-3 mb-3">
                    <label class="totals-input-label">{{ __('admin.invoice_total_after_tax') }}</label>
                    <div class="totals-result-box" id="invoice_total_after_tax">0.00</div>
                </div>
            </div>
        </div>

        <hr class="totals-hr">

        {{-- المرفقات والمبلغ المطلوب سداده --}}
        <div class="totals-block">
            <h3 class="totals-block-title">
                <span class="totals-block-icon totals-block-icon--teal"><i class="fas fa-paperclip"></i></span>
                المرفقات والمبلغ المطلوب
            </h3>
            <div class="row">
                <div class="col-md-6 col-lg-4 mb-3">
                    <div class="totals-value-box">
                        <span class="totals-value-label">{{ __('admin.attachments_total') }}</span>
                        <span class="totals-value-number">{{ number_format((float)$untaxed_services_total, 2) }}</span>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4 mb-3">
                    <div class="totals-value-box totals-value-box--primary">
                        <span class="totals-value-label">{{ __('admin.required_to_be_paid') }}</span>
                        <span class="totals-value-number totals-value-number--primary" id="required_to_be_paid">0.00</span>
                    </div>
                </div>
            </div>
        </div>

        <hr class="totals-hr">

        {{-- الخصم والرقم النهائي ورقم الفاتورة --}}
        <div class="totals-block">
            <h3 class="totals-block-title">
                <span class="totals-block-icon totals-block-icon--green"><i class="fas fa-hand-holding-dollar"></i></span>
                الخصم والمبلغ النهائي
            </h3>
            <div class="row align-items-end">
                <div class="col-md-6 col-lg-3 mb-3">
                    <label class="totals-input-label">{{ __('admin.discount') }} (جنيه)</label>
                    {!! Form::number('discount', old('discount') ?? (isset($invoice) ? $invoice->discount : 0), [
                        'class' => 'form-control form-control-solid totals-input',
                        'required' => 'required',
                        'step' => '0.01',
                        'min' => '0',
                        'id' => 'discount',
                    ]) !!}
                    @error('discount') <small class="text-danger d-block mt-1">{{ $message }}</small> @enderror
                </div>
                <div class="col-md-6 col-lg-3 mb-3">
                    <label class="totals-input-label">{{ __('admin.required_to_be_paid_after_discount') }}</label>
                    <div class="totals-result-box totals-result-box--success" id="required_to_be_paid_after_discount">0.00</div>
                </div>
                <div class="col-md-6 col-lg-4 mb-3">
                    <label class="totals-input-label">{{ __('admin.invoice_number') }}</label>
                    {!! Form::text('invoice_number', old('invoice_number') ?? ($invoice_number ?? ''), [
                        'class' => 'form-control form-control-solid totals-input',
                        'id' => 'invoice_number',
                    ]) !!}
                </div>
            </div>
        </div>
    </div>

    <div class="totals-card-footer">
        @if ($method == 'POST')
            <button type="submit" class="totals-submit-btn">
                <i class="fas fa-save ml-2"></i>
                حفظ وإنشاء الفاتورة
            </button>
            <a href="{{ route('bookings.show', $booking) }}" class="totals-cancel-btn">
                <i class="fas fa-times ml-2"></i>
                إلغاء
            </a>
        @elseif ($method == 'PUT')
            <button type="submit" class="totals-submit-btn">
                <i class="fas fa-edit ml-2"></i>
                {{ __('admin.update') }}
            </button>
            <a href="{{ route('booking-invoices.show', ['booking_invoice' => $invoice->id]) }}" class="totals-cancel-btn">
                <i class="fas fa-eye ml-2"></i>
                عرض الفاتورة
            </a>
        @endif
    </div>
</div>

{!! Form::close() !!}

<style>
.totals-card {
    background: #fff;
    border-radius: 14px;
    box-shadow: 0 2px 14px rgba(0,0,0,0.06);
    border: 1px solid #e8ecf1;
    overflow: hidden;
}
.totals-card-head {
    background: linear-gradient(135deg, #1e3a5f 0%, #2d5a87 100%);
    color: #fff;
    padding: 1rem 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}
.totals-card-icon {
    width: 42px;
    height: 42px;
    background: rgba(255,255,255,0.2);
    border-radius: 10px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
}
.totals-card-title {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 700;
    color: #fff;
}
.totals-card-body { padding: 1.5rem; }
.totals-card-footer {
    background: #f8fafc;
    border-top: 1px solid #e8ecf1;
    padding: 1.25rem 1.5rem;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.75rem;
}

.totals-block { margin-bottom: 1.25rem; }
.totals-block:last-of-type { margin-bottom: 0; }
.totals-block-title {
    font-size: 1.05rem;
    font-weight: 700;
    color: #1a1d21;
    margin: 0 0 1rem 0;
    display: flex;
    align-items: center;
    gap: 0.6rem;
}
.totals-block-icon {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    color: #fff;
}
.totals-block-icon--blue { background: #0d6efd; }
.totals-block-icon--orange { background: #fd7e14; }
.totals-block-icon--teal { background: #0dcaf0; }
.totals-block-icon--green { background: #198754; }

.totals-value-box {
    background: #f8fafc;
    border: 1px solid #e8ecf1;
    border-radius: 10px;
    padding: 1rem 1.25rem;
    text-align: center;
}
.totals-value-box--primary {
    background: #e8f4fc;
    border-color: #0d6efd;
}
.totals-value-label {
    display: block;
    font-size: 0.8rem;
    font-weight: 600;
    color: #5c6370;
    margin-bottom: 0.35rem;
}
.totals-value-number {
    font-size: 1.25rem;
    font-weight: 700;
    color: #1a1d21;
}
.totals-value-number--primary { color: #0d6efd; }

.totals-input-label {
    display: block;
    font-size: 0.9rem;
    font-weight: 700;
    color: #1a1d21;
    margin-bottom: 0.4rem;
}
.totals-input {
    font-size: 1rem;
    font-weight: 600;
}
.totals-result-box {
    background: #f8fafc;
    border: 1px solid #e8ecf1;
    border-radius: 8px;
    padding: 0.65rem 1rem;
    font-size: 1.15rem;
    font-weight: 700;
    color: #1a1d21;
}
.totals-result-box--success {
    background: #e8f5e9;
    border-color: #198754;
    color: #198754;
}
.totals-hr { border-color: #e8ecf1; margin: 1.25rem 0; }

.totals-submit-btn {
    background: #198754;
    color: #fff;
    border: none;
    padding: 0.7rem 1.5rem;
    border-radius: 10px;
    font-weight: 700;
    font-size: 1rem;
    display: inline-flex;
    align-items: center;
    cursor: pointer;
    transition: background 0.2s, transform 0.2s;
}
.totals-submit-btn:hover { background: #157347; color: #fff; transform: translateY(-1px); }
.totals-cancel-btn {
    background: #fff;
    color: #5c6370;
    border: 1px solid #dee2e6;
    padding: 0.6rem 1.25rem;
    border-radius: 10px;
    font-weight: 700;
    font-size: 0.95rem;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    transition: background 0.2s, color 0.2s;
}
.totals-cancel-btn:hover { background: #f8fafc; color: #1a1d21; text-decoration: none; }
</style>

@push('js')
<script>
    function getInvoiceTotalAfterTax() {
        var invoiceTotalBeforeTax = {{ $transportation_total + $taxed_services_total }};
        var valueAddedTax = parseFloat($('#value_added_tax').val()) || 0;
        var salesTax = parseFloat($('#sales_tax').val()) || 0;
        var taxAmount = invoiceTotalBeforeTax * (valueAddedTax / 100 - salesTax / 100);
        return invoiceTotalBeforeTax + taxAmount;
    }

    function updateInvoiceAndBillableAmounts() {
        var valueAddedTax = parseFloat($('#value_added_tax').val()) || 0;
        var salesTax = parseFloat($('#sales_tax').val()) || 0;
        var invoiceTotalBeforeTax = {{ $transportation_total + $taxed_services_total }};
        var taxAmount = invoiceTotalBeforeTax * (valueAddedTax / 100 - salesTax / 100);
        var invoiceTotalAfterTax = invoiceTotalBeforeTax + taxAmount;

        $('#invoice_total_after_tax').text(invoiceTotalAfterTax.toLocaleString('en-US', { minimumFractionDigits: 2 }));

        var untaxedServicesTotal = {{ $untaxed_services_total }};
        var billableAmount = invoiceTotalAfterTax + untaxedServicesTotal;
        $('#required_to_be_paid').text(billableAmount.toLocaleString('en-US', { minimumFractionDigits: 2 }));

        var discount = parseFloat($('#discount').val()) || 0;
        var billableAfterDiscount = billableAmount - discount;
        $('#required_to_be_paid_after_discount').text(billableAfterDiscount.toLocaleString('en-US', { minimumFractionDigits: 2 }));

        $('#taxAmount').val(taxAmount.toFixed(2));
    }

    function updateTaxPercentagesBasedOnAmount() {
        var invoiceTotalBeforeTax = {{ $transportation_total + $taxed_services_total }};
        var taxAmount = parseFloat($('#taxAmount').val()) || 0;
        var valueAddedTax = invoiceTotalBeforeTax ? ((taxAmount / invoiceTotalBeforeTax) * 100).toFixed(2) : 0;
        $('#value_added_tax').val(valueAddedTax);
        $('#sales_tax').val(0);
        updateInvoiceAndBillableAmounts();
    }

    updateInvoiceAndBillableAmounts();

    $('#value_added_tax').on('input', updateInvoiceAndBillableAmounts);
    $('#sales_tax').on('input', updateInvoiceAndBillableAmounts);
    $('#discount').on('input', updateInvoiceAndBillableAmounts);
    $('#taxAmount').on('input', updateTaxPercentagesBasedOnAmount);
</script>
@endpush
