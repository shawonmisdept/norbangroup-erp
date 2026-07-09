@extends('layouts.admin')
@section('title', 'Working Hour Limits')
@section('admin-content')
@include('partials.erp.page-header', ['title' => 'Working Hour Limit Violations', 'actions' => '<a href="'.route('admin.hrm.compliance.hub').'" class="erp-btn-secondary">← Hub</a>'])

<div class="erp-panel mb-4">
    <div class="erp-panel-body">
        <div class="erp-filter-bar">
            <form method="GET" class="flex flex-wrap items-end gap-3 flex-1 min-w-0">
                <div class="erp-filter-field">
                    <label class="erp-form-label">Factory</label>
                    <select name="factory_id" class="erp-input !text-xs">
                        @foreach($factories as $id => $n)
                            <option value="{{ $id }}" {{ (int) $factoryId === (int) $id ? 'selected' : '' }}>{{ $n }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="erp-filter-field w-24 sm:w-auto">
                    <label class="erp-form-label">Year</label>
                    <input type="number" name="year" value="{{ $year }}" class="erp-input !text-xs w-full">
                </div>
                <div class="erp-filter-field w-20 sm:w-auto">
                    <label class="erp-form-label">Month</label>
                    <input type="number" name="month" value="{{ $month }}" min="1" max="12" class="erp-input !text-xs w-full">
                </div>
                <div class="erp-filter-actions">
                    <button type="submit" class="erp-btn-primary">Filter</button>
                </div>
            </form>

            @if($canManage && $factoryId)
                <form method="POST" action="{{ route('admin.hrm.compliance.working-hours.notify') }}" class="erp-filter-actions"
                      data-confirm="{{ $violationTotal > 0 ? 'Send working-hours violation notifications to HR?' : 'No violations in this period. Notify anyway?' }}"
                      data-confirm-variant="warning">
                    @csrf
                    <input type="hidden" name="factory_id" value="{{ $factoryId }}">
                    <input type="hidden" name="year" value="{{ $year }}">
                    <input type="hidden" name="month" value="{{ $month }}">
                    <button type="submit" class="erp-btn-secondary !text-xs" @disabled($violationTotal === 0)>
                        Notify HR ({{ $violationTotal }})
                    </button>
                </form>
            @endif
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
    <div class="erp-panel">
        <div class="erp-panel-header text-xs font-semibold">Daily Violations ({{ count($dailyViolations) }})</div>
        <div class="overflow-x-auto">
            <table class="erp-table w-full text-xs">
                <thead><tr><th>Employee</th><th>Date</th><th>Hours</th><th>Limit</th></tr></thead>
                <tbody>
                    @forelse($dailyViolations as $v)
                        <tr>
                            <td>{{ $v['employee']?->name ?? $v['employee']?->employee_code }}</td>
                            <td>{{ $v['date'] }}</td>
                            <td>{{ $v['hours'] }}</td>
                            <td>{{ $v['limit'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center py-6 text-gray-400">None</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="erp-panel">
        <div class="erp-panel-header text-xs font-semibold">Weekly Violations ({{ count($weeklyViolations) }})</div>
        <div class="overflow-x-auto">
            <table class="erp-table w-full text-xs">
                <thead><tr><th>Employee</th><th>Week Start</th><th>Hours</th><th>Limit</th></tr></thead>
                <tbody>
                    @forelse($weeklyViolations as $v)
                        <tr>
                            <td>{{ $v['employee']?->name ?? $v['employee']?->employee_code }}</td>
                            <td>{{ $v['week_start'] }}</td>
                            <td>{{ $v['hours'] }}</td>
                            <td>{{ $v['limit'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center py-6 text-gray-400">None</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
