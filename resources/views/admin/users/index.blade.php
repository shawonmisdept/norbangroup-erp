@extends('layouts.admin')

@section('title', 'Users — ' . config('app.name'))

@section('breadcrumbs')
    <span class="text-gray-600 font-medium">Administration</span>
    <span>/</span>
    <span class="text-gray-800 font-medium">Users</span>
@endsection

@section('admin-content')

@include('partials.erp.page-header', [
    'title' => 'User Management',
    'subtitle' => 'Team members, roles and factory assignments',
    'actions' => '<a href="' . route('admin.users.create') . '" class="erp-btn-primary">+ New User</a>',
])

<div class="erp-panel overflow-hidden">
    <div class="erp-panel-head">
        <h2 class="text-xs font-semibold text-gray-700 uppercase tracking-wide">User Directory</h2>
        <span class="text-[11px] text-gray-400">{{ $users->total() }} user(s)</span>
    </div>
    <div class="overflow-x-auto">
        <table class="erp-table">
            <thead>
                <tr>
                    <th class="w-12"></th>
                    <th>User ID</th>
                    <th>Name</th>
                    <th class="hidden lg:table-cell">Email</th>
                    <th class="hidden md:table-cell">Factory</th>
                    <th>Role</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    <tr>
                        <td>@include('partials.user-avatar', ['user' => $user, 'size' => '40'])</td>
                        <td><code class="text-[11px] bg-gray-100 px-1.5 py-0.5 rounded-sm font-mono">{{ $user->user_code }}</code></td>
                        <td class="font-medium text-gray-900">{{ $user->name }}</td>
                        <td class="hidden lg:table-cell text-xs">{{ $user->email }}</td>
                        <td class="hidden md:table-cell text-xs text-gray-500">{{ $user->factory?->name ?? '—' }}</td>
                        <td><span class="erp-badge bg-gold-light text-gold-dark">{{ $user->roleLabel() }}</span></td>
                        <td class="text-right">
                            <div class="erp-table-actions">
                                <a href="{{ route('admin.users.show', $user) }}" class="erp-btn-sm-secondary">View</a>
                                <a href="{{ route('admin.users.edit', $user) }}" class="erp-btn-sm-primary">Edit</a>
                                @if($user->id !== auth()->id())
                                    <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="inline"
                                          onsubmit="return confirm('Delete this user?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="erp-btn-danger !py-1 !px-2">Del</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center py-10 text-gray-400">No users found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($users->hasPages())
        <div class="px-4 py-3 border-t border-erp-border bg-gray-50/50">{{ $users->links() }}</div>
    @endif
</div>
@endsection
