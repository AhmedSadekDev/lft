<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SponserRequest;
use App\Models\Sponser;
use Illuminate\Http\Request;

class SponserController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:sponsers.index')->only('index');
        $this->middleware('permission:sponsers.create')->only(['create', 'store']);
        $this->middleware('permission:sponsers.udpate')->only(['edit', 'udpate']);
        $this->middleware('permission:sponsers.delete')->only('destroy');
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $input = [
            'sponsers' => Sponser::all(),
        ];

        return view('admin.sponsers.index', $input);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $input = [
            'method' => 'POST',
            'action' => route('sponsers.store'),
        ];

        return view('admin.sponsers.create', $input);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(SponserRequest $request)
    {
        Sponser::create($request->validated());
        return redirect()->route('sponsers.index');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Sponser  $sponser
     * @return \Illuminate\Http\Response
     */
    public function show(Sponser $sponser)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Sponser  $sponser
     * @return \Illuminate\Http\Response
     */
    public function edit(Sponser $sponser)
    {
        $input = [
            'sponser' => $sponser,
            'method' => 'PUT',
            'action' => route('sponsers.update', $sponser),
        ];

        return view('admin.sponsers.edit', $input);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Sponser  $sponser
     * @return \Illuminate\Http\Response
     */
    public function update(SponserRequest $request, Sponser $sponser)
    {
        $sponser->update($request->all());
        return redirect()->route('sponsers.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Sponser  $sponser
     * @return \Illuminate\Http\Response
     */
    public function destroy(Sponser $sponser)
    {
        $sponser->delete();
        return response()->json(['staus' => true, 'msg' => __('alerts.deleted_successfully')], 200);
    }
}
