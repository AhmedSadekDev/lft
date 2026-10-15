@extends('layouts.auth')

@section('title', 'تسجيل الدخول')

@section('content')
    <h1 class="lft-glass-title">تسجيل الدخول</h1>
    <p class="lft-glass-subtitle">مرحباً بك في لوحة التحكم</p>

    @if (session('status'))
        <div class="lft-auth-alert lft-auth-alert--success" role="status">
            <i class="fas fa-check-circle" aria-hidden="true"></i>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" autocomplete="on" data-lft-auth-form>
        @csrf

        <label for="email" class="lft-glass-label">البريد الإلكتروني</label>
        <div class="lft-glass-input-wrapper">
            <input id="email"
                   type="email"
                   class="lft-glass-input"
                   name="email"
                   value="{{ old('email') }}"
                   required
                   autocomplete="email"
                   autofocus
                   placeholder="name@company.com"
                   @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
            <span class="lft-glass-input-icon"><i class="far fa-envelope" aria-hidden="true"></i></span>
        </div>
        @error('email')
            <span class="lft-glass-field-error" id="email-error" role="alert">{{ $message }}</span>
        @enderror

        <label for="password" class="lft-glass-label">كلمة المرور</label>
        <div class="lft-glass-input-wrapper">
            <input id="password"
                   type="password"
                   class="lft-glass-input"
                   name="password"
                   required
                   autocomplete="current-password"
                   placeholder="••••••••"
                   @error('password') aria-invalid="true" aria-describedby="password-error" @enderror>
            <span class="lft-glass-input-icon"><i class="fas fa-lock" aria-hidden="true"></i></span>
            <button type="button" class="lft-glass-toggle-pw" data-target="password" aria-label="إظهار كلمة المرور" aria-pressed="false">
                <i class="far fa-eye" aria-hidden="true"></i>
            </button>
        </div>
        @error('password')
            <span class="lft-glass-field-error" id="password-error" role="alert">{{ $message }}</span>
        @enderror

        <div class="lft-glass-remember-row">
            <label class="lft-glass-checkbox-label" for="remember">
                <input type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                <span>تذكرني</span>
            </label>

            @if (Route::has('password.request'))
                <a class="lft-glass-forgot-link" href="{{ route('password.request') }}">نسيت كلمة المرور؟</a>
            @endif
        </div>

        <button type="submit" class="lft-glass-submit-btn" data-loading-text="جاري تسجيل الدخول...">
            <span>تسجيل الدخول</span>
            <i class="fas fa-arrow-left" aria-hidden="true"></i>
        </button>

        <div class="lft-glass-footer">
            ليس لديك حساب؟ تواصل مع مسؤول النظام
        </div>
    </form>
@endsection
