<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\StaticPageRequest;
use App\Http\Controllers\Controller;
use App\Models\StaticPage;

class StaticPageController extends Controller
{

    public function __construct()
    {
        $this->middleware('permission:staticPages.index')->only('index');
        $this->middleware('permission:staticPages.create')->only(['create', 'store']);
        $this->middleware('permission:staticPages.udpate')->only(['edit', 'udpate']);
        $this->middleware('permission:staticPages.delete')->only('destroy');
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $input = [
            'staticPages' => StaticPage::all(),
        ];

        return view('admin.staticPages.index', $input);
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
            'action' => route('staticPages.store'),
        ];

        return view('admin.staticPages.create', $input);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StaticPageRequest $request)
    {
        StaticPage::create($request->validated());
        return redirect()->route('staticPages.index');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\StaticPage  $staticPage
     * @return \Illuminate\Http\Response
     */
    public function show(StaticPage $staticPage)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\StaticPage  $staticPage
     * @return \Illuminate\Http\Response
     */
    public function edit(StaticPage $staticPage)
    {
        $input = [
            'staticPage' => $staticPage,
            'method' => 'PUT',
            'action' => route('staticPages.update', $staticPage),
        ];

        return view('admin.staticPages.edit', $input);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\StaticPage  $staticPage
     * @return \Illuminate\Http\Response
     */
    public function update(StaticPageRequest $request, StaticPage $staticPage)
    {
        $staticPage->update($request->all());
        return redirect()->route('staticPages.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\StaticPage  $staticPage
     * @return \Illuminate\Http\Response
     */
    public function destroy(StaticPage $staticPage)
    {
        $staticPage->delete();
        return response()->json(['staus' => true, 'msg' => __('alerts.deleted_successfully')], 200);
    }
}
