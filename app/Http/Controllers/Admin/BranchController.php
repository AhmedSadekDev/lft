<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BranchRequest;
use App\Models\Branch;
use App\Models\CitiesAndRegions;
use App\Models\Factory;
use Illuminate\Http\Request;

class BranchController extends Controller
{
     public function __construct()
     {
        $this->middleware('permission:branches.index')->only('index');
        $this->middleware('permission:branches.create')->only(['create', 'store']);
        $this->middleware('permission:branches.udpate')->only(['edit', 'udpate']);
        $this->middleware('permission:branches.delete')->only('destroy');
     }
    
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $input = [
            'branches' => Branch::all(),
        ];

        return view('admin.branches.index', $input);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $input = [
            'factories' => Factory::pluck('name', 'id'),
            'method'    => 'POST',
            'action'    => route('branches.store'),
            'citiesAndRegions'  => CitiesAndRegions::pluck('title', 'id')
        ];

        return view('admin.branches.create', $input);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(BranchRequest $request)
    {
        Branch::create($request->validated());
        return redirect()->route('branches.index');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Branch  $branch
     * @return \Illuminate\Http\Response
     */
    public function show(Branch $branch)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Branch  $branch
     * @return \Illuminate\Http\Response
     */
    public function edit(Branch $branch)
    {
        $input = [
            'method' => 'PUT',
            'action' => route('branches.update', $branch),
            'branch' => $branch,
            'factories' => Factory::pluck('name', 'id'),
            'citiesAndRegions'  => CitiesAndRegions::pluck('title', 'id')
        ];

        return view('admin.branches.edit', $input);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Branch  $branch
     * @return \Illuminate\Http\Response
     */
    public function update(BranchRequest $request, Branch $branch)
    {
        $branch->update($request->all());
        return redirect()->route('branches.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Branch  $branch
     * @return \Illuminate\Http\Response
     */
    public function destroy(Branch $branch)
    {
        $branch->delete();
        return response()->json(['staus' => true, 'msg' => __('alerts.deleted_successfully')], 200);
    }
}
