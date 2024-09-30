@if($method == 'POST')
    {!! Form::open(['url' => $action, 'method' => $method, 'enctype'=>'multipart/form-data']) !!}
@elseif ($method == 'PUT')
    {!! Form::model($container, ['url' => [$action], 'method'=>$method, 'enctype'=>'multipart/form-data']) !!}
@endif
        <div class="card-body">
            <div class="row">
                <!-- For loop this div -->
                <div class="col-md-6 col-sm-12">
                    <div class="form-group">
                        {!! Form::label("input_size", __('admin.size')) !!}
                        {!! Form::text( 'size' , old('size'), ["class" => "form-control", "id" => "input_size", "placeholder"=> __('admin.size')]) !!}
                        @error('size')
                            <small class="aleart text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>
                <!-- For loop this div -->

                <!-- For loop this div -->
                <div class="col-md-6 col-sm-12">
                    <div class="form-group">
                        {!! Form::label("input_type", __('admin.type')) !!}
                        {!! Form::text( 'type' , old('type'), ["class" => "form-control", "id" => "input_type", "placeholder"=> __('admin.type')]) !!}
                        @error('type')
                            <small class="aleart text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>
                <!-- For loop this div -->

                {{--  <!-- For loop this div -->
                <div class="col-md-6 col-sm-12">
                    <div class="form-group">
                        {!! Form::label("input_container_no", __('admin.container_no')) !!}
                        {!! Form::number('container_no', old('container_no'), ["class" => "form-control", "id" => "input_container_no", "placeholder"=> __('admin.container_no'), 'min' => 0]) !!}
                        @error('container_no')
                            <small class="aleart text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>  --}}
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
{{--  {!! Form::close() !!} --}}
<!-- /.card-body -->
