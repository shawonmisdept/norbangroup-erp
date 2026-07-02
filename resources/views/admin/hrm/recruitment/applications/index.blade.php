@extends('layouts.admin')

@section('title', 'Recruitment Applications')

@section('breadcrumbs')
    <a href="{{ route('admin.hrm.dashboard') }}" class="hover:text-brand">HRM</a>
    <span>/</span>
    <span class="text-gray-800 font-medium">Applications</span>
@endsection

@section('admin-content')
@include('partials.erp.page-header', [
    'title' => 'Recruitment Applications',
    'subtitle' => 'Online & manual candidate applications',
    'actions' => ($canManage ? '<a href="' . route('admin.hrm.recruitment.applications.create') . '" class="erp-btn-primary !py-2 !px-4 text-xs">Manual Entry</a> ' : '')
        . '<a href="' . route('admin.hrm.recruitment.applications.export', $filters) . '" class="erp-btn-secondary !py-2 !px-4 text-xs">Export CSV</a>',
])

<div class="grid grid-cols-3 gap-3 mb-4 max-w-lg">
    <div class="erp-panel"><div class="erp-panel-body"><p class="text-xl font-bold text-blue-600">{{ $stats['applied'] }}</p><p class="text-xs text-gray-500 uppercase">New</p></div></div>
    <div class="erp-panel"><div class="erp-panel-body"><p class="text-xl font-bold text-amber-600">{{ $stats['screening'] }}</p><p class="text-xs text-gray-500 uppercase">Screening</p></div></div>
    <div class="erp-panel"><div class="erp-panel-body"><p class="text-xl font-bold text-purple-600">{{ $stats['interview'] }}</p><p class="text-xs text-gray-500 uppercase">Interview</p></div></div>
</div>

<div class="erp-panel mb-4">
    <div class="erp-panel-body">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <div class="flex-1 min-w-[160px]">
                <label class="erp-form-label">Search</label>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="App no, name, phone…" class="erp-input !text-xs">
            </div>
            <div class="w-40">
                <label class="erp-form-label">Status</label>
                <select name="status" class="erp-input !text-xs">
                    <option value="">All</option>
                    @foreach($statuses as $val => $label)
                        <option value="{{ $val }}" {{ ($filters['status'] ?? '') === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-36">
                <label class="erp-form-label">Source</label>
                <select name="source" class="erp-input !text-xs">
                    <option value="">All</option>
                    @foreach($sources as $val => $label)
                        <option value="{{ $val }}" {{ ($filters['source'] ?? '') === $val ? 'selected' : '' }}>{{ $label }}</option>
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
                    <th>Application</th>
                    <th>Candidate</th>
                    <th>Job</th>
                    <th>Source</th>
                    <th>Status</th>
                    <th>Offer Response</th>
                    <th>Applied</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($applications as $row)
                    <tr>
                        <td><code class="text-xs">{{ $row->application_no }}</code></td>
                        <td>
                            <p class="font-medium text-sm">{{ $row->name }}</p>
                            <p class="text-xs text-gray-500">{{ $row->phone }}</p>
                        </td>
                        <td class="text-xs">{{ $row->jobPosting?->title }}</td>
                        <td class="text-xs">{{ $row->sourceLabel() }}</td>
                        <td><span class="erp-badge bg-gray-100 text-gray-700 text-[10px]">{{ $row->statusLabel() }}</span></td>
                        <td class="text-xs">
                            @if($row->latestOffer)
                                @include('admin.hrm.recruitment.partials.offer-response-badge', ['letter' => $row->latestOffer])
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="text-xs text-gray-600">{{ $row->applied_at->format('d M Y') }}</td>
                        <td class="text-right">
                            <div class="inline-flex items-center gap-1">
                                <a href="{{ route('admin.hrm.recruitment.applications.show', $row) }}" class="erp-btn-sm-secondary">View</a>
                                @if($canManage && $row->canEdit())
                                    <a href="{{ route('admin.hrm.recruitment.applications.edit', $row) }}" class="erp-btn-sm-secondary">Edit</a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-gray-400 py-8">No applications yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($applications->hasPages())
        <div class="erp-panel-body border-t border-erp-border">{{ $applications->links() }}</div>
    @endif
</div>
@endsection
