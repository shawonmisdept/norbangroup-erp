@if($canViewGratuity && $employee->gratuitySettlement)
    <div class="erp-panel">
        <div class="erp-panel-head">
            <h2 class="text-xs font-semibold text-gray-700 uppercase tracking-wide">Gratuity Settlement</h2>
        </div>
        <div class="erp-panel-body space-y-3 text-sm">
            <div class="flex items-center justify-between gap-2">
                <span class="text-gray-500">Amount</span>
                <strong class="text-brand tabular-nums">৳{{ number_format((float) $employee->gratuitySettlement->gratuity_amount, 2) }}</strong>
            </div>
            <div class="flex items-center justify-between gap-2 text-xs">
                <span class="text-gray-500">Service</span>
                <span>{{ number_format((float) $employee->gratuitySettlement->years_of_service, 1) }} years</span>
            </div>
            <div class="flex items-center justify-between gap-2 text-xs">
                <span class="text-gray-500">Status</span>
                @php
                    $gratuityBadge = $employee->gratuitySettlement->status === 'paid'
                        ? 'bg-green-100 text-green-800'
                        : 'bg-amber-100 text-amber-800';
                @endphp
                <span class="erp-badge {{ $gratuityBadge }}">{{ ucfirst($employee->gratuitySettlement->status) }}</span>
            </div>
            <a href="{{ route('admin.hrm.compliance.gratuity.show', $employee->gratuitySettlement) }}" class="erp-btn-sm-secondary w-full justify-center">View Settlement</a>
        </div>
    </div>
@endif
