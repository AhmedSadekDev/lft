@if($method == 'POST')
    {!! Form::open(['url' => $action, 'method' => $method, 'enctype'=>'multipart/form-data', 'files' => true]) !!}
@elseif ($method == 'PUT')
    {!! Form::model($service, ['url' => [$action], 'method'=>$method , 'enctype'=>'multipart/form-data', 'files' => true]) !!}
@endif
    <div class="card-body">
        <div class="row">

            <!-- For loop this div -->
            <div class="col-md-6 col-sm-12">
                <div class="form-group">
                    {!! Form::label("input_service_categories", __('admin.serviceCategory'), ["class" => "required-field"]) !!}
                    {!! Form::select('service_category_id' , $serviceCategories, old('service_category_id') , ["class" => "form-control", "id" => "input_service_categories", "placeholder"=> __('admin.serviceCategory')]) !!}
                    @error('service_category_id')
                        <small class="aleart text-danger">{{ $message }}</small>
                    @enderror
                </div>
            </div>
            <!-- For loop this div -->

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
