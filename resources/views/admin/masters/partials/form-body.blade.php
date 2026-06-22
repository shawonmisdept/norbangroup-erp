{{-- Main field grid (excludes image & is_active — those live in sidebar / image panel) --}}
@php
    $mainFields = collect($config['fields'])->reject(fn ($meta, $field) => in_array($meta['type'], ['image', 'boolean']));
    $hasImage = collect($config['fields'])->contains(fn ($meta) => ($meta['type'] ?? '') === 'image');
@endphp

@if($mainFields->isNotEmpty())
<div class="erp-panel">
    <div class="erp-panel-head">
        <h2 class="text-xs font-semibold text-gray-700 uppercase tracking-wide">General Information</h2>
    </div>
    <div class="erp-panel-body">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-5 gap-y-4">
            @foreach($mainFields as $field => $meta)
                @php
                    $fullWidth = in_array($meta['type'], ['relation']) && ($meta['required'] ?? false)
                        ? false
                        : in_array($field, ['name', 'address', 'company', 'notes', 'description']) || ($meta['type'] ?? '') === 'textarea';
                    $colSpan = $fullWidth ? 'md:col-span-2' : '';
                @endphp
                <div class="{{ $colSpan }}">
                    @include('admin.masters.partials.field', [
                        'field'     => $field,
                        'meta'      => $meta,
                        'record'    => $record ?? null,
                        'relations' => $relations ?? [],
                    ])
                </div>
            @endforeach
        </div>
    </div>
</div>
@endif

@if($hasImage)
<div class="erp-panel">
    <div class="erp-panel-head">
        <h2 class="text-xs font-semibold text-gray-700 uppercase tracking-wide">Image</h2>
    </div>
    <div class="erp-panel-body">
        @include('admin.masters.partials.field-image', ['record' => $record ?? null])
    </div>
</div>
@endif
