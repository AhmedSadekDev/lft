@if($method == 'POST')
    {!! Form::open(['url' => $action, 'method' => $method, 'enctype'=>'multipart/form-data', 'files' => true]) !!}
@elseif ($method == 'PUT')
    {!! Form::model($factory, ['url' => [$action], 'method'=>$method , 'enctype'=>'multipart/form-data', 'files' => true]) !!}
@endif
        <div class="card-body">
            <div class="row">
                <!-- For loop this div -->
                <div class="col-md-6 col-sm-12">
                    <div class="form-group">
                        {!! Form::label("input_name", __('admin.name'), ["class" => "required-field"]) !!}
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
                        {!! Form::label("input_email", __('admin.email'), ["class" => "required-field"]) !!}
                        {!! Form::text( 'email' , old('email'), ["class" => "form-control", "id" => "input_email", "placeholder"=> __('admin.email')]) !!}
                        @error('email')
                            <small class="aleart text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>
                <!-- For loop this div -->

                <!-- For loop this div -->
                <div class="col-md-6 col-sm-12">
                    <div class="form-group">
                        {!! Form::label("input_number", __('admin.factory_number'), ["class" => "required-field"]) !!}
                        {!! Form::number('number' , old('number'), ["class" => "form-control", "id" => "input_number", "placeholder"=> __('admin.factory_number')]) !!}
                        @error('number')
                            <small class="aleart text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>
                <!-- For loop this div -->

                <!-- For loop this div -->
                <div class="col-md-6 col-sm-12">
                    <div class="form-group">
                        {!! Form::label("input_attachments", __('admin.attachments'), ["class" => "required-field"]) !!}
                        {!! Form::file('attachments[]', ["multiple" => true, "class" => "form-control", "id" => "input_attachments", "placeholder"=> __('admin.attachments'), "accept" => "application/msword, application/pdf"]) !!}
                        @error('attachments')
                            <small class="aleart text-danger">{{ $message }}</small>
                        @enderror
                        @error('attachments.*')
                            <small class="aleart text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="card-footer">
            @if($method == 'POST')
                    {!! Form::submit(__('admin.save'), ["class"=>"btn btn-primary btn-md text-uppercase font-weight-bold chat-send py-2 px-6"]) !!}
                @elseif ($method == 'PUT')
                    {!! Form::submit(__('admin.update'), ["class"=>"btn btn-primary"]) !!}
                @endif
        </div>

</form>
{!! Form::close() !!}
<!-- /.card-body -->

