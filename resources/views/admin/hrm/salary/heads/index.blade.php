@extends('layouts.admin')
@section('title', 'Salary Heads')
@section('admin-content')
@include('partials.erp.page-header', [
    'title' => 'Salary Heads',
    'subtitle' => 'Earning, deduction & statutory components',
    'actions' => auth()->user()->hasPermission('hrm.salary.manage')
        ? '<a href="' . route('admin.hrm.salary.heads.create') . '" class="erp-btn-primary !py-2 !px-4 text-xs">+ Add Head</a>'
        : '',
])
@include('admin.hrm.partials.submodule-nav', ['section' => 'salary', 'current' => 'heads'])

<div class="erp-panel overflow-hidden">
    <table class="erp-table">
        <thead>
            <tr>
                <th>Code</th>
                <th>Name</th>
                <th>Type</th>
                <th>Seq</th>
                <th>Status</th>
                <th class="text-right">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($heads as $head)
                <tr>
                    <td><code class="text-xs">{{ $head->code }}</code></td>
                    <td>
                        <p class="font-medium text-sm">{{ $head->name }}</p>
                        @if($head->description)<p class="text-[11px] text-gray-400">{{ Str::limit($head->description, 50) }}</p>@endif
                    </td>
                    <td><span class="text-xs font-semibold">{{ $head->head_type }}</span> <span class="text-[11px] text-gray-400">{{ $head->headTypeLabel() }}</span></td>
                    <td class="text-xs tabular-nums">{{ $head->sort_order }}</td>
                    <td><span class="text-xs {{ $head->is_active ? 'text-green-600' : 'text-gray-400' }}">{{ $head->is_active ? 'Active' : 'Inactive' }}</span></td>
                    <td class="text-right">
                        @include('admin.hrm.salary.partials.row-actions', [
                            'viewUrl' => route('admin.hrm.salary.heads.show', $head),
                            'editRoute' => auth()->user()->canManageSalarySubmodule('heads') ? route('admin.hrm.salary.heads.edit', $head) : null,
                            'destroyRoute' => auth()->user()->canManageSalarySubmodule('heads') ? route('admin.hrm.salary.heads.destroy', $head) : null,
                            'canManage' => auth()->user()->canManageSalarySubmodule('heads'),
                            'confirm' => 'Delete salary head "' . $head->name . '"?',
                        ])
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center py-8 text-gray-400">No salary heads yet.</td></tr>
            @endforelse
        </tbody>
    </table>
    @if($heads->hasPages())
        <div class="px-4 py-3 border-t">{{ $heads->links() }}</div>
    @endif
</div>

@include('admin.hrm.salary.partials.view-modal')
@endsection
