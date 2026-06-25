@extends('layouts.admin')
@section('title', 'Buyer Audit Export')
@section('admin-content')
@include('partials.erp.page-header', [
    'title' => 'Buyer Audit Export',
    'subtitle' => 'Attendance & wage register pack for buyer compliance audits',
    'actions' => '<a href="' . route('admin.hrm.rmg.hub') . '" class="erp-btn-secondary">← Hub</a>',
])
<div class="erp-panel max-w-lg">
    <form method="GET" action="{{ route('admin.hrm.rmg.buyer-audit-export.export') }}" class="erp-panel-body space-y-4">
        <div><label class="erp-form-label">Factory</label><select name="factory_id" class="erp-input" required>@foreach($factories as $id=>$n)<option value="{{ $id }}">{{ $n }}</option>@endforeach</select></div>
        <div class="grid grid-cols-2 gap-4">
            <div><label class="erp-form-label">Year</label><input type="number" name="year" value="{{ $year }}" class="erp-input" required></div>
            <div><label class="erp-form-label">Month</label><input type="number" name="month" value="{{ $month }}" class="erp-input" min="1" max="12" required></div>
        </div>
        <button type="submit" class="erp-btn-primary">Download CSV Pack</button>
    </form>
</div>
@endsection
