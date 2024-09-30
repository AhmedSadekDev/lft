<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\YardRequest;
use App\Models\Yard;
use Illuminate\Http\Request;

class YardController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:yards.index')->only('index');
        $this->middleware('permission:yards.create')->only(['create', 'store']);
        $this->middleware('permission:yards.udpate')->only(['edit', 'udpate']);
        $this->middleware('permission:yards.delete')->only('destroy');
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $input = [
            'yards' => Yard::all(),
        ];

        return view('admin.yards.index', $input);
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
            'action' => route('yards.store')
        ];

        return view('admin.yards.create', $input);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(YardRequest $request)
    {
        Yard::create($request->validated());
        return redirect()->route('yards.index');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Yard  $yard
     * @return \Illuminate\Http\Response
     */
    public function show(Yard $yard)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Yard  $yard
     * @return \Illuminate\Http\Response
     */
    public function edit(Yard $yard)
    {
        $input = [
            'yard' => $yard,
            'method' => 'PUT',
            'action' => route('yards.update', $yard)
        ];

        return view('admin.yards.edit', $input);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Yard  $Yard
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Yard $yard)
    {
        $yard->update($request->all());
        return redirect()->route('yards.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Yard  $Yard
     * @return \Illuminate\Http\Response
     */
    public function destroy(Yard $yard)
    {
        $yard->delete();
        return response()->json(['staus' => true, 'msg' => __('alerts.deleted_successfully')], 200);
    }
}
