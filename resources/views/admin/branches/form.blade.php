@if($method == 'POST')
    {!! Form::open(['url' => $action, 'method' => $method, 'enctype'=>'multipart/form-data', 'files' => true]) !!}
@elseif ($method == 'PUT')
    {!! Form::model($branch, ['url' => [$action], 'method'=>$method , 'enctype'=>'multipart/form-data', 'files' => true]) !!}
@endif
        <div class="card-body">
            <div class="row">

                <!-- For loop this div -->
                <div class="col-md-12 col-sm-12">
                    <div class="form-group">
                        {!! Form::label("input_factory_id", __('main.factory'), ["class" => "required-field"]) !!}
                        {!! Form::select('factory_id', $factories , old('factory_id') ?? null, ["class" => "form-control first-disabled", "id" => "input_factory_id", "placeholder"=> __('admin.select_factory')]) !!}
                        @error('factory_id')
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

                <!-- For loop this div -->
{{--                <div class="col-md-6 col-sm-12">--}}
{{--                    <div class="form-group">--}}
{{--                        {!! Form::label("input_country", __('admin.country'), ["class" => "required-field"]) !!}--}}
{{--                        {!! Form::select('country_id' , $citiesAndRegions, old('country_id'), ["class" => "form-control", "id" => "input_country", "placeholder"=> __('admin.country')]) !!}--}}
{{--                        @error('country_id')--}}
{{--                            <small class="aleart text-danger">{{ $message }}</small>--}}
{{--                        @enderror--}}
{{--                    </div>--}}
{{--                </div>--}}
                <!-- For loop this div -->

                <!-- For loop this div -->
                <div class="col-md-6 col-sm-12">
                    <div class="form-group">
                        {!! Form::label("input_city", __('admin.city'), ["class" => "required-field"]) !!}
                        {!! Form::select('city_id' , $citiesAndRegions, old('city_id'), ["class" => "form-control", "id" => "input_city", "placeholder"=> __('admin.city')]) !!}
                        @error('city_id')
                            <small class="aleart text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>
                <!-- For loop this div -->

                <!-- For loop this div -->
                <div class="col-md-6 col-sm-12">
                    <div class="form-group">
                        {!! Form::label("input_address", __('admin.address'), ["class" => "required-field"]) !!}
                        {!! Form::text('address' , old('address'), ["class" => "form-control", "id" => "input_address", "placeholder"=> __('admin.address')]) !!}
                        @error('address')
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
                        {!! Form::label("input_number", __('admin.branch_number'), ["class" => "required-field"]) !!}
                        {!! Form::number('number' , old('number'), ["class" => "form-control", "id" => "input_number", "placeholder"=> __('admin.factory_number')]) !!}
                        @error('number')
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
@push('js')
    <script>
        $(function($) {
            "use strict";
            $( "select.first-disabled option:first-child" ).attr("disabled", "disabled");
        })(jQuery);
    </script>
@endpush

