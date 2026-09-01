@if ($method == 'POST')
    {!! Form::open([
        'url' => $action,
        'method' => $method,
        'enctype' => 'multipart/form-data',
    ]) !!}
@elseif ($method == 'PUT')
    {!! Form::model($booking_service ?? null, [
        'url' => $action,
        'method' => $method,
        'enctype' => 'multipart/form-data',
    ]) !!}
@endif

<div class="card-body">
    <div class="row">
        <div class="col-md-6 col-sm-12">
            <div class="form-group{{ $errors->has('service_type_id') ? ' has-error' : '' }}">
                {!! Form::label('service_type_id', __('admin.service_type')) !!}
                {!! Form::select(
                    'service_type_id',
                    array_replace(['to_be_disabled' => __('admin.select')], $service_types->all()),
                    old('service_type_id', isset($booking_service) ? $service_type_id : null),
                    ['id' => 'service_type_id', 'class' => 'form-control', 'required' => 'required'],
                ) !!}
                <small class="text-danger">{{ $errors->first('service_type_id') }}</small>
            </div>
        </div>

        <div class="col-md-6 col-sm-12">
            <div class="form-group{{ $errors->has('service_id') ? ' has-error' : '' }}">
                {!! Form::label('service_id', __('admin.service')) !!}
                @php
                    $selected_service_id = old('service_id', isset($booking_service) ? $booking_service->service_id : null);
                    $service_options = ['to_be_disabled' => __('admin.select')];
                    if (isset($booking_service) && $booking_service->service) {
                        $service_options[$booking_service->service_id] = $booking_service->service->name;
                    }
                @endphp
                {!! Form::select('service_id', $service_options, $selected_service_id, [
                    'id' => 'service_id',
                    'class' => 'form-control',
                    'required' => 'required',
                ]) !!}
                <small class="text-danger">{{ $errors->first('service_id') }}</small>
            </div>
        </div>

        <div class="col-md-12 col-sm-12">
            <div class="form-group{{ $errors->has('price') ? ' has-error' : '' }}">
                {!! Form::label('price', __('admin.price')) !!}
                {!! Form::number('price', old('price'), ['class' => 'form-control', 'required' => 'required', 'step' => '0.01', 'min' => 0]) !!}

                <small class="text-danger">{{ $errors->first('price') }}</small>
            </div>
        </div>

        <div class="col-md-12 col-sm-12">
            <div class="form-group">
                {!! Form::label('input_note', __('admin.note')) !!}
                {!! Form::textarea('note', old('note'), [
                    'class' => 'form-control',
                    'id' => 'input_note',
                    'rows' => 2,
                    'placeholder' => __('admin.note'),
                ]) !!}
                <small class="alert text-danger"></small>
            </div>
        </div>

        <div class="col-md-12 col-sm-12">
            <div class="form-group{{ $errors->has('payment_type') ? ' has-error' : '' }}">
                {!! Form::label('payment_type', __('admin.payment_type')) !!}
                {!! Form::select('payment_type',
                    [
                        '' => __('admin.select_payment_type'),
                        'vault' => __('admin.vault'),
                        'bank' => __('main.bank'),
                        'agent' => __('main.agent'),
                        'supplier' => 'مورد',
                    ],
                    old('payment_type', isset($booking_service) ? $booking_service->payment_type : null),
                    ['id' => 'payment_type', 'class' => 'form-control', 'onchange' => 'togglePaymentFields()']
                ) !!}
                <small class="text-danger">{{ $errors->first('payment_type') }}</small>
            </div>
        </div>

        <div class="col-md-12 col-sm-12" id="vault_field" style="display: none;">
            <div class="form-group">
                <small class="text-muted d-block mb-2">{{ __('admin.payment_from_vault') }}</small>
            </div>
        </div>

        <div class="col-md-12 col-sm-12" id="bank_field" style="display: none;">
            <div class="form-group{{ $errors->has('bank_id') ? ' has-error' : '' }}">
                {!! Form::label('bank_id', __('main.bank')) !!}
                {!! Form::select('bank_id',
                    array_replace(['' => __('admin.select_bank')], isset($banks) ? $banks->toArray() : []),
                    old('bank_id', isset($booking_service) ? $booking_service->bank_id : null),
                    ['id' => 'bank_id', 'class' => 'form-control']
                ) !!}
                <small class="text-danger">{{ $errors->first('bank_id') }}</small>
            </div>
        </div>

        <div class="col-md-12 col-sm-12" id="agent_field" style="display: none;">
            <div class="form-group{{ $errors->has('agent_id') ? ' has-error' : '' }}">
                {!! Form::label('agent_id', __('main.agent')) !!}
                {!! Form::select('agent_id',
                    array_replace(['' => __('admin.select_agent')], isset($agents) ? $agents->toArray() : []),
                    old('agent_id', isset($booking_service) ? $booking_service->agent_id : null),
                    ['id' => 'agent_id', 'class' => 'form-control']
                ) !!}
                <small class="text-danger">{{ $errors->first('agent_id') }}</small>
            </div>
        </div>

        <div class="col-md-6 col-sm-12" id="supplier_field" style="display: none;">
            <div class="form-group{{ $errors->has('supplier_id') ? ' has-error' : '' }}">
                {!! Form::label('supplier_id', 'المورد', ['class' => 'required-field']) !!}
                {!! Form::select('supplier_id',
                    array_replace(['' => 'اختر المورد'], isset($suppliers) ? $suppliers->toArray() : []),
                    old('supplier_id', isset($booking_service) ? $booking_service->supplier_id : null),
                    ['id' => 'supplier_id', 'class' => 'form-control selectpicker', 'data-live-search' => 'true']
                ) !!}
                <small class="text-danger">{{ $errors->first('supplier_id') }}</small>
            </div>
        </div>

        <div class="col-md-6 col-sm-12" id="supplier_invoice_field" style="display: none;">
            <div class="form-group{{ $errors->has('supplier_invoice_number') ? ' has-error' : '' }}">
                {!! Form::label('supplier_invoice_number', 'رقم فاتورة المورد') !!}
                {!! Form::text('supplier_invoice_number',
                    old('supplier_invoice_number', isset($booking_service) ? $booking_service->supplier_invoice_number : null),
                    ['id' => 'supplier_invoice_number', 'class' => 'form-control', 'placeholder' => 'اختياري — افتراضي رقم البوكينج']
                ) !!}
                <small class="text-danger">{{ $errors->first('supplier_invoice_number') }}</small>
            </div>
        </div>

        <div class="col-md-12 col-sm-12">
            <div class="form-group">
                {!! Form::label('input_note', __('admin.receipt_image')) !!}
                <input type="file" accept="image/*" name="image" id="input_receipt_image" class="form-control">
                @if(isset($booking_service) && $booking_service->image && $booking_service->getRawOriginal('image'))
                    <div class="mt-3">
                        <small class="text-muted d-block mb-2">{{ __('admin.current_image') }}:</small>
                        <a href="{{ $booking_service->image }}" target="_blank" style="display: inline-block;">
                            <img src="{{ $booking_service->image }}" alt="Current Receipt"
                                 style="max-width: 200px; max-height: 200px; object-fit: cover; border-radius: 8px; cursor: pointer; border: 2px solid #e0e0e0; box-shadow: 0 2px 8px rgba(0,0,0,0.1);"
                                 onmouseover="this.style.borderColor='#007bff'; this.style.boxShadow='0 4px 12px rgba(0,123,255,0.3)'"
                                 onmouseout="this.style.borderColor='#e0e0e0'; this.style.boxShadow='0 2px 8px rgba(0,0,0,0.1)'">
                        </a>
                    </div>
                @endif
                <small class="alert text-danger"></small>
            </div>
        </div>
    </div>
