<input type="hidden" class="salary-modal-title" value="Grade Detail — {{ $detail->salaryHead?->name }}">

<div class="space-y-4">
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3">
        <div>
            <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 block">Grade</span>
            {{ $detail->grade?->name }}
        </div>
        <div>
            <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 block">Salary Head</span>
            {{ $detail->salaryHead?->name }}
        </div>
        <div>
            <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 block">Type</span>
            {{ $detail->detail_type }} — {{ $detail->detailTypeLabel() }}
        </div>
        <div>
            <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 block">Is Fixed</span>
            {{ $detail->is_fixed ? 'Yes' : 'No' }}
        </div>
        <div>
            <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 block">Status</span>
            <span class="text-green-600">Active</span>
        </div>
        <div>
            <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 block">Created</span>
            {{ $detail->created_at?->format('M d, Y') ?? '—' }}
        </div>
    </div>

    @if($detail->detail_type === 'M')
        <div>
            <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 block">Formula</span>
            <code class="text-xs block mt-1 p-3 bg-gray-50 rounded border border-erp-border break-all">{{ $detail->formula }}</code>
        </div>
    @elseif($detail->detail_type === 'P')
        <div>
            <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 block">Percentage</span>
            {{ number_format((float) $detail->percentage, 2) }}% of {{ $detail->percentageOfHead?->name }}
        </div>
    @else
        <div>
            <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 block">Fixed Amount</span>
            ৳{{ number_format((float) $detail->amount, 2) }}
        </div>
    @endif
</div>
