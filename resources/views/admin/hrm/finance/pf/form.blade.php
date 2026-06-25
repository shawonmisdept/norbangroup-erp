@extends('layouts.admin')
@section('title', 'Open PF Account')
@section('admin-content')
@include('partials.erp.page-header', ['title' => 'Open PF Account', 'actions' => '<a href="' . route('admin.hrm.finance.pf.index') . '" class="erp-btn-secondary">← Back</a>'])
<div class="erp-panel max-w-lg"><form method="POST" action="{{ route('admin.hrm.finance.pf.store') }}" class="erp-panel-body space-y-4">@csrf
    <div><label class="erp-form-label">Factory</label><select name="factory_id" class="erp-input" required>@foreach($factories as $id=>$n)<option value="{{ $id }}">{{ $n }}</option>@endforeach</select></div>
    <div><label class="erp-form-label">Employee</label><select name="employee_id" class="erp-input" required><option value="">Select employee</option>@foreach($employees as $id=>$n)<option value="{{ $id }}">{{ $n }}</option>@endforeach</select></div>
    <div class="grid grid-cols-2 gap-4">
        <div><label class="erp-form-label">Employee %</label><input type="number" step="0.01" name="employee_rate_pct" value="{{ old('employee_rate_pct', $account->employee_rate_pct) }}" class="erp-input" required></div>
        <div><label class="erp-form-label">Employer %</label><input type="number" step="0.01" name="employer_rate_pct" value="{{ old('employer_rate_pct', $account->employer_rate_pct) }}" class="erp-input" required></div>
    </div>
    <div><label class="erp-form-label">Opened At</label><input type="date" name="opened_at" value="{{ old('opened_at', $account->opened_at?->format('Y-m-d')) }}" class="erp-input"></div>
    <button type="submit" class="erp-btn-primary">Open Account</button>
</form></div>
@endsection
