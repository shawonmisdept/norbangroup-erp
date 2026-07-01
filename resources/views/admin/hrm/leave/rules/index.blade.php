@extends('layouts.admin')
@section('title', 'Leave Rules')
@section('admin-content')
@include('partials.erp.page-header', ['title' => 'Leave Rules', 'subtitle' => 'Eligibility by category, tenure, gender', 'actions' => auth()->user()->canManageLeaveSubmodule('rules') ? '<a href="' . route('admin.hrm.leave.rules.create') . '" class="erp-btn-primary !py-2 !px-4 text-xs">Add Rule</a>' : ''])
@include('admin.hrm.partials.submodule-nav', ['section' => 'leave', 'current' => 'rules'])
<div class="erp-panel overflow-hidden"><table class="erp-table"><thead><tr><th>Factory</th><th>Leave Type</th><th>Category</th><th>Tenure</th><th>Gender</th><th></th></tr></thead><tbody>
@forelse($rules as $rule)
<tr><td class="text-xs">{{ $rule->factory?->name }}</td><td>{{ $rule->leaveType?->name }}</td><td class="text-xs">{{ $rule->workerCategory?->name ?? 'Any' }}</td><td class="text-xs">{{ $rule->min_tenure_days }}d</td><td class="text-xs">{{ $rule->gender ? ucfirst($rule->gender) : 'Any' }}</td>
<td class="text-right">@if(auth()->user()->canManageLeaveSubmodule('rules'))@include('partials.erp.table-actions', ['editUrl' => route('admin.hrm.leave.rules.edit', $rule), 'destroyUrl' => route('admin.hrm.leave.rules.destroy', $rule), 'destroyConfirm' => 'Delete this leave rule?'])@endif</td></tr>
@empty<tr><td colspan="6" class="text-center py-8 text-gray-400">No rules yet.</td></tr>@endforelse
</tbody></table>@if($rules->hasPages())<div class="px-4 py-3 border-t">{{ $rules->links() }}</div>@endif</div>
@endsection
