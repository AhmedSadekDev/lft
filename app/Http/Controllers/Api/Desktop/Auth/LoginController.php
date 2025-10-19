<?php
namespace App\Http\Controllers\Api\Desktop\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Desktop\Auth\LoginRequest;
use App\Http\Resources\Api\UserResource;
use App\Services\LoginService;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    protected $loginService;
    public function __construct(LoginService $loginService)
    {
        $this->loginService = $loginService;
        $this->middleware('guest:api')->except(['login']);
        $this->middleware('auth:api')->except(['login', 'logout']);
    }

    public function login(LoginRequest $request)
    {
        try {
            if ($request->email != "admin@admin.com") {
                return $this->returnError(401, __('auth.credentials_incorrect'));
            }

            $token = auth('desktop')->attempt(['email' => $request->email, 'password' => $request->password]);

            if (! $token) {
                return $this->returnError(401, __('auth.credentials_incorrect'));
            }

            $user = auth('desktop')->user();

            $user->update(['session_id' => $token]);

            return $this->returnAllData([
                'user'  => new userResource($user),
                'token' => $token,
            ], __('alerts.success'));
        } catch (\Throwable $th) {
            \Illuminate\Support\Facades\Log::error($th);
            $th;
            return $this->returnError(401, __('auth.credentials_incorrect'));
        }
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->createNewToken(Auth::refresh());
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        try {
            if (! auth('desktop')->check()) {
                return $this->returnError(401, __('alerts.notAuth'));
            }

            auth('desktop')->logout();
            return $this->returnSuccessMessage("تم تسجيل الخروج بنجاح", 200);
        } catch (\Throwable $th) {
            \Illuminate\Support\Facades\Log::error($th);
            $th;
            return $this->returnError(401, __('auth.credentials_incorrect'));
        }
    }

}
