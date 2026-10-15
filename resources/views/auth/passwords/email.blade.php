@extends('layouts.auth')

@section('title', 'استعادة كلمة المرور')

@section('content')
    <h1 class="lft-glass-title">استعادة كلمة المرور</h1>
    <p class="lft-glass-subtitle">أدخل بريدك الإلكتروني لاستلام رابط إعادة تعيين كلمة المرور</p>

    @if (session('status'))
        <div class="lft-auth-alert lft-auth-alert--success" role="status">
            <i class="fas fa-check-circle" aria-hidden="true"></i>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" data-lft-auth-form>
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

        <button type="submit" class="lft-glass-submit-btn" data-loading-text="جاري الإرسال...">
            <span>إرسال رابط الاستعادة</span>
            <i class="fas fa-paper-plane" aria-hidden="true"></i>
        </button>

        <div class="lft-glass-footer">
            <a href="{{ route('login') }}">
                <i class="fas fa-arrow-right" aria-hidden="true"></i> العودة لتسجيل الدخول
            </a>
        </div>
    </form>
@endsection
