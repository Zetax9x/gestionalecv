<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    public function index()
    {
        return Permission::all();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|unique:permissions,name',
        ]);

        return Permission::create($data);
    }

    public function show(Permission $permission)
    {
        return $permission;
    }

    public function update(Request $request, Permission $permission)
    {
        $data = $request->validate([
            'name' => 'sometimes|required|string|unique:permissions,name,' . $permission->id,
        ]);

        $permission->update($data);
        return $permission;
    }

    public function destroy(Permission $permission)
    {
        $permission->delete();
        return response()->noContent();
    }
}
