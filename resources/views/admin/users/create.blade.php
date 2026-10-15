@extends('layouts.admin')
@section('content')
    
    <div class="container-fluid lft-simple-page">
        @include('layouts.includes.breadcrumb', ['page' => __('main.manage_users')])
        <!--begin::Card-->
        <div class="card card-custom gutter-b lft-simple-card">
            <div class="card-header border-0">
                <div class="card-toolbar">
                    <!--begin::Button-->

                    <a href="{{ request()->has('role') ? route('users.index', ['role' => request()->role]) : route('users.index') }}"
                        class="btn btn-primary font-weight-bolder">
                        <i class="fas fa-arrow-right" aria-hidden="true"></i>{{ __('main.back') }}
                    </a>

                    <!--end::Button-->
                </div>
                <div class="card-toolbar">
                    <a href="{{ route('users.index', ['role' => request()->role]) }}" class="btn btn-secondary btn-sm">
                        {{ __('main.back') }}
                    </a>
                </div>
            </div>
            <div class="card-body">
                <form action="{{ route('users.store') }}" method="post">
                    @csrf
                    <div class="form-group">
                        <label for="nameInput">{{ __('admin.name') }}</label>
                        <input class="form-control" id="nameInput" type="text" name="name" value="{{ old('name') }}"
                            required>
                        @error('name')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="emailInput">{{ __('admin.email') }}</label>
                        <input class="form-control" id="emailInput" type="email" name="email"
                            value="{{ old('email') }}" required>
                        @error('email')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="phoneInput">{{ __('admin.phone') }}</label>
                        <input class="form-control" id="phoneInput" type="number" name="phone"
                            value="{{ old('phone') }}">
                        @error('phone')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="passwordInput">{{ __('admin.password') }}</label>
                        <input class="form-control" id="passwordInput" type="password" name="password"
                            value="{{ old('password') }}">
                        @error('password')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="roleSelect">{{ __('admin.role') }}</label>
                        <select class="form-control" id="roleSelect" name="role">
                            @foreach ($roles as $role)
                                <option value="{{ $role->id }}" @if ($role->id == old('role')) selected @endif>
                                    {{ $role->name }}</option>
                            @endforeach
                        </select>
                        @error('role')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="addressInput">{{ __('admin.address') }}</label>
                        <textarea class="form-control" id="addressInput" name="address">{{ old('address') }}</textarea>
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
