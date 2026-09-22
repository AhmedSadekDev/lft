@extends('layouts.admin')
@section('content')
<div class="container">
    @include('layouts.includes.breadcrumb', ['page' => __('profile.title')])
    @if(session('success'))
        <div class="alert alert-success" role="status">{{ session('success') }}</div>
    @endif
    <div class="row">
        <div class="col-lg-7 mb-5">
            <div class="card card-custom">
                <div class="card-header"><h3 class="card-title">{{ __('profile.details') }}</h3></div>
                <form action="{{ route('profile.update') }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        <div class="form-group">
                            <label for="profile_name">{{ __('admin.name') }}</label>
                            <input id="profile_name" class="form-control" type="text" name="name" value="{{ old('name', $user->name) }}" required maxlength="255">
                            @error('name')<div class="text-danger">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group">
                            <label for="profile_email">{{ __('admin.email') }}</label>
                            <input id="profile_email" class="form-control" type="email" name="email" value="{{ old('email', $user->email) }}" required maxlength="255">
                            @error('email')<div class="text-danger">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group">
                            <label for="profile_phone">{{ __('admin.phone') }}</label>
                            <input id="profile_phone" class="form-control" type="tel" name="phone" value="{{ old('phone', $user->phone) }}"  maxlength="255">
                            @error('phone')<div class="text-danger">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group">
                            <label for="profile_address">{{ __('admin.address') }}</label>
                            <input id="profile_address" class="form-control" type="text" name="address" value="{{ old('address', $user->address) }}"  maxlength="2000">
                            @error('address')<div class="text-danger">{{ $message }}</div>@enderror
                        </div>
                        <button class="btn btn-primary" type="submit">{{ __('admin.save') }}</button>
                    </div>
                </form>
            </div>
        </div>
        <div class="col-lg-5 mb-5">
            <div class="card card-custom">
                <div class="card-header"><h3 class="card-title">{{ __('profile.change_password') }}</h3></div>
                <form action="{{ route('profile.password') }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        <p class="text-muted">{{ __('profile.password_hint') }}</p>
                        <div class="form-group">
                            <label for="current_password">{{ __('profile.current_password') }}</label>
                            <input id="current_password" class="form-control" type="password" name="current_password" autocomplete="current-password" required >
                            @error('current_password')<div class="text-danger">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group">
                            <label for="password">{{ __('profile.new_password') }}</label>
                            <input id="password" class="form-control" type="password" name="password" autocomplete="new-password" required minlength="8">
                            @error('password')<div class="text-danger">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group">
                            <label for="password_confirmation">{{ __('profile.confirm_password') }}</label>
                            <input id="password_confirmation" class="form-control" type="password" name="password_confirmation" autocomplete="new-password" required minlength="8">
                            @error('password_confirmation')<div class="text-danger">{{ $message }}</div>@enderror
                        </div>
                        <button class="btn btn-primary" type="submit">{{ __('profile.change_password') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
