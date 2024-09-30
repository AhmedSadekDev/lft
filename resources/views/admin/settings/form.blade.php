@if($method == 'POST')
    {!! Form::open(['url' => $action, 'method' => $method, 'enctype'=>'multipart/form-data', 'files' => true]) !!}
@elseif ($method == 'PUT')
    {!! Form::model($setting, ['url' => [$action], 'method'=>$method , 'enctype'=>'multipart/form-data', 'files' => true]) !!}
@endif
        <div class="card-body">
            <div class="row">

                <!-- For loop this div -->
                <div class="col-md-6 col-sm-12">
                    <div class="form-group">
                        {!! Form::label("input_phone", __('admin.phone'), ["class" => "required-field"]) !!}
                        {!! Form::text('phone' , old('phone'), ["class" => "form-control", "id" => "input_phone", "placeholder"=> __('admin.phone')]) !!}
                        @error('phone')
                            <small class="aleart text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>
                <!-- For loop this div -->

                <!-- For loop this div -->
                <div class="col-md-6 col-sm-12">
                    <div class="form-group">
                        {!! Form::label("input_email", __('admin.email'), ["class" => "required-field"]) !!}
                        {!! Form::email('email' , old('email'), ["class" => "form-control", "id" => "input_email", "placeholder"=> __('admin.email')]) !!}
                        @error('email')
                            <small class="aleart text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>
                <!-- For loop this div -->

                <!-- For loop this div -->
                <div class="col-md-6 col-sm-12">
                    <div class="form-group">
                        {!! Form::label("input_whatsapp", 'Whatsapp', ["class" => "required-field"]) !!}
                        {!! Form::text('whatsapp' , old('whatsapp'), ["class" => "form-control", "id" => "input_whatsapp", "placeholder"=> 'Whatsapp']) !!}
                        @error('whatsapp')
                            <small class="aleart text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>
                <!-- For loop this div -->

                <!-- For loop this div -->
                <div class="col-md-6 col-sm-12">
                    <div class="form-group">
                        {!! Form::label("input_facebook", 'Facebook', ["class" => "required-field"]) !!}
                        {!! Form::text('facebook' , old('facebook'), ["class" => "form-control", "id" => "input_facebook", "placeholder"=> 'Facebook URL']) !!}
                        @error('facebook')
                            <small class="aleart text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>
                <!-- For loop this div -->

                <!-- For loop this div -->
                <div class="col-md-6 col-sm-12">
                    <div class="form-group">
                        {!! Form::label("input_twitter", 'Twitter', ["class" => "required-field"]) !!}
                        {!! Form::text('twitter' , old('twitter'), ["class" => "form-control", "id" => "input_twitter", "placeholder"=> 'Twitter URL']) !!}
                        @error('twitter')
                            <small class="aleart text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>
                <!-- For loop this div -->

                <!-- For loop this div -->
                <div class="col-md-6 col-sm-12">
                    <div class="form-group">
                        {!! Form::label("input_linkedin", 'LinkedIn', ["class" => "required-field"]) !!}
                        {!! Form::text('linkedin' , old('linkedin'), ["class" => "form-control", "id" => "input_linkedin", "placeholder"=> 'LinkedIn URL']) !!}
                        @error('linkedin')
                            <small class="aleart text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>
                <!-- For loop this div -->

                <!-- For loop this div -->
                <div class="col-md-6 col-sm-12">
                    <div class="form-group">
                        {!! Form::label("input_instagram", 'Instagram', ["class" => "required-field"]) !!}
                        {!! Form::text('instagram' , old('instagram'), ["class" => "form-control", "id" => "input_instagram", "placeholder"=> 'Instagram URL']) !!}
                        @error('instagram')
                            <small class="aleart text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>
                <!-- For loop this div -->

                <!-- For loop this div -->
                <div class="col-md-6 col-sm-12">
                    <div class="form-group">
                        {!! Form::label("input_logo", __('admin.logo'), ["class" => "required-field"]) !!}
                        {!! Form::file('logo', ["multiple" => true, "class" => "form-control", "id" => "input_logo", "placeholder"=> __('admin.logo')]) !!}
                        @error('logo')
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

