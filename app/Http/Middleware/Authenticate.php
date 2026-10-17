<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected function authenticate($request, array $guards)
    {
        parent::authenticate($request, $guards);
        if (in_array(auth()->getDefaultDriver(), ['api', 'employees'], true)) {
            (new VerifyCustomerSession)->handle($request, function () {});
        }
    }

    protected function redirectTo($request)
    {
        // if (!$request->expectsJson()) {
        //     return route("token_ininvalid");
        // }
        if (!$request->expectsJson()) {
            return route('login');
        }
    }
}
