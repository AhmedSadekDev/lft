<?php

namespace Tests\Unit;

use App\Http\Middleware\Localization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class LocalizationMiddlewareTest extends TestCase
{
    public function test_api_accept_language_ar_sets_arabic_locale(): void
    {
        $request = Request::create('/api/settings', 'GET');
        $request->headers->set('Accept-Language', 'ar');

        (new Localization())->handle($request, function () {
            $this->assertSame('ar', app()->getLocale());

            return response('ok');
        });
    }

    public function test_api_accept_language_en_sets_english_locale(): void
    {
        $request = Request::create('/api/settings', 'GET');
        $request->headers->set('Accept-Language', 'en-US,en;q=0.9');

        (new Localization())->handle($request, function () {
            $this->assertSame('en', app()->getLocale());

            return response('ok');
        });
    }

    public function test_dashboard_ignores_browser_accept_language_and_keeps_arabic(): void
    {
        config(['app.locale' => 'ar']);
        $request = Request::create('/dashboard', 'GET');
        $request->headers->set('Accept-Language', 'en-US,en;q=0.9');

        (new Localization())->handle($request, function () {
            $this->assertSame('ar', app()->getLocale());

            return response('ok');
        });
    }

    public function test_dashboard_uses_session_locale_when_set(): void
    {
        Session::put('locale', 'en');
        $request = Request::create('/dashboard', 'GET');
        $request->headers->set('Accept-Language', 'ar');

        (new Localization())->handle($request, function () {
            $this->assertSame('en', app()->getLocale());

            return response('ok');
        });
    }
}
