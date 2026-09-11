@extends('layouts.admin')
@section('content')
    <div class="card card-custom gutter-b">
        <div class="card-header">
            <div class="card-title">
                {{ __('admin.edit_service') }}
            </div>
            <div class="mt-3">
                <a href="{{ route('bookings.show', $booking->id) }}" class="btn btn-secondary float-right">
                    {{ __('main.back') }}
                </a>
            </div>
        </div>

        @include('admin.components.booking-agent-expenses.form')
    </div>
@endsection
