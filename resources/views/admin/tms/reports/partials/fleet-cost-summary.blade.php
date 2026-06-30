<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-4">
    <div class="erp-panel p-4">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Fuel</p>
        <p class="text-xl font-bold tabular-nums">৳{{ number_format($summary['fuel_total'], 2) }}</p>
        <p class="text-xs text-gray-500 mt-1">Company ৳{{ number_format($summary['fuel_company'], 2) }} · Rental ৳{{ number_format($summary['fuel_rental_party'], 2) }}</p>
    </div>

    <div class="erp-panel p-4">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Rental Vehicle Charges</p>
        <p class="text-xl font-bold tabular-nums">৳{{ number_format($summary['rental_charges_total'], 2) }}</p>
        <p class="text-xs text-gray-500 mt-1">Paid ৳{{ number_format($summary['rental_charges_paid'], 2) }} · Pending ৳{{ number_format($summary['rental_charges_pending'], 2) }}</p>
    </div>

    <div class="erp-panel p-4">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Driver Pay</p>
        <p class="text-xl font-bold tabular-nums">৳{{ number_format($summary['driver_pay_total'], 2) }}</p>
        <p class="text-xs text-gray-500 mt-1">Paid ৳{{ number_format($summary['driver_pay_paid'], 2) }} · Pending ৳{{ number_format($summary['driver_pay_pending'], 2) }}</p>
    </div>

    <div class="erp-panel p-4">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Maintenance</p>
        <p class="text-xl font-bold tabular-nums">৳{{ number_format($summary['maintenance_total'], 2) }}</p>
        <p class="text-xs text-gray-500 mt-1">Company ৳{{ number_format($summary['maintenance_company'], 2) }} · Rental ৳{{ number_format($summary['maintenance_rental_party'], 2) }}</p>
    </div>

    <div class="erp-panel p-4 md:col-span-2 lg:col-span-2 bg-brand/5 border-brand/20">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Grand Total</p>
        <p class="text-3xl font-bold tabular-nums text-brand">৳{{ number_format($summary['grand_total'], 2) }}</p>
    </div>
</div>
