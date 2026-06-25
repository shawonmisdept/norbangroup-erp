@extends('layouts.admin')
@section('title', 'Maternity Rules')
@section('admin-content')
@include('partials.erp.page-header', ['title'=>'Maternity Rules','actions'=>auth()->user()->canManageLeaveSubmodule('maternity-rules')?'<a href="'.route('admin.hrm.leave.maternity-rules.create').'" class="erp-btn-primary !py-2 !px-4 text-xs">Add Rule</a>':''])
@include('admin.hrm.partials.submodule-nav', ['section'=>'leave','current'=>'maternity-rules'])
<div class="erp-panel overflow-hidden"><table class="erp-table"><thead><tr><th>Factory</th><th>Total Weeks</th><th>Paid</th><th>Unpaid</th><th>Min Service</th><th></th></tr></thead><tbody>
@forelse($rules as $rule)<tr><td>{{ $rule->factory?->name }}</td><td>{{ $rule->total_weeks }}</td><td>{{ $rule->paid_weeks }}</td><td>{{ $rule->unpaid_weeks }}</td><td>{{ $rule->min_service_days }}d</td>
<td class="text-right">@if(auth()->user()->canManageLeaveSubmodule('maternity-rules'))@include('partials.erp.table-actions', ['editUrl' => route('admin.hrm.leave.maternity-rules.edit', $rule)])@endif</td></tr>
@empty<tr><td colspan="6" class="text-center py-8 text-gray-400">No maternity rules.</td></tr>@endforelse</tbody></table></div>
@endsection
