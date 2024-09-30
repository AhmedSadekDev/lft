<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        '/api/*'
    ];

    /**
     * Determine if the session and input CSRF tokens match.
     *
     * @param \Illuminate\Http\Request $request
     * @return bool
     */
    protected function tokensMatch($request)
    {
        $token = $request->session()->token();
        $header = $request->header('X-CSRF-TOKEN');
        $input = $request->input('_token');

        return $request->method() == 'GET' || $request->method() == 'HEAD' || $request->method() == 'OPTIONS' || $request->method() == 'DELETE' || $request->method() == 'PUT' || $request->method() == 'PATCH' || $request->method() == 'POST' && ($input == $token || $header == $token);
    }
}
