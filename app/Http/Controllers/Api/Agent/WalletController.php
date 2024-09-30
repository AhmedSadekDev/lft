<?php

namespace App\Http\Controllers\Api\Agent;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Car;
use App\Models\Shipment;
use App\Models\AgentCarTranfer;
use App\Http\Traits\ImagesTrait;


class WalletController extends Controller
{
    use ImagesTrait;

    public function index(Request $request)
    {
        $user = auth()->guard("agent")->user();
        $wallet = 0;
        if ($user) {
            $wallet = $user->wallet;
        }
        
        return response()->json([
            'status' => 'success',
            'data' => [
                'wallet' => (string) $wallet
            ],
            'message' => '',
        ], 200);
    }
    
    public function chargeCarWallet(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|gt:0',
            'car_id' => 'required|exists:cars,id',
            'name' => 'required|string|max:255',
            'image' => 'required',
            'addition' => 'nullable',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'data' => '',
                'message' => $validator->errors()->first(),
            ], 422);
        }
        
        $car = Car::find($request->car_id);
        $user = auth()->guard("agent")->user();
        
        if ($user->wallet < $request->amount) {
         return response()->json([
                'status' => 'error',
                'data' => '',
                'message' => __('main.you dont have enougth money'),
            ], 422);   
        }
        
        $user->wallet = $user->wallet - $request->amount;
        $user->save();
        
        $data = [
            'value' => $request->amount,
            'name' => $request->name,
            'car_id' => $car->id,
            'agent_id' => $user->id,
            'user_id' => $user->id,
            'date' => now(),
            'addition' => $request->addition
        ];
        
        if($request->hasFile('image')) {
            $imageName = time() . '_transaction.' . $request->image->extension();
            $this->uploadImage($request->image, $imageName, 'banks');
            $data['image'] = 'Admin/images/banks/' .  $imageName;
        }
        
        Shipment::create($data);
        
        return response()->json([
                'status' => 'success',
                'data' => '',
                'message' => __("alerts.added_successfully"),
            ], 201);
        
    }
}
