@extends('layouts.admin')
@section('content')
    
    <div class="container-fluid lft-simple-page">
<div class="card card-custom gutter-b lft-simple-card">
        <div class="card-header">
            <div class="card-title">
                {{ __('admin.edit_service') }}
            </div>
            <div class="card-toolbar">
                <a href="{{ route('bookings.show', $booking->id) }}" class="btn btn-secondary btn-sm">
                    {{ __('main.back') }}
                </a>
            </div>
        </div>

        @include('admin.components.booking-agent-expenses.form')
    </div>
    </div>
@endsection
