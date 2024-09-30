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
            <div class="form-group{{ $errors->has('service_type_id') ? ' has-error' : '' }}">
                {!! Form::label('service_type_id', __('admin.service_type')) !!}
                {!! Form::select(
                    'service_type_id',
                    array_replace(['to_be_disabled' => __('admin.select')], $service_types->all()),
                    old('service_type_id'),
                    ['id' => 'service_type_id', 'class' => 'form-control', 'required' => 'required'],
                ) !!}
                <small class="text-danger">{{ $errors->first('service_type_id') }}</small>
            </div>
        </div>

        <div class="col-md-6 col-sm-12">
            <div class="form-group{{ $errors->has('service_id') ? ' has-error' : '' }}">
                {!! Form::label('service_id', __('admin.service')) !!}
                {!! Form::select('service_id', ['to_be_disabled' => __('admin.select')], old('service_id'), [
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
                {!! Form::number('price', old('price'), ['class' => 'form-control', 'required' => 'required']) !!}
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
            <div class="form-group">
                {!! Form::label('input_note', __('admin.receipt_image')) !!}
                <input type="file" accept="image/*" name="image" id="input_receipt_image" class="form-control">
                {{-- {!! Form::file('image', ['class' => 'form-control', 'id' => 'input_receipt_image']) !!} --}}
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
        $('#service_type_id').on('change', function() {
            var service_type_id = $(this).val();

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
                        $('#service_id').append(`<option value="${i}">${v}</option>`);
                    });
                }
            })
        });
    </script>
    <script>
        var company_prices = {!! json_encode($company_prices) !!};
        $('#service_id').on('change', updatePrice);

        function updatePrice() {
            var service_id = $('#service_id option:selected').val();
            if (company_prices[service_id])
                $('#price').val(company_prices[service_id]);
        }
    </script>
@endpush
