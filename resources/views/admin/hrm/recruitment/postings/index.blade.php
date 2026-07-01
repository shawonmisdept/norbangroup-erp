@extends('layouts.admin')

@section('title', 'Job Postings')

@section('breadcrumbs')
    <a href="{{ route('admin.hrm.dashboard') }}" class="hover:text-brand">HRM</a>
    <span>/</span>
    <span class="text-gray-800 font-medium">Job Postings</span>
@endsection

@section('admin-content')
@include('partials.erp.page-header', [
    'title' => 'Job Postings',
    'subtitle' => 'Manage vacancies for the careers portal',
    'actions' => ($canManage
        ? '<a href="' . route('admin.hrm.recruitment.postings.export', $filters) . '" class="erp-btn-secondary !py-2 !px-4 text-xs">Export CSV</a>'
            . ' <a href="' . route('admin.hrm.recruitment.postings.bulk.create') . '" class="erp-btn-secondary !py-2 !px-4 text-xs">Bulk Create</a>'
            . ' <a href="' . route('admin.hrm.recruitment.postings.create') . '" class="erp-btn-primary !py-2 !px-4 text-xs">New Posting</a>'
        : '<a href="' . route('admin.hrm.recruitment.postings.export', $filters) . '" class="erp-btn-secondary !py-2 !px-4 text-xs">Export CSV</a>'),
])

<div class="erp-panel mb-4">
    <div class="erp-panel-body">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <div class="flex-1 min-w-[160px]">
                <label class="erp-form-label">Search</label>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" class="erp-input !text-xs" placeholder="Job title…">
            </div>
            @if(count($factories) > 1)
                <div class="w-40">
                    <label class="erp-form-label">Factory</label>
                    <select name="factory_id" class="erp-input !text-xs">
                        <option value="">All</option>
                        @foreach($factories as $id => $name)
                            <option value="{{ $id }}" {{ (string) ($filters['factory_id'] ?? '') === (string) $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="w-36">
                <label class="erp-form-label">Status</label>
                <select name="status" class="erp-input !text-xs">
                    <option value="">All</option>
                    @foreach($statuses as $val => $label)
                        <option value="{{ $val }}" {{ ($filters['status'] ?? '') === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            @if($filterOpts['departments'] !== [])
                <div class="w-40">
                    <label class="erp-form-label">Department</label>
                    <select name="department_id" class="erp-input !text-xs">
                        <option value="">All</option>
                        @foreach($filterOpts['departments'] as $id => $label)
                            <option value="{{ $id }}" {{ (string) ($filters['department_id'] ?? '') === (string) $id ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            @if($filterOpts['designations'] !== [])
                <div class="w-40">
                    <label class="erp-form-label">Designation</label>
                    <select name="designation_id" class="erp-input !text-xs">
                        <option value="">All</option>
                        @foreach($filterOpts['designations'] as $id => $label)
                            <option value="{{ $id }}" {{ (string) ($filters['designation_id'] ?? '') === (string) $id ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            @if($filterOpts['workerCategories'] !== [])
                <div class="w-36">
                    <label class="erp-form-label">Category</label>
                    <select name="worker_category_id" class="erp-input !text-xs">
                        <option value="">All</option>
                        @foreach($filterOpts['workerCategories'] as $id => $label)
                            <option value="{{ $id }}" {{ (string) ($filters['worker_category_id'] ?? '') === (string) $id ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            @if($filterOpts['shiftTypes'] !== [])
                <div class="w-32">
                    <label class="erp-form-label">Shift</label>
                    <select name="shift_type" class="erp-input !text-xs">
                        <option value="">All</option>
                        @foreach($filterOpts['shiftTypes'] as $val => $label)
                            <option value="{{ $val }}" {{ ($filters['shift_type'] ?? '') === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <label class="inline-flex items-center gap-2 text-xs text-gray-600 pb-2">
                <input type="checkbox" name="closing_soon" value="1" class="rounded border-gray-300" {{ !empty($filters['closing_soon']) ? 'checked' : '' }}>
                Closing soon
            </label>
            <label class="inline-flex items-center gap-2 text-xs text-gray-600 pb-2">
                <input type="checkbox" name="has_applications" value="1" class="rounded border-gray-300" {{ !empty($filters['has_applications']) ? 'checked' : '' }}>
                Has applications
            </label>
            <button type="submit" class="erp-btn-secondary !py-2 !px-4 text-xs">Filter</button>
        </form>
    </div>
</div>

<div class="erp-panel">
    <div class="overflow-x-auto">
        <table class="erp-table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Factory</th>
                    <th>Slots</th>
                    <th>Apps</th>
                    <th>Views</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($postings as $row)
                    @php
                        $badge = match($row->status) {
                            'open' => 'bg-green-100 text-green-800',
                            'closed' => 'bg-gray-100 text-gray-600',
                            'pending_approval' => 'bg-blue-100 text-blue-800',
                            default => 'bg-amber-100 text-amber-800',
                        };
                    @endphp
                    <tr>
                        <td class="font-medium text-sm">
                            {{ $row->title }}
                            @if($row->is_internal)<span class="erp-badge bg-purple-100 text-purple-700 ml-1">Internal</span>@endif
                        </td>
                        <td class="text-xs text-gray-600">{{ $row->factory?->name }}</td>
                        <td class="text-xs">{{ $row->openings_filled }}/{{ $row->slots }}</td>
                        <td class="text-xs">{{ $row->applications_count }}</td>
                        <td class="text-xs tabular-nums">{{ number_format($row->page_views) }}</td>
                        <td><span class="erp-badge {{ $badge }}">{{ $row->statusLabel() }}</span></td>
                        <td class="text-right whitespace-nowrap">
                            @include('partials.erp.table-actions', [
                                'viewUrl' => route('admin.hrm.recruitment.postings.show', $row),
                                'editUrl' => $canManage ? route('admin.hrm.recruitment.postings.edit', $row) : null,
                                'destroyUrl' => ($canManage && ($row->applications_count ?? 0) === 0) ? route('admin.hrm.recruitment.postings.destroy', $row) : null,
                                'destroyConfirm' => 'Delete this job posting permanently?',
                            ])
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-gray-400 py-8">No job postings yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($postings->hasPages())
        <div class="erp-panel-body border-t border-erp-border">{{ $postings->links() }}</div>
    @endif
</div>
@endsection
