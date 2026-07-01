@extends('layouts.admin')

@section('title', 'Roles — ' . config('app.name'))

@section('breadcrumbs')
    <span class="text-gray-600 font-medium">Administration</span>
    <span>/</span>
    <span class="text-gray-800 font-medium">Roles</span>
@endsection

@section('admin-content')

@include('partials.erp.page-header', [
    'title' => 'Roles & Permissions',
    'subtitle' => 'Define access levels for ERP modules',
    'actions' => '<a href="' . route('admin.roles.create') . '" class="erp-btn-primary">+ New Role</a>',
])

<div class="grid gap-3 sm:grid-cols-3 mb-4">
    <div class="erp-panel erp-panel-body">
        <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">Total Roles</p>
        <p class="text-2xl font-semibold text-gray-900 mt-1">{{ number_format($stats['total']) }}</p>
    </div>
    <div class="erp-panel erp-panel-body">
        <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">Assigned to Users</p>
        <p class="text-2xl font-semibold text-emerald-700 mt-1">{{ number_format($stats['in_use']) }}</p>
    </div>
    <div class="erp-panel erp-panel-body">
        <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">Unassigned</p>
        <p class="text-2xl font-semibold text-gray-600 mt-1">{{ number_format($stats['unassigned']) }}</p>
    </div>
</div>

<div class="erp-panel mb-4">
    <form method="GET" class="erp-panel-body flex flex-wrap gap-2 items-center">
        <input type="search" name="search" value="{{ $search }}" placeholder="Search role by name…"
               class="erp-input flex-1 min-w-48 !text-xs">
        <button type="submit" class="erp-btn-primary">Search</button>
        @if($search !== '')
            <a href="{{ route('admin.roles.index') }}" class="text-xs text-gray-400 hover:text-gray-600 px-2">Clear</a>
        @endif
    </form>
</div>

<div class="erp-panel overflow-hidden">
    <div class="erp-panel-head">
        <h2 class="text-xs font-semibold text-gray-700 uppercase tracking-wide">Role Directory</h2>
        <span class="text-[11px] text-gray-400">
            @if($search !== '')
                {{ $roles->total() }} match(es)
            @else
                {{ $roles->total() }} role(s)
            @endif
        </span>
    </div>
    <div class="overflow-x-auto">
        <table class="erp-table">
            <thead>
                <tr>
                    <th>Role</th>
                    <th class="hidden sm:table-cell w-28">Users</th>
                    <th>Module Access</th>
                    <th class="hidden md:table-cell w-32">Permissions</th>
                    <th class="hidden lg:table-cell w-36">Updated</th>
                    <th class="text-right w-44">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($roles as $role)
                    @php
                        $areas = $role->moduleAccessAreas();
                        $permissionCount = $role->permissionCount();
                    @endphp
                    <tr>
                        <td>
                            <a href="{{ route('admin.roles.edit', $role) }}"
                               class="font-medium text-gray-900 hover:text-brand transition-colors">
                                {{ $role->name }}
                            </a>
                            <p class="text-[11px] text-gray-400 mt-0.5 sm:hidden">
                                {{ $role->users_count }} user(s) · {{ $permissionCount }} permission(s)
                            </p>
                        </td>
                        <td class="hidden sm:table-cell">
                            @if($role->users_count > 0)
                                <span class="erp-badge bg-emerald-50 text-emerald-700">{{ $role->users_count }}</span>
                            @else
                                <span class="text-xs text-gray-400">—</span>
                            @endif
                        </td>
                        <td>
                            @if($areas !== [])
                                <div class="flex flex-wrap gap-1 max-w-md">
                                    @foreach($areas as $area)
                                        <span class="erp-badge {{ \App\Models\Role::moduleAreaBadgeClass($area) }}">
                                            {{ $area }}
                                        </span>
                                    @endforeach
                                </div>
                            @else
                                <span class="text-xs text-gray-400">No modules</span>
                            @endif
                        </td>
                        <td class="hidden md:table-cell">
                            <span class="erp-badge bg-gray-100 text-gray-600">{{ $permissionCount }}</span>
                        </td>
                        <td class="hidden lg:table-cell text-xs text-gray-500">
                            {{ $role->updated_at?->format('d M Y') ?? '—' }}
                        </td>
                        <td class="text-right">
                            <div class="erp-table-actions">
                                <a href="{{ route('admin.roles.show', $role) }}" class="erp-btn-sm-secondary">View</a>
                                <a href="{{ route('admin.roles.edit', $role) }}" class="erp-btn-sm-primary">Edit</a>
                                @if($role->users_count)
                                    <button type="button" disabled
                                            title="Cannot delete — assigned to {{ $role->users_count }} user(s)"
                                            class="erp-btn-danger !py-1 !px-2 opacity-40 cursor-not-allowed">Del</button>
                                @else
                                    <form method="POST" action="{{ route('admin.roles.destroy', $role) }}" class="inline" data-confirm="Delete this role?">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="erp-btn-danger !py-1 !px-2">Del</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-10 text-gray-400">
                            @if($search !== '')
                                No roles match “{{ $search }}”.
                            @else
                                No roles found. Create your first role to get started.
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($roles->hasPages())
        <div class="px-4 py-3 border-t border-erp-border bg-gray-50/50">{{ $roles->links() }}</div>
    @endif
</div>

<p class="text-xs text-gray-400 mt-3">
    Open a role to view and edit the full permission list. Module badges above are a summary only.
</p>
@endsection
