<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FactoryRequest;
use App\Models\Factory;
use Illuminate\Http\Request;

class FactoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:factories.index')->only('index');
        $this->middleware('permission:factories.create')->only(['create', 'store']);
        $this->middleware('permission:factories.udpate')->only(['edit', 'udpate']);
        $this->middleware('permission:factories.delete')->only('destroy');
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $input = [
            'factories' => Factory::all(),
        ];

        return view('admin.factories.index', $input);
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
            'action' => route('factories.store'),
        ];

        return view('admin.factories.create', $input);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(FactoryRequest $request)
    {
        Factory::create($request->validated());
        return redirect()->route('factories.index');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Factory  $factory
     * @return \Illuminate\Http\Response
     */
    public function show(Factory $factory)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Factory  $factory
     * @return \Illuminate\Http\Response
     */
    public function edit(Factory $factory)
    {
        $input = [
            'factory' => $factory,
            'method' => 'PUT',
            'action' => route('factories.update', $factory),
        ];

        return view('admin.factories.edit', $input);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Factory  $factory
     * @return \Illuminate\Http\Response
     */
    public function update(FactoryRequest $request, Factory $factory)
    {
        $factory->update($request->all());
        return redirect()->route('factories.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Factory  $factory
     * @return \Illuminate\Http\Response
     */
    public function destroy(Factory $factory)
    {
        $factory->delete();
        return response()->json(['staus' => true, 'msg' => __('alerts.deleted_successfully')], 200);
    }

    public function getBranches(Factory $factory)
    {
        $branches = $factory->branches->pluck('name', 'id')->toArray();
        return response()->json(['status' => true, 'data' => $branches], 200);
    }
}
