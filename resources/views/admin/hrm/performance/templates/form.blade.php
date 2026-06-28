@extends('layouts.admin')

@section('title', isset($template->id) ? 'Edit Template' : 'New Template')

@section('breadcrumbs')
    <a href="{{ route('admin.hrm.performance.templates.index') }}" class="hover:text-brand">Templates</a>
    <span>/</span>
    <span class="text-gray-800 font-medium">{{ isset($template->id) ? 'Edit' : 'New' }}</span>
@endsection

@section('admin-content')
@include('partials.erp.page-header', [
    'title' => isset($template->id) ? 'Edit Template' : 'New Score Template',
    'subtitle' => 'Weights must total 100%',
])

<form method="POST" action="{{ isset($template->id) ? route('admin.hrm.performance.templates.update', $template) : route('admin.hrm.performance.templates.store') }}" class="max-w-3xl space-y-4">
    @csrf
    @if(isset($template->id)) @method('PUT') @endif

    <div class="erp-panel">
        <div class="erp-panel-body space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="erp-form-label">Template Name *</label>
                    <input type="text" name="name" value="{{ old('name', $template->name) }}" class="erp-input" required>
                </div>
                <div>
                    <label class="erp-form-label">Factory (optional)</label>
                    <select name="factory_id" class="erp-input">
                        <option value="">All units (global)</option>
                        @foreach($factories as $id => $name)
                            <option value="{{ $id }}" {{ old('factory_id', $template->factory_id) == $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="flex gap-6">
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_default" value="1" {{ old('is_default', $template->is_default) ? 'checked' : '' }}> Default template</label>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" {{ old('is_active', $template->is_active ?? true) ? 'checked' : '' }}> Active</label>
            </div>
        </div>
    </div>

    <div class="erp-panel">
        <div class="erp-panel-head flex justify-between items-center">
            <h2 class="text-xs font-semibold text-gray-700 uppercase">Criteria</h2>
            <span class="text-[10px] text-gray-400">Auto = attendance system · Manual = reporting person</span>
        </div>
        <div class="erp-panel-body space-y-3">
            @foreach(old('criteria', $criteria) as $i => $criterion)
                <div class="grid grid-cols-12 gap-2 items-end border-b border-gray-50 pb-3">
                    <div class="col-span-2">
                        @if($i === 0)<label class="erp-form-label">Code</label>@endif
                        <input type="text" name="criteria[{{ $i }}][code]" value="{{ $criterion['code'] ?? '' }}" class="erp-input !text-xs" required readonly>
                    </div>
                    <div class="col-span-4">
                        @if($i === 0)<label class="erp-form-label">Label</label>@endif
                        <input type="text" name="criteria[{{ $i }}][label]" value="{{ $criterion['label'] ?? '' }}" class="erp-input !text-xs" required>
                    </div>
                    <div class="col-span-2">
                        @if($i === 0)<label class="erp-form-label">Type</label>@endif
                        <select name="criteria[{{ $i }}][criterion_type]" class="erp-input !text-xs" required>
                            <option value="auto" {{ ($criterion['criterion_type'] ?? '') === 'auto' ? 'selected' : '' }}>Auto</option>
                            <option value="manual" {{ ($criterion['criterion_type'] ?? '') === 'manual' ? 'selected' : '' }}>Manual</option>
                        </select>
                    </div>
                    <div class="col-span-2">
                        @if($i === 0)<label class="erp-form-label">Weight %</label>@endif
                        <input type="number" name="criteria[{{ $i }}][weight]" value="{{ $criterion['weight'] ?? 0 }}" class="erp-input !text-xs" min="0" max="100" step="0.01" required>
                    </div>
                    <input type="hidden" name="criteria[{{ $i }}][sort_order]" value="{{ $criterion['sort_order'] ?? $i }}">
                </div>
            @endforeach
            @error('criteria')<p class="text-red-600 text-xs">{{ $message }}</p>@enderror
        </div>
    </div>

    <div class="flex gap-2">
        <button type="submit" class="erp-btn-primary">Save Template</button>
        <a href="{{ route('admin.hrm.performance.templates.index') }}" class="erp-btn-secondary">Cancel</a>
    </div>
</form>
@endsection
