@if ($method == 'POST')
    {!! Form::open([
        'url' => $action,
        'method' => $method,
        'enctype' => 'multipart/form-data',
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
                {!! Form::select(
                    'factory_id',
                    array_replace(['to_be_disabled' => __('admin.select')], $factories->all()),
                    old('factory_id'),
                    [
                        'id' => 'factory_id',
                        'class' => 'form-control',
                    ],
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
                    old('yard_id'),
                    ['id' => 'yard_id', 'class' => 'form-control'],
                ) !!}
                <small class="text-danger">{{ $errors->first('yard_id') }}</small>
            </div>
        </div>
        {{--  --}}
        {{--  --}}
        {{--  --}}
        <div class="col-md-6 col-sm-12">
            <div class="form-group{{ $errors->has('container_id') ? ' has-error' : '' }}">
                {!! Form::label('container_id', __('admin.container')) !!}
                {!! Form::select(
                    'container_id',
                    array_replace(['to_be_disabled' => __('admin.select')], $container_types->all()),
                    old('container_id'),
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
                    old('departure_id'),
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
                    old('loading_id'),
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
                    old('aging_id'),
                    [
                        'id' => 'aging_id',
                        'class' => 'form-control agingSelect',
                    ],
                ) !!}
                <small class="text-danger">{{ $errors->first('aging_id') }}</small>
            </div>
        </div>
        <div class="col-md-6 col-sm-12">
            <div class="form-group{{ $errors->has('price') ? ' has-error' : '' }}">
                {!! Form::label('price', __('admin.price')) !!}
                {!! Form::number('price', old('price'), ['class' => 'form-control']) !!}
                <small class="text-danger">{{ $errors->first('price') }}</small>
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
        var company_prices = {!! json_encode($company_prices) !!};
        $('#container_id').on('change', updatePrice);
        $('#departure_id').on('change', updatePrice);
        $('#loading_id').on('change', updatePrice);
        $('#aging_id').on('change', updatePrice);

        function updatePrice() {
            var container_id = $('#container_id option:selected').val();
            var departure_id = $('#departure_id option:selected').val();
            var loading_id = $('#loading_id option:selected').val();
            var aging_id = $('#aging_id option:selected').val();

            var container_prices = company_prices[container_id];

            container_prices.forEach(quotation => {
                if (
                    departure_id == quotation.departure_id &&
                    loading_id == quotation.loading_id &&
                    aging_id == quotation.aging_id
                )
                    $('#price').val(quotation.price);
            });
        }
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
    </script>
@endpush
