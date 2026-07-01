@extends('layouts.admin')

@section('title', 'Letter Templates')

@section('breadcrumbs')
    <a href="{{ route('admin.hrm.dashboard') }}" class="hover:text-brand">HRM</a>
    <span>/</span>
    <a href="{{ route('admin.hrm.letters.index') }}" class="hover:text-brand">HR Letters</a>
    <span>/</span>
    <span class="text-gray-800 font-medium">Templates</span>
@endsection

@section('admin-content')
@include('partials.erp.page-header', [
    'title' => 'Letter Templates',
    'subtitle' => 'Manage appointment, warning & exit letter templates',
    'actions' => ($canManage ? '<a href="' . route('admin.hrm.letter-templates.create') . '" class="erp-btn-primary !py-2 !px-4 text-xs">Add Template</a>' : '')
        . '<a href="' . route('admin.hrm.letters.index') . '" class="erp-btn-secondary ml-2">← Letters</a>',
])

<div class="erp-panel mb-4">
    <div class="erp-panel-body">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            @if(count($factories) > 1)
                <div class="w-44">
                    <label class="erp-form-label">Factory</label>
                    <select name="factory_id" class="erp-input !text-xs">
                        <option value="">All units</option>
                        @foreach($factories as $id => $name)
                            <option value="{{ $id }}" {{ (string) ($filters['factory_id'] ?? '') === (string) $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="w-44">
                <label class="erp-form-label">Type</label>
                <select name="letter_type" class="erp-input !text-xs">
                    <option value="">All types</option>
                    @foreach($letterTypes as $value => $label)
                        <option value="{{ $value }}" {{ ($filters['letter_type'] ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="erp-btn-secondary !py-2 !px-4 text-xs">Filter</button>
        </form>
    </div>
</div>

<div class="erp-panel">
    <div class="overflow-x-auto">
        <table class="erp-table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Name</th>
                    <th>Type</th>
                    <th>Factory</th>
                    <th>Active</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($templates as $template)
                    <tr>
                        <td><code class="text-xs">{{ $template->code }}</code></td>
                        <td class="font-medium text-sm">{{ $template->name }}</td>
                        <td class="text-xs">{{ $template->typeLabel() }}</td>
                        <td class="text-xs text-gray-600">{{ $template->factory?->name ?? 'All units' }}</td>
                        <td>
                            <span class="erp-badge {{ $template->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                {{ $template->is_active ? 'Yes' : 'No' }}
                            </span>
                        </td>
                        <td class="text-right">
                            @if($canManage)
                                @include('partials.erp.table-actions', [
                                    'editUrl' => route('admin.hrm.letter-templates.edit', $template),
                                    'destroyUrl' => route('admin.hrm.letter-templates.destroy', $template),
                                ])
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-gray-400 py-8">No templates found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($templates->hasPages())
        <div class="erp-panel-body border-t border-erp-border">{{ $templates->links() }}</div>
    @endif
</div>
@endsection
