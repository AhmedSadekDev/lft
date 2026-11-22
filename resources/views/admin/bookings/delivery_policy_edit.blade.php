@extends('layouts.admin')
@section('content')
<div class="container-fluid lft-simple-page">
    @include('layouts.includes.breadcrumb', ['page' => __('main.delivery_policies')])

    <div class="card card-custom gutter-b lft-simple-card lft-simple-card--wide">
        <div class="card-header border-0">
            <div class="card-title">
                <h3 class="card-label mb-0">تعديل بوليصة التوصيل #{{ $policy->id }}</h3>
            </div>
            <div class="card-toolbar">
                @if($container)
                    <a href="{{ route('bookings.booking_container_policies', $container->id) }}" class="btn btn-light font-weight-bolder">
                        {{ __('main.back') }}
                    </a>
                @elseif($booking)
                    <a href="{{ route('bookings.show', $booking->id) }}" class="btn btn-light font-weight-bolder">
                        {{ __('main.back') }}
                    </a>
                @endif
            </div>
        </div>

        <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif
            @if($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('bookings.delivery_policy_update', $policy) }}">
                @csrf
                @method('PUT')

                <div class="row">
                    <div class="form-group col-md-6">
                        <label class="font-weight-bold">{{ __('main.drivers') }}</label>
                        <select name="driver_id" class="form-control" required>
                            <option value="">—</option>
                            @foreach($drivers as $driver)
                                <option value="{{ $driver->id }}" @selected((int) old('driver_id', $policy->driver_id) === (int) $driver->id)>
                                    {{ $driver->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group col-md-6">
                        <label class="font-weight-bold">{{ __('admin.car_number') }}</label>
                        <select name="car_id" class="form-control" required>
                            <option value="">—</option>
                            @foreach($cars as $car)
                                <option value="{{ $car->id }}" @selected((int) old('car_id', $policy->car_id) === (int) $car->id)>
                                    {{ $car->car_number }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group col-md-4">
                        <label class="font-weight-bold">{{ __('admin.value') }}</label>
                        <input type="number" step="0.01" min="0" name="value" class="form-control"
                               value="{{ old('value', $policy->money_transfer->value ?? 0) }}" required>
                    </div>

                    <div class="form-group col-md-4">
                        <label class="font-weight-bold">دخان المكتب</label>
                        <input type="number" step="0.01" min="0" name="office_commission" class="form-control"
                               value="{{ old('office_commission', $policy->office_commission ?? 0) }}">
                    </div>

                    @php
                        $dateValue = old('date');
                        if (! $dateValue) {
                            try {
                                $rawDate = $policy->getRawOriginal('date')
                                    ?? $policy->money_transfer?->getRawOriginal('date')
                                    ?? $policy->date
                                    ?? null;
                                $dateValue = $rawDate ? \Carbon\Carbon::parse($rawDate)->format('Y-m-d') : '';
                            } catch (\Throwable $e) {
                                $dateValue = '';
                            }
                        }
                    @endphp
                    <div class="form-group col-md-4">
                        <label class="font-weight-bold">{{ __('main.date') }}</label>
                        <input type="date" name="date" class="form-control" value="{{ $dateValue }}">
                    </div>

                    @if($isReefer)
                        <div class="form-group col-md-4">
                            <label class="font-weight-bold">{{ __('admin.custody_usd') }}</label>
                            <input type="number" step="0.01" min="0" name="custody_usd" class="form-control"
                                   value="{{ old('custody_usd', $policy->custody_usd) }}">
                        </div>
                    @endif

                    <div class="form-group col-md-4">
                        <label class="font-weight-bold">{{ __('admin.departure') }}</label>
                        <select name="departure_id" class="form-control">
                            <option value="">—</option>
                            @foreach($cities as $id => $title)
                                <option value="{{ $id }}" @selected((int) old('departure_id', $container?->departure_id) === (int) $id)>
                                    {{ $title }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group col-md-4">
                        <label class="font-weight-bold">{{ __('admin.loading') }}</label>
                        <select name="loading_id" class="form-control">
                            <option value="">—</option>
                            @foreach($cities as $id => $title)
                                <option value="{{ $id }}" @selected((int) old('loading_id', $container?->loading_id) === (int) $id)>
                                    {{ $title }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group col-md-4">
                        <label class="font-weight-bold">{{ __('admin.aging') }}</label>
                        <select name="aging_id" class="form-control">
                            <option value="">—</option>
                            @foreach($cities as $id => $title)
                                <option value="{{ $id }}" @selected((int) old('aging_id', $container?->aging_id) === (int) $id)>
                                    {{ $title }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="d-flex" style="gap: 8px;">
                    <button type="submit" class="btn btn-primary font-weight-bolder">
                        <i class="fas fa-save"></i> {{ __('admin.save') }}
                    </button>
                    @if($container)
                        <a href="{{ route('bookings.booking_container_policies', $container->id) }}" class="btn btn-light">
                            {{ __('alerts.cancel') }}
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
