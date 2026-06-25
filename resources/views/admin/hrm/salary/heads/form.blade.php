@extends('layouts.admin')
@section('title', 'Salary Head')
@section('admin-content')
@include('partials.erp.page-header', ['title' => ($head->exists ? 'Edit' : 'Add') . ' Salary Head', 'actions' => '<a href="' . route('admin.hrm.salary.heads.index') . '" class="erp-btn-secondary">← Back</a>'])
@include('admin.hrm.partials.submodule-nav', ['section' => 'salary', 'current' => 'heads'])

<div class="erp-panel max-w-2xl">
    <div class="erp-panel-body">
        <form method="POST" action="{{ $head->exists ? route('admin.hrm.salary.heads.update', $head) : route('admin.hrm.salary.heads.store') }}" class="space-y-4">
            @csrf
            @if($head->exists) @method('PUT') @endif

            <div>
                <label class="erp-form-label">Factory</label>
                <select name="factory_id" required class="erp-input !text-xs">
                    @foreach($factories as $id => $name)
                        <option value="{{ $id }}" {{ (string) old('factory_id', $head->factory_id) === (string) $id ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="erp-form-label">Code</label>
                    <input name="code" value="{{ old('code', $head->code) }}" required maxlength="20" class="erp-input !text-xs" placeholder="GROSS, BASIC…">
                </div>
                <div>
                    <label class="erp-form-label">Sequence</label>
                    <input type="number" name="sort_order" value="{{ old('sort_order', $head->sort_order) }}" min="0" class="erp-input !text-xs">
                </div>
            </div>

            <div>
                <label class="erp-form-label">Name</label>
                <input name="name" value="{{ old('name', $head->name) }}" required class="erp-input !text-xs">
            </div>

            <div>
                <label class="erp-form-label">Native Name</label>
                <input name="name_bangla" value="{{ old('name_bangla', $head->name_bangla) }}" class="erp-input !text-xs">
            </div>

            <div>
                <label class="erp-form-label">Description</label>
                <textarea name="description" rows="2" class="erp-input !text-xs">{{ old('description', $head->description) }}</textarea>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="erp-form-label">Type (E/D/S)</label>
                    <select name="head_type" class="erp-input !text-xs">
                        @foreach($headTypes as $k => $label)
                            <option value="{{ $k }}" {{ old('head_type', $head->head_type) === $k ? 'selected' : '' }}>{{ $k }} — {{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="erp-form-label">Sort Code</label>
                    <input name="sort_code" value="{{ old('sort_code', $head->sort_code) }}" class="erp-input !text-xs">
                </div>
            </div>

            <div class="flex flex-wrap gap-4 text-xs">
                <label class="flex items-center gap-2"><input type="checkbox" name="is_taxable" value="1" {{ old('is_taxable', $head->is_taxable) ? 'checked' : '' }}> Taxable</label>
                <label class="flex items-center gap-2"><input type="checkbox" name="is_perquisite" value="1" {{ old('is_perquisite', $head->is_perquisite) ? 'checked' : '' }}> Perquisite</label>
                <label class="flex items-center gap-2"><input type="checkbox" name="is_disburse" value="1" {{ old('is_disburse', $head->is_disburse ?? true) ? 'checked' : '' }}> Disburse</label>
                <label class="flex items-center gap-2"><input type="checkbox" name="is_active" value="1" {{ old('is_active', $head->is_active ?? true) ? 'checked' : '' }}> Active</label>
            </div>

            <button type="submit" class="erp-btn-primary">Save</button>
        </form>
    </div>
</div>
@endsection
