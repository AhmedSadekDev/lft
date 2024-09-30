<?php

namespace App\Http\Controllers\Api\Superagent\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Superagent\Auth\ChangePasswordRequest;
use App\Http\Resources\Api\Superagent\SuperagentResource;
use App\Models\Superagent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ChangePasswordController extends Controller
{
    public function change_password(ChangePasswordRequest $request)
    {
        try {

            $superagent = auth()->guard("superagent")->user();

            //check if old password and new password if the same

            if (!Hash::check($request->old_password, $superagent->password)) {
                return $this->returnError(400, __('auth.old_password_incorrect'));
            }
            $superagent->update([

                'password' => $request->new_password,
            ]);

            //response

            return $this->returnAllData(new SuperagentResource($superagent), __('alerts.success'));
        } catch (\Throwable $th) {
            \Illuminate\Support\Facades\Log::error($th);
            $th;
            return $this->returnError(401, __('auth.credentials_incorrect'));
        }
    }
}
