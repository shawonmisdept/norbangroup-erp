@extends('layouts.admin')
@section('title', 'Increment Rule')
@section('admin-content')
@include('partials.erp.page-header', [
    'title' => ($rule->exists ? 'Edit' : 'Add') . ' Increment Rule',
    'actions' => '<a href="' . route('admin.hrm.salary.increment-rules.index') . '" class="erp-btn-secondary">← Back</a>',
])
@include('admin.hrm.partials.submodule-nav', ['section' => 'salary', 'current' => 'increment-rules'])

<div class="erp-panel max-w-2xl">
    <div class="erp-panel-body">
        <form method="POST" action="{{ $rule->exists ? route('admin.hrm.salary.increment-rules.update', $rule) : route('admin.hrm.salary.increment-rules.store') }}" class="space-y-4">
            @csrf
            @if($rule->exists) @method('PUT') @endif

            <div>
                <label class="erp-form-label">Factory</label>
                <select name="factory_id" required class="erp-input !text-xs">
                    @foreach($factories as $id => $name)
                        <option value="{{ $id }}" {{ (string) old('factory_id', $rule->factory_id) === (string) $id ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="erp-form-label">Salary Grade</label>
                <select name="salary_grade_id" class="erp-input !text-xs">
                    <option value="">All grades in factory</option>
                    @foreach($grades as $id => $name)
                        <option value="{{ $id }}" {{ (string) old('salary_grade_id', $rule->salary_grade_id) === (string) $id ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="erp-form-label">Rule Name</label>
                <input name="name" value="{{ old('name', $rule->name) }}" required maxlength="80" class="erp-input !text-xs" placeholder="Annual increment 5%">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="erp-form-label">Increment Type</label>
                    <select name="increment_type" class="erp-input !text-xs">
                        @foreach($types as $k => $label)
                            <option value="{{ $k }}" {{ old('increment_type', $rule->increment_type) === $k ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="erp-form-label">Value (% or ৳)</label>
                    <input type="number" step="0.01" min="0.01" name="increment_value" value="{{ old('increment_value', $rule->increment_value) }}" required class="erp-input !text-xs">
                </div>
            </div>

            <div>
                <label class="erp-form-label">Minimum Tenure (months)</label>
                <input type="number" min="0" name="min_tenure_months" value="{{ old('min_tenure_months', $rule->min_tenure_months ?? 0) }}" class="erp-input !text-xs">
            </div>

            <div>
                <label class="erp-form-label">Description</label>
                <textarea name="description" rows="2" class="erp-input !text-xs">{{ old('description', $rule->description) }}</textarea>
            </div>

            <label class="inline-flex items-center gap-2 text-sm">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $rule->is_active) ? 'checked' : '' }} class="rounded border-gray-300">
                Active
            </label>

            <button type="submit" class="erp-btn-primary">Save Rule</button>
        </form>
    </div>
</div>
@endsection
