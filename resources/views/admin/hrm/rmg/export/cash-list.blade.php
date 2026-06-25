@extends('layouts.admin')
@section('title', 'Cash Payment List')
@section('admin-content')
@include('partials.erp.page-header', [
    'title' => 'Cash Payment List',
    'subtitle' => 'Export net-pay cash workers by line for a payroll period',
    'actions' => '<a href="' . route('admin.hrm.rmg.hub') . '" class="erp-btn-secondary">← Hub</a>',
])
<div class="erp-panel max-w-lg">
    <form method="GET" action="{{ route('admin.hrm.rmg.cash-list.export') }}" class="erp-panel-body space-y-4">
        <div><label class="erp-form-label">Payroll Period</label>
        <select name="payroll_period_id" class="erp-input" required>
            <option value="">Select period</option>
            @foreach($periods as $period)
                <option value="{{ $period->id }}">{{ $period->factory?->name }} — {{ $period->periodLabel() }}</option>
            @endforeach
        </select></div>
        <button type="submit" class="erp-btn-primary">Download CSV</button>
    </form>
</div>
@endsection
