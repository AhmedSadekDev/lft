<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <script>
        (function () {
            var saved = localStorage.getItem('lft_theme');
            var theme = saved || (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            document.documentElement.setAttribute('data-theme', theme);
        })();
    </script>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') · Leader for Trans</title>

    <link rel="shortcut icon" href="{{ asset('assets/media/logo.png') }}" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" href="{{ asset('assets/css/admin-ui.css') }}?v=5c">
</head>
<body style="margin: 0; padding: 0;">
    <main class="lft-login-page">
        <div class="lft-login-overlay" aria-hidden="true"></div>

        <div class="lft-login-topbar">
            <div class="lft-theme-toggle" role="group" aria-label="المظهر">
                <button type="button" class="lft-theme-toggle-btn" data-theme-val="light" title="الوضع الفاتح" aria-label="الوضع الفاتح">
                    <i class="fas fa-sun" aria-hidden="true"></i>
                </button>
                <button type="button" class="lft-theme-toggle-btn" data-theme-val="dark" title="الوضع الداكن" aria-label="الوضع الداكن">
                    <i class="fas fa-moon" aria-hidden="true"></i>
                </button>
            </div>
        </div>

        <div class="lft-login-card-container">
            <div class="lft-login-brand-header">
                <div class="lft-login-logo-tile">
                    <img src="{{ asset('assets/media/logo.png') }}" alt="Leader for Trans">
                </div>
                <div class="lft-login-brand-name">Leader for Trans</div>
                <div class="lft-login-brand-tagline">منظومة التجارة والأعمال الرقمية</div>
            </div>

            <div class="lft-glass-card">
                @yield('content')
            </div>

            <div class="lft-security-badge">
                <i class="fas fa-shield-alt" aria-hidden="true"></i>
                <span>
                    @hasSection('badge')
                        @yield('badge')
                    @else
                        بوابة دخول المسؤولين فقط
                    @endif
                </span>
            </div>
        </div>
    </main>

    <script src="{{ asset('assets/js/admin-ui.js') }}?v=5b"></script>
</body>
</html>
