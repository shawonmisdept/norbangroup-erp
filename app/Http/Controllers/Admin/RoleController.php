<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::withCount('users')->latest()->get();

        return view('admin.roles.index', compact('roles'));
    }

    public function create()
    {
        return view('admin.roles.create', [
            'permissionGroups' => Role::permissionGroups(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateRole($request);

        Role::create($data);

        return redirect()->route('admin.roles.index')
            ->with('success', 'Role created successfully.');
    }

    public function edit(Role $role)
    {
        return view('admin.roles.edit', [
            'role'             => $role,
            'permissionGroups' => Role::permissionGroups(),
        ]);
    }

    public function update(Request $request, Role $role)
    {
        $data = $this->validateRole($request, $role);

        $role->update($data);

        return redirect()->route('admin.roles.index')
            ->with('success', 'Role updated successfully.');
    }

    public function destroy(Role $role)
    {
        if ($role->users()->exists()) {
            return back()->with('success', 'Cannot delete a role that is assigned to users.');
        }

        $role->delete();

        return redirect()->route('admin.roles.index')
            ->with('success', 'Role deleted successfully.');
    }

    private function validateRole(Request $request, ?Role $role = null): array
    {
        $validPermissions = array_keys(Role::permissionOptions());

        $data = $request->validate([
            'name'          => ['required', 'string', 'max:255', Rule::unique('roles', 'name')->ignore($role?->id)],
            'permissions'   => ['required', 'array', 'min:1'],
            'permissions.*' => ['string', Rule::in($validPermissions)],
        ]);

        $data['permissions'] = array_values(array_unique($data['permissions']));

        return $data;
    }
}
