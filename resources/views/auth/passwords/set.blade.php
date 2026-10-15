@extends('layouts.auth')

@section('title', 'تعيين كلمة المرور')

@section('badge', 'هذا الرابط خاص بحسابك، لا تشاركه مع أحد')

@section('content')
    <h1 class="lft-glass-title">تعيين كلمة المرور</h1>
    <p class="lft-glass-subtitle">اختر كلمة مرور جديدة {{ $audience }}</p>

    @error('token')
        <div class="lft-auth-alert lft-auth-alert--error" role="alert">
            <i class="fas fa-exclamation-circle" aria-hidden="true"></i>
            <span>رابط تعيين كلمة المرور غير صالح. يرجى طلب رابط جديد.</span>
        </div>
    @enderror

    <form method="POST" action="{{ $action }}" autocomplete="off" data-lft-auth-form>
        @csrf

        <input type="hidden" name="token" value="{{ $token }}">

        <label for="password" class="lft-glass-label">
            كلمة المرور الجديدة
            <span class="lft-glass-label-hint">(8 أحرف على الأقل)</span>
        </label>
        <div class="lft-glass-input-wrapper">
            <input id="password"
                   type="password"
                   class="lft-glass-input"
                   name="password"
                   required
                   minlength="8"
                   autocomplete="new-password"
                   autofocus
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
                   minlength="8"
                   autocomplete="new-password"
                   placeholder="••••••••">
            <span class="lft-glass-input-icon"><i class="fas fa-lock" aria-hidden="true"></i></span>
            <button type="button" class="lft-glass-toggle-pw" data-target="password-confirm" aria-label="إظهار تأكيد كلمة المرور" aria-pressed="false">
                <i class="far fa-eye" aria-hidden="true"></i>
            </button>
        </div>

        <button type="submit" class="lft-glass-submit-btn" data-loading-text="جاري الحفظ...">
            <span>حفظ كلمة المرور</span>
            <i class="fas fa-check" aria-hidden="true"></i>
        </button>
    </form>
@endsection
