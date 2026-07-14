<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
    <div class="erp-panel p-4">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Total</p>
        <p class="text-xl font-bold tabular-nums">৳{{ number_format($summary['total'], 2) }}</p>
        <p class="text-xs text-gray-500 mt-1">{{ number_format($summary['entry_count']) }} {{ Str::plural('entry', $summary['entry_count']) }}</p>
    </div>

    <div class="erp-panel p-4">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Paid</p>
        <p class="text-xl font-bold tabular-nums text-green-700">৳{{ number_format($summary['paid'], 2) }}</p>
    </div>

    <div class="erp-panel p-4">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Pending</p>
        <p class="text-xl font-bold tabular-nums text-amber-700">৳{{ number_format($summary['pending'], 2) }}</p>
    </div>
</div>
