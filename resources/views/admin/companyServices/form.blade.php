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
                    {!! Form::label("input_service", __('admin.services'), ["class" => "required-field"]) !!}
                    {!! Form::select('service_id' , [], old('service_id') , ["class" => "form-control", "id" => "input_service", "placeholder"=> __('admin.services')]) !!}
                    @error('service_id')
                        <small class="aleart text-danger">{{ $message }}</small>
                    @enderror
                </div>
            </div>
            <!-- For loop this div -->

            <!-- For loop this div -->
            <div class="col-md-6 col-sm-12">
                <div class="form-group">
                    {!! Form::label("input_cost", __('admin.cost'), ["class" => "required-field"]) !!}
                    {!! Form::number('cost' , old('cost'), ["class" => "form-control", "id" => "input_cost", "min" => "1","placeholder"=> __('admin.cost')]) !!}
                    @error('cost')
                        <small class="aleart text-danger">{{ $message }}</small>
                    @enderror
                </div>
            </div>
            <!-- For loop this div -->

            {{--  <!-- For loop this div -->
            <div class="col-md-6 col-sm-12">
                <div class="form-group">
                    {!! Form::label("input_name", __('admin.name'), ["class" => "required-field"]) !!}
                    {!! Form::text('name' , old('name'), ["class" => "form-control", "id" => "input_name", "placeholder"=> __('admin.name')]) !!}
                    @error('name')
                        <small class="aleart text-danger">{{ $message }}</small>
                    @enderror
                </div>
            </div>
            <!-- For loop this div -->  --}}
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

    <script>
        $('#input_service_categories').on('change', function(){
            var classification_id = $(this).val();
            if(classification_id == ''){
                return;
            }

            var url = "{{ route('services.getServices', ':id') }}"
            url = url.replace(':id', classification_id);
            var token = '{{ csrf_token() }}';

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': token,
                    'X-Requested-With': 'XMLHttpRequest',
                }
            });

            $.ajax({
                url:url,
                type:'GET',
                success:function(res){
                    $('#input_service').empty();
                    $('#input_service').append(`<option value="">{{ __('admin.choose_service') }}</option>`);
                    $.each(res, function(i, v){
                        console.log(i, v);
                        $('#input_service').append(`<option value="${i}">${v}</option>`);
                    });
                    var service_id = `{{ isset($booking) ? ($booking->service_type ?? old('service_type')): old('service_type') }}`;
                    if(service_id != ''){
                        $('#input_service option[value='+service_id+']').attr('selected','selected');
                    }
                }
            })
        });
    </script>

