@extends('layouts.admin')

@php
    $isEdit = $article->exists;
    $prefillModule = request('module') ? \App\Models\KbModule::where('code', request('module'))->first() : null;
    $activeModule = $selectedModule ?? $prefillModule;
    $prefillSubmodule = request('submodule', $article->submodule_key ?? 'overview');
    $sectionFields = [
        ['name' => 'purpose', 'label_en' => '1. Purpose — what this module/screen does', 'label_bn' => '১. এই মডিউল/স্ক্রিনের কাজ কী'],
        ['name' => 'audience', 'label_en' => '2. Who uses it · which department', 'label_bn' => '২. কে ব্যবহার করবে · কোন department'],
        ['name' => 'usage_rules', 'label_en' => '3. Usage rules & step-by-step workflow', 'label_bn' => '৩. ব্যবহার বিধি ও step-by-step workflow'],
    ];
@endphp

@section('title', ($isEdit ? 'Edit' : 'New') . ' KB Article')

@section('breadcrumbs')
    <a href="{{ route('admin.kb.hub') }}" class="text-gray-600 hover:text-brand">Knowledge Base</a><span>/</span>
    <a href="{{ route('admin.kb.manage.index') }}" class="text-gray-600 hover:text-brand">Manage</a><span>/</span>
    <span class="text-gray-800 font-medium">{{ $isEdit ? 'Edit' : 'New' }}</span>
@endsection

@section('admin-content')
@include('partials.erp.page-header', [
    'title' => $isEdit ? 'Edit article' : 'New article',
    'subtitle' => '৩টি section — কাজ · ব্যবহারকারী/department · step-by-step workflow (BN + EN)',
])

<form method="POST"
      action="{{ $isEdit ? route('admin.kb.manage.update', $article) : route('admin.kb.manage.store') }}"
      class="erp-panel">
    @csrf
    @if($isEdit)
        @method('PUT')
    @endif

    <div class="erp-panel-body space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="erp-form-label" for="kb_module_id">Module</label>
                <select name="kb_module_id" id="kb_module_id" class="erp-input w-full text-xs" required
                        @change="window.dispatchEvent(new CustomEvent('kb-module-changed'))">
                    <option value="">Select module…</option>
                    @foreach($modules as $mod)
                        <option value="{{ $mod->id }}"
                                @selected(old('kb_module_id', $article->kb_module_id ?? $activeModule?->id) == $mod->id)>
                            {{ $mod->label_en }}
                        </option>
                    @endforeach
                </select>
                @error('kb_module_id')<p class="text-red-500 text-[10px] mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="erp-form-label" for="submodule_key">Sub-module</label>
                <select name="submodule_key" id="submodule_key" class="erp-input w-full text-xs">
                    <option value="overview" @selected(old('submodule_key', $prefillSubmodule) === 'overview' || old('submodule_key', $prefillSubmodule) === null)>Overview (module-wide)</option>
                </select>
                @error('submodule_key')<p class="text-red-500 text-[10px] mt-1">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 border-t border-gray-100 pt-4">
            <div>
                <label class="erp-form-label" for="title_en">Title (English)</label>
                <input type="text" name="title_en" id="title_en" class="erp-input w-full text-xs"
                       value="{{ old('title_en', $article->title_en) }}" required>
                @error('title_en')<p class="text-red-500 text-[10px] mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="erp-form-label" for="title_bn">শিরোনাম (বাংলা)</label>
                <input type="text" name="title_bn" id="title_bn" class="erp-input w-full text-xs"
                       value="{{ old('title_bn', $article->title_bn) }}" required>
                @error('title_bn')<p class="text-red-500 text-[10px] mt-1">{{ $message }}</p>@enderror
            </div>
        </div>

        @foreach($sectionFields as $section)
            <div class="border-t border-gray-100 pt-4">
                <p class="text-xs font-semibold text-gray-800 mb-3">{{ $section['label_bn'] }} / {{ $section['label_en'] }}</p>
                <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
                    @include('partials.admin.rich-text-field', [
                        'name'  => $section['name'] . '_bn',
                        'label' => 'বাংলা',
                        'value' => old($section['name'] . '_bn', $article->{$section['name'] . '_bn'}),
                    ])
                    @include('partials.admin.rich-text-field', [
                        'name'  => $section['name'] . '_en',
                        'label' => 'English',
                        'value' => old($section['name'] . '_en', $article->{$section['name'] . '_en'}),
                    ])
                </div>
            </div>
        @endforeach

        <div class="flex items-center gap-2 pt-2 border-t border-gray-100">
            <input type="checkbox" name="is_published" id="is_published" value="1" class="rounded border-gray-300"
                   @checked(old('is_published', $article->is_published))>
            <label for="is_published" class="text-xs text-gray-700">Publish (visible to users with module access)</label>
        </div>
    </div>

    <div class="erp-panel-footer flex justify-end gap-2">
        <a href="{{ route('admin.kb.manage.index') }}" class="erp-btn-secondary">Cancel</a>
        <button type="submit" class="erp-btn-primary">{{ $isEdit ? 'Update' : 'Create' }}</button>
    </div>
</form>
@endsection

@include('partials.admin.rich-text-editor')

@push('scripts')
<script>
(function () {
    const submoduleOptions = @json($submoduleOptions);
    const preselected = @json(old('submodule_key', $prefillSubmodule ?? 'overview'));

    function refillSubmodules() {
        const moduleSelect = document.getElementById('kb_module_id');
        const subSelect = document.getElementById('submodule_key');
        if (!moduleSelect || !subSelect) return;

        const moduleId = moduleSelect.value;
        const items = submoduleOptions[moduleId] || [];

        subSelect.innerHTML = '<option value="overview">Overview (module-wide)</option>';
        items.forEach(function (item) {
            const opt = document.createElement('option');
            opt.value = item.key;
            opt.textContent = item.label;
            subSelect.appendChild(opt);
        });

        if (preselected && preselected !== 'overview') {
            subSelect.value = preselected;
        }
    }

    document.getElementById('kb_module_id')?.addEventListener('change', refillSubmodules);
    window.addEventListener('kb-module-changed', refillSubmodules);
    refillSubmodules();
})();
</script>
@endpush
