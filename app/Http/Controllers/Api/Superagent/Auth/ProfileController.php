<?php

namespace App\Http\Controllers\Api\Superagent\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Superagent\Auth\LoginRequest;
use App\Http\Requests\Api\Superagent\ProfileRequest;
use App\Http\Resources\Api\Superagent\SuperagentResource;
use App\Models\Superagent;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function fetch_profile()
    {
        try {

            $superagent = auth()->guard('superagent')->user();

            return $this->returnAllData(new SuperagentResource($superagent), __('alerts.success'));

        } catch (\Throwable $th) {
            \Illuminate\Support\Facades\Log::error($th);
            $th;
            return $this->returnError(401, __('auth.credentials_incorrect'));
        }
    }

    public function token_ininvalid()
    {
        try {

            return $this->returnError("token is invalid",401);

        } catch (\Throwable $th) {
            \Illuminate\Support\Facades\Log::error($th);
            $th;
            return $this->returnError(401, __('auth.credentials_incorrect'));
        }
    }
    public function update_profile(ProfileRequest $request)
    {
        try {

            $superagent = auth()->guard('superagent')->user();

            $superagent->update($request->validated());

            return $this->returnAllData(new SuperagentResource($superagent), __('alerts.success'));

        } catch (\Throwable $th) {
            return $this->returnError(401, __('auth.credentials_incorrect'));
        }
    }
}
