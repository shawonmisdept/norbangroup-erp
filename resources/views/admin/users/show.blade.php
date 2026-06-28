@extends('layouts.admin')

@section('title', $user->name)

@section('breadcrumbs')
    <a href="{{ route('admin.users.index') }}" class="hover:text-brand">Users</a>
    <span>/</span>
    <span class="text-gray-800 font-medium">{{ $user->user_code }}</span>
@endsection

@section('admin-content')
@include('partials.erp.page-header', [
    'title' => $user->name,
    'subtitle' => $user->email,
    'actions' => '<a href="' . route('admin.users.edit', $user) . '" class="erp-btn-primary">Edit</a>',
])

<div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
    <div class="xl:col-span-2 erp-panel">
        <div class="erp-panel-body">
            <div class="flex items-start gap-6 mb-6 pb-6 border-b border-erp-border">
                @include('partials.user-avatar', ['user' => $user, 'size' => '180'])
                <div>
                    <code class="text-xs bg-gray-100 px-2 py-1 rounded-sm font-mono">{{ $user->user_code }}</code>
                    <span class="ml-2 erp-badge bg-gold-light text-gold-dark">{{ $user->roleLabel() }}</span>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div><p class="erp-form-label !mb-0.5">Email</p><p class="font-medium text-brand">{{ $user->email }}</p></div>
                <div><p class="erp-form-label !mb-0.5">Factory</p><p class="font-medium">{{ $user->factory?->name ?? '—' }}</p></div>
                <div><p class="erp-form-label !mb-0.5">Member Since</p><p class="font-medium tabular-nums">{{ $user->created_at->format('d M Y') }}</p></div>
            </div>
            @if($user->role)
                <div class="mt-6 pt-6 border-t border-erp-border">
                    <p class="erp-form-label mb-2">Permissions</p>
                    <div class="flex flex-wrap gap-1">
                        @foreach($user->role->permissions ?? [] as $permission)
                            <span class="erp-badge bg-gray-100 text-gray-600">{{ \App\Models\Role::permissionLabel($permission) }}</span>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
    <div class="space-y-3">
        <a href="{{ route('admin.users.edit', $user) }}" class="erp-btn-primary w-full justify-center !py-2.5">Edit User</a>
        @if($user->id !== auth()->id())
            <form method="POST" action="{{ route('admin.users.destroy', $user) }}" data-confirm="Delete permanently?">
                @csrf @method('DELETE')
                <button type="submit" class="erp-btn-danger w-full justify-center !py-2.5">Delete User</button>
            </form>
        @endif
    </div>
</div>
@endsection
