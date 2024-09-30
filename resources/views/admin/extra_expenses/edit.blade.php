@extends('layouts.admin')
@section('content')
    <!--begin::Card-->
    <div class="card card-custom gutter-b">
        <div class="card-header">
            <div class="card-title">
                {{ __('main.extra_expense') }}
            </div>
            <div class="mt-3">
                <a href="{{ route('booking_contrainer_extra_costs', $bookingContainer->id) }}" class="btn btn-secondary float-right">
                    {{ __('main.back') }}
                </a>
            </div>
        </div>
        <div class="card-body">
            <form action="{{ route('booking_contrainer_extra_costs_update', $expense->id) }}" method="post">
              @csrf

                <div class="form-group">
                    <label for="name">{{ __('main.operation_name') }}</label>
                    <input type="text" class="form-control" id="name" name="name" value="{{ $expense->name }}" required>
                    @error('name')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="value">{{ __('admin.cost') }}</label>
                    <input type="number" class="form-control" id="value" name="value" value="{{ $expense->value }}" required>
                    @error('value')
                        <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>


                <div class="form-group">
                    <label for="booking_container_id">{{ __('main.container') }}</label>
                    <select class="form-control" name="booking_container_id" id="booking_container_id">
                        @foreach ($booking_containers as $booking_container)
                            <option value="{{ $booking_container->id }}" @if ($bookingContainer->id == $booking_container->id) selected @endif>
                                {{ $booking_container->container_no }}</option>
                        @endforeach
                    </select>
                    @error('booking_container_id')
                      <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="car_id">{{ __("admin.car_number") }}</label>
                    <select class="form-control" name="car_id" id="car_id">
                        @foreach ($cars as $car)
                            <option value="{{ $car->id }}" @if ($expense->car_id == $bookingContainer->delivery_policies->first()->car_id) selected @endif>
                                {{ $car->car_number }}</option>
                        @endforeach
                    </select>
                    @error('car_id')
                      <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="driver_id">{{ __("main.drivers") }}</label>
                    <select class="form-control" name="driver_id" id="driver_id">
                        @foreach ($drivers as $driver)
                            <option value="{{ $driver->id }}" @if ($expense->driver_id == $bookingContainer->delivery_policies->first()->driver_id) selected @endif>
                                {{ $driver->name }}</option>
                        @endforeach
                    </select>
                    @error('driver_id')
                      <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-group">
                  <button type="submit" class="btn btn-primary">{{ __("admin.save") }}</button>
                </div>

            </form>
        </div>
    </div>
@endsection
