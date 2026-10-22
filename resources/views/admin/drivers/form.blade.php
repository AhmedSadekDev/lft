@if($method == 'POST')
    {!! Form::open(['url' => $action, 'method' => $method, 'enctype'=>'multipart/form-data', 'files' => true,
        'class' => 'lft-simple-form',
    ]) !!}
@elseif ($method == 'PUT')
    {!! Form::model($driver, ['url' => [$action], 'method'=>$method , 'enctype'=>'multipart/form-data', 'files' => true]) !!}
@endif
        <div class="card-body">
            <div class="row">
                <!-- For loop this div -->
                <div class="col-md-6 col-sm-12">
                    <div class="form-group">
                        {!! Form::label("input_name", __('admin.name')) !!}
                        {!! Form::text('name' , old('name'), ["class" => "form-control", "id" => "input_name", "placeholder"=> __('admin.name')]) !!}
                        @error('name')
                            <small class="aleart text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>
                <!-- For loop this div -->




                <!-- For loop this div -->
                <div class="col-md-6 col-sm-12">
                    <div class="form-group">
                        {!! Form::label("input_phone", __('admin.phone')) !!}
                        {!! Form::number( 'phone' , old('phone'), ["class" => "form-control", "id" => "input_phone", "placeholder"=> __('admin.phone')]) !!}
                        @error('phone')
                            <small class="aleart text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>
                <!-- For loop this div -->

                <div class="col-md-6 col-sm-12">
                    <div class="form-group">
                        {!! Form::label("input_national_id", __('admin.national_id')) !!}
                        <small class="text-muted d-block mb-1">{{ __('admin.optional') }}</small>
                        {!! Form::text('national_id', old('national_id'), ["class" => "form-control", "id" => "input_national_id", "placeholder"=> __('admin.national_id')]) !!}
                        @error('national_id')
                            <small class="aleart text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>

                <div class="col-md-6 col-sm-12">
                    <div class="form-group">
                        {!! Form::label("input_passport_number", __('admin.passport_number')) !!}
                        <small class="text-muted d-block mb-1">{{ __('admin.optional') }}</small>
                        {!! Form::text('passport_number', old('passport_number'), ["class" => "form-control", "id" => "input_passport_number", "placeholder"=> __('admin.passport_number')]) !!}
                        @error('passport_number')
                            <small class="aleart text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>

            </div>
        </div>

        <div class="card-footer lft-simple-form-footer">
            @if($method == 'POST')
                    {!! Form::submit(__('admin.save'), ["class"=>"btn btn-primary"]) !!}
                @elseif ($method == 'PUT')
                    {!! Form::submit(__('admin.update'), ["class"=>"btn btn-primary"]) !!}
                @endif
        </div>

</form>
{!! Form::close() !!}
<!-- /.card-body -->

