<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index()
    {
        return Role::with('permissions')->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|unique:roles,name',
            'permission_ids' => 'array',
            'permission_ids.*' => 'exists:permissions,id',
        ]);

        $role = Role::create(['name' => $data['name']]);
        if (!empty($data['permission_ids'])) {
            $role->permissions()->sync($data['permission_ids']);
        }

        return $role->load('permissions');
    }

    public function show(Role $role)
    {
        return $role->load('permissions');
    }

    public function update(Request $request, Role $role)
    {
        $data = $request->validate([
            'name' => 'sometimes|required|string|unique:roles,name,' . $role->id,
            'permission_ids' => 'array',
            'permission_ids.*' => 'exists:permissions,id',
        ]);

        if (isset($data['name'])) {
            $role->name = $data['name'];
            $role->save();
        }
        if (isset($data['permission_ids'])) {
            $role->permissions()->sync($data['permission_ids']);
        }

        return $role->load('permissions');
    }

    public function destroy(Role $role)
    {
        $role->delete();
        return response()->noContent();
    }
}
