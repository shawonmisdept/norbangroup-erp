@extends('layouts.admin')
@section('title', 'Flag Proxy Punch')
@section('admin-content')
@include('partials.erp.page-header', ['title' => 'Flag Proxy Punch', 'actions' => '<a href="' . route('admin.hrm.rmg.proxy-punch.index') . '" class="erp-btn-secondary">← Back</a>'])
<div class="erp-panel max-w-lg">
    <form method="POST" action="{{ route('admin.hrm.rmg.proxy-punch.store') }}" class="erp-panel-body space-y-4">
        @csrf
        <div><label class="erp-form-label">Factory</label><select name="factory_id" class="erp-input" required>@foreach($factories as $id=>$n)<option value="{{ $id }}">{{ $n }}</option>@endforeach</select></div>
        <div><label class="erp-form-label">Raw Punch</label><select name="attendance_raw_punch_id" class="erp-input" required><option value="">Select</option>@foreach($punches as $id=>$n)<option value="{{ $id }}">{{ $n }}</option>@endforeach</select></div>
        <div><label class="erp-form-label">Employee (optional)</label><select name="employee_id" class="erp-input"><option value="">—</option>@foreach($employees as $id=>$n)<option value="{{ $id }}">{{ $n }}</option>@endforeach</select></div>
        <div><label class="erp-form-label">Reason</label><textarea name="reason" rows="2" class="erp-input">{{ old('reason') }}</textarea></div>
        <button type="submit" class="erp-btn-primary">Record Flag</button>
    </form>
</div>
@endsection
