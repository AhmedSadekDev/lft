@if($method == 'POST')
    {!! Form::open(['url' => $action, 'method' => $method, 'enctype'=>'multipart/form-data', 'files' => true]) !!}
@elseif ($method == 'PUT')
    {!! Form::model($employee, ['url' => [$action], 'method'=>$method , 'enctype'=>'multipart/form-data', 'files' => true]) !!}
@endif
        <div class="card-body">
            <div class="row">
                <!-- For loop this div -->
                <div class="col-md-12 col-sm-12">
                    <div class="form-group">
                        {!! Form::label("input_company_id", __('main.company'), ["class" => "required-field"]) !!}
                        {!! Form::select('company_id', $companies , old('company_id') ?? null, ["class" => "form-control first-disabled", "id" => "input_company_id", "placeholder"=> __('admin.select_company')]) !!}
                        @error('company_id')
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
                <div class="col-md-6 col-sm-12">
                    <div class="form-group">
                        {!! Form::label("input_job", __('admin.job'), ["class" => "required-field"]) !!}
                        {!! Form::text('job' , old('job'), ["class" => "form-control", "id" => "input_job", "placeholder"=> __('admin.job')]) !!}
                        @error('job')
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
                        {!! Form::label("input_phone", __('admin.phone'), ["class" => "required-field"]) !!}
                        {!! Form::text( 'phone' , old('phone'), ["class" => "form-control", "id" => "input_phone", "placeholder"=> __('admin.phone')]) !!}
                        @error('phone')
                            <small class="aleart text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>
                <!-- For loop this div -->

                <!-- For loop this div -->
                <div class="col-md-12 col-sm-12">
                    <div class="form-group">
                        {!! Form::label("input_note", __('admin.note')) !!}
                        {!! Form::textarea('note', old('note'), ["class" => "form-control", "id" => "input_note", "placeholder"=> __('admin.note')]) !!}
                        @error('note')
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

