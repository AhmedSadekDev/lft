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
                    {!! Form::label("input_service_categories", __('admin.agent'), ["class" => "required-field"]) !!}
                    {!! Form::select('agent_id' , $agents, old('agent_id') , ["class" => "form-control selectpicker", "id" => "agents", "placeholder"=> __('admin.agent'),"data-live-search" => "true"]) !!}
                    @error('agent_id')
                        <small class="aleart text-danger">{{ $message }}</small>
                    @enderror
                </div>
            </div>
            <!-- For loop this div -->

            <!-- For loop this div -->
            <div class="col-md-6 col-sm-12">
                <div class="form-group">
                    {!! Form::label("input_financial_custody", __('admin.financial_custody'), ["class" => "required-field"]) !!}
                    {!! Form::number('value' , old('value'), ["class" => "form-control", "id" => "input_financial_custody", "placeholder"=> __('admin.financial_custody')]) !!}
                    @error('value')
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
