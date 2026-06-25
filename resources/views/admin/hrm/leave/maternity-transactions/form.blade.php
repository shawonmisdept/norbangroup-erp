@extends('layouts.admin')
@section('title', 'Add Maternity Case')
@section('admin-content')
@include('partials.erp.page-header', ['title' => 'Add Maternity Benefit Case', 'actions' => '<a href="'.route('admin.hrm.leave.maternity-transactions.index').'" class="erp-btn-secondary">← Back</a>'])
<div class="erp-panel max-w-xl"><div class="erp-panel-body"><form method="POST" action="{{ route('admin.hrm.leave.maternity-transactions.store') }}" class="space-y-3">@csrf
<div><label class="erp-form-label">Employee (female, active)</label><select name="employee_id" required class="erp-input !text-xs">@foreach($employees as $e)<option value="{{ $e->id }}">{{ $e->employee_code }} — {{ $e->name }}</option>@endforeach</select></div>
<div><label class="erp-form-label">Expected Delivery Date</label><input type="date" name="expected_delivery_date" class="erp-input !text-xs"></div>
<div><label class="erp-form-label">Leave Start</label><input type="date" name="start_date" required class="erp-input !text-xs"></div>
<div><label class="erp-form-label">Leave End</label><input type="date" name="end_date" required class="erp-input !text-xs"></div>
<div><label class="erp-form-label">Notes</label><textarea name="notes" rows="2" class="erp-input !text-xs"></textarea></div>
<button type="submit" class="erp-btn-primary">Create & Link Leave</button></form></div></div>
@endsection
