@extends('layouts.admin')
@section('title', 'Salary Bank')
@section('admin-content')
@include('partials.erp.page-header', ['title' => ($bank->exists ? 'Edit' : 'Add') . ' Salary Bank', 'actions' => '<a href="' . route('admin.hrm.salary.banks.index') . '" class="erp-btn-secondary">← Back</a>'])
@include('admin.hrm.partials.submodule-nav', ['section' => 'salary', 'current' => 'banks'])

<div class="erp-panel max-w-xl">
    <div class="erp-panel-body">
        <form method="POST" action="{{ $bank->exists ? route('admin.hrm.salary.banks.update', $bank) : route('admin.hrm.salary.banks.store') }}" class="space-y-4">
            @csrf
            @if($bank->exists) @method('PUT') @endif

            <div>
                <label class="erp-form-label">Factory <span class="text-red-500">*</span></label>
                <select name="factory_id" required class="erp-input !text-xs">
                    @foreach($factories as $id => $name)
                        <option value="{{ $id }}" {{ (string) old('factory_id', $bank->factory_id) === (string) $id ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="erp-form-label">Code <span class="text-red-500">*</span></label>
                    <input name="code" value="{{ old('code', $bank->code) }}" required maxlength="20" class="erp-input !text-xs uppercase" placeholder="SJIB, BRAC…">
                </div>
                <div>
                    <label class="erp-form-label">Short Name</label>
                    <input name="short_name" value="{{ old('short_name', $bank->short_name) }}" maxlength="40" class="erp-input !text-xs" placeholder="Shahjalal Islami Bank">
                </div>
            </div>

            <div>
                <label class="erp-form-label">Full Bank Name <span class="text-red-500">*</span></label>
                <input name="name" value="{{ old('name', $bank->name) }}" required maxlength="120" class="erp-input !text-xs" placeholder="Shahjalal Islami Bank PLC">
            </div>

            <label class="flex items-center gap-2 text-xs text-gray-600">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $bank->is_active) ? 'checked' : '' }} class="rounded border-gray-300 text-brand">
                Active
            </label>

            <button type="submit" class="erp-btn-primary">{{ $bank->exists ? 'Save Changes' : 'Create Bank' }}</button>
        </form>
    </div>
</div>
@endsection
