<?php

namespace App\Http\Controllers\Setting;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;
use Throwable;

class RolePermissionController extends Controller
{
    public function index()
    {
        $roles = Role::query()->with('permissions')->orderBy('name')->get();
        $permissions = Permission::query()->orderBy('group_name')->orderBy('name')->get()->groupBy('group_name');

        return view('setting.roles', compact('roles', 'permissions'));
    }

    public function update(Request $request, Role $role)
    {
        $data = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,id'],
        ]);

        try {
            $role->permissions()->sync($data['permissions'] ?? []);

            return back()->with('swal_success', 'Hak akses role '.$role->name.' berhasil diperbarui.');
        } catch (Throwable $e) {
            return back()->with('swal_error', $e->getMessage());
        }
    }
}
