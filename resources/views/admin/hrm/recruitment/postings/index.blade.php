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
    'actions' => ($canManage ? '<a href="' . route('admin.hrm.recruitment.postings.create') . '" class="erp-btn-primary !py-2 !px-4 text-xs">New Posting</a>' : ''),
])

<div class="erp-panel mb-4">
    <div class="erp-panel-body">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <div class="flex-1 min-w-[180px]">
                <label class="erp-form-label">Search</label>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" class="erp-input !text-xs" placeholder="Job title…">
            </div>
            @if(count($factories) > 1)
                <div class="w-44">
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
                    <th>Applications</th>
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
                            default => 'bg-amber-100 text-amber-800',
                        };
                    @endphp
                    <tr>
                        <td class="font-medium text-sm">{{ $row->title }}</td>
                        <td class="text-xs text-gray-600">{{ $row->factory?->name }}</td>
                        <td class="text-xs">{{ $row->openings_filled }}/{{ $row->slots }}</td>
                        <td class="text-xs">{{ $row->applications_count }}</td>
                        <td><span class="erp-badge {{ $badge }}">{{ $row->statusLabel() }}</span></td>
                        <td class="text-right">
                            <a href="{{ route('admin.hrm.recruitment.postings.show', $row) }}" class="erp-btn-sm-secondary">View</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-gray-400 py-8">No job postings yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($postings->hasPages())
        <div class="erp-panel-body border-t border-erp-border">{{ $postings->links() }}</div>
    @endif
</div>
@endsection
