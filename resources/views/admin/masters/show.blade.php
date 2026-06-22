@extends('layouts.admin')

@section('title', $config['label'] . ' ' . $record->code)

@section('breadcrumbs')
    <a href="{{ route('admin.masters.hub') }}" class="hover:text-brand">Master Data</a>
    <span>/</span>
    <a href="{{ route('admin.masters.index', $module) }}" class="hover:text-brand">{{ $config['label_plural'] }}</a>
    <span>/</span>
    <span class="text-gray-800 font-medium">{{ $record->code }}</span>
@endsection

@section('admin-content')

{{-- Summary header card --}}
<div class="erp-panel mb-4">
    <div class="erp-panel-body flex flex-col sm:flex-row sm:items-center gap-4">
        @php
            $hasImage = collect($config['fields'])->contains(fn ($m) => ($m['type'] ?? '') === 'image');
        @endphp
        @if($hasImage && $record->image)
            <img src="{{ $record->imageUrl() }}" alt=""
                 class="w-20 h-20 object-cover rounded-sm border border-erp-border shrink-0">
        @else
            <div class="w-16 h-16 rounded-sm bg-brand/10 text-brand flex items-center justify-center shrink-0">
                <span class="text-xl font-bold">{{ strtoupper(substr($record->name, 0, 1)) }}</span>
            </div>
        @endif
        <div class="flex-1 min-w-0">
            <div class="flex flex-wrap items-center gap-2 mb-1">
                <h1 class="text-lg font-bold text-gray-900">{{ $record->name }}</h1>
                @if($record->is_active)
                    <span class="erp-badge bg-emerald-50 text-emerald-700 border border-emerald-200">Active</span>
                @else
                    <span class="erp-badge bg-gray-100 text-gray-500">Inactive</span>
                @endif
            </div>
            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-gray-500">
                <span><span class="text-gray-400">Code:</span> <code class="font-mono text-brand">{{ $record->code }}</code></span>
                <span><span class="text-gray-400">Module:</span> {{ $config['label'] }}</span>
                <span><span class="text-gray-400">Created:</span> {{ $record->created_at->format('d M Y') }}</span>
            </div>
        </div>
        @if(auth()->user()->canManageMaster($module))
            <div class="flex items-center gap-2 shrink-0">
                <a href="{{ route('admin.masters.edit', [$module, $record]) }}" class="erp-btn-primary">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    Edit
                </a>
                <form method="POST" action="{{ route('admin.masters.destroy', [$module, $record]) }}"
                      onsubmit="return confirm('Delete this {{ strtolower($config['label']) }} permanently?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="erp-btn-danger">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        Delete
                    </button>
                </form>
            </div>
        @endif
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-4 items-start">

    {{-- Detail fields --}}
    <div class="xl:col-span-2 erp-panel overflow-hidden">
        <div class="erp-panel-head">
            <h2 class="text-xs font-semibold text-gray-700 uppercase tracking-wide">Record Details</h2>
        </div>
        <div class="divide-y divide-gray-100">
            @foreach($config['fields'] as $field => $meta)
                @if($meta['type'] === 'boolean') @continue @endif
                <div class="flex flex-col sm:flex-row px-5 py-3.5 hover:bg-gray-50/50 gap-1 sm:gap-0">
                    <dt class="sm:w-44 shrink-0 text-[11px] font-semibold text-gray-400 uppercase tracking-wide sm:pt-0.5">
                        {{ $meta['label'] }}
                    </dt>
                    <dd class="flex-1 text-sm text-gray-800">
                        @if($meta['type'] === 'image')
                            @if($record->image)
                                <img src="{{ $record->imageUrl() }}" alt=""
                                     class="w-36 h-36 object-cover rounded-sm border border-erp-border shadow-sm">
                            @else
                                <span class="text-gray-400 text-xs italic">No image uploaded</span>
                            @endif
                        @elseif($meta['type'] === 'relation')
                            @php
                            $relName = match(str_replace('_id', '', $field)) {
                                'material_type' => 'materialType',
                                'supplier_type' => 'supplierType',
                                default => str_replace('_id', '', $field),
                            };
                            @endphp
                            @if($record->{$relName})
                                <span class="inline-flex items-center gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-brand"></span>
                                    {{ $record->{$relName}?->{$meta['display'] ?? 'name'} }}
                                </span>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        @elseif($meta['type'] === 'date' && $record->{$field})
                            <span class="tabular-nums">{{ $record->{$field}->format('d M Y') }}</span>
                        @elseif($field === 'hex_code' && $record->hex_code)
                            <span class="inline-flex items-center gap-2.5">
                                <span class="w-8 h-8 rounded-sm border border-erp-border shadow-inner"
                                      style="background-color: {{ $record->hex_code }}"></span>
                                <code class="font-mono text-xs bg-gray-100 px-2 py-1 rounded-sm">{{ $record->hex_code }}</code>
                            </span>
                        @elseif($meta['type'] === 'textarea' && $record->{$field})
                            <p class="text-sm text-gray-600 leading-relaxed whitespace-pre-line">{{ $record->{$field} }}</p>
                        @elseif($field === 'email' && $record->{$field})
                            <a href="mailto:{{ $record->{$field} }}" class="text-brand hover:underline">{{ $record->{$field} }}</a>
                        @elseif($field === 'phone' && $record->{$field})
                            <span class="tabular-nums">{{ $record->{$field} }}</span>
                        @else
                            {{ $record->{$field} ?? '—' }}
                        @endif
                    </dd>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Sidebar meta --}}
    <div class="space-y-4">
        <div class="erp-panel">
            <div class="erp-panel-head">
                <h2 class="text-xs font-semibold text-gray-700 uppercase tracking-wide">Status</h2>
            </div>
            <div class="erp-panel-body">
                @if($record->is_active)
                    <div class="flex items-center gap-3 p-3 rounded-sm bg-emerald-50 border border-emerald-200">
                        <div class="w-8 h-8 rounded-full bg-emerald-100 flex items-center justify-center">
                            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path d="M5 13l4 4L19 7" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-emerald-800">Active</p>
                            <p class="text-[11px] text-emerald-600">Available for use</p>
                        </div>
                    </div>
                @else
                    <div class="flex items-center gap-3 p-3 rounded-sm bg-gray-50 border border-erp-border">
                        <div class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center">
                            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" stroke-linecap="round"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-gray-700">Inactive</p>
                            <p class="text-[11px] text-gray-400">Not available for use</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <div class="erp-panel">
            <div class="erp-panel-head">
                <h2 class="text-xs font-semibold text-gray-700 uppercase tracking-wide">System Info</h2>
            </div>
            <div class="erp-panel-body space-y-3 text-xs">
                <div class="flex justify-between">
                    <span class="text-gray-400">Reference</span>
                    <code class="font-mono text-brand">{{ $record->code }}</code>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-400">Record ID</span>
                    <span class="tabular-nums text-gray-600">#{{ $record->id }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-400">Created</span>
                    <span class="tabular-nums text-gray-600">{{ $record->created_at->format('d M Y, H:i') }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-400">Last Modified</span>
                    <span class="tabular-nums text-gray-600">{{ $record->updated_at->format('d M Y, H:i') }}</span>
                </div>
            </div>
        </div>

        <div class="erp-panel">
            <div class="erp-panel-body space-y-2">
                <a href="{{ route('admin.masters.index', $module) }}" class="erp-btn-secondary w-full justify-center !py-2">
                    ← Back to {{ $config['label_plural'] }}
                </a>
                @if(auth()->user()->canManageMaster($module))
                    <a href="{{ route('admin.masters.edit', [$module, $record]) }}" class="erp-btn-primary w-full justify-center !py-2">
                        Edit Record
                    </a>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
