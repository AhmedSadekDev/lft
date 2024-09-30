@if ($method == 'POST')
    {!! Form::open(['url' => $action, 'method' => $method, 'enctype' => 'multipart/form-data', 'files' => true]) !!}
@elseif ($method == 'PUT')
    {!! Form::model($company, [
        'url' => [$action],
        'method' => $method,
        'enctype' => 'multipart/form-data',
        'files' => true,
    ]) !!}
@endif
<div class="card-body">
    <div class="row">
        <!-- For loop this div -->
        <div class="col-md-6 col-sm-12">
            <div class="form-group">
                {!! Form::label('input_name', __('admin.name')) !!}
                {!! Form::text('name', old('name'), [
                    'class' => 'form-control',
                    'id' => 'input_name',
                    'placeholder' => __('admin.name'),
                ]) !!}
                @error('name')
                    <small class="aleart text-danger">{{ $message }}</small>
                @enderror
            </div>
        </div>
        <!-- For loop this div -->

        <!-- For loop this div -->
        <div class="col-md-6 col-sm-12">
            <div class="form-group">
                {!! Form::label('input_email', __('admin.email')) !!}
                {!! Form::text('email', old('email'), [
                    'class' => 'form-control',
                    'id' => 'input_email',
                    'placeholder' => __('admin.email'),
                ]) !!}
                @error('email')
                    <small class="aleart text-danger">{{ $message }}</small>
                @enderror
            </div>
        </div>
        <!-- For loop this div -->
        <!-- For loop this div -->
        <div class="col-md-6 col-sm-12">
            <div class="form-group">
                {!! Form::label('input_address', __('admin.address')) !!}
                {!! Form::text('address', old('address'), [
                    'class' => 'form-control',
                    'id' => 'input_address',
                    'placeholder' => __('admin.address'),
                ]) !!}
                @error('address')
                    <small class="aleart text-danger">{{ $message }}</small>
                @enderror
            </div>
        </div>
        <!-- For loop this div -->

        <!-- For loop this div -->
        <div class="col-md-6 col-sm-12">
            <div class="form-group">
                {!! Form::label('input_tax_no', __('admin.tax_no')) !!}
                {!! Form::text('tax_no', old('tax_no'), [
                    'class' => 'form-control',
                    'id' => 'input_tax_no',
                    'placeholder' => __('admin.tax_no'),
                ]) !!}
                @error('tax_no')
                    <small class="aleart text-danger">{{ $message }}</small>
                @enderror
            </div>
        </div>
        <!-- For loop this div -->

        <!-- For loop this div -->
        <div class="col-md-6 col-sm-12">
            <!--begin::Select -->
            <div class="form-group">
                {!! Form::label('taxed_input', __('admin.taxed_status')) !!}
                {!! Form::select('taxed', [0 => __('admin.no'), 1 => __('admin.yes')], old('taxed'), [
                    'id' => 'add_vat_input',
                    'class' => 'form-control',
                    'placeholder' => __('admin.taxed_status'),
                ]) !!}
                @error('taxed')
                    <span class="form-text text-danger">{{ $message }}</span>
                @enderror
            </div>
            <!--end::Select-->
        </div>
        <!-- For loop this div -->

        <!-- For loop this div -->
        <div class="col-md-6 col-sm-12">
            <div class="form-group">
                {!! Form::label('input_phone', __('admin.phone')) !!}
                {!! Form::text('phone', old('phone'), [
                    'class' => 'form-control',
                    'id' => 'input_phone',
                    'placeholder' => __('admin.phone'),
                ]) !!}
                @error('phone')
                    <small class="aleart text-danger">{{ $message }}</small>
                @enderror
            </div>
        </div>
        <!-- For loop this div -->

        <!-- For loop this div -->
        <div class="col-md-6 col-sm-12 mt-2">
            <div class="form-group">
                {!! Form::label('default_type', __('admin.default_billing_type')) !!}
                <div class="row" id="default_type">
                    <div class="col-6">
                        {!! Form::radio('bill_type', '1', ['class' => 'form-check-input col-1 mt-3', 'id' => 'input_invoice']) !!}
                        {!! Form::label('input_invoice', __('admin.invoice')) !!}
                    </div>
                    <div class="col-6">
                        {!! Form::radio('bill_type', '2', ['class' => 'form-check-input col-1 mt-3', 'id' => 'input_statment']) !!}
                        {!! Form::label('input_statment', __('admin.statment')) !!}
                    </div>
                </div>
                @error('bill_type')
                    <small class="aleart text-danger">{{ $message }}</small>
                @enderror
            </div>
        </div>
        <!-- For loop this div -->

        <!-- For loop this div -->
        <div class="col-md-6 col-sm-12">
            <div class="form-group">
                {!! Form::label('input_attachments', __('admin.attachments')) !!}
                {!! Form::file('attachments[]', [
                    'multiple' => true,
                    'class' => 'form-control',
                    'id' => 'input_attachments',
                    'placeholder' => __('admin.attachments'),
                ]) !!}
                @error('attachments.*')
                    <small class="aleart text-danger">{{ $message }}</small>
                @enderror
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
        {!! Form::submit(__('admin.update'), ['class' => 'btn btn-primary']) !!}
    @endif
</div>

</form>
{!! Form::close() !!}
<!-- /.card-body -->
