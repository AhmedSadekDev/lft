<?php

namespace Tests\Unit;

use App\Http\Middleware\Localization;
use Illuminate\Http\Request;
use Tests\TestCase;

class LocalizationMiddlewareTest extends TestCase
{
    public function test_accept_language_ar_sets_arabic_locale(): void
    {
        $request = Request::create('/api/settings', 'GET');
        $request->headers->set('Accept-Language', 'ar');

        (new Localization())->handle($request, function () {
            $this->assertSame('ar', app()->getLocale());

            return response('ok');
        });
    }

    public function test_accept_language_en_sets_english_locale(): void
    {
        $request = Request::create('/api/settings', 'GET');
        $request->headers->set('Accept-Language', 'en-US,en;q=0.9');

        (new Localization())->handle($request, function () {
            $this->assertSame('en', app()->getLocale());

            return response('ok');
        });
    }

    public function test_unsupported_accept_language_falls_back_to_default(): void
    {
        config(['app.locale' => 'ar']);
        $request = Request::create('/api/settings', 'GET');
        $request->headers->set('Accept-Language', 'fr-FR');

        (new Localization())->handle($request, function () {
            $this->assertSame('ar', app()->getLocale());

            return response('ok');
        });
    }
}
