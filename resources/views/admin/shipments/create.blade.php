@extends('layouts.admin')
@section('content')
    <div class="container">
        @include('layouts.includes.breadcrumb', ['page' => __('main.car_shipments')])
        <!--begin::Card-->
        <div class="card card-custom">
            <div class="card-header flex-wrap py-5">
                <div class="card-toolbar">
                    <!--begin::Button-->

                    <a href="{{ route('shipments.index', request()->id) }}" class="btn btn-primary font-weight-bolder">
                        <i class="fas fa-arrow-right" aria-hidden="true"></i>{{ __('main.back') }}
                    </a>

                    <!--end::Button-->
                </div>
            </div>
            <div class="card-body">
                <form action="{{ route('shipments.store') }}" method="post" enctype='multipart/form-data'>
                    @csrf
                    <input type="hidden" name="car_id" value="{{ $car->id }}">
                    
                    <div class="form-group">
                      <label for="nameInput">{{ __('admin.name') }}</label>
                        <input class="form-control" id="nameInput" type="text" name="name" value="{{ old('name') }}" required>
                        @error('name')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                   
                    <div class="form-group">
                      <label for="valueInput">{{ __('admin.agent') }}</label>
                        <select class="form-control" id="valueInput" type="number" name="agent_id" required>
                            @foreach($agents as $agent)
                                <option value="{{ $agent->id }}">{{ $agent->name }}</option>
                            @endforeach
                        </select>
                        @error('agent_id')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    
                    
                    <div class="form-group">
                      <label for="valueInput">{{ __('admin.value') }}</label>
                        <input class="form-control" id="valueInput" type="number" name="value" value="{{ old('value') }}" required>
                        @error('value')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                   
                    <div class="form-group">
                      <label for="dateInput">{{ __('main.date') }}</label>
                        <input class="form-control" id="dateInput" type="date" name="date" value="{{ old('date') }}" required>
                        @error('date')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>


                    
                    <div class="form-group">
                      <label for="additionInput">{{ __('admin.addition') }}</label>
                        <input class="form-control" id="additionInput" type="number" name="addition" value="{{ old('addition') }}">
                        @error('addition')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    
                    <div class="form-group">
                      <label for="additionInput">{{ __('admin.image') }}</label>
                        <input class="form-control" id="additionInput" type="file" name="image" value="{{ old('image') }}">
                        @error('image')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                   


                    <div class="d-flex justify-content-end my-3">
                        <button type="submit" class="btn btn-primary">{{ __('admin.save') }}</button>
                    </div>
                </form>
            </div>
        </div>
        <!--end::Card-->
    </div>
@endsection
