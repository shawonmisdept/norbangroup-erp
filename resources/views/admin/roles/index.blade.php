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

<div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
    @foreach($roles as $role)
        <div class="erp-panel">
            <div class="erp-panel-head">
                <div>
                    <h2 class="text-sm font-semibold text-gray-800">{{ $role->name }}</h2>
                    <p class="text-[11px] text-gray-400">{{ $role->users_count }} assigned user(s)</p>
                </div>
                <div class="flex gap-1">
                    <a href="{{ route('admin.roles.edit', $role) }}" class="erp-btn-secondary !py-1 !px-2">Edit</a>
                    @if(! $role->users_count)
                        <form method="POST" action="{{ route('admin.roles.destroy', $role) }}" class="inline"
                              onsubmit="return confirm('Delete this role?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="erp-btn-danger !py-1 !px-2">Del</button>
                        </form>
                    @endif
                </div>
            </div>
            <div class="erp-panel-body">
                <p class="erp-form-label mb-2">Granted Permissions</p>
                <div class="flex flex-wrap gap-1">
                    @foreach($role->permissions ?? [] as $permission)
                        <span class="erp-badge bg-gray-100 text-gray-600">
                            {{ \App\Models\Role::permissionLabel($permission) }}
                        </span>
                    @endforeach
                </div>
            </div>
        </div>
    @endforeach
</div>
@endsection
