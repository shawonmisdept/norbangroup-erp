@extends('layouts.admin')

@section('title', 'Bulk Create Postings')

@section('breadcrumbs')
    <a href="{{ route('admin.hrm.recruitment.postings.index') }}" class="hover:text-brand">Job Postings</a>
    <span>/</span>
    <span class="text-gray-800 font-medium">Bulk Create</span>
@endsection

@section('admin-content')
@include('partials.erp.page-header', [
    'title' => 'Bulk Create Job Postings',
    'subtitle' => 'Create the same vacancy across multiple factory units',
    'actions' => '<a href="' . route('admin.hrm.recruitment.postings.index') . '" class="erp-btn-secondary">← Back</a>',
])

<div class="erp-panel mb-4">
    <div class="erp-panel-body">
        <p class="text-xs text-gray-500 mb-2">Template:</p>
        <div class="flex flex-wrap gap-2">
            @foreach($templates as $key => $tpl)
                <a href="{{ route('admin.hrm.recruitment.postings.bulk.create', ['template' => $key]) }}" class="erp-btn-sm-secondary {{ $templateKey === $key ? '!bg-brand !text-white' : '' }}">{{ $tpl['label'] ?? $key }}</a>
            @endforeach
        </div>
    </div>
</div>

<form method="POST" action="{{ route('admin.hrm.recruitment.postings.bulk.store') }}" class="max-w-3xl erp-panel">
    @csrf
    <input type="hidden" name="template_key" value="{{ $templateKey }}">
    <div class="erp-panel-body space-y-4">
        <div>
            <label class="erp-form-label">Factory Units *</label>
            <div class="grid grid-cols-2 gap-2 mt-1">
                @foreach($factories as $id => $name)
                    <label class="inline-flex items-center gap-2 text-xs text-gray-700">
                        <input type="checkbox" name="factory_ids[]" value="{{ $id }}" class="rounded border-gray-300">
                        {{ $name }}
                    </label>
                @endforeach
            </div>
            @error('factory_ids')<p class="text-[11px] text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="erp-form-label">Title *</label>
            <input type="text" name="title" required value="{{ old('title', $defaults['title'] ?? '') }}" class="erp-input !text-xs">
        </div>
        <div class="grid grid-cols-3 gap-4">
            <div>
                <label class="erp-form-label">Slots *</label>
                <input type="number" name="slots" min="1" required value="{{ old('slots', 1) }}" class="erp-input !text-xs">
            </div>
            <div>
                <label class="erp-form-label">Shift</label>
                <select name="shift_type" class="erp-input !text-xs">
                    <option value="">—</option>
                    @foreach($shiftTypes as $val => $label)
                        <option value="{{ $val }}" {{ old('shift_type', $defaults['shift_type'] ?? '') === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="erp-form-label">Status</label>
                <select name="status" class="erp-input !text-xs">
                    <option value="draft">Draft</option>
                    <option value="open">Open</option>
                    @if(config('hrm.recruitment_posting_settings.require_approval'))
                        <option value="pending_approval">Pending Approval</option>
                    @endif
                </select>
            </div>
        </div>
        <div>
            <label class="erp-form-label">Closing Date</label>
            <input type="date" name="closes_at" value="{{ old('closes_at') }}" class="erp-input !text-xs">
        </div>
        <div>
            <label class="erp-form-label">Requirements</label>
            <textarea name="requirements" rows="4" class="erp-input !text-xs w-full">{{ old('requirements', strip_tags($defaults['requirements'] ?? '', '<ul><ol><li><p><br>')) }}</textarea>
        </div>
        <div>
            <label class="erp-form-label">Responsibilities</label>
            <textarea name="responsibilities" rows="4" class="erp-input !text-xs w-full">{{ old('responsibilities', strip_tags($defaults['responsibilities'] ?? '', '<ul><ol><li><p><br>')) }}</textarea>
        </div>
        <div>
            <label class="erp-form-label">Employment Status</label>
            <textarea name="employment_status" rows="2" class="erp-input !text-xs w-full">{{ old('employment_status', strip_tags($defaults['employment_status'] ?? '', '<p><br>')) }}</textarea>
        </div>
        <button type="submit" class="erp-btn-primary">Create for Selected Units</button>
    </div>
</form>
@endsection
