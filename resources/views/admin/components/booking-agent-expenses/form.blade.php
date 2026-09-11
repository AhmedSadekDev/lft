@php
    $agentExpense = $agent_expense;
@endphp

{!! Form::model($agentExpense, [
    'url' => $action,
    'method' => $method,
    'enctype' => 'multipart/form-data',
]) !!}

<div class="card-body">
    <div class="row">
        <div class="col-md-6 col-sm-12">
            <div class="form-group{{ $errors->has('service_type_id') ? ' has-error' : '' }}">
                {!! Form::label('service_type_id', __('admin.service_type')) !!}
                {!! Form::select(
                    'service_type_id',
                    array_replace(['to_be_disabled' => __('admin.select')], $service_types->all()),
                    old('service_type_id', $service_type_id ?? null),
                    ['id' => 'service_type_id', 'class' => 'form-control', 'required' => 'required'],
                ) !!}
                <small class="text-danger">{{ $errors->first('service_type_id') }}</small>
            </div>
        </div>

        <div class="col-md-6 col-sm-12">
            <div class="form-group{{ $errors->has('service_id') ? ' has-error' : '' }}">
                {!! Form::label('service_id', __('admin.service')) !!}
                @php
                    $selected_service_id = old('service_id', $agentExpense->service_id);
                    $service_options = ['to_be_disabled' => __('admin.select')];
                    if ($agentExpense->service) {
                        $service_options[$agentExpense->service_id] = $agentExpense->service->name;
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
            <div class="form-group{{ $errors->has('value') ? ' has-error' : '' }}">
                {!! Form::label('value', __('admin.price')) !!}
                {!! Form::number('value', old('value', $agentExpense->value), [
                    'class' => 'form-control',
                    'required' => 'required',
                    'step' => '0.01',
                    'min' => 0,
                ]) !!}
                <small class="text-danger">{{ $errors->first('value') }}</small>
            </div>
        </div>

        <div class="col-md-12 col-sm-12">
            <div class="form-group">
                {!! Form::label('notes', __('admin.note')) !!}
                {!! Form::textarea('notes', old('notes', $agentExpense->notes), [
                    'class' => 'form-control',
                    'id' => 'input_notes',
                    'rows' => 2,
                    'placeholder' => __('admin.note'),
                ]) !!}
                <small class="text-danger">{{ $errors->first('notes') }}</small>
            </div>
        </div>

        <div class="col-md-12 col-sm-12">
            <div class="form-group">
                {!! Form::label('image', __('admin.receipt_image')) !!}
                <input type="file" accept="image/*" name="image" id="input_receipt_image" class="form-control">
                @php
                    $receiptFile = $agentExpense->getRawOriginal('image_agent_expenses');
                @endphp
                @if(filled($receiptFile))
                    <div class="mt-3">
                        <small class="text-muted d-block mb-2">{{ __('admin.current_image') }}:</small>
                        <a href="{{ asset('Admin/images/expenses/' . $receiptFile) }}" target="_blank" rel="noopener" style="display: inline-block;">
                            <img src="{{ asset('Admin/images/expenses/' . $receiptFile) }}" alt="Current Receipt"
                                 style="max-width: 200px; max-height: 200px; object-fit: cover; border-radius: 8px; cursor: pointer; border: 2px solid #e0e0e0; box-shadow: 0 2px 8px rgba(0,0,0,0.1);"
                                 onmouseover="this.style.borderColor='#007bff'; this.style.boxShadow='0 4px 12px rgba(0,123,255,0.3)'"
                                 onmouseout="this.style.borderColor='#e0e0e0'; this.style.boxShadow='0 2px 8px rgba(0,0,0,0.1)'">
                        </a>
                    </div>
                @endif
                <small class="text-danger">{{ $errors->first('image') }}</small>
            </div>
        </div>
    </div>
</div>

<div class="card-footer">
    {!! Form::submit(__('admin.update'), [
        'class' => 'btn btn-primary',
    ]) !!}
</div>

{!! Form::close() !!}

@push('js')
    <script>
        @if($agentExpense->service_id)
            $(document).ready(function() {
                var service_type_id = $('#service_type_id').val();
                if (service_type_id && service_type_id !== 'to_be_disabled') {
                    loadServices(service_type_id, {{ $agentExpense->service_id }});
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
            var url = "{{ route('services.getServices', ':id') }}";
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
                    if (typeof executeToBeDisabledSelections === 'function') {
                        executeToBeDisabledSelections();
                    }
                    $.each(res, function(i, v) {
                        var selected = (selected_service_id && i == selected_service_id) ? 'selected' : '';
                        $('#service_id').append(`<option value="${i}" ${selected}>${v}</option>`);
                    });
                }
            });
        }

        var company_prices = {!! json_encode($company_prices) !!};
        $('#service_id').on('change', function() {
            var service_id = $('#service_id option:selected').val();
            if (company_prices[service_id]) {
                $('#value').val(company_prices[service_id]);
            }
        });
    </script>
@endpush
