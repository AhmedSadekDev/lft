@php
    $currentPaymentSource = old(
        'payment_source',
        isset($receipt) ? $receipt->payment_source : 'supplier'
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
    $bookingService = $booking_service ?? ($receipt->bookingService ?? null);
    $currentServiceTypeId = old(
        'service_type_id',
        $service_type_id ?? ($bookingService?->service?->service_category_id)
    );
    $currentServiceId = old('service_id', $bookingService->service_id ?? null);
@endphp

@if($method == 'POST')
    {!! Form::open(['url' => $action, 'method' => $method, 'id' => 'receiptForm', 'files' => true, 'enctype' => 'multipart/form-data']) !!}
@elseif ($method == 'PUT')
    {!! Form::model($receipt, ['url' => [$action], 'method' => $method, 'id' => 'receiptForm', 'files' => true, 'enctype' => 'multipart/form-data']) !!}
@endif

<div class="card-body">
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="alert alert-light border mb-4">
        املأ بيانات الإيصال بنفس طريقة إيصالات الطلب: اختر الطلب + نوع الخدمة + الخدمة، وسيظهر تلقائياً في الطلب وفي قسم الإيصالات بالفاتورة.
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                {!! Form::label('booking_number', 'رقم البوكينج', ['class' => 'required-field']) !!}
                {!! Form::text('booking_number', $currentBookingNumber, [
                    'class' => 'form-control',
                    'id' => 'booking_number',
                    'placeholder' => 'أدخل رقم البوكينج',
                    'autocomplete' => 'off',
                ]) !!}
                <small class="text-muted">مطلوب لربط الإيصال بالطلب والفاتورة</small>
                @error('booking_number') <small class="text-danger d-block">{{ $message }}</small> @enderror
                @error('booking_id') <small class="text-danger d-block">{{ $message }}</small> @enderror
            </div>
        </div>

        <div class="col-md-6">
            <div class="form-group">
                {!! Form::label('booking_id', 'أو اختر الطلب من القائمة') !!}
                <select name="booking_id" class="form-control selectpicker" id="booking_id" data-live-search="true">
                    <option value="">اختر الطلب</option>
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

        <div class="col-md-6">
            <div class="form-group">
                {!! Form::label('service_type_id', __('admin.service_type'), ['class' => 'required-field']) !!}
                {!! Form::select(
                    'service_type_id',
                    array_replace(['to_be_disabled' => __('admin.select')], collect($service_types ?? [])->toArray()),
                    $currentServiceTypeId,
                    ['id' => 'service_type_id', 'class' => 'form-control', 'required' => 'required']
                ) !!}
                @error('service_type_id') <small class="text-danger">{{ $message }}</small> @enderror
            </div>
        </div>

        <div class="col-md-6">
            <div class="form-group">
                {!! Form::label('service_id', __('admin.service'), ['class' => 'required-field']) !!}
                @php
                    $serviceOptions = ['to_be_disabled' => __('admin.select')];
                    if ($bookingService && $bookingService->service) {
                        $serviceOptions[$bookingService->service_id] = $bookingService->service->name;
                    }
                @endphp
                {!! Form::select('service_id', $serviceOptions, $currentServiceId, [
                    'id' => 'service_id',
                    'class' => 'form-control',
                    'required' => 'required',
                ]) !!}
                @error('service_id') <small class="text-danger">{{ $message }}</small> @enderror
            </div>
        </div>

        <div class="col-md-6">
            <div class="form-group">
                {!! Form::label('cost', 'التكلفة', ['class' => 'required-field']) !!}
                {!! Form::number('cost', old('cost', isset($receipt) ? $receipt->cost : null), [
                    'class' => 'form-control',
                    'id' => 'cost',
                    'step' => '0.01',
                    'min' => '0',
                    'required' => true,
                ]) !!}
                @error('cost') <small class="text-danger">{{ $message }}</small> @enderror
            </div>
        </div>

        <div class="col-md-6">
            <div class="form-group">
                {!! Form::label('payment_source', 'مصدر الدفع / نوع الدفع', ['class' => 'required-field']) !!}
                {!! Form::select('payment_source', [
                    '' => 'اختر نوع الدفع',
                    'supplier' => 'مورد',
                    'safe' => 'الخزنة',
                    'representative' => 'مندوب',
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

        <div class="col-md-12">
            <div class="form-group">
                {!! Form::label('notes', 'ملاحظات') !!}
                {!! Form::textarea('notes', old('notes', isset($receipt) ? $receipt->notes : null), [
                    'class' => 'form-control',
                    'rows' => 2,
                    'placeholder' => __('admin.note'),
                ]) !!}
                @error('notes') <small class="text-danger">{{ $message }}</small> @enderror
            </div>
        </div>

        <div class="col-md-12">
            <div class="form-group">
                {!! Form::label('image', __('admin.receipt_image')) !!}
                <input type="file" accept="image/*" name="image" id="input_receipt_image" class="form-control">
                @if($bookingService && $bookingService->image && $bookingService->getRawOriginal('image'))
                    <div class="mt-3">
                        <small class="text-muted d-block mb-2">{{ __('admin.current_image') }}:</small>
                        <a href="{{ $bookingService->image }}" target="_blank">
                            <img src="{{ $bookingService->image }}" alt="Receipt"
                                 style="max-width: 180px; max-height: 180px; object-fit: cover; border-radius: 8px; border: 2px solid #e0e0e0;">
                        </a>
                    </div>
                @endif
                @error('image') <small class="text-danger">{{ $message }}</small> @enderror
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
        }
    }

    function loadServices(serviceTypeId, selectedServiceId) {
        if (!serviceTypeId || serviceTypeId === 'to_be_disabled') {
            return;
        }

        var url = "{{ route('services.getServices', ':id') }}".replace(':id', serviceTypeId);

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'X-Requested-With': 'XMLHttpRequest',
            }
        });

        $.ajax({
            url: url,
            type: 'GET',
            success: function (res) {
                $('#service_id').empty();
                $('#service_id').append(`<option value="to_be_disabled">{{ __('admin.choose_service') }}</option>`);
                $.each(res, function (i, v) {
                    var selected = (selectedServiceId && String(i) === String(selectedServiceId)) ? 'selected' : '';
                    $('#service_id').append(`<option value="${i}" ${selected}>${v}</option>`);
                });
            }
        });
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

    $('#service_type_id').on('change', function () {
        loadServices($(this).val());
    });

    $(document).ready(function () {
        var serviceTypeId = $('#service_type_id').val();
        var selectedServiceId = @json($currentServiceId);
        if (serviceTypeId && serviceTypeId !== 'to_be_disabled') {
            loadServices(serviceTypeId, selectedServiceId);
        }
    });
</script>
@endpush
