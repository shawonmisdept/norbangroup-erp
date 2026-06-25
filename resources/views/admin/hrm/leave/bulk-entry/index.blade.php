@extends('layouts.admin')
@section('title', 'Leave Entry Bulk')
@section('admin-content')
@include('partials.erp.page-header', [
    'title' => 'Leave Entry Bulk',
    'subtitle' => 'CSV bulk leave entry — auto-approved and synced to attendance',
    'actions' => '<a href="' . route('admin.hrm.leave.bulk-entry.template') . '" class="erp-btn-secondary">Download Template</a>',
])
@include('admin.hrm.partials.submodule-nav', ['section' => 'leave', 'current' => 'bulk-entry'])

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
    <div class="erp-panel">
        <div class="erp-panel-head"><h2 class="text-xs font-semibold uppercase tracking-wide text-gray-700">Upload CSV</h2></div>
        <div class="erp-panel-body">
            @if(auth()->user()->canManageLeaveSubmodule('bulk-entry'))
            <form method="POST" action="{{ route('admin.hrm.leave.bulk-entry.store') }}" enctype="multipart/form-data" class="space-y-3">
                @csrf
                <div>
                    <label class="erp-form-label">Factory</label>
                    <select name="factory_id" required class="erp-input !text-xs">
                        @foreach($factories as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="erp-form-label">CSV File</label>
                    <input type="file" name="file" accept=".csv,text/csv" required class="erp-input !text-xs">
                </div>
                <button type="submit" class="erp-btn-primary">Upload & Import</button>
            </form>
            @else
            <p class="text-sm text-gray-500">You do not have permission to import leave records.</p>
            @endif
        </div>
    </div>

    <div class="erp-panel">
        <div class="erp-panel-head"><h2 class="text-xs font-semibold uppercase tracking-wide text-gray-700">Column Guide</h2></div>
        <div class="erp-panel-body text-xs text-gray-600 space-y-2">
            <p><code class="text-[10px] bg-gray-100 px-1">employee_code</code> — required, must exist in selected factory</p>
            <p><code class="text-[10px] bg-gray-100 px-1">leave_type_code</code> — required, e.g. <strong>LVT-CL001</strong></p>
            <p><code class="text-[10px] bg-gray-100 px-1">start_date</code> / <code class="text-[10px] bg-gray-100 px-1">end_date</code> — YYYY-MM-DD format</p>
            <p><code class="text-[10px] bg-gray-100 px-1">reason</code> — optional note</p>
            <p class="text-gray-400 pt-2">Each row creates an <strong>approved</strong> leave and updates attendance. Weekends and factory holidays are excluded from day count. Paid leave balance is deducted immediately.</p>
        </div>
    </div>
</div>
@endsection
