@php
    $views = [
        'detail'     => 'Line Items',
        'by_vehicle' => 'By Vehicle',
    ];
@endphp

<div class="flex flex-wrap gap-2 mb-4">
    @foreach($views as $key => $label)
        <a href="{{ route('admin.tms.reports.index', array_merge($filters, ['tab' => 'fuel', 'fuel_view' => $key])) }}"
           class="px-3 py-1.5 rounded-lg text-xs font-semibold {{ ($filters['fuel_view'] ?? 'detail') === $key ? 'bg-slate-800 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
            {{ $label }}
        </a>
    @endforeach
</div>
