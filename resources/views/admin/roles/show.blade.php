@extends('layouts.admin')

@section('title', $role->name . ' — Role')

@section('breadcrumbs')
    <a href="{{ route('admin.roles.index') }}" class="hover:text-brand">Roles</a>
    <span>/</span>
    <span class="text-gray-800 font-medium">{{ $role->name }}</span>
@endsection

@section('admin-content')
@include('partials.erp.page-header', [
    'title' => $role->name,
    'subtitle' => $role->permissionCount() . ' permission(s) · ' . $role->users_count . ' assigned user(s)',
    'actions' => '<a href="' . route('admin.roles.edit', $role) . '" class="erp-btn-primary">Edit</a>',
])

<div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
    <div class="xl:col-span-2 space-y-4">
        <div class="erp-panel">
            <div class="erp-panel-head">
                <h2 class="text-xs font-semibold text-gray-700 uppercase tracking-wide">Overview</h2>
            </div>
            <div class="erp-panel-body">
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm mb-4">
                    <div>
                        <p class="erp-form-label !mb-0.5">Permissions</p>
                        <p class="font-medium tabular-nums">{{ $role->permissionCount() }}</p>
                    </div>
                    <div>
                        <p class="erp-form-label !mb-0.5">Assigned Users</p>
                        <p class="font-medium tabular-nums">{{ $role->users_count }}</p>
                    </div>
                    <div>
                        <p class="erp-form-label !mb-0.5">Created</p>
                        <p class="font-medium tabular-nums">{{ $role->created_at?->format('d M Y') ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="erp-form-label !mb-0.5">Last Updated</p>
                        <p class="font-medium tabular-nums">{{ $role->updated_at?->format('d M Y') ?? '—' }}</p>
                    </div>
                </div>

                @php $areas = $role->moduleAccessAreas(); @endphp
                @if($areas !== [])
                    <div class="pt-4 border-t border-erp-border">
                        <p class="erp-form-label mb-2">Module Access</p>
                        <div class="flex flex-wrap gap-1">
                            @foreach($areas as $area)
                                <span class="erp-badge {{ \App\Models\Role::moduleAreaBadgeClass($area) }}">{{ $area }}</span>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <div class="erp-panel">
            <div class="erp-panel-head">
                <h2 class="text-xs font-semibold text-gray-700 uppercase tracking-wide">Granted Permissions</h2>
                <span class="text-[11px] text-gray-400">{{ $role->permissionCount() }} total</span>
            </div>
            <div class="erp-panel-body space-y-4 max-h-[32rem] overflow-y-auto">
                @forelse($groupedPermissions as $groupName => $permissions)
                    <section>
                        <h3 class="text-[11px] font-bold text-gray-500 uppercase tracking-wide mb-2">{{ $groupName }}</h3>
                        <div class="flex flex-wrap gap-1">
                            @foreach($permissions as $key => $label)
                                <span class="erp-badge bg-gray-100 text-gray-600" title="{{ $key }}">{{ $label }}</span>
                            @endforeach
                        </div>
                    </section>
                @empty
                    <p class="text-sm text-gray-400">No permissions assigned.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="space-y-4">
        <div class="erp-panel">
            <div class="erp-panel-head">
                <h2 class="text-xs font-semibold text-gray-700 uppercase tracking-wide">Assigned Users</h2>
                <span class="text-[11px] text-gray-400">{{ $role->users_count }}</span>
            </div>
            <div class="erp-panel-body">
                @forelse($role->users as $user)
                    <div class="flex items-center justify-between gap-2 py-2 {{ ! $loop->last ? 'border-b border-erp-border' : '' }}">
                        <div class="min-w-0">
                            <a href="{{ route('admin.users.show', $user) }}" class="text-sm font-medium text-gray-900 hover:text-brand truncate block">
                                {{ $user->name }}
                            </a>
                            <p class="text-[11px] text-gray-400 truncate">{{ $user->email }}</p>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-400">No users assigned to this role.</p>
                @endforelse
            </div>
        </div>

        <a href="{{ route('admin.roles.edit', $role) }}" class="erp-btn-primary w-full justify-center !py-2.5">Edit Role</a>
        @if($role->users_count)
            <button type="button" disabled
                    title="Cannot delete — assigned to {{ $role->users_count }} user(s)"
                    class="erp-btn-danger w-full justify-center !py-2.5 opacity-40 cursor-not-allowed">
                Delete Role
            </button>
            <p class="text-[11px] text-gray-400 text-center">Reassign users before deleting this role.</p>
        @else
            <form method="POST" action="{{ route('admin.roles.destroy', $role) }}" data-confirm="Delete this role permanently?">
                @csrf @method('DELETE')
                <button type="submit" class="erp-btn-danger w-full justify-center !py-2.5">Delete Role</button>
            </form>
        @endif
    </div>
</div>
@endsection
