@extends('layouts.admin')
@section('title', 'Statutory Registers')
@section('admin-content')
@include('partials.erp.page-header', ['title' => 'Statutory Registers', 'actions' => '<a href="' . route('admin.hrm.compliance.hub') . '" class="erp-btn-secondary">← Hub</a>'])
<div class="erp-panel mb-4"><div class="erp-panel-body">
<form method="GET" class="flex flex-wrap gap-3 items-end">
    <div><label class="erp-form-label">Factory</label><select name="factory_id" class="erp-input !text-xs">@foreach($factories as $id=>$n)<option value="{{ $id }}" {{ (int)$factoryId===(int)$id?'selected':'' }}>{{ $n }}</option>@endforeach</select></div>
    <div><label class="erp-form-label">Year</label><input type="number" name="year" value="{{ $year }}" class="erp-input !text-xs w-24"></div>
    <div><label class="erp-form-label">Month</label><input type="number" name="month" min="1" max="12" value="{{ $month }}" class="erp-input !text-xs w-20"></div>
    <button type="submit" class="erp-btn-primary">Filter</button>
</form></div></div>
<div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
    @foreach(['attendance'=>'Attendance Register','wage'=>'Wage Register','leave'=>'Leave Register','ot'=>'OT Register'] as $type=>$label)
        <div class="erp-panel"><div class="erp-panel-body flex items-center justify-between">
            <div><h3 class="text-sm font-semibold">{{ $label }}</h3><p class="text-[11px] text-gray-500 mt-1">BD format CSV export</p></div>
            @if($factoryId)
            <a href="{{ route('admin.hrm.compliance.registers.export', $type) }}?factory_id={{ $factoryId }}&year={{ $year }}&month={{ $month }}" class="erp-btn-secondary !text-xs">Export CSV</a>
            @endif
        </div></div>
    @endforeach
</div>
@endsection
