@props([
    'name',
    'label',
    'accept' => 'image/jpeg,image/png,image/gif,image/webp,application/pdf',
    'currentUrl' => null,
    'currentName' => null,
    'hint' => 'JPG, PNG, GIF, WEBP or PDF. Max 5 MB.',
    'preview' => null,
])

<div>
    <label class="erp-form-label">{{ $label }}</label>
    @if($currentUrl)
        <div class="mb-2 flex items-center gap-2">
            <a href="{{ $currentUrl }}" target="_blank" class="text-xs text-brand hover:underline">View uploaded file</a>
            @if($currentName)
                <span class="text-[11px] text-gray-400">{{ $currentName }}</span>
            @endif
        </div>
    @endif
    <input type="file" name="{{ $name }}" accept="{{ $accept }}"
           class="w-full text-xs text-gray-600 file:mr-2 file:py-1.5 file:px-3 file:rounded-sm file:border-0 file:text-xs file:font-medium file:bg-brand file:text-white">
    <p class="text-[11px] text-gray-400 mt-1">{{ $hint }}</p>
    @error($name)<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
</div>
