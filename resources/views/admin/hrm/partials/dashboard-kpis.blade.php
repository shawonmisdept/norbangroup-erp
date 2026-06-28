@props(['kpis' => [], 'columns' => 'grid-cols-2 md:grid-cols-4 xl:grid-cols-6'])

<div class="grid {{ $columns }} gap-3 mb-6">
    @foreach($kpis as $kpi)
        @php
            $tag = ($kpi['url'] ?? null) ? 'a' : 'div';
        @endphp
        <{{ $tag }}
            @if($tag === 'a') href="{{ $kpi['url'] }}" @endif
            class="erp-kpi {{ $kpi['panel'] ?? 'border-gray-200 bg-gray-50/60' }} {{ isset($kpi['url']) ? 'hover:border-brand/40 transition-all block' : '' }}">
            <p class="erp-kpi-value {{ $kpi['text'] ?? 'text-gray-700' }}">
                {{ is_numeric($kpi['value'] ?? null) ? number_format($kpi['value']) : ($kpi['value'] ?? '—') }}
            </p>
            <p class="erp-kpi-label {{ $kpi['text'] ?? 'text-gray-700' }}">{{ $kpi['label'] }}</p>
        </{{ $tag }}>
    @endforeach
</div>
