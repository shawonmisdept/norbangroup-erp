<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Factory;
use App\Models\Role;
use App\Models\User;
use App\Services\UserPhotoService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with(['role', 'factory'])->latest()->paginate(15);

        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        return view('admin.users.create', [
            'roles'     => Role::orderBy('name')->pluck('name', 'id'),
            'factories' => Factory::orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate(array_merge($this->baseRules(), [
            'password' => ['required', 'confirmed', Password::defaults()],
            'photo'    => ['nullable', 'image', 'max:5120'],
        ]));

        if ($request->hasFile('photo')) {
            $data['photo'] = UserPhotoService::store($request->file('photo'));
        }

        $data['factory_id'] = $data['factory_id'] ?? null;

        $user = User::create($data);

        return redirect()->route('admin.users.show', $user)
            ->with('success', 'User created successfully.');
    }

    public function show(User $user)
    {
        $user->load(['role', 'factory']);

        return view('admin.users.show', compact('user'));
    }

    public function edit(User $user)
    {
        return view('admin.users.edit', [
            'user'      => $user,
            'roles'     => Role::orderBy('name')->pluck('name', 'id'),
            'factories' => Factory::orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate(array_merge($this->baseRules($user), [
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'photo'    => ['nullable', 'image', 'max:5120'],
        ]));

        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->role_id = $data['role_id'];
        $user->factory_id = $data['factory_id'] ?? null;

        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }

        if ($request->hasFile('photo')) {
            $user->photo = UserPhotoService::store($request->file('photo'), $user->photo);
        }

        $user->save();

        return redirect()->route('admin.users.show', $user)
            ->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('success', 'You cannot delete your own account.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'User deleted successfully.');
    }

    private function baseRules(?User $user = null): array
    {
        return [
            'name'       => ['required', 'string', 'max:255'],
            'email'      => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user?->id)],
            'role_id'    => ['required', Rule::exists('roles', 'id')],
            'factory_id' => ['nullable', Rule::exists('factories', 'id')],
        ];
    }
}
