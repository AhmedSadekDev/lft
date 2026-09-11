@if ($method == 'POST')
    {!! Form::open(['url' => $action, 'method' => $method, 'enctype' => 'multipart/form-data', 'files' => true]) !!}
@elseif ($method == 'PUT')
    {!! Form::model($privateCompany, [
        'url' => [$action],
        'method' => $method,
        'enctype' => 'multipart/form-data',
        'files' => true,
    ]) !!}
@endif
<div class="card-body">
    <div class="row">
        <div class="col-md-6 col-sm-12">
            <div class="form-group">
                {!! Form::label('input_name', __('admin.name')) !!}
                {!! Form::text('name', old('name'), [
                    'class' => 'form-control',
                    'id' => 'input_name',
                    'placeholder' => __('admin.name'),
                    'required' => true,
                ]) !!}
                @error('name')
                    <small class="aleart text-danger">{{ $message }}</small>
                @enderror
            </div>
        </div>

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

        <div class="col-md-6 col-sm-12">
            <div class="form-group">
                {!! Form::label('input_commercial_register', 'السجل التجاري') !!}
                {!! Form::text('commercial_register', old('commercial_register'), [
                    'class' => 'form-control',
                    'id' => 'input_commercial_register',
                    'placeholder' => 'السجل التجاري',
                ]) !!}
                @error('commercial_register')
                    <small class="aleart text-danger">{{ $message }}</small>
                @enderror
            </div>
        </div>

        <div class="col-md-6 col-sm-12">
            <div class="form-group">
                {!! Form::label('input_logo', 'اللوجو') !!}
                {!! Form::file('logo', [
                    'class' => 'form-control',
                    'id' => 'input_logo',
                    'accept' => 'image/*',
                ]) !!}
                @error('logo')
                    <small class="aleart text-danger">{{ $message }}</small>
                @enderror
                @if(isset($privateCompany) && $privateCompany->logo)
                    <div class="mt-2">
                        <img src="{{ $privateCompany->logo }}" alt="Logo"
                             style="max-width: 150px; max-height: 150px; border-radius: 4px;">
                        <p class="text-muted mt-1">اللوجو الحالي</p>
                    </div>
                @endif
            </div>
        </div>

        <div class="col-md-12">
            <h5 class="font-weight-bold mt-3 mb-3">معلومات الاتصال</h5>
        </div>

        <div class="col-md-6 col-sm-12">
            <div class="form-group">
                {!! Form::label('input_phone1', 'الهاتف الأول') !!}
                {!! Form::text('phone1', old('phone1'), [
                    'class' => 'form-control',
                    'id' => 'input_phone1',
                    'placeholder' => '01001365666',
                ]) !!}
                @error('phone1')
                    <small class="aleart text-danger">{{ $message }}</small>
                @enderror
            </div>
        </div>

        <div class="col-md-6 col-sm-12">
            <div class="form-group">
                {!! Form::label('input_phone2', 'الهاتف الثاني') !!}
                {!! Form::text('phone2', old('phone2'), [
                    'class' => 'form-control',
                    'id' => 'input_phone2',
                    'placeholder' => '01013118008',
                ]) !!}
                @error('phone2')
                    <small class="aleart text-danger">{{ $message }}</small>
                @enderror
            </div>
        </div>

        <div class="col-md-6 col-sm-12">
            <div class="form-group">
                {!! Form::label('input_tel_fax', 'تليفون - فاكس') !!}
                {!! Form::text('tel_fax', old('tel_fax'), [
                    'class' => 'form-control',
                    'id' => 'input_tel_fax',
                    'placeholder' => '057 - 2292423',
                ]) !!}
                @error('tel_fax')
                    <small class="aleart text-danger">{{ $message }}</small>
                @enderror
            </div>
        </div>

        <div class="col-md-6 col-sm-12">
            <div class="form-group">
                {!! Form::label('input_email', __('admin.email')) !!}
                {!! Form::email('email', old('email'), [
                    'class' => 'form-control',
                    'id' => 'input_email',
                    'placeholder' => 'leader@leaderfortrans.com',
                ]) !!}
                @error('email')
                    <small class="aleart text-danger">{{ $message }}</small>
                @enderror
            </div>
        </div>

        <div class="col-md-12 col-sm-12">
            <div class="form-group">
                {!! Form::label('input_address', __('admin.address')) !!}
                {!! Form::textarea('address', old('address'), [
                    'class' => 'form-control',
                    'id' => 'input_address',
                    'placeholder' => 'ميناء دمياط المجمع الاستثمارى وحدة ٢٠٢',
                    'rows' => 2,
                ]) !!}
                @error('address')
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
