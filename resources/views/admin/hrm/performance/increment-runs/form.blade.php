@extends('layouts.admin')

@section('title', 'New Increment Run')

@section('breadcrumbs')
    <a href="{{ route('admin.hrm.performance.increment-runs.index') }}" class="hover:text-brand">Increment Runs</a>
    <span>/</span>
    <span class="text-gray-800 font-medium">New</span>
@endsection

@section('admin-content')
@include('partials.erp.page-header', [
    'title' => 'New Annual Increment Run',
    'subtitle' => 'Links to an approved annual (12-month) review cycle',
])

<form method="POST" action="{{ route('admin.hrm.performance.increment-runs.store') }}" class="max-w-2xl space-y-4">
    @csrf
    <div class="erp-panel">
        <div class="erp-panel-body space-y-4">
            @if(count($factories) > 1)
                <div>
                    <label class="erp-form-label">Factory *</label>
                    <select name="factory_id" class="erp-input" required>
                        @foreach($factories as $id => $name)
                            <option value="{{ $id }}" {{ old('factory_id', $run->factory_id) == $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
            @else
                <input type="hidden" name="factory_id" value="{{ array_key_first($factories) }}">
            @endif

            <div>
                <label class="erp-form-label">Annual Cycle (optional)</label>
                <select name="performance_cycle_id" class="erp-input">
                    <option value="">All approved annual reviews for year</option>
                    @foreach($cycles as $id => $label)
                        <option value="{{ $id }}" {{ old('performance_cycle_id', $run->performance_cycle_id) == $id ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="erp-form-label">Year *</label>
                    <input type="number" name="year" value="{{ old('year', $run->year) }}" class="erp-input" required>
                </div>
                <div>
                    <label class="erp-form-label">Run Name *</label>
                    <input type="text" name="name" value="{{ old('name', $run->name) }}" class="erp-input" required>
                </div>
            </div>

            <div>
                <label class="erp-form-label">Notes</label>
                <textarea name="notes" rows="2" class="erp-input">{{ old('notes') }}</textarea>
            </div>
        </div>
    </div>

    <div class="flex gap-2">
        <button type="submit" class="erp-btn-primary">Create Run</button>
        <a href="{{ route('admin.hrm.performance.increment-runs.index') }}" class="erp-btn-secondary">Cancel</a>
    </div>
</form>
@endsection
