@extends("layouts.admin")
@section("content")
    <!--begin::Card-->
    <div class="card card-custom gutter-b">
        <div class="card-header">
            <div class="card-title">
                {{ __('admin.edit_container_information') }}
            </div>
        </div>
        
        
            {!! Form::model($container, ['url' => route('booking_containers_agents.update', $container->id), 'method' => 'POST', 'enctype' => 'multipart/form-data', 'files' => true]) !!}
                <input type="hidden" name="type_id" value="{{ $stageType }}">
                <div class="card-body">
                    <div class="mb-4">
                        @foreach([0 => 'التخصيص', 1 => 'التحميل', 2 => 'التعتيق'] as $type => $label)
                            <a class="btn {{ $stageType === $type ? 'btn-primary' : 'btn-light' }}" href="{{ request()->url() }}?type_id={{ $type }}">{{ $label }}</a>
                        @endforeach
                    </div>
                    <div class="row">
                        <!-- For loop this div -->
                        <div class="col-sm-12">
                            <div class="form-group">
                               <lable>#</lable>
                               <input readonly value="{{ $container->id }}" class="form-control">
                            </div>
                        </div>
                        <!-- For loop this div -->
                        
                        
                        @foreach($agents as $agent)
                            <div class="col-sm-4 mb-3">
                                <div class="form-group">
                                    <label for="agent{{ $agent->id }}">{{ $agent->name }}</label>
                                    <input id="agent{{ $agent->id }}" type="checkbox" name="agents[]" value="{{ $agent->id }}" @if(in_array($agent->id, $container->agents->pluck('id')->toArray())) checked @endif>
                                </div>
                            </div>
                        @endforeach

        
                    </div>
                </div>
        
                <div class="card-footer">
                    
                    {!! Form::submit(__('admin.update'), ["class"=>"btn btn-primary"]) !!}
                </div>
        
        </form>
        {!! Form::close() !!}
<!-- /.card-body -->


        
        
    </div>
@endsection
