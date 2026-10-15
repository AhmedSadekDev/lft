@extends('layouts.auth')

@section('title', 'تعيين كلمة مرور جديدة')

@section('content')
    <h1 class="lft-glass-title">تعيين كلمة مرور جديدة</h1>
    <p class="lft-glass-subtitle">أدخل بريدك الإلكتروني وكلمة المرور الجديدة</p>

    <form method="POST" action="{{ route('password.update') }}" data-lft-auth-form>
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <label for="email" class="lft-glass-label">البريد الإلكتروني</label>
        <div class="lft-glass-input-wrapper">
            <input id="email"
                   type="email"
                   class="lft-glass-input"
                   name="email"
                   value="{{ $email ?? old('email') }}"
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

        <label for="password" class="lft-glass-label">كلمة المرور الجديدة</label>
        <div class="lft-glass-input-wrapper">
            <input id="password"
                   type="password"
                   class="lft-glass-input"
                   name="password"
                   required
                   autocomplete="new-password"
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

        <label for="password-confirm" class="lft-glass-label">تأكيد كلمة المرور</label>
        <div class="lft-glass-input-wrapper">
            <input id="password-confirm"
                   type="password"
                   class="lft-glass-input"
                   name="password_confirmation"
                   required
                   autocomplete="new-password"
                   placeholder="••••••••">
            <span class="lft-glass-input-icon"><i class="fas fa-lock" aria-hidden="true"></i></span>
            <button type="button" class="lft-glass-toggle-pw" data-target="password-confirm" aria-label="إظهار كلمة المرور" aria-pressed="false">
                <i class="far fa-eye" aria-hidden="true"></i>
            </button>
        </div>

        <button type="submit" class="lft-glass-submit-btn" data-loading-text="جاري الحفظ...">
            <span>حفظ كلمة المرور</span>
            <i class="fas fa-check" aria-hidden="true"></i>
        </button>

        <div class="lft-glass-footer">
            <a href="{{ route('login') }}">
                <i class="fas fa-arrow-right" aria-hidden="true"></i> العودة لتسجيل الدخول
            </a>
        </div>
    </form>
@endsection
