<?php

namespace App\Http\Controllers\Api\Superagent;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Agent;
use App\Models\superagentTransaction;
use Illuminate\Support\Facades\Validator;
use App\Http\Traits\ImagesTrait;


class WalletController extends Controller
{
    use ImagesTrait;
    
    public function index(Request $request)
    {
        $user = auth()->guard("superagent")->user();
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
    
    
    public function chargeAgentWallet(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|gt:0',
            'agent_id' => 'required|exists:agents,id',
            'image' => 'required',
            'name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'data' => '',
                'message' => $validator->errors()->first(),
            ], 422);
        }
        
        $agent = Agent::find($request->agent_id);
        $user = auth()->guard("superagent")->user();
        
        if ($user->wallet < $request->amount) {
         return response()->json([
                'status' => 'error',
                'data' => '',
                'message' => __('main.you dont have enougth money'),
            ], 422);   
        }
        
        $agent->wallet = $agent->wallet + $request->amount;
        $agent->save();
        
        $user->wallet = $user->wallet - $request->amount;
        $user->save();
        
        
        if($request->hasFile('image')) {
            $imageName = time() . '_transaction.' . $request->image->extension();
            $this->uploadImage($request->image, $imageName, 'superagents');
            $path = 'Admin/images/superagents/' .  $imageName;
            
            superagentTransaction::create([
                'amount'=> $request->amount,
                'image' => $path,
                'superagent_id' => $user->id,
                'name' => $request->name
            ]);
        }
        
        
        
        return response()->json([
                'status' => 'success',
                'data' => '',
                'message' => __("alerts.added_successfully"),
            ], 201);
        
    }
}
