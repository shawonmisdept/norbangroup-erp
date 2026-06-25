@extends('layouts.admin')
@section('title', 'Leave Policies')
@section('admin-content')
@include('partials.erp.page-header', ['title' => 'Leave Policies', 'subtitle' => 'Factory-wise entitlement per leave type', 'actions' => auth()->user()->canManageLeaveSubmodule('policies') ? '<a href="' . route('admin.hrm.leave.policies.create') . '" class="erp-btn-primary !py-2 !px-4 text-xs">Add Policy</a>' : ''])
@include('admin.hrm.partials.submodule-nav', ['section' => 'leave', 'current' => 'policies'])
<div class="erp-panel overflow-hidden">
<table class="erp-table">
<thead><tr><th>Factory</th><th>Leave Type</th><th>Days/Year</th><th>Notice</th><th>Active</th><th></th></tr></thead>
<tbody>
@forelse($policies as $policy)
<tr>
<td class="text-xs">{{ $policy->factory?->name }}</td>
<td>{{ $policy->leaveType?->name }}</td>
<td class="tabular-nums">{{ number_format($policy->days_per_year, 1) }}</td>
<td class="text-xs">{{ $policy->min_days_notice }} days</td>
<td><span class="erp-badge {{ $policy->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }}">{{ $policy->is_active ? 'Yes' : 'No' }}</span></td>
<td class="text-right">@if(auth()->user()->canManageLeaveSubmodule('policies'))@include('partials.erp.table-actions', ['editUrl' => route('admin.hrm.leave.policies.edit', $policy)])@endif</td>
</tr>
@empty<tr><td colspan="6" class="text-center py-8 text-gray-400">No policies yet.</td></tr>@endforelse
</tbody></table>
@if($policies->hasPages())<div class="px-4 py-3 border-t">{{ $policies->links() }}</div>@endif
</div>
@endsection
