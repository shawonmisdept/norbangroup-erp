@extends('layouts.frontend')

@section('title', 'Submit Requirement')

@section('frontend-content')

<div class="relative text-white portal-requirement-hero bg-cover bg-center overflow-hidden"
     style="background-image: url('{{ config('portal.hero_image') }}')">
    <div class="absolute inset-0 bg-brand/75"></div>
    <div class="relative z-10 portal-container">
        <span class="inline-block text-xs tracking-widest uppercase text-gold border border-gold/40
                     bg-gold/10 px-4 py-1 rounded-full mb-4">New Requirement</span>
        <h1 class="font-bold mb-3">Request a <span class="text-gold">Production Quote</span></h1>
        <p class="text-white/80 max-w-md mx-auto leading-relaxed">
            Fill in your requirements and {{ config('portal.name') }} will respond within 24 hours.
        </p>
    </div>
</div>

<div class="portal-container portal-section">
    <form method="POST" action="{{ route('orders.store') }}" enctype="multipart/form-data" class="space-y-4">
        @csrf

        @if($errors->any())
            <div class="bg-red-50 border border-red-200 rounded-xl p-4 text-sm text-red-700">
                <ul class="list-disc list-inside space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Contact Information --}}
        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
            <div class="flex items-center gap-2.5 px-5 py-3 bg-gray-50 border-b border-gray-200">
                <span class="w-6 h-6 rounded-sm bg-brand text-white text-[10px] font-bold flex items-center justify-center shrink-0">1</span>
                <div>
                    <p class="font-semibold text-sm text-gray-800">Contact Information</p>
                    <p class="text-xs text-gray-400">Who should we reach out to?</p>
                </div>
            </div>
            <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Your Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}"
                           class="w-full rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand @error('name') border-red-400 @enderror"
                           placeholder="e.g. John Smith">
                    @error('name')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Company Name</label>
                    <input type="text" name="company" value="{{ old('company') }}"
                           class="w-full rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand"
                           placeholder="e.g. Nova Fashion Ltd">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Email <span class="text-red-500">*</span></label>
                    <input type="email" name="email" value="{{ old('email') }}"
                           class="w-full rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand @error('email') border-red-400 @enderror"
                           placeholder="you@company.com">
                    @error('email')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Phone Number <span class="text-red-500">*</span></label>
                    <input type="tel" name="phone" value="{{ old('phone') }}"
                           class="w-full rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand @error('phone') border-red-400 @enderror"
                           placeholder="+880 17XXXXXXXX">
                    @error('phone')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        {{-- Product Requirement --}}
        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
            <div class="flex items-center gap-2.5 px-5 py-3 bg-gray-50 border-b border-gray-200">
                <span class="w-6 h-6 rounded-sm bg-brand text-white text-[10px] font-bold flex items-center justify-center shrink-0">2</span>
                <div>
                    <p class="font-semibold text-sm text-gray-800">Product Requirement</p>
                    <p class="text-xs text-gray-400">What are you looking to produce?</p>
                </div>
            </div>
            <div class="p-5">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Item Name <span class="text-red-500">*</span></label>
                        <input type="text" name="item_name" value="{{ old('item_name') }}" required
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand @error('item_name') border-red-400 @enderror"
                               placeholder="e.g. T-Shirt, Polo, Hoodie">
                        @error('item_name')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Estimated Quantity (pcs)</label>
                        <input type="number" name="quantity" value="{{ old('quantity') }}" min="1"
                               class="w-full rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand"
                               placeholder="e.g. 500">
                    </div>
                </div>
            </div>
        </div>

        {{-- Reference Files --}}
        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
            <div class="flex items-center justify-between gap-3 px-5 py-3 bg-gray-50 border-b border-gray-200">
                <div class="flex items-center gap-2.5">
                    <span class="w-6 h-6 rounded-sm bg-brand text-white text-[10px] font-bold flex items-center justify-center shrink-0">3</span>
                    <div>
                        <p class="font-semibold text-sm text-gray-800 leading-tight">Reference Files</p>
                        <p class="text-[11px] text-gray-400">Tech pack & artwork (optional)</p>
                    </div>
                </div>
            </div>
            <div class="p-4 grid grid-cols-1 md:grid-cols-2 gap-4" x-data="referenceFiles">

                {{-- Tech Pack --}}
                <div class="rounded-sm border border-gray-200 bg-gray-50/40 p-3">
                    <div class="flex items-center justify-between gap-2 mb-2">
                        <label class="text-xs font-semibold text-gray-700">Tech Pack</label>
                        <span class="text-[10px] font-medium text-blue-700 bg-blue-50 border border-blue-100 px-1.5 py-0.5 rounded-sm">Max 200 MB</span>
                    </div>
                    <div class="space-y-1.5">
                        <template x-for="slot in techpack" :key="slot.id">
                            <label class="flex items-center gap-2.5 border border-dashed border-gray-300 rounded-sm bg-white
                                          px-3 py-2 cursor-pointer hover:border-brand/50 hover:bg-blue-50/40 transition min-h-[2.75rem]">
                                <span class="w-9 h-9 shrink-0 rounded-sm border border-gray-200 bg-gray-50 flex items-center justify-center overflow-hidden">
                                    <template x-if="slot.previewUrl">
                                        <img :src="slot.previewUrl" class="w-full h-full object-cover">
                                    </template>
                                    <template x-if="!slot.previewUrl && slot.isPdf">
                                        <span class="text-base leading-none">📄</span>
                                    </template>
                                    <template x-if="!slot.previewUrl && !slot.isPdf">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                            <path d="M12 4v16m8-8H4" stroke-linecap="round"/>
                                        </svg>
                                    </template>
                                </span>
                                <span class="flex-1 min-w-0 text-left">
                                    <span class="block text-[11px] text-gray-400 truncate" x-show="!slot.fileName">Click to upload file</span>
                                    <span class="block text-xs font-medium text-brand truncate" x-show="slot.fileName" x-text="slot.fileName"></span>
                                </span>
                                <input type="file" name="techpack[]" class="sr-only"
                                       accept=".pdf,.ai,.eps,.psd,.dwg,.zip,.rar,.docx,.xlsx,.jpg,.jpeg,.png"
                                       @change="onSelect(slot, $event)">
                            </label>
                        </template>
                    </div>
                    <button type="button" x-show="canAddMore(techpack)" @click="addTechpack()"
                            class="mt-2 text-[11px] font-semibold text-brand hover:text-brand-dark">
                        + Add file
                    </button>
                    @error('techpack')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    @error('techpack.*')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>

                {{-- Artwork --}}
                <div class="rounded-sm border border-gray-200 bg-gray-50/40 p-3">
                    <div class="flex items-center justify-between gap-2 mb-2">
                        <label class="text-xs font-semibold text-gray-700">Artwork</label>
                        <span class="text-[10px] font-medium text-emerald-700 bg-emerald-50 border border-emerald-100 px-1.5 py-0.5 rounded-sm">Max 20 MB</span>
                    </div>
                    <div class="space-y-1.5">
                        <template x-for="slot in artwork" :key="slot.id">
                            <label class="flex items-center gap-2.5 border border-dashed border-gray-300 rounded-sm bg-white
                                          px-3 py-2 cursor-pointer hover:border-brand/50 hover:bg-blue-50/40 transition min-h-[2.75rem]">
                                <span class="w-9 h-9 shrink-0 rounded-sm border border-gray-200 bg-gray-50 flex items-center justify-center overflow-hidden">
                                    <template x-if="slot.previewUrl">
                                        <img :src="slot.previewUrl" class="w-full h-full object-cover">
                                    </template>
                                    <template x-if="!slot.previewUrl && slot.isPdf">
                                        <span class="text-base leading-none">📄</span>
                                    </template>
                                    <template x-if="!slot.previewUrl && !slot.isPdf">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                            <path d="M12 4v16m8-8H4" stroke-linecap="round"/>
                                        </svg>
                                    </template>
                                </span>
                                <span class="flex-1 min-w-0 text-left">
                                    <span class="block text-[11px] text-gray-400 truncate" x-show="!slot.fileName">Click to upload file</span>
                                    <span class="block text-xs font-medium text-brand truncate" x-show="slot.fileName" x-text="slot.fileName"></span>
                                </span>
                                <input type="file" name="artwork[]" class="sr-only"
                                       accept=".pdf,.ai,.eps,.psd,.png,.jpg,.jpeg,.svg,.tiff,.tif"
                                       @change="onSelect(slot, $event)">
                            </label>
                        </template>
                    </div>
                    <button type="button" x-show="canAddMore(artwork)" @click="addArtwork()"
                            class="mt-2 text-[11px] font-semibold text-brand hover:text-brand-dark">
                        + Add file
                    </button>
                    @error('artwork')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    @error('artwork.*')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>

            </div>
        </div>

        {{-- Additional Notes --}}
        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
            <div class="flex items-center gap-2.5 px-5 py-3 bg-gray-50 border-b border-gray-200">
                <span class="w-6 h-6 rounded-sm bg-brand text-white text-[10px] font-bold flex items-center justify-center shrink-0">4</span>
                <div>
                    <p class="font-semibold text-sm text-gray-800">Additional Notes / Message</p>
                </div>
            </div>
            <div class="p-5">
                <textarea name="notes" rows="4"
                          class="w-full rounded-lg border-gray-300 text-sm focus:border-brand focus:ring-brand"
                          placeholder="Fabric preferences, delivery timeline, special finishing requirements…">{{ old('notes') }}</textarea>
            </div>
        </div>

        {{-- Submit --}}
        <div class="bg-white rounded-lg border border-gray-200 p-5 flex flex-col sm:flex-row items-center justify-between gap-4">
            <p class="text-xs text-gray-400 max-w-sm leading-relaxed">
                <strong class="text-gray-600">Secure submission.</strong>
                Your files and information are kept confidential.
            </p>
            <button type="submit"
                    class="flex items-center gap-2 bg-brand hover:bg-brand-dark text-white font-semibold
                           text-sm px-7 py-3 rounded-xl transition active:scale-95 whitespace-nowrap">
                Submit Requirement
            </button>
        </div>
    </form>
</div>

@endsection
