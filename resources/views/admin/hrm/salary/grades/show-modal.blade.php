<input type="hidden" class="salary-modal-title" value="Salary Grade — {{ $grade->name }}">

<div class="space-y-4">
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3">
        <div>
            <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 block">Name</span>
            <span class="font-medium">{{ $grade->name }}</span>
        </div>
        <div>
            <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 block">Code</span>
            <code class="text-xs">{{ $grade->code }}</code>
        </div>
        <div>
            <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 block">Factory</span>
            {{ $grade->factory?->name }}
        </div>
        <div>
            <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 block">Status</span>
            <span class="{{ $grade->is_active ? 'text-green-600' : 'text-gray-400' }}">{{ $grade->is_active ? 'Active' : 'Inactive' }}</span>
        </div>
    </div>

    @if($grade->description)
        <div>
            <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 block">Description</span>
            {{ $grade->description }}
        </div>
    @endif

    <div>
        <h4 class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-2">Grade Details</h4>
        <div class="overflow-x-auto border border-erp-border rounded-sm">
            <table class="erp-table !text-xs">
                <thead>
                    <tr>
                        <th>Head</th>
                        <th>Type</th>
                        <th>Fixed</th>
                        <th>Value / Formula</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($grade->details->sortBy(fn ($d) => $d->salaryHead?->sort_order ?? 999) as $d)
                        <tr>
                            <td>{{ $d->salaryHead?->name }}</td>
                            <td class="font-semibold">{{ $d->detail_type }}</td>
                            <td>{{ $d->is_fixed ? 'Yes' : 'No' }}</td>
                            <td class="max-w-[200px]">
                                @if($d->detail_type === 'M')
                                    <code class="text-[10px] break-all">{{ $d->formula }}</code>
                                @elseif($d->detail_type === 'P')
                                    {{ number_format((float) $d->percentage, 2) }}% of {{ $d->percentageOfHead?->name }}
                                @else
                                    ৳{{ number_format((float) $d->amount, 2) }}
                                @endif
                            </td>
                            <td class="text-right whitespace-nowrap">
                                <button type="button" data-salary-view="{{ route('admin.hrm.salary.grade-details.show', $d) }}" class="erp-btn-sm-secondary">View</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center py-6 text-gray-400">No details configured.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
