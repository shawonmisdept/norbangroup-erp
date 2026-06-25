@extends('layouts.admin')
@section('title', 'Working Hour Limits')
@section('admin-content')
@include('partials.erp.page-header', ['title' => 'Working Hour Limit Violations', 'actions' => '<a href="'.route('admin.hrm.compliance.hub').'" class="erp-btn-secondary">← Hub</a>'])
<div class="erp-panel mb-4"><div class="erp-panel-body space-y-3">
<form method="GET" class="flex flex-wrap gap-3 items-end">
    <div><label class="erp-form-label">Factory</label><select name="factory_id" class="erp-input !text-xs">@foreach($factories as $id=>$n)<option value="{{ $id }}" {{ (int)$factoryId===(int)$id?'selected':'' }}>{{ $n }}</option>@endforeach</select></div>
    <div><label class="erp-form-label">Year</label><input type="number" name="year" value="{{ $year }}" class="erp-input !text-xs w-24"></div>
    <div><label class="erp-form-label">Month</label><input type="number" name="month" value="{{ $month }}" class="erp-input !text-xs w-20"></div>
    <button type="submit" class="erp-btn-primary">Filter</button>
</form>
@if($canManage && $factoryId)
<form method="POST" action="{{ route('admin.hrm.compliance.working-hours.notify') }}" class="flex gap-2 items-end">
    @csrf
    <input type="hidden" name="factory_id" value="{{ $factoryId }}">
    <input type="hidden" name="year" value="{{ $year }}">
    <input type="hidden" name="month" value="{{ $month }}">
    <button type="submit" class="erp-btn-secondary !text-xs">Notify HR of Violations</button>
</form>
@endif
</div></div>
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
    <div class="erp-panel"><div class="erp-panel-header text-xs font-semibold">Daily Violations ({{ count($dailyViolations) }})</div><div class="overflow-x-auto"><table class="erp-table w-full text-xs">
    <thead><tr><th>Employee</th><th>Date</th><th>Hours</th><th>Limit</th></tr></thead>
    <tbody>@forelse($dailyViolations as $v)<tr><td>{{ $v['employee']?->name ?? $v['employee']?->employee_code }}</td><td>{{ $v['date'] }}</td><td>{{ $v['hours'] }}</td><td>{{ $v['limit'] }}</td></tr>@empty<tr><td colspan="4" class="text-center py-6 text-gray-400">None</td></tr>@endforelse</tbody></table></div></div>
    <div class="erp-panel"><div class="erp-panel-header text-xs font-semibold">Weekly Violations ({{ count($weeklyViolations) }})</div><div class="overflow-x-auto"><table class="erp-table w-full text-xs">
    <thead><tr><th>Employee</th><th>Week Start</th><th>Hours</th><th>Limit</th></tr></thead>
    <tbody>@forelse($weeklyViolations as $v)<tr><td>{{ $v['employee']?->name ?? $v['employee']?->employee_code }}</td><td>{{ $v['week_start'] }}</td><td>{{ $v['hours'] }}</td><td>{{ $v['limit'] }}</td></tr>@empty<tr><td colspan="4" class="text-center py-6 text-gray-400">None</td></tr>@endforelse</tbody></table></div></div>
</div>
@endsection
