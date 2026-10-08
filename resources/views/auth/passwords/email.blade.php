<!DOCTYPE html>
<html lang="ar" dir="rtl" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>استعادة كلمة المرور · Leader for Trans</title>

    <link rel="shortcut icon" href="{{ asset('assets/media/logo.png') }}" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" href="{{ asset('assets/css/admin-ui.css') }}">

    <script>
        (function() {
            var theme = localStorage.getItem('lft_theme') || 'dark';
            document.documentElement.setAttribute('data-theme', theme);
        })();
    </script>
</head>
<body style="margin: 0; padding: 0;">
    <div class="lft-login-page">
        <div class="lft-login-overlay"></div>

        <div class="lft-login-topbar">
            <div class="lft-lang-pill">
                <i class="fas fa-globe"></i>
                <span>العربية</span>
            </div>
            <div class="lft-theme-toggle">
                <button type="button" class="lft-theme-toggle-btn" data-theme-val="light" title="الوضع الفاتح">
                    <i class="fas fa-sun"></i>
                </button>
                <button type="button" class="lft-theme-toggle-btn active" data-theme-val="dark" title="الوضع الداكن">
                    <i class="fas fa-moon"></i>
                </button>
            </div>
        </div>

        <div class="lft-login-card-container">
            <div class="lft-login-brand-header">
                <div class="lft-login-emblem">
                    <i class="fas fa-truck-moving"></i>
                </div>
                <div class="lft-login-brand-name">Leader for Trans</div>
                <div class="lft-login-brand-tagline">منظومة التجارة والأعمال الرقمية</div>
            </div>

            <div class="lft-glass-card">
                <h1 class="lft-glass-title">استعادة كلمة المرور</h1>
                <p class="lft-glass-subtitle">أدخل بريدك الإلكتروني لاستلام رابط إعادة تعيين كلمة المرور</p>

                @if (session('status'))
                    <div style="background: rgba(16, 185, 129, 0.2); border: 1px solid rgba(16, 185, 129, 0.4); color: #a7f3d0; padding: 10px 14px; border-radius: 10px; font-size: 13px; margin-bottom: 18px;">
                        <i class="fas fa-check-circle ml-2"></i>
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div style="background: rgba(239, 68, 68, 0.2); border: 1px solid rgba(239, 68, 68, 0.4); color: #fca5a5; padding: 10px 14px; border-radius: 10px; font-size: 13px; margin-bottom: 18px;">
                        <i class="fas fa-exclamation-circle ml-2"></i>
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('password.email') }}">
                    @csrf

                    <div class="form-group mb-4">
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
                                   placeholder="name@company.com">
                            <span class="lft-glass-input-icon">
                                <i class="far fa-envelope"></i>
                            </span>
                        </div>
                    </div>

                    <button type="submit" class="lft-glass-submit-btn">
                        <span>إرسال رابط الاستعادة</span>
                        <i class="fas fa-paper-plane"></i>
                    </button>

                    <div class="lft-glass-footer mt-4">
                        <a href="{{ route('login') }}" class="lft-glass-forgot-link">
                            <i class="fas fa-arrow-right ml-1"></i> العودة لتسجيل الدخول
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="{{ asset('assets/js/admin-ui.js') }}"></script>
</body>
</html>
