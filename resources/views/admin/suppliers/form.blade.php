@if($method == 'POST')
    {!! Form::open(['url' => $action, 'method' => $method]) !!}
@elseif ($method == 'PUT')
    {!! Form::model($supplier, ['url' => [$action], 'method' => $method]) !!}
@endif
    <div class="card-body">
        <div class="row">
            <div class="col-md-6 col-sm-12">
                <div class="form-group">
                    {!! Form::label('input_name', __('admin.name'), ['class' => 'required-field']) !!}
                    {!! Form::text('name', old('name', isset($supplier) ? $supplier->name : null), [
                        'class' => 'form-control',
                        'id' => 'input_name',
                        'placeholder' => __('admin.name'),
                        'required' => true,
                    ]) !!}
                    @error('name')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>
            </div>

            @if($method == 'POST')
                <div class="col-md-6 col-sm-12">
                    <div class="form-group">
                        {!! Form::label('input_balance', 'الرصيد الافتتاحي') !!}
                        {!! Form::number('balance', old('balance', 0), [
                            'class' => 'form-control',
                            'id' => 'input_balance',
                            'step' => '0.01',
                            'min' => '0',
                        ]) !!}
                        @error('balance')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>
            @endif
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
