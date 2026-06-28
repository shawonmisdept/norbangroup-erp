@extends('layouts.admin')

@section('title', 'Open Performance Cycle')

@section('breadcrumbs')
    <a href="{{ route('admin.hrm.performance.cycles.index') }}" class="hover:text-brand">Cycles</a>
    <span>/</span>
    <span class="text-gray-800 font-medium">Open</span>
@endsection

@section('admin-content')
@include('partials.erp.page-header', [
    'title' => 'Open Review Cycle',
    'subtitle' => 'Eligible employees will receive review records automatically',
])

<form method="POST" action="{{ route('admin.hrm.performance.cycles.store') }}" class="max-w-2xl space-y-4">
    @csrf

    <div class="erp-panel">
        <div class="erp-panel-body space-y-4">
            @if(count($factories) > 1)
                <div>
                    <label class="erp-form-label">Factory / Unit *</label>
                    <select name="factory_id" class="erp-input" required>
                        <option value="">Select factory</option>
                        @foreach($factories as $id => $name)
                            <option value="{{ $id }}" {{ old('factory_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
            @else
                <input type="hidden" name="factory_id" value="{{ array_key_first($factories) }}">
            @endif

            <div>
                <label class="erp-form-label">Cycle Type *</label>
                <select name="cycle_type" class="erp-input" required id="cycle_type">
                    @foreach($cycleTypes as $value => $label)
                        <option value="{{ $value }}" {{ old('cycle_type', $cycle->cycle_type) === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                <p class="text-[11px] text-gray-500 mt-1">Probation = joining + 6 months · Mid-year = January HR batch · Annual = 12-month anniversary</p>
            </div>

            <div>
                <label class="erp-form-label">Cycle Name *</label>
                <input type="text" name="name" value="{{ old('name', $cycle->name) }}" class="erp-input" required>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="erp-form-label">Year</label>
                    <input type="number" name="year" value="{{ old('year', $cycle->year) }}" class="erp-input" min="2000" max="2100">
                </div>
                <div>
                    <label class="erp-form-label">Template</label>
                    <select name="template_id" class="erp-input">
                        <option value="">Default template</option>
                        @foreach($templates as $id => $name)
                            <option value="{{ $id }}" {{ old('template_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="erp-form-label">Period From *</label>
                    <input type="date" name="period_from" value="{{ old('period_from', $cycle->period_from?->format('Y-m-d')) }}" class="erp-input" required>
                </div>
                <div>
                    <label class="erp-form-label">Period To *</label>
                    <input type="date" name="period_to" value="{{ old('period_to', $cycle->period_to?->format('Y-m-d')) }}" class="erp-input" required>
                </div>
            </div>

            <div>
                <label class="erp-form-label">Notes</label>
                <textarea name="notes" rows="2" class="erp-input">{{ old('notes') }}</textarea>
            </div>
        </div>
    </div>

    <div class="flex gap-2">
        <button type="submit" class="erp-btn-primary">Open Cycle & Generate Reviews</button>
        <a href="{{ route('admin.hrm.performance.cycles.index') }}" class="erp-btn-secondary">Cancel</a>
    </div>
</form>
@endsection
