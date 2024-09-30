<?php

namespace App\Http\Controllers\Api\Agent\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Agent\Auth\ImageRequest;
use App\Http\Requests\Api\Agent\Auth\LoginRequest;
use App\Http\Requests\Api\Agent\ProfileRequest;
use App\Http\Resources\Api\Agent\AgentResource;
use App\Models\Agent;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function fetch_profile()
    {
        try {

            $agent = auth()->guard('agent')->user();

            return $this->returnAllData(new AgentResource($agent), __('alerts.success'));

        } catch (\Throwable $th) {
            \Illuminate\Support\Facades\Log::error($th);
            $th;
            return $this->returnError(401, __('auth.credentials_incorrect'));
        }
    }

    public function change_image(ImageRequest $request)
    {
        try {

            $agent = auth()->guard('agent')->user();

            $agent->update($request->validated());
            return $this->returnAllData(new AgentResource($agent), __('alerts.success'));

        } catch (\Throwable $th) {
            \Illuminate\Support\Facades\Log::error($th);
            $th;
            return $this->returnError(401, __('auth.credentials_incorrect'));
        }
    }
    public function update_profile(ProfileRequest $request)
    {
        try {

            $superagent = auth()->guard('agent')->user();

            $superagent->update($request->validated());

            return $this->returnAllData(new AgentResource($superagent), __('alerts.success'));

        } catch (\Throwable $th) {
            return $this->returnError(401, __('auth.credentials_incorrect'));
        }
    }
}
