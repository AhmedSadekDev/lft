@extends('layouts.admin')
@section('content')
    <div class="container">
        @include('layouts.includes.breadcrumb', ['page' => __('main.manage_users')])
        <!--begin::Card-->
        <div class="card card-custom">
            <div class="card-header flex-wrap py-5">
                <div class="card-toolbar">
                    <!--begin::Button-->

                    <a href="{{ request()->has('role') ? route('users.index', ['role' => request()->role]) : route('users.index') }}"
                        class="btn btn-primary font-weight-bolder">
                        <i class="fas fa-arrow-right" aria-hidden="true"></i>{{ __('main.back') }}
                    </a>

                    <!--end::Button-->
                </div>
                <div class="mt-3">
                    <a href="{{ route('users.index', ['role' => request()->role]) }}" class="btn btn-secondary float-right">
                        {{ __('main.back') }}
                    </a>
                </div>
            </div>
            <div class="card-body">
                <form action="{{ route('users.update', $user->id) }}" method="post">
                    @csrf
                    @method('put')
                    <div class="form-group">
                        <label for="nameInput">{{ __('admin.name') }}</label>
                        <input class="form-control" id="nameInput" type="text" name="name"
                            value="{{ old('name') ?? $user->name }}" required>
                        @error('name')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="emailInput">{{ __('admin.email') }}</label>
                        <input class="form-control" id="emailInput" type="email" name="email"
                            value="{{ old('email') ?? $user->email }}" required>
                        @error('email')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="phoneInput">{{ __('admin.phone') }}</label>
                        <input class="form-control" id="phoneInput" type="number" name="phone"
                            value="{{ old('phone') ?? $user->phone }}">
                        @error('phone')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="passwordInput">{{ __('admin.password') }}</label>
                        <input class="form-control" id="passwordInput" type="password" name="password"
                            autocomplete="new-password" minlength="8">
                        <small class="text-muted">{{ __('profile.leave_blank') }}</small>
                        @error('password')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="roleSelect">{{ __('admin.role') }}</label>
                        <select class="form-control" id="roleSelect" name="role">
                            @foreach ($roles as $role)
                                <option value="{{ $role->id }}" @if ($role->id == old('role') || in_array($role->id, $user->roles->pluck('id')->toArray())) selected @endif>
                                    {{ $role->name }}</option>
                            @endforeach
                        </select>
                        @error('role')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="addressInput">{{ __('admin.address') }}</label>
                        <textarea class="form-control" id="addressInput" name="address">{{ old('address') ?? $user->address }}</textarea>
                        @error('address')
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
