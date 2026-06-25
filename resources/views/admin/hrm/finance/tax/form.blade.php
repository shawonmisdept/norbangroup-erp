@extends('layouts.admin')
@section('title', $taxYear->exists ? 'Edit Tax Year' : 'New Tax Year')
@section('admin-content')
@include('partials.erp.page-header', [
    'title' => $taxYear->exists ? 'Edit Assessment Year' : 'New Assessment Year',
    'actions' => '<a href="' . route('admin.hrm.finance.tax.index') . '" class="erp-btn-secondary">← Back</a>',
])
<div class="erp-panel max-w-3xl">
    <form method="POST"
          action="{{ $taxYear->exists ? route('admin.hrm.finance.tax.update', $taxYear) : route('admin.hrm.finance.tax.store') }}"
          class="erp-panel-body space-y-4">
        @csrf
        @if($taxYear->exists) @method('PUT') @endif
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @if(!$taxYear->exists)
            <div>
                <label class="erp-form-label">Factory</label>
                <select name="factory_id" class="erp-input" required>
                    @foreach($factories as $id => $n)
                        <option value="{{ $id }}" {{ old('factory_id') == $id ? 'selected' : '' }}>{{ $n }}</option>
                    @endforeach
                </select>
            </div>
            @else
            <div>
                <label class="erp-form-label">Factory</label>
                <p class="erp-input bg-gray-50">{{ $taxYear->factory?->name }}</p>
            </div>
            @endif
            <div>
                <label class="erp-form-label">Label</label>
                <input type="text" name="label" value="{{ old('label', $taxYear->label) }}" class="erp-input" required>
            </div>
            <div>
                <label class="erp-form-label">Start Date</label>
                <input type="date" name="start_date" value="{{ old('start_date', $taxYear->start_date?->format('Y-m-d')) }}" class="erp-input" required>
            </div>
            <div>
                <label class="erp-form-label">End Date</label>
                <input type="date" name="end_date" value="{{ old('end_date', $taxYear->end_date?->format('Y-m-d')) }}" class="erp-input" required>
            </div>
        </div>
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $taxYear->is_active ?? true) ? 'checked' : '' }}>
            Active for TDS calculation (deactivates other years for this factory)
        </label>
        <div>
            <p class="erp-form-label mb-2">Tax Slabs (Bangladesh individual — annual income)</p>
            <div class="space-y-2" id="slab-rows">
                @foreach($slabs as $i => $slab)
                    <div class="grid grid-cols-3 gap-2">
                        <input type="number" name="slabs[{{ $i }}][min_income]" value="{{ old("slabs.$i.min_income", $slab['min'] ?? $slab['min_income'] ?? 0) }}" class="erp-input" placeholder="Min" required>
                        <input type="number" name="slabs[{{ $i }}][max_income]" value="{{ old("slabs.$i.max_income", $slab['max'] ?? $slab['max_income'] ?? '') }}" class="erp-input" placeholder="Max (blank=∞)">
                        <input type="number" step="0.01" name="slabs[{{ $i }}][rate_percent]" value="{{ old("slabs.$i.rate_percent", $slab['rate'] ?? $slab['rate_percent'] ?? 0) }}" class="erp-input" placeholder="Rate %" required>
                    </div>
                @endforeach
            </div>
        </div>
        <button type="submit" class="erp-btn-primary">{{ $taxYear->exists ? 'Update Tax Year' : 'Create Tax Year' }}</button>
    </form>
</div>
@endsection
