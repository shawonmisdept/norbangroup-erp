<input type="hidden" class="salary-modal-title" value="Salary Head — {{ $head->name }}">

<div class="space-y-4">
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3">
        <div>
            <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 block">Name</span>
            <span class="font-medium">{{ $head->name }}</span>
        </div>
        <div>
            <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 block">Code</span>
            <code class="text-xs">{{ $head->code }}</code>
        </div>
        <div>
            <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 block">Type</span>
            {{ $head->head_type }} — {{ $head->headTypeLabel() }}
        </div>
        <div>
            <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 block">Sequence</span>
            {{ $head->sort_order }}
        </div>
        <div>
            <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 block">Sort Code</span>
            {{ $head->sort_code ?: '—' }}
        </div>
        <div>
            <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 block">Status</span>
            <span class="{{ $head->is_active ? 'text-green-600' : 'text-gray-400' }}">{{ $head->is_active ? 'Active' : 'Inactive' }}</span>
        </div>
        <div>
            <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 block">Factory</span>
            {{ $head->factory?->name }}
        </div>
        <div>
            <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 block">Created</span>
            {{ $head->created_at?->format('M d, Y') ?? '—' }}
        </div>
    </div>

    @if($head->name_bangla)
        <div>
            <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 block">Native Name</span>
            {{ $head->name_bangla }}
        </div>
    @endif

    @if($head->description)
        <div>
            <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 block">Description</span>
            {{ $head->description }}
        </div>
    @endif

    <div class="flex flex-wrap gap-4 text-xs text-gray-600 pt-1 border-t border-erp-border">
        <span>Taxable: {{ $head->is_taxable ? 'Yes' : 'No' }}</span>
        <span>Perquisite: {{ $head->is_perquisite ? 'Yes' : 'No' }}</span>
        <span>Disburse: {{ $head->is_disburse ? 'Yes' : 'No' }}</span>
    </div>
</div>
