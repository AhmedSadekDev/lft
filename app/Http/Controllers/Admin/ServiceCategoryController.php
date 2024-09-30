<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ServiceCategoryRequest;
use App\Models\ServiceCategory;
use Illuminate\Http\Request;

class ServiceCategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:serviceCategories.index')->only('index');
        $this->middleware('permission:serviceCategories.create')->only(['create', 'store']);
        $this->middleware('permission:serviceCategories.udpate')->only(['edit', 'udpate']);
        $this->middleware('permission:serviceCategories.delete')->only('destroy');
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $input = [
            'serviceCategories' => ServiceCategory::all(),
        ];

        return view('admin.serviceCategories.index', $input);
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
            'action' => route('serviceCategories.store'),
            'status' => [ 0 => __('admin.taxed'), 1 => __('admin.untaxed'), 2 => __('admin.not_added')],
        ];

        return view('admin.serviceCategories.create', $input);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(ServiceCategoryRequest $request)
    {
        ServiceCategory::create($request->validated());
        return redirect()->route('serviceCategories.index');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\ServiceCategory  $serviceCategory
     * @return \Illuminate\Http\Response
     */
    public function show(ServiceCategory $serviceCategory)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\ServiceCategory  $serviceCategory
     * @return \Illuminate\Http\Response
     */
    public function edit(ServiceCategory $serviceCategory)
    {
        $input = [
            'serviceCategory' => $serviceCategory,
            'method' => 'PUT',
            'action' => route('serviceCategories.update', $serviceCategory),
            'status' => [ 0 => __('admin.taxed'), 1 => __('admin.untaxed'), 2 => __('admin.not_added')],
        ];

        return view('admin.serviceCategories.edit', $input);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ServiceCategory  $serviceCategory
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ServiceCategory $serviceCategory)
    {
        $serviceCategory->update($request->all());
        return redirect()->route('serviceCategories.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\ServiceCategory  $serviceCategory
     * @return \Illuminate\Http\Response
     */
    public function destroy(ServiceCategory $serviceCategory)
    {
        $serviceCategory->delete();
        return response()->json(['staus' => true, 'msg' => __('alerts.deleted_successfully')], 200);
    }
}
