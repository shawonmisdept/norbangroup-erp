@extends('layouts.admin')

@section('title', 'Issue HR Letter')

@section('breadcrumbs')
    <a href="{{ route('admin.hrm.letters.index') }}" class="hover:text-brand">HR Letters</a>
    <span>/</span>
    <span class="text-gray-800 font-medium">Issue</span>
@endsection

@section('admin-content')
@include('partials.hrm.letter-document-styles')

@include('partials.erp.page-header', [
    'title' => 'Issue HR Letter',
    'subtitle' => 'Select employee and template',
    'actions' => '<a href="' . route('admin.hrm.letters.index') . '" class="erp-btn-secondary">← Back</a>',
])

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
    <div class="erp-panel">
        <div class="erp-panel-head"><h2 class="text-xs font-semibold text-gray-700 uppercase">Letter Details</h2></div>
        <form method="GET" action="{{ route('admin.hrm.letters.create') }}" class="erp-panel-body space-y-3 border-b border-erp-border">
            <div>
                <label class="erp-form-label">Employee *</label>
                <select name="employee_id" class="erp-input !text-xs" onchange="this.form.submit()">
                    <option value="">Select employee…</option>
                    @foreach($employees as $id => $label)
                        <option value="{{ $id }}" {{ (int) $selectedEmployeeId === (int) $id ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            @if($selectedEmployeeId)
                <div>
                    <label class="erp-form-label">Template *</label>
                    <select name="template_id" class="erp-input !text-xs" onchange="this.form.submit()">
                        <option value="">Select template…</option>
                        @foreach($templates as $template)
                            <option value="{{ $template->id }}" {{ (int) $selectedTemplateId === (int) $template->id ? 'selected' : '' }}>
                                {{ $template->name }} ({{ $template->typeLabel() }})
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif
        </form>

        @if($selectedEmployeeId && $selectedTemplateId)
            <form method="POST" action="{{ route('admin.hrm.letters.store') }}" class="erp-panel-body space-y-3"
                  data-confirm="Issue this letter to the employee?"
                  data-confirm-variant="primary"
                  data-confirm-ok="Yes, issue">
                @csrf
                <input type="hidden" name="employee_id" value="{{ $selectedEmployeeId }}">
                <input type="hidden" name="template_id" value="{{ $selectedTemplateId }}">
                <div>
                    <label class="erp-form-label">Internal Notes</label>
                    <textarea name="notes" rows="2" class="erp-input !text-xs" placeholder="Optional notes…">{{ old('notes') }}</textarea>
                </div>
                <button type="submit" class="erp-btn-primary w-full justify-center">Issue Letter</button>
            </form>
        @endif
    </div>

    <div class="erp-panel">
        <div class="erp-panel-head"><h2 class="text-xs font-semibold text-gray-700 uppercase">Preview</h2></div>
        <div class="erp-panel-body !p-0">
            @if($preview)
                @php
                    $previewEmployee = \App\Models\Hrm\Employee::find($selectedEmployeeId);
                    $previewTemplate = $templates->firstWhere('id', $selectedTemplateId);
                @endphp
                @include('partials.hrm.letter-document', [
                    'content'     => $preview,
                    'title'       => $previewTemplate?->typeLabel(),
                    'factoryName' => $previewEmployee?->factory?->name,
                ])
            @else
                <p class="text-gray-400 text-sm p-4">Select employee and template to preview letter content.</p>
            @endif
        </div>
    </div>
</div>
@endsection
