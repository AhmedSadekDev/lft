<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Superagent\StoreRequest;
use App\Http\Requests\Admin\Superagent\UpdateRequest;
use App\Models\Superagent;
use App\Notifications\AssignPasswordNotification;
use App\Notifications\AssignSuperagentPasswordNotification;
use App\Services\PasswordResetSuperagentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class SuperagentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    protected $PasswordResetSuperagentService;

    public function __construct(PasswordResetSuperagentService $PasswordResetSuperagentService)
    {
        $this->PasswordResetSuperagentService = $PasswordResetSuperagentService;

        $this->middleware('permission:superagents.index')->only('index');
        $this->middleware('permission:superagents.create')->only(['create', 'store']);
        $this->middleware('permission:superagents.udpate')->only(['edit', 'udpate']);
        $this->middleware('permission:superagents.delete')->only('destroy');
    }
    public function index()
    {
        $input = [
            'superagents' => superagent::all(),
        ];
        return view('admin.superagents.index', $input);
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $input = [
            'method'    => 'POST',
            'action'    => route('superagents.store')
        ];

        return view('admin.superagents.create', $input);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreRequest $request)
    {
        $superagent = Superagent::create($request->validated());
        $token = \JWTAuth::fromUser($superagent);
        $superagent->update(array_merge(['session_id' => $token]));
        Notification::send($superagent, new AssignSuperagentPasswordNotification($superagent));
        $otp = $this->PasswordResetSuperagentService->sendOtp($request->email);

        return redirect()->route('superagents.index')
            ->with('success', __('alerts.added_successfully'));
    }


    public function edit(Superagent $superagent)
    {
        $input = [
            'method'    => 'PUT',
            'action'    => route('superagents.update', $superagent->id),
            'superagent'   => $superagent,
        ];

        return view('admin.superagents.edit', $input);
    }


    public function update(UpdateRequest $request, Superagent $superagent)
    {
        $superagent->update($request->validated());
        return redirect()->route('superagents.index')
            ->with('success', __('alerts.updated_successfully'));
    }


    public function destroy(Superagent $superagent)
    {
        $superagent->delete();
        return response()->json(['staus' => true, 'msg' => __('alerts.deleted_successfully')], 200);
    }

}
