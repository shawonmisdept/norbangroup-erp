<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));

        $roles = Role::query()
            ->withCount('users')
            ->when($search !== '', fn ($query) => $query->where('name', 'like', '%' . $search . '%'))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'total'      => Role::count(),
            'in_use'     => Role::has('users')->count(),
            'unassigned' => Role::doesntHave('users')->count(),
        ];

        return view('admin.roles.index', compact('roles', 'search', 'stats'));
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

    public function show(Role $role)
    {
        $role->loadCount('users');
        $role->load(['users' => fn ($query) => $query->orderBy('name')]);

        $granted = collect($role->permissions ?? []);
        $groupedPermissions = [];

        foreach (Role::permissionGroups() as $groupName => $permissions) {
            $items = collect($permissions)
                ->filter(fn ($label, $key) => $granted->contains($key))
                ->all();

            if ($items !== []) {
                $groupedPermissions[$groupName] = $items;
            }
        }

        return view('admin.roles.show', compact('role', 'groupedPermissions'));
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
            return back()->with('error', 'Cannot delete a role that is assigned to users.');
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
