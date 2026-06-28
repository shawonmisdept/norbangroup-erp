@extends('layouts.admin')
@section('title', 'Allocation Process')
@section('admin-content')
@include('partials.erp.page-header', ['title'=>'Allocation Process','subtitle'=>'Run yearly leave balance allocation from policies'])
@include('admin.hrm.partials.submodule-nav', ['section'=>'leave','current'=>'allocation'])
<div class="grid grid-cols-3 gap-3 mb-4">@foreach(['policies'=>'Active Policies','balances'=>'Balances '.$year,'employees'=>'Active Employees'] as $k=>$l)<div class="erp-panel"><div class="erp-panel-body"><p class="text-xl font-bold">{{ $stats[$k] }}</p><p class="text-xs text-gray-500 uppercase">{{ $l }}</p></div></div>@endforeach</div>
@if(auth()->user()->canManageLeaveSubmodule('allocation'))
<div class="erp-panel max-w-lg"><div class="erp-panel-body"><form method="POST" action="{{ route('admin.hrm.leave.allocation.run') }}" class="space-y-3"
      data-confirm="Run allocation for all active employees?"
      data-confirm-variant="warning"
      data-confirm-ok="Yes, run allocation">@csrf
<div><label class="erp-form-label">Factory</label><select name="factory_id" required class="erp-input !text-xs">@foreach($factories as $id=>$n)<option value="{{ $id }}">{{ $n }}</option>@endforeach</select></div>
<div><label class="erp-form-label">Year</label><input type="number" name="year" value="{{ $year }}" required class="erp-input !text-xs"></div>
<button type="submit" class="erp-btn-primary">Run Allocation</button>
</form></div></div>@endif
@endsection
