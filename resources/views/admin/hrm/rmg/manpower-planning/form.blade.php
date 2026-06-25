@extends('layouts.admin')
@section('title', 'New Manpower Plan')
@section('admin-content')
@include('partials.erp.page-header', ['title' => 'New Manpower Plan', 'actions' => '<a href="' . route('admin.hrm.rmg.manpower-planning.index') . '" class="erp-btn-secondary">← Back</a>'])
<div class="erp-panel max-w-lg">
    <form method="POST" action="{{ route('admin.hrm.rmg.manpower-planning.store') }}" class="erp-panel-body space-y-4">
        @csrf
        <div><label class="erp-form-label">Factory</label><select name="factory_id" class="erp-input" required>@foreach($factories as $id=>$n)<option value="{{ $id }}">{{ $n }}</option>@endforeach</select></div>
        <div><label class="erp-form-label">Line</label><select name="line_id" class="erp-input" required>@foreach($lines as $id=>$n)<option value="{{ $id }}">{{ $n }}</option>@endforeach</select></div>
        <div><label class="erp-form-label">Plan Date</label><input type="date" name="plan_date" value="{{ old('plan_date', $plan->plan_date?->toDateString()) }}" class="erp-input" required></div>
        <div><label class="erp-form-label">Required Headcount</label><input type="number" name="required_count" value="{{ old('required_count', $plan->required_count) }}" class="erp-input" min="0" required></div>
        <div><label class="erp-form-label">Notes</label><textarea name="notes" rows="2" class="erp-input">{{ old('notes') }}</textarea></div>
        <button type="submit" class="erp-btn-primary">Save Plan</button>
    </form>
</div>
@endsection