</div>

<div class="card-footer">
    @if ($method == 'POST')
        {!! Form::submit(__('admin.save'), [
            'class' => 'btn btn-primary btn-md text-uppercase font-weight-bold chat-send py-2 px-6',
        ]) !!}
    @elseif ($method == 'PUT')
        {!! Form::submit(__('admin.update'), [
            'class' => 'btn btn-primary',
        ]) !!}
    @endif
</div>

</form>
{!! Form::close() !!}
<!-- /.card-body -->

@push('js')
    <script>
        @if(isset($booking_service) && $booking_service->service_id)
            // Set initial service when editing
            $(document).ready(function() {
                var service_type_id = $('#service_type_id').val();
                if (service_type_id && service_type_id !== 'to_be_disabled') {
                    loadServices(service_type_id, {{ $booking_service->service_id }});
                }
            });
        @endif

        $('#service_type_id').on('change', function() {
            var service_type_id = $(this).val();
            if (service_type_id && service_type_id !== 'to_be_disabled') {
                loadServices(service_type_id);
            }
        });

        function loadServices(service_type_id, selected_service_id = null) {
            var url = "{{ route('services.getServices', ':id') }}"
            url = url.replace(':id', service_type_id);
            var token = '{{ csrf_token() }}';

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': token,
                    'X-Requested-With': 'XMLHttpRequest',
                }
            });

            $.ajax({
                url: url,
                type: 'GET',
                success: function(res) {
                    $('#service_id').empty();
                    $('#service_id').append(
                        `<option value="to_be_disabled">{{ __('admin.choose_service') }}</option>`
                    );
                    executeToBeDisabledSelections();
                    $.each(res, function(i, v) {
                        var selected = (selected_service_id && i == selected_service_id) ? 'selected' : '';
                        $('#service_id').append(`<option value="${i}" ${selected}>${v}</option>`);
                    });
                }
            })
        }
    </script>
    <script>
        var company_prices = {!! json_encode($company_prices) !!};
        $('#service_id').on('change', updatePrice);

        function updatePrice() {
            var service_id = $('#service_id option:selected').val();
            if (company_prices[service_id])
                $('#price').val(company_prices[service_id]);
        }

        function togglePaymentFields() {
            var paymentType = $('#payment_type').val();

            $('#vault_field').hide();
            $('#bank_field').hide();
            $('#agent_field').hide();
            $('#supplier_field').hide();
            $('#supplier_invoice_field').hide();

            if (paymentType === 'vault') {
                $('#vault_field').show();
            } else if (paymentType === 'bank') {
                $('#bank_field').show();
            } else if (paymentType === 'agent') {
                $('#agent_field').show();
            } else if (paymentType === 'supplier') {
                $('#supplier_field').show();
                $('#supplier_invoice_field').show();
            }
        }

        // Initialize on page load
        $(document).ready(function() {
            togglePaymentFields();
        });
    </script>
@endpush
