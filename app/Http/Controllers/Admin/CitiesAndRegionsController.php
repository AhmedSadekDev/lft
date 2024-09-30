<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CityRequest;
use App\Models\CitiesAndRegions;
use Illuminate\Http\Request;

class CitiesAndRegionsController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:cities.index')->only('index');
        $this->middleware('permission:cities.create')->only(['create', 'store']);
        $this->middleware('permission:cities.udpate')->only(['edit', 'udpate']);
        $this->middleware('permission:cities.delete')->only('destroy');
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $input = [
            'cities' => CitiesAndRegions::all(),
        ];
        return view('admin.cities.index', $input);
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
            'action'    => route('citiesAndRegions.store')
        ];

        return view('admin.cities.create', $input);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(CityRequest $request)
    {
        $cities = CitiesAndRegions::create($request->validated());

        return redirect()->route('citiesAndRegions.index')
            ->with('success', __('alerts.added_successfully'));
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\CitiesAndRegions  $citiesAndRegions
     * @return \Illuminate\Http\Response
     */
    public function show(CitiesAndRegions $citiesAndRegions)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\CitiesAndRegions  $citiesAndRegions
     * @return \Illuminate\Http\Response
     */
    public function edit(citiesAndRegions $citiesAndRegion)
    {
        $input = [
            'city' => $citiesAndRegion,
            'method' => 'PUT',
            'action' => route('citiesAndRegions.update', $citiesAndRegion->id),
        ];

        return view('admin.cities.edit', $input);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\CitiesAndRegions  $citiesAndRegions
     * @return \Illuminate\Http\Response
     */
    public function update(CityRequest $request, CitiesAndRegions $citiesAndRegion)
    {
        $citiesAndRegion->update($request->validated());
        return redirect()->route('citiesAndRegions.index')
            ->with('success', __('alerts.updated_successfully'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\CitiesAndRegions  $citiesAndRegions
     * @return \Illuminate\Http\Response
     */
    public function destroy(CitiesAndRegions $citiesAndRegion)
    {
        $citiesAndRegion->delete();
        return response()->json(['staus' => true, 'msg' => __('alerts.deleted_successfully')], 200);
    }
}
