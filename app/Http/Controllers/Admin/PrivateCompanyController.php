<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PrivateCompanyRequest;
use App\Models\PrivateCompany;
use Illuminate\Http\Request;

class PrivateCompanyController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = PrivateCompany::query();

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('tax_no', 'like', "%{$search}%")
                  ->orWhere('commercial_register', 'like', "%{$search}%");
            });
        }

        // Sort by
        $sortBy = $request->get('sort_by', 'id');
        $sortDir = $request->get('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        $privateCompanies = $query->paginate(20);

        $input = [
            'privateCompanies' => $privateCompanies,
        ];
        return view('admin.private-companies.index', $input);
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
            'action'    => route('private-companies.store')
        ];

        return view('admin.private-companies.create', $input);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(PrivateCompanyRequest $request)
    {
        $data = $request->all();

        $data = array_filter($data, function ($value) {
            return $value !== null;
        });

        PrivateCompany::create($data);

        return to_route('private-companies.index')->with('success', __('alerts.added_successfully'));
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\PrivateCompany  $privateCompany
     * @return \Illuminate\Http\Response
     */
    public function show(PrivateCompany $privateCompany)
    {
        $input = [
            'privateCompany' => $privateCompany,
        ];
        return view('admin.private-companies.show', $input);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\PrivateCompany  $privateCompany
     * @return \Illuminate\Http\Response
     */
    public function edit(PrivateCompany $privateCompany)
    {
        $input = [
            'method'    => 'PUT',
            'action'    => route('private-companies.update', $privateCompany->id),
            'privateCompany'   => $privateCompany,
        ];

        return view('admin.private-companies.edit', $input);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\PrivateCompany  $privateCompany
     * @return \Illuminate\Http\Response
     */
    public function update(PrivateCompanyRequest $request, PrivateCompany $privateCompany)
    {
        $data = $request->all();

        // إذا لم يتم رفع صورة جديدة، احتفظ بالصورة القديمة
        if (!$request->hasFile('logo')) {
            unset($data['logo']);
        }

        $privateCompany->update($data);

        return redirect()->route('private-companies.index')
            ->with('success', __('alerts.updated_successfully'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\PrivateCompany  $privateCompany
     * @return \Illuminate\Http\Response
     */
    public function destroy(PrivateCompany $privateCompany)
    {
        $privateCompany->delete();
        return response()->json(['status' => true, 'msg' => __('alerts.deleted_successfully')], 200);
    }
}
