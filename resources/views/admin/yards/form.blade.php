@if($method == 'POST')
    {!! Form::open(['url' => $action, 'method' => $method, 'enctype'=>'multipart/form-data', 'files' => true]) !!}
@elseif ($method == 'PUT')
    {!! Form::model($yard, ['url' => [$action], 'method'=>$method , 'enctype'=>'multipart/form-data', 'files' => true]) !!}
@endif
    <div class="card-body">
        <div class="row">
            <!-- For loop this div -->
            <div class="col-md-6 col-sm-12">
                <div class="form-group">
                    {!! Form::label("input_title", __('admin.en_title'), ["class" => "required-field"]) !!}
                    {!! Form::text('title[en]' , (isset($yard) ? $yard->getTranslations('title')['en'] : old('title[en]')) , ["class" => "form-control", "id" => "input_title", "placeholder"=> __('admin.en_title')]) !!}
                    @error('title.en')
                        <small class="aleart text-danger">{{ $message }}</small>
                    @enderror
                </div>
            </div>
            <!-- For loop this div -->

            <!-- For loop this div -->
            <div class="col-md-6 col-sm-12">
                <div class="form-group">
                    {!! Form::label("input_title", __('admin.ar_title'), ["class" => "required-field"]) !!}
                    {!! Form::text('title[ar]' , (isset($yard) ? $yard->getTranslations('title')['ar'] : old('title[ar]')), ["class" => "form-control", "id" => "input_title", "placeholder"=> __('admin.ar_title')]) !!}
                    @error('title.ar')
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

