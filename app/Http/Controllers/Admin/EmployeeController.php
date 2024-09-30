<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EmployeeRequest;
use App\Models\Company;
use Illuminate\Http\Request;
use App\Models\Employee;

class EmployeeController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:employees.index')->only('index');
        $this->middleware('permission:employees.create')->only(['create', 'store']);
        $this->middleware('permission:employees.udpate')->only(['edit', 'udpate']);
        $this->middleware('permission:employees.delete')->only('destroy');
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $input = [
            'employees' => Employee::all(),
        ];

        return view('admin.employees.index', $input);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $input = [
            'companies' => Company::pluck('name', 'id'),
            'method' => 'POST',
            'action' => route('employees.store'),
        ];

        return view('admin.employees.create', $input);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(EmployeeRequest $request)
    {
        $employee = Employee::create($request->validated());
        return redirect()->route('employees.index');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Employee  $employee
     * @return \Illuminate\Http\Response
     */
    public function show(Employee $employee)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Employee  $employee
     * @return \Illuminate\Http\Response
     */
    public function edit(Employee $employee)
    {
        $input = [
            'companies' => Company::pluck('name', 'id'),
            'employee' => $employee,
            'method' => 'PUT',
            'action' => route('employees.update', $employee->id),
        ];

        return view('admin.employees.edit', $input);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Employee  $employee
     * @return \Illuminate\Http\Response
     */
    public function update(EmployeeRequest $request, Employee $employee)
    {
        $employee->update($request->validated());
        return redirect()->route('employees.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Employee  $employee
     * @return \Illuminate\Http\Response
     */
    public function destroy(Employee $employee)
    {
        $employee->delete();
        return response()->json(['staus' => true, 'msg' => __('alerts.deleted_successfully')], 200);
    }
    
    
    public function company(Request $request)
    {
        if ($request->has('company_id')) {
            // Fetch the employees for the given company_id
            $employees = Employee::where('company_id', $request->company_id)->get();
            
            // Return the employees as JSON
            return response()->json($employees);
        }
        
        return response()->json(['error' => 'Company ID not provided'], 400);
    }



}
