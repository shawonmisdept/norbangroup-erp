<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
    <div class="erp-panel p-4">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Total Amount</p>
        <p class="text-xl font-bold tabular-nums">৳{{ number_format($summary['total_amount'], 2) }}</p>
        <p class="text-xs text-gray-500 mt-1">{{ number_format($summary['entry_count']) }} {{ Str::plural('entry', $summary['entry_count']) }}</p>
    </div>

    <div class="erp-panel p-4">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Total Quantity</p>
        <p class="text-xl font-bold tabular-nums">{{ \App\Support\PortalNumber::quantity($summary['total_quantity']) }}</p>
        <p class="text-xs text-gray-500 mt-1">Litres / units</p>
    </div>

    <div class="erp-panel p-4">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Company</p>
        <p class="text-xl font-bold tabular-nums">৳{{ number_format($summary['company_amount'], 2) }}</p>
    </div>

    <div class="erp-panel p-4">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Rental Party</p>
        <p class="text-xl font-bold tabular-nums">৳{{ number_format($summary['rental_party_amount'], 2) }}</p>
    </div>
</div>
