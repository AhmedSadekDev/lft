<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;

class VerifyCustomerSession
{
    public function handle($request, Closure $next)
    {
        $guard = auth()->guard(); // Authenticate has selected api or employees.
        $user = $guard->user();
        $claim = $guard->payload()->get('pwdv');
        if (!$user || !is_string($claim) || !hash_equals($user->sessionFingerprint(), $claim)) {
            throw new AuthenticationException('Unauthenticated.');
        }

        return $next($request);
    }
}
