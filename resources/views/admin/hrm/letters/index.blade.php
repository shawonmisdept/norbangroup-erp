@extends('layouts.admin')

@section('title', 'HR Letters')

@section('breadcrumbs')
    <a href="{{ route('admin.hrm.dashboard') }}" class="hover:text-brand">HRM</a>
    <span>/</span>
    <span class="text-gray-800 font-medium">HR Letters</span>
@endsection

@section('admin-content')
@include('partials.erp.page-header', [
    'title' => 'HR Letters',
    'subtitle' => 'Issue appointment, confirmation, warning & exit letters',
    'actions' => ($canManage ? '<a href="' . route('admin.hrm.letters.create') . '" class="erp-btn-primary !py-2 !px-4 text-xs">Issue Letter</a>' : '')
        . '<a href="' . route('admin.hrm.letter-templates.index') . '" class="erp-btn-secondary ml-2 !py-2 !px-4 text-xs">Templates</a>',
])

<div class="erp-panel mb-4">
    <div class="erp-panel-body">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <div class="flex-1 min-w-[180px]">
                <label class="erp-form-label">Search</label>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Reference, employee…" class="erp-input !text-xs">
            </div>
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

@if($templates->isNotEmpty())
    <div class="erp-panel mb-4">
        <div class="erp-panel-head"><h2 class="text-xs font-semibold text-gray-700 uppercase tracking-wide">Available Templates</h2></div>
        <div class="erp-panel-body flex flex-wrap gap-2">
            @foreach($templates as $template)
                <span class="erp-badge bg-gray-100 text-gray-700">{{ $template->name }}</span>
            @endforeach
        </div>
    </div>
@endif

<div class="erp-panel">
    <div class="overflow-x-auto">
        <table class="erp-table">
            <thead>
                <tr>
                    <th>Reference</th>
                    <th>Employee</th>
                    <th>Type</th>
                    <th>Issued</th>
                    <th>By</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($letters as $letter)
                    <tr class="{{ $letter->isVoided() ? 'opacity-70' : '' }}">
                        <td>
                            <code class="text-xs">{{ $letter->reference_no }}</code>
                            @if($letter->reissued_from_id)
                                <span class="erp-badge bg-blue-100 text-blue-700 text-[10px] ml-1">Reissued</span>
                            @endif
                        </td>
                        <td>
                            <p class="font-medium text-sm">{{ $letter->employee->name }}</p>
                            <code class="text-xs text-gray-500">{{ $letter->employee->employee_code }}</code>
                        </td>
                        <td>{{ $letter->typeLabel() }}</td>
                        <td class="text-xs text-gray-600">{{ $letter->issued_at->format('d M Y') }}</td>
                        <td class="text-xs text-gray-600">{{ $letter->issuer?->name ?? '—' }}</td>
                        <td>
                            @if($letter->isVoided())
                                <span class="erp-badge bg-red-100 text-red-700 text-[10px]">Voided</span>
                            @else
                                <span class="erp-badge bg-green-100 text-green-700 text-[10px]">Active</span>
                            @endif
                        </td>
                        <td class="text-right">
                            <a href="{{ route('admin.hrm.letters.show', $letter) }}" class="erp-btn-sm-secondary">View</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-gray-400 py-8">No letters issued yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($letters->hasPages())
        <div class="erp-panel-body border-t border-erp-border">{{ $letters->links() }}</div>
    @endif
</div>
@endsection
