<?php

namespace App\Http\Controllers\Api\Superagent\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Superagent\Auth\SetPasswordRequest;
use App\Http\Resources\Api\Superagent\SuperagentResource;
use App\Models\Superagent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class SetPasswordController extends Controller
{
    public function set_password(SetPasswordRequest $request)
    {
        try {

            $token = $request->token;
            $superagent = Superagent::where('session_id', $token)->first();


            if (!$superagent) {

                return $this->returnError(404, __('alerts.no_data_found'));
            }

            $superagent->update(["password" => $request->password]);
            //response

            return $this->returnAllData(new SuperagentResource($superagent), __('alerts.success'));
        } catch (\Exception $ex) {


            return $this->returnError(500, $ex->getMessage());
        }
    }
}
