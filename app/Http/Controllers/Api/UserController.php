<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\UserRequest;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function update(UserRequest $request){

        DB::beginTransaction();
        try {
            //code...
            $this->userService->update($request->validated());
            DB::commit();
            return $this->returnResponseSuccessMessage(__('alerts.updated_successfully'));
        } catch (\Throwable $th) {
            \Illuminate\Support\Facades\Log::error($th); $th;
            DB::rollBack();
            if(!$th->getMessage()){
                return $this->returnError('403', $th->getResponse()?->getData());
            }elseif($th->getMessage()){
                return $this->returnError('403', $th->getMessage());
            }
        }

    }
}
