<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:permissions.index')->only('index');
        $this->middleware('permission:permissions.create')->only(['create', 'store']);
        $this->middleware('permission:permissions.udpate')->only(['edit', 'udpate']);
        $this->middleware('permission:permissions.delete')->only('destroy');
    }

    public function index()
    {
        $roles = Role::all();

        return view('admin.permissions.index', compact('roles'));
    }

    public function create()
    {
        $permissions = Permission::all();

        return view('admin.permissions.create', compact('permissions'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'permissions' => 'required'
        ]);


        $role = Role::create(['name' => $request->name]);
        $role->syncPermissions($request->permissions);

        return to_route('permissions.index')->with(['success' => __('alerts.added_successfully')]);
    }

    public function edit($id)
    {
        $role = Role::findOrFail($id);
        $permissions = Permission::all();

        return view('admin.permissions.edit', compact('role', 'permissions'));
    }

    public function update(Request $request, $id)
    {
        $role = Role::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'permissions' => 'required'
        ]);

        $role->update(['name' => $request->name]);
        $role->syncPermissions($request->permissions);

        return to_route('permissions.index')->with(['success' => __('alerts.updated_successfully')]);
    }

    public function destroy($id)
    {
        $role = Role::findOrFail($id);
        $role->delete();

        return response()->json(['staus' => true, 'msg' => __('alerts.deleted_successfully')], 200);
    }
}
