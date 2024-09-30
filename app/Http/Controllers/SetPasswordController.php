<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\Company;
use App\Models\Superagent;
use Illuminate\Http\Request;

class SetPasswordController extends Controller
{
    public function company(Request $request)
    {
        $company = Company::where('session_id', $request->token)->firstOrFail();

        return view('admin.companies.reset',[
            'token' => $request->token,
        ]);
    }

    public function superagent(Request $request)
    {
        $superagent = Superagent::where('session_id', $request->token)->firstOrFail();

        return view('admin.superagents.reset',[
            'token' => $request->token,
        ]);
    }

    public function agent(Request $request)
    {
        $agent = Agent::where('session_id', $request->token)->firstOrFail();

        return view('admin.agents.reset',[
            'token' => $request->token,
        ]);
    }


    public function updateCompany(Request $request)
    {
        $request->validate([
            'token' => 'required|exists:companies,session_id',
            'password' => 'required|string|min:8|same:password_confirmation'
        ]);


        $company = Company::where('session_id', $request->token)->first();

        $company->update([
            'password' => $request->password
        ]);


        return to_route('main');
    }


    public function updateSuperAgent(Request $request)
    {
        $request->validate([
            'token' => 'required|exists:superagents,session_id',
            'password' => 'required|string|min:8|same:password_confirmation'
        ]);


        $superagetn = Superagent::where('session_id', $request->token)->first();

        $superagetn->update([
            'password' => $request->password
        ]);


        return to_route('main');
    }

    public function updateAgent(Request $request)
    {
        $request->validate([
            'token' => 'required|exists:agents,session_id',
            'password' => 'required|string|min:8|same:password_confirmation'
        ]);


        $superagetn = Agent::where('session_id', $request->token)->first();

        $superagetn->update([
            'password' => $request->password
        ]);


        return to_route('main');
    }
}
