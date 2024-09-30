<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\LoginRequest;
use App\Http\Resources\Api\CompanyResource;
use App\Http\Resources\Api\UserResource;
use Illuminate\Support\Facades\Auth;
use App\Services\LoginService;


class LoginController extends Controller
{
    protected $loginService;
    public function __construct(LoginService $loginService){
        $this->loginService = $loginService;
        $this->middleware('guest:api')->except(['login', 'logout']);
        $this->middleware('auth:api')->except(['login']);
    }

    public function login(LoginRequest $request){
        try {
            $company = $this->loginService->login($request->only('email', 'password'));
            return $this->returnAllData([ 'company' => new CompanyResource($company), 'token' => $company->session_id], __('alerts.success'));
            //code...
        } catch (\Throwable $th) {
            \Illuminate\Support\Facades\Log::error($th); $th;
            return $this->returnError(401, __('auth.credentials_incorrect'));
        }
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh() {
        return $this->createNewToken(Auth::refresh());
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
    */
    public function logout() {
        auth()->logout();
        return response()->json(['message' => 'User successfully signed out']);
    }


}
