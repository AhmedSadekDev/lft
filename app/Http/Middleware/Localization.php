<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

class Localization
{
    /**
     * Resolve locale from Accept-Language (API) or session (dashboard), then set app locale.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        App::setLocale($this->resolveLocale($request));

        return $next($request);
    }

    private function resolveLocale(Request $request): string
    {
        $supported = $this->supportedLocales();
        $default = config('app.locale', 'ar');

        // API / mobile: Accept-Language wins when present.
        if ($request->hasHeader('Accept-Language')) {
            $fromHeader = $this->localeFromAcceptLanguage((string) $request->header('Accept-Language'), $supported);
            if ($fromHeader !== null) {
                return $fromHeader;
            }
        }

        // Dashboard: session locale from /language/{locale}.
        $fromSession = Session::get('locale');
        if (is_string($fromSession) && in_array($fromSession, $supported, true)) {
            return $fromSession;
        }

        return in_array($default, $supported, true) ? $default : 'ar';
    }

    /**
     * @return list<string>
     */
    private function supportedLocales(): array
    {
        $configured = config('app.available_locales', ['en', 'ar']);
        $locales = array_values(is_array($configured) ? $configured : ['en', 'ar']);

        return array_values(array_unique(array_filter($locales, 'is_string')));
    }

    /**
     * Parse Accept-Language like "ar", "en-US", "ar,en;q=0.9".
     *
     * @param  list<string>  $supported
     */
    private function localeFromAcceptLanguage(string $header, array $supported): ?string
    {
        $header = trim($header);
        if ($header === '') {
            return null;
        }

        foreach (explode(',', $header) as $part) {
            $tag = strtolower(trim(explode(';', $part, 2)[0]));
            if ($tag === '') {
                continue;
            }

            $primary = explode('-', $tag, 2)[0];
            if (in_array($primary, $supported, true)) {
                return $primary;
            }
            if (in_array($tag, $supported, true)) {
                return $tag;
            }
        }

        return null;
    }
}
