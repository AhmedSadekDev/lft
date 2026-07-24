@php
    $currentPaymentSource = old(
        'payment_source',
        isset($receipt) ? $receipt->payment_source : null
    );
    $currentSupplierId = old(
        'supplier_id',
        $selectedSupplierId ?? (isset($receipt) ? $receipt->supplier_id : null)
    );
    $currentBookingId = old('booking_id', isset($receipt) ? $receipt->booking_id : null);
    $currentBookingNumber = old(
        'booking_number',
        isset($receipt) && $receipt->booking ? $receipt->booking->booking_number : null
    );
@endphp

@if($method == 'POST')
    {!! Form::open(['url' => $action, 'method' => $method, 'id' => 'receiptForm']) !!}
@elseif ($method == 'PUT')
    {!! Form::model($receipt, ['url' => [$action], 'method' => $method, 'id' => 'receiptForm']) !!}
@endif

<div class="card-body">
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                {!! Form::label('cost', 'التكلفة', ['class' => 'required-field']) !!}
                {!! Form::number('cost', old('cost', isset($receipt) ? $receipt->cost : null), [
                    'class' => 'form-control',
                    'step' => '0.01',
                    'min' => '0',
                    'required' => true,
                ]) !!}
                @error('cost') <small class="text-danger">{{ $message }}</small> @enderror
            </div>
        </div>

        <div class="col-md-6">
            <div class="form-group">
                {!! Form::label('payment_source', 'مصدر الدفع', ['class' => 'required-field']) !!}
                {!! Form::select('payment_source', [
                    '' => 'اختر مصدر الدفع',
                    'safe' => 'الخزنة',
                    'representative' => 'مندوب',
                    'supplier' => 'مورد',
                ], $currentPaymentSource, [
                    'class' => 'form-control',
                    'id' => 'payment_source',
                    'required' => true,
                ]) !!}
                @error('payment_source') <small class="text-danger">{{ $message }}</small> @enderror
            </div>
        </div>

        <div class="col-md-6 js-supplier-fields" style="display: none;">
            <div class="form-group">
                {!! Form::label('supplier_id', 'المورد', ['class' => 'required-field']) !!}
                {!! Form::select('supplier_id', ['' => 'اختر المورد'] + collect($suppliers ?? [])->toArray(), $currentSupplierId, [
                    'class' => 'form-control selectpicker',
                    'id' => 'supplier_id',
                    'data-live-search' => 'true',
                ]) !!}
                @error('supplier_id') <small class="text-danger">{{ $message }}</small> @enderror
            </div>
        </div>

        <div class="col-md-6 js-supplier-fields" style="display: none;">
            <div class="form-group">
                {!! Form::label('supplier_invoice_number', 'رقم فاتورة المورد', ['class' => 'required-field']) !!}
                {!! Form::text('supplier_invoice_number', old('supplier_invoice_number', isset($receipt) ? $receipt->supplier_invoice_number : null), [
                    'class' => 'form-control',
                    'id' => 'supplier_invoice_number',
                    'placeholder' => 'رقم فاتورة المورد',
                ]) !!}
                @error('supplier_invoice_number') <small class="text-danger">{{ $message }}</small> @enderror
            </div>
        </div>

        <div class="col-md-6">
            <div class="form-group">
                {!! Form::label('booking_number', 'رقم البوكينج') !!}
                {!! Form::text('booking_number', $currentBookingNumber, [
                    'class' => 'form-control',
                    'id' => 'booking_number',
                    'placeholder' => 'أدخل رقم البوكينج لربط الإيصال بالطلب',
                    'autocomplete' => 'off',
                ]) !!}
                <small class="text-muted">عند الحفظ يُربط الإيصال تلقائياً بشاشة الطلب</small>
                @error('booking_number') <small class="text-danger d-block">{{ $message }}</small> @enderror
                @error('booking_id') <small class="text-danger d-block">{{ $message }}</small> @enderror
            </div>
        </div>

        <div class="col-md-6">
            <div class="form-group">
                {!! Form::label('booking_id', 'أو اختر الطلب من القائمة') !!}
                <select name="booking_id" class="form-control selectpicker" id="booking_id" data-live-search="true">
                    <option value="">بدون ربط بطلب</option>
                    @foreach($bookings ?? [] as $booking)
                        <option value="{{ $booking->id }}"
                            data-booking-number="{{ $booking->booking_number }}"
                            {{ (string) $currentBookingId === (string) $booking->id ? 'selected' : '' }}>
                            {{ $booking->booking_number ?: ('#' . $booking->id) }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="col-md-12">
            <div class="form-group">
                {!! Form::label('notes', 'ملاحظات') !!}
                {!! Form::textarea('notes', old('notes', isset($receipt) ? $receipt->notes : null), [
                    'class' => 'form-control',
                    'rows' => 3,
                ]) !!}
                @error('notes') <small class="text-danger">{{ $message }}</small> @enderror
            </div>
        </div>
    </div>
</div>

<div class="card-footer">
    @if($method == 'POST')
        {!! Form::submit(__('admin.save'), ['class' => 'btn btn-primary']) !!}
    @else
        {!! Form::submit(__('admin.update'), ['class' => 'btn btn-primary']) !!}
    @endif
</div>
{!! Form::close() !!}

@push('js')
<script>
    function toggleSupplierFields() {
        var source = document.getElementById('payment_source').value;
        var fields = document.querySelectorAll('.js-supplier-fields');
        var supplierSelect = document.getElementById('supplier_id');
        var invoiceInput = document.getElementById('supplier_invoice_number');

        if (source === 'supplier') {
            fields.forEach(function (el) { el.style.display = 'block'; });
            supplierSelect.setAttribute('required', 'required');
            invoiceInput.setAttribute('required', 'required');
        } else {
            fields.forEach(function (el) { el.style.display = 'none'; });
            supplierSelect.removeAttribute('required');
            invoiceInput.removeAttribute('required');
            supplierSelect.value = '';
            invoiceInput.value = '';
        }
    }

    document.getElementById('payment_source').addEventListener('change', toggleSupplierFields);
    toggleSupplierFields();

    $('#booking_id').on('changed.bs.select change', function () {
        var selected = $(this).find('option:selected');
        var number = selected.data('booking-number') || '';
        if ($(this).val()) {
            $('#booking_number').val(number);
        }
    });
</script>
@endpush
