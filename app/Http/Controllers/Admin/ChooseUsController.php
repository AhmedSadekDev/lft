<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ChooseRequest;
use App\Models\ChooseUs;
use Illuminate\Http\Request;

class ChooseUsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $input = [
            'chooses' => ChooseUs::all(),
        ];

        return view('admin.chooseUs.index', $input);
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
            'action' => route('chooseUs.store'),
        ];

        return view('admin.chooseUs.create', $input);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(ChooseRequest $request)
    {
        if(!is_null(ChooseUs::count()) && ChooseUs::count() > 4){
            return redirect()->route('chooseUs.index')->with(['error' => __('alerts.maximum_services')]);
        }

        ChooseUs::create($request->validated());
        return redirect()->route('chooseUs.index');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\ChooseUs  $choose
     * @return \Illuminate\Http\Response
     */
    public function show(ChooseUs $chooseU)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\ChooseUs  $choose
     * @return \Illuminate\Http\Response
     */
    public function edit(ChooseUs $chooseU)
    {
        $input = [
            'choose' => $chooseU,
            'method' => 'PUT',
            'action' => route('chooseUs.update', $chooseU),
        ];

        return view('admin.chooseUs.edit', $input);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ChooseUs  $choose
     * @return \Illuminate\Http\Response
     */
    public function update(ChooseRequest $request, ChooseUs $chooseU)
    {
        $chooseU->update($request->all());
        return redirect()->route('chooseUs.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\ChooseUs  $choose
     * @return \Illuminate\Http\Response
     */
    public function destroy(ChooseUs $chooseU)
    {
        $chooseU->delete();
        return response()->json(['staus' => true, 'msg' => __('alerts.deleted_successfully')], 200);
    }
}
