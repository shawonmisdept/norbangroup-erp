@props(['name', 'label', 'value' => '', 'hint' => null])

<div class="col-span-2 rich-text-wrap">
    <label class="erp-form-label">{{ $label }}</label>
    @if($hint)
        <p class="text-[10px] text-gray-400 mb-1.5">{{ $hint }}</p>
    @endif
    <textarea
        name="{{ $name }}"
        id="ckeditor_{{ preg_replace('/[^a-z0-9_]/i', '_', $name) }}"
        data-ckeditor
        rows="8"
        class="erp-input !text-xs w-full"
    >{{ old($name, $value) }}</textarea>
</div>
