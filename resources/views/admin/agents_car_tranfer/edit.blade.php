@extends('layouts.admin')
@section('content')
    
    <div class="container-fluid lft-simple-page">
<div class="container">
        @include('layouts.includes.breadcrumb', ['page' => __('main.car_shipments')])
        <!--begin::Card-->
        <div class="card card-custom">
            <div class="card-header flex-wrap py-5">
                <div class="card-toolbar">
                    <!--begin::Button-->

                    <a href="{{ route('agent_car_tranfer.index', $item->agent_id) }}" class="btn btn-primary font-weight-bolder">
                        <i class="fas fa-arrow-right" aria-hidden="true"></i>{{ __('main.back') }}
                    </a>

                    <!--end::Button-->
                </div>
            </div>
            <div class="card-body">
                <form action="{{ route('agent_car_tranfer.update', $item->id) }}" method="post" enctype="multipart/form-data">
                    @csrf
                    @method('put')

                    <div class="form-group">
                      <label for="carSelect">{{ __('main.cars') }}</label>
                        <select class="form-control" id="carSelect" name="car_id" required>
                            @foreach ($cars as $car)
                                <option value="{{ $car->id }}" @if($car->id == old('car_id') || $car->id == $item->car_id) selected @endif> {{ $car->car_number }} </option>
                            @endforeach
                        </select>
                        @error('car_id')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                      <label for="nameInput">{{ __('admin.title') }}</label>
                        <input class="form-control" id="nameInput" type="text" name="name" value="{{ old('name') ?? $item->name }}" required>
                        @error('name')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>


                    <div class="form-group">
                      <label for="valueInput">{{ __('admin.value') }}</label>
                        <input class="form-control" id="valueInput" type="number" name="value" value="{{ old('value') ?? $item->value }}" required>
                        @error('value')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                    
                    
                    <div class="form-group">
                      <label for="imageInput">{{ __('admin.image') }}</label>
                        <input class="form-control" id="imageInput" type="file" name="image" required>
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
    </div>
@endsection
