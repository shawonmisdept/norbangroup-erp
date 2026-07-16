@extends('layouts.admin')

@section('title', ($template->exists ? 'Edit' : 'Add') . ' Letter Template')

@section('admin-content')
@include('partials.erp.page-header', [
    'title' => ($template->exists ? 'Edit' : 'Add') . ' Letter Template',
    'actions' => '<a href="' . route('admin.hrm.letter-templates.index') . '" class="erp-btn-secondary">← Back</a>',
])

<div class="erp-panel max-w-3xl">
    <div class="erp-panel-body space-y-4">
        <form method="POST" action="{{ $template->exists ? route('admin.hrm.letter-templates.update', $template) : route('admin.hrm.letter-templates.store') }}">
            @csrf
            @if($template->exists) @method('PUT') @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="erp-form-label">Code</label>
                    <input type="text" name="code" value="{{ old('code', $template->code) }}" class="erp-input" required maxlength="40" placeholder="e.g. appointment">
                    <p class="text-[10px] text-gray-500 mt-1">Unique identifier (letters, numbers, dash, underscore)</p>
                </div>
                <div>
                    <label class="erp-form-label">Name</label>
                    <input type="text" name="name" value="{{ old('name', $template->name) }}" class="erp-input" required>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="erp-form-label">Letter Type</label>
                    <select name="letter_type" class="erp-input" required>
                        @foreach($letterTypes as $value => $label)
                            <option value="{{ $value }}" {{ old('letter_type', $template->letter_type) === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="erp-form-label">Factory (optional)</label>
                    <select name="factory_id" class="erp-input">
                        <option value="">All units</option>
                        @foreach($factories as $id => $name)
                            <option value="{{ $id }}" {{ (string) old('factory_id', $template->factory_id) === (string) $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="erp-form-label">Body</label>
                <textarea name="body" rows="16" class="erp-input font-mono text-xs" required>{{ old('body', $template->body) }}</textarea>
                <p class="text-[10px] text-gray-500 mt-1">
                    Placeholders:
                    @verbatim
                    {{date}}, {{employee_name}}, {{employee_code}}, {{factory_name}}, {{office_address}}, {{department}}, {{designation}}, {{joining_date}}, {{confirmation_date}}, {{last_working_day}}, {{phone}}, {{reporting_manager_name}}, {{reporting_manager_designation}}, {{reporting_manager_phone}}
                    @endverbatim
                </p>
            </div>

            <label class="flex items-center gap-2 text-sm">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300" {{ old('is_active', $template->is_active ?? true) ? 'checked' : '' }}>
                Active
            </label>

            <button type="submit" class="erp-btn-primary">Save Template</button>
        </form>
    </div>
</div>
@endsection
