<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
    <div class="erp-panel p-4">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Total</p>
        <p class="text-xl font-bold tabular-nums">৳{{ number_format($summary['total'], 2) }}</p>
        <p class="text-xs text-gray-500 mt-1">{{ number_format($summary['bill_count']) }} {{ Str::plural('bill', $summary['bill_count']) }}</p>
    </div>

    <div class="erp-panel p-4">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Company</p>
        <p class="text-xl font-bold tabular-nums">৳{{ number_format($summary['company'], 2) }}</p>
    </div>

    <div class="erp-panel p-4">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Rental Party</p>
        <p class="text-xl font-bold tabular-nums">৳{{ number_format($summary['rental_party'], 2) }}</p>
    </div>
</div>
