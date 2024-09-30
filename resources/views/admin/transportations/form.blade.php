@if($method == 'POST')
    {!! Form::open(['url' => $action, 'method' => $method, 'enctype'=>'multipart/form-data', 'files' => true]) !!}
@elseif ($method == 'PUT')
    {!! Form::model($companyTransportation, ['url' => [$action], 'method'=>$method , 'enctype'=>'multipart/form-data', 'files' => true]) !!}
@endif
        <div class="card-body">
            <div class="row">

                <!-- For loop this div -->
                <div class="col-md-6 col-sm-12">
                    <div class="form-group">
                        {!! Form::label("input_company", __('main.company'), ["class" => "required-field"]) !!}
                        {!! Form::select('company_id' , $companies, isset($company) ? $company->id : old('company_id'), ["class" => "form-control", "id" => "input_company", "placeholder"=> __('admin.select_company')]) !!}
                        @error('company_id')
                            <small class="aleart text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>
                <!-- For loop this div -->

                <!-- For loop this div -->
                <div class="col-md-6 col-sm-12">
                    <div class="form-group">
                        {!! Form::label("input_container", __('main.container'), ["class" => "required-field"]) !!}
                        {!! Form::select('container_id' , $containers, old('container_id'), ["class" => "form-control", "id" => "input_container", "placeholder"=> __('admin.select_container')]) !!}
                        @error('container_id')
                            <small class="aleart text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>
                <!-- For loop this div -->

                <!-- For loop this div -->
                <div class="col-md-6 col-sm-12">
                    <div class="form-group">
                        {!! Form::label("input_departure", __('admin.departure_location'), ["class" => "required-field"]) !!}
                        {!! Form::select('departure_id' , $citiesAndRegions, old('departure_id'), ["class" => "form-control", "id" => "input_departure", "placeholder"=> __('admin.departure_location')]) !!}
                        @error('departure_id')
                            <small class="aleart text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>
                <!-- For loop this div -->

                <!-- For loop this div -->
                <div class="col-md-6 col-sm-12">
                    <div class="form-group">
                        {!! Form::label("input_loading", __('admin.loading_location'), ["class" => "required-field"]) !!}
                        {!! Form::select('loading_id' , $citiesAndRegions, old('loading_id'), ["class" => "form-control", "id" => "input_loading", "placeholder"=> __('admin.loading_location')]) !!}
                        @error('loading_id')
                            <small class="aleart text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>
                <!-- For loop this div -->

                <!-- For loop this div -->
                <div class="col-md-6 col-sm-12">
                    <div class="form-group">
                        {!! Form::label("input_aging", __('admin.aging_location'), ["class" => "required-field"]) !!}
                        {!! Form::select('aging_id' , $citiesAndRegions, old('aging_id'), ["class" => "form-control", "id" => "input_aging", "placeholder"=> __('admin.aging_location')]) !!}
                        @error('aging_id')
                            <small class="aleart text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                </div>
                <!-- For loop this div -->

                <!-- For loop this div -->
                <div class="col-md-6 col-sm-12">
                    <div class="form-group">
                        {!! Form::label("input_price", __('admin.price'), ["class" => "required-field"]) !!}
                        {!! Form::number('price' , old('price'), ["class" => "form-control", "id" => "input_price", "placeholder"=> __('admin.price'), 'min' => 0]) !!}
                        @error('price')
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

