<div class="flex flex-col sm:flex-row gap-5 items-start" x-data="{ preview: '{{ (isset($record) && $record->image) ? $record->imageUrl() : '' }}' }">
    {{-- Preview --}}
    <div class="shrink-0">
        <template x-if="preview">
            <img :src="preview" alt="" class="w-32 h-32 object-cover rounded-sm border border-erp-border bg-gray-50">
        </template>
        <template x-if="!preview">
            <div class="w-32 h-32 rounded-sm border-2 border-dashed border-erp-border bg-gray-50 flex flex-col items-center justify-center text-gray-400">
                <svg class="w-8 h-8 mb-1 opacity-40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14M14 8h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span class="text-[10px] uppercase tracking-wide">Preview</span>
            </div>
        </template>
    </div>

    {{-- Upload zone --}}
    <div class="flex-1 min-w-0">
        <label class="erp-form-label">Upload Image</label>
        <label class="flex flex-col items-center justify-center w-full h-28 border-2 border-dashed border-erp-border rounded-sm bg-gray-50/50 hover:bg-blue-50/30 hover:border-brand/40 cursor-pointer transition">
            <svg class="w-7 h-7 text-gray-400 mb-1" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path d="M12 4v16m8-8H4" stroke-linecap="round"/>
            </svg>
            <span class="text-xs text-gray-500">Click to browse · JPG, PNG, GIF, WebP</span>
            <span class="text-[10px] text-gray-400 mt-0.5">Max 5 MB · Auto-resized to 400px</span>
            <input type="file" name="image" accept="image/jpeg,image/png,image/gif,image/webp" class="sr-only"
                   @change="preview = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : preview">
        </label>
        @error('image')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
    </div>
</div>
