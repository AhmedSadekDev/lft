@extends('layouts.admin')
@section('content')
    <!--begin::Card-->
    <div class="card card-custom gutter-b">
        <div class="card-header">
            <div class="card-title">
                {{ __('admin.add_new_container') }}
            </div>
            <div class="mt-3">
                <a href="{{ route('bookings.show', $booking->id) }}" class="btn btn-secondary float-right">
                    {{ __('main.back') }}
                </a>
            </div>
        </div>

        @include('admin.components.booking-services.form')
    </div>
@endsection
