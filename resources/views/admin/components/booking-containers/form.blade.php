@if ($method == 'POST')
    {!! Form::open([
        'url' => $action,
        'method' => $method,
        'enctype' => 'multipart/form-data',
        'class' => 'lft-simple-form',
    ]) !!}
@elseif ($method == 'PUT')
    {!! Form::model($booking_container, [
        'url' => $action,
        'method' => $method,
        'enctype' => 'multipart/form-data',
    ]) !!}
@endif

<div class="card-body">
    <div class="row">
        <div class="col-md-6 col-sm-12">
            <div class="form-group{{ $errors->has('container_no') ? ' has-error' : '' }}">
                {!! Form::label('container_no', __('admin.container_no')) !!}
                {!! Form::text('container_no', old('container_no'), ['class' => 'form-control']) !!}
                <small class="text-danger">{{ $errors->first('container_no') }}</small>
            </div>
        </div>
        <div class="col-md-6 col-sm-12">
            <div class="form-group{{ $errors->has('sail_of_number') ? ' has-error' : '' }}">
                {!! Form::label('sail_of_number', __('admin.sail_of_number')) !!}
                {!! Form::text('sail_of_number', old('sail_of_number'), ['class' => 'form-control']) !!}
                <small class="text-danger">{{ $errors->first('sail_of_number') }}</small>
            </div>
        </div>
        <div class="col-md-6 col-sm-12">
            <div class="form-group{{ $errors->has('factory_id') ? ' has-error' : '' }}">
                {!! Form::label('factory_id', __('admin.factory')) !!}
                @php
                    $selectedFactoryId = old(
                        'factory_id',
                        $booking->factory_id
                            ?? (isset($booking_container) ? $booking_container->factory_id : null)
                    );
                @endphp
                {!! Form::select(
                    'factory_id',
                    array_replace(['to_be_disabled' => __('admin.select')], $factories->all()),
                    $selectedFactoryId,
                    array_filter([
                        'id' => 'factory_id',
                        'class' => 'form-control',
                        'required' => 'required',
                        'readonly' => !empty($booking->factory_id) ? 'readonly' : null,
                        'style' => !empty($booking->factory_id) ? 'pointer-events:none;background:#e9ecef;' : null,
                    ]),
                ) !!}
                <small class="text-danger">{{ $errors->first('factory_id') }}</small>
            </div>
        </div>
        <div class="col-md-6 col-sm-12">
            <div class="form-group{{ $errors->has('branch_id') ? ' has-error' : '' }}">
                {!! Form::label('branch_id', __('admin.branch')) !!}
                {!! Form::select(
                    'branch_id',
                    array_replace(['to_be_disabled' => __('admin.select')], $branches ?? []),
                    old('branch_id'),
                    ['id' => 'branch_id', 'class' => 'form-control'],
                ) !!}
                <small class="text-danger">{{ $errors->first('branch_id') }}</small>
            </div>
        </div>
        <div class="col-md-6 col-sm-12">
            <div class="form-group{{ $errors->has('arrival_date') ? ' has-error' : '' }}">
                {!! Form::label('arrival_date', __('admin.arrival_date')) !!}
                {!! Form::date('arrival_date', old('arrival_date'), ['class' => 'form-control']) !!}
                <small class="text-danger">{{ $errors->first('arrival_date') }}</small>
            </div>
        </div>
        <div class="col-md-6 col-sm-12">
            <div class="form-group{{ $errors->has('status') ? ' has-error' : '' }}">
                {!! Form::label('status', __('admin.status')) !!}
                <select name="status" id="status" class="form-control">
                    @foreach ($available_statuses as $value => $label)
                        <option value="{{ $value }}"
                            {{ old('status') == $value || (isset($booking_container) && $booking_container->status == $value) ? 'selected class=selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                <small class="text-danger">{{ $errors->first('status') }}</small>
            </div>

        </div>
        <div class="col-md-6 col-sm-12">
            <div class="form-group{{ $errors->has('yard_id') ? ' has-error' : '' }}">
                {!! Form::label('yard_id', __('admin.yard')) !!}
                {!! Form::select(
                    'yard_id',
                    array_replace(['to_be_disabled' => __('admin.select')], $yards->all()),
                    old('yard_id', isset($booking_container) ? $booking_container->yard_id : null),
                    ['id' => 'yard_id', 'class' => 'form-control'],
                ) !!}
                <small class="text-danger">{{ $errors->first('yard_id') }}</small>
            </div>
        </div>

        <div class="col-md-6 col-sm-12">
            <div class="form-group{{ $errors->has('container_id') ? ' has-error' : '' }}">
                {!! Form::label('container_id', __('admin.container')) !!}
                {!! Form::select(
                    'container_id',
                    array_replace(['to_be_disabled' => __('admin.select')], $container_types->all()),
                    old('container_id', isset($booking_container) ? $booking_container->container_id : null),
                    [
                        'id' => 'container_id',
                        'class' => 'form-control',
                    ],
                ) !!}
                <small class="text-danger">{{ $errors->first('container_id') }}</small>
            </div>
        </div>
        <div class="col-md-6 col-sm-12">
            <div class="form-group{{ $errors->has('departure_id') ? ' has-error' : '' }}">
                {!! Form::label('departure_id', __('admin.departure')) !!}
                {!! Form::select(
                    'departure_id',
                    array_replace(['to_be_disabled' => __('admin.select')], $cities_and_regions->all()),
                    old('departure_id', isset($booking_container) ? $booking_container->departure_id : null),
                    [
                        'id' => 'departure_id',
                        'class' => 'form-control departureSelect',
                    ],
                ) !!}
                <small class="text-danger">{{ $errors->first('departure_id') }}</small>
            </div>
        </div>
        <div class="col-md-6 col-sm-12">
            <div class="form-group{{ $errors->has('loading_id') ? ' has-error' : '' }}">
                {!! Form::label('loading_id', __('admin.loading')) !!}
                {!! Form::select(
                    'loading_id',
                    array_replace(['to_be_disabled' => __('admin.select')], $cities_and_regions->all()),
                    old('loading_id', isset($booking_container) ? $booking_container->loading_id : null),
                    [
                        'id' => 'loading_id',
                        'class' => 'form-control loadingSelect',
                    ],
                ) !!}
                <small class="text-danger">{{ $errors->first('loading_id') }}</small>
            </div>
        </div>
        <div class="col-md-6 col-sm-12">
            <div class="form-group{{ $errors->has('aging_id') ? ' has-error' : '' }}">
                {!! Form::label('aging_id', __('admin.aging')) !!}
                {!! Form::select(
                    'aging_id',
                    array_replace(['to_be_disabled' => __('admin.select')], $cities_and_regions->all()),
                    old('aging_id', isset($booking_container) ? $booking_container->aging_id : null),
                    [
                        'id' => 'aging_id',
                        'class' => 'form-control agingSelect',
                    ],
                ) !!}
                <small class="text-danger">{{ $errors->first('aging_id') }}</small>
            </div>
        </div>
        @php
            $lineCurrency = old('currency', isset($booking_container) ? ($booking_container->currency ?? 'EGP') : 'EGP');
            $lineAmount = old('price', isset($booking_container)
                ? (($booking_container->currency ?? 'EGP') === 'USD' ? $booking_container->foreign_amount : $booking_container->price)
                : null);
            $lineRate = old('exchange_rate', isset($booking_container) ? $booking_container->exchange_rate : null);
        @endphp

        <div class="col-md-4 col-sm-12">
            <div class="form-group{{ $errors->has('currency') ? ' has-error' : '' }}">
                {!! Form::label('currency', 'العملة') !!}
                {!! Form::select(
                    'currency',
                    ['EGP' => 'جنيه مصري (ج.م)', 'USD' => 'دولار أمريكي ($)'],
                    $lineCurrency,
                    ['id' => 'currency', 'class' => 'form-control', 'required' => 'required']
                ) !!}
                <small class="text-danger">{{ $errors->first('currency') }}</small>
            </div>
        </div>

        <div class="col-md-4 col-sm-12">
            <div class="form-group{{ $errors->has('price') ? ' has-error' : '' }}">
                {!! Form::label('price', 'المبلغ', ['id' => 'price_label']) !!}
                {!! Form::number('price', $lineAmount, [
                    'class' => 'form-control',
                    'id' => 'price',
                    'step' => '0.01',
                    'min' => 0,
                ]) !!}
                <small class="text-muted" id="price_hint">أدخل سعر النقلة بالجنيه المصري</small>
                <small class="text-danger d-block">{{ $errors->first('price') }}</small>
            </div>
        </div>

        <div class="col-md-4 col-sm-12" id="exchange_rate_field">
            <div class="form-group{{ $errors->has('exchange_rate') ? ' has-error' : '' }}">
                {!! Form::label('exchange_rate', 'سعر الصرف (اختياري — ج.م لكل $1)') !!}
                {!! Form::number('exchange_rate', $lineRate, [
                    'id' => 'exchange_rate',
                    'class' => 'form-control',
                    'step' => '0.000001',
                    'min' => 0,
                    'placeholder' => 'اختياري للمرجع',
                ]) !!}
                <small class="text-muted">الدولار يتجمع لوحده في كشف الحساب. سعر الصرف اختياري للمرجع الداخلي فقط.</small>
                <small class="text-danger d-block">{{ $errors->first('exchange_rate') }}</small>
                <div class="mt-2 font-weight-bold" id="egp_preview"></div>
            </div>
        </div>
    </div>
</div>

<div class="card-footer lft-simple-form-footer">
    @if ($method == 'POST')
        {!! Form::submit(__('admin.save'), [
            'class' => 'btn btn-primary',
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
        var company_prices = {!! json_encode($company_prices, JSON_UNESCAPED_UNICODE) !!};

        function bookingContainerNormalizeId(v) {
            if (v === undefined || v === null || v === '' || v === 'to_be_disabled') {
                return null;
            }
            return String(v);
        }

        function bookingContainerIdsMatch(selected, rowValue) {
            var a = bookingContainerNormalizeId(selected);
            if (a === null) {
                return false;
            }
            if (rowValue === undefined || rowValue === null) {
                return false;
            }
            return a === String(rowValue);
        }

        function updatePrice() {
            var containerId = bookingContainerNormalizeId($('#container_id').val());
            if (!containerId) {
                return;
            }

            var departureId = $('#departure_id').val();
            var loadingId = $('#loading_id').val();
            var agingId = $('#aging_id').val();

            var rows = company_prices[containerId];
            if (!rows || !Array.isArray(rows)) {
                return;
            }

            var matchedPrice = null;
            for (var i = 0; i < rows.length; i++) {
                var q = rows[i];
                if (
                    bookingContainerIdsMatch(departureId, q.departure_id) &&
                    bookingContainerIdsMatch(loadingId, q.loading_id) &&
                    bookingContainerIdsMatch(agingId, q.aging_id)
                ) {
                    matchedPrice = q.price;
                }
            }

            if (matchedPrice !== null && matchedPrice !== undefined && matchedPrice !== '') {
                $('#price').val(matchedPrice);
            }
        }

        function toggleCurrencyFields() {
            var isUsd = $('#currency').val() === 'USD';
            $('#exchange_rate_field').toggle(isUsd);
            $('#price_label').text(isUsd ? 'المبلغ بالدولار' : 'المبلغ بالجنيه');
            $('#price_hint').text(isUsd
                ? 'أدخل سعر النقلة بالدولار — يُسجّل في رصيد الدولار منفصلًا'
                : 'أدخل سعر النقلة بالجنيه المصري');
            updateEgpPreview();
        }

        function updateEgpPreview() {
            if ($('#currency').val() !== 'USD') {
                $('#egp_preview').text('');
                return;
            }
            var amount = parseFloat($('#price').val()) || 0;
            var rate = parseFloat($('#exchange_rate').val()) || 0;
            if (amount > 0 && rate > 0) {
                var egp = (amount * rate).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                $('#egp_preview').text('مرجع داخلي: ' + egp + ' ج.م (لا يدخل رصيد العميل بالمصري)');
            } else if (amount > 0) {
                $('#egp_preview').text('سيُضاف ' + amount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' $ لرصيد الدولار');
            } else {
                $('#egp_preview').text('');
            }
        }

        $(function () {
            $('#container_id, #departure_id, #loading_id, #aging_id').on('change', updatePrice);
            toggleCurrencyFields();
            $('#currency').on('change', toggleCurrencyFields);
            $('#price, #exchange_rate').on('input', updateEgpPreview);

            var pv = $('#price').val();
            if (pv === '' || pv === null || parseFloat(String(pv).replace(',', '.')) === 0) {
                updatePrice();
            }
        });
    </script>
    <script>
        var factory_branches = {!! json_encode($factory_branches) !!};
        $('#factory_id').on('change', updateBranches);

        function updateBranches() {
            var factory_id = $('#factory_id option:selected').val();

            $('#branch_id option').remove();

            $('#branch_id').append(
                "<option value='to_be_disabled' disabled='disabled' selected='selected'>{{ __('admin.select') }}</option>"
            );
            var available_branches = factory_branches[factory_id];
            for (const branch in available_branches) {
                $('#branch_id').append(`<option value='${branch}'>${available_branches[branch]}</option>`);
            };
        }

        @if (!empty($booking->factory_id) && empty(old('branch_id')) && !isset($booking_container))
            updateBranches();
        @endif
    </script>
@endpush
