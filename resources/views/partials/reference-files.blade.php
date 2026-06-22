@props(['files', 'type', 'order', 'icon' => '📄', 'label' => 'Files'])

@php
    $canDownload = auth()->user()?->hasPermission('orders.download') ?? false;
@endphp

<div>
    <p class="text-xs font-medium text-gray-600 mb-3">{{ $label }} ({{ $files->count() }})</p>

    @forelse($files as $file)
        <div class="flex items-start gap-3 mb-3 p-3 rounded-sm border border-gray-100 bg-gray-50/50">
            @if($canDownload && \App\Models\Order::isPreviewable($file['mime']))
                <a href="{{ route('admin.requirements.files.preview', [$order, $type, $file['index']]) }}"
                   target="_blank" class="shrink-0 block">
                    @if(str_starts_with($file['mime'], 'image/'))
                        <img src="{{ route('admin.requirements.files.preview', [$order, $type, $file['index']]) }}"
                             alt="{{ $file['original_name'] }}"
                             class="w-14 h-14 object-cover rounded-sm border border-gray-200">
                    @else
                        <div class="w-14 h-14 flex items-center justify-center rounded-sm border border-gray-200 bg-white text-2xl">
                            📄
                        </div>
                    @endif
                </a>
            @else
                <div class="w-14 h-14 flex items-center justify-center rounded-sm border border-gray-200 bg-white text-2xl shrink-0">
                    {{ $icon }}
                </div>
            @endif

            <div class="min-w-0 flex-1">
                @if($canDownload)
                    <a href="{{ route('admin.requirements.files.download', [$order, $type, $file['index']]) }}"
                       class="text-sm text-brand hover:underline font-medium truncate block">
                        {{ $file['original_name'] }}
                    </a>
                    @if(\App\Models\Order::isPreviewable($file['mime']))
                        <a href="{{ route('admin.requirements.files.preview', [$order, $type, $file['index']]) }}"
                           target="_blank" class="text-xs text-gray-400 hover:text-brand mt-0.5 inline-block">
                            Preview
                        </a>
                    @endif
                @else
                    <p class="text-sm text-gray-700 font-medium truncate">{{ $file['original_name'] }}</p>
                @endif
                <p class="text-xs text-gray-400 mt-0.5">{{ number_format($file['size'] / 1024, 1) }} KB</p>
            </div>
        </div>
    @empty
        <p class="text-sm text-gray-400">No files uploaded.</p>
    @endforelse
</div>
