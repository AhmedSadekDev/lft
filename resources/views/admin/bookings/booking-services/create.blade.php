@extends('layouts.admin')
@section('content')
    
    <div class="container-fluid lft-simple-page">
<!--begin::Card-->
    <div class="card card-custom gutter-b lft-simple-card lft-simple-card--wide">
        <div class="card-header">
            <div class="card-title">
                {{ __('admin.add_new_container') }}
            </div>
            <div class="card-toolbar">
                <a href="{{ route('bookings.show', $booking->id) }}" class="btn btn-secondary btn-sm">
                    {{ __('main.back') }}
                </a>
            </div>
        </div>

        @include('admin.components.booking-services.form')
    </div>
    </div>
@endsection
