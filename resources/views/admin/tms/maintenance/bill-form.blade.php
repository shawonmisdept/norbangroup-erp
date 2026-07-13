@extends('layouts.admin')
@section('title', $bill->exists ? 'Edit Bill' : 'Add Bill')
@section('admin-content')
@include('partials.erp.page-header', [
    'title' => $bill->exists ? 'Edit Maintenance Bill' : 'Add Maintenance Bill',
    'subtitle' => $vehicle->postingCarNoLabel(),
    'actions' => '<a href="' . route('admin.tms.maintenance.register', $vehicle) . '" class="erp-btn-secondary">← Register</a>',
])

<div class="erp-panel p-6 max-w-4xl">
    <form method="POST" action="{{ $bill->exists ? route('admin.tms.maintenance.bills.update', $bill) : route('admin.tms.maintenance.bills.store', $vehicle) }}" class="space-y-4">
        @csrf
        @if($bill->exists)
            @method('PUT')
        @endif

        <input type="hidden" name="factory_id" value="{{ $vehicle->factory_id }}">
        <input type="hidden" name="vehicle_id" value="{{ $vehicle->id }}">

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div>
                <label class="erp-label">Bill / Invoice No</label>
                <input type="text" name="bill_no" class="erp-input @error('bill_no') border-red-400 @enderror" value="{{ old('bill_no', $bill->bill_no) }}" required autofocus>
                @error('bill_no')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="erp-label">Bill Date</label>
                <input type="date" name="bill_date" class="erp-input" value="{{ old('bill_date', $bill->bill_date?->format('Y-m-d') ?? now()->toDateString()) }}" required>
            </div>
            <div>
                <label class="erp-label">Workshop</label>
                <input type="text" name="workshop_name" class="erp-input" value="{{ old('workshop_name', $bill->workshop_name) }}" required placeholder="JK Motors">
            </div>
            <div>
                <label class="erp-label">Paid By</label>
                <select name="paid_by" class="erp-input">
                    @foreach($paidBy as $k => $l)
                        <option value="{{ $k }}" @selected(old('paid_by', $bill->paid_by) === $k)>{{ $l }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="border-t pt-4">
            <div class="flex items-center justify-between mb-2">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Bill Items</p>
                <button type="button" id="add-item-row" class="erp-btn-sm-secondary">+ Add Item</button>
            </div>

            <div class="hidden md:grid grid-cols-12 gap-2 mb-1 px-0.5">
                <p class="col-span-5 text-[10px] font-semibold text-gray-400 uppercase">Item / Service</p>
                <p class="col-span-2 text-[10px] font-semibold text-gray-400 uppercase">Qty</p>
                <p class="col-span-2 text-[10px] font-semibold text-gray-400 uppercase">Unit</p>
                <p class="col-span-2 text-[10px] font-semibold text-gray-400 uppercase">Amount</p>
                <p class="col-span-1"></p>
            </div>

            <div id="items-container" class="space-y-2">
                @php
                    $items = old('items', $bill->exists
                        ? $bill->items->map(fn($i) => [
                            'item_name' => $i->item_name,
                            'quantity' => $i->quantity,
                            'unit' => $i->unit,
                            'amount' => $i->amount,
                        ])->all()
                        : [['item_name' => '', 'quantity' => '', 'unit' => '', 'amount' => '']]);
                @endphp

                @foreach($items as $i => $item)
                    <div class="grid grid-cols-12 gap-2 item-row">
                        <div class="col-span-12 md:col-span-5">
                            <input type="text" name="items[{{ $i }}][item_name]" class="erp-input" placeholder="Item / service" value="{{ $item['item_name'] ?? '' }}" required>
                        </div>
                        <div class="col-span-4 md:col-span-2">
                            <input type="number" step="0.001" min="0" name="items[{{ $i }}][quantity]" class="erp-input" placeholder="Qty" value="{{ $item['quantity'] ?? '' }}">
                        </div>
                        <div class="col-span-4 md:col-span-2">
                            <select name="items[{{ $i }}][unit]" class="erp-input">
                                <option value="">—</option>
                                @foreach($units as $unit)
                                    <option value="{{ $unit }}" @selected(($item['unit'] ?? '') === $unit)>{{ $unit }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-span-3 md:col-span-2">
                            <input type="number" step="0.01" min="0" name="items[{{ $i }}][amount]" class="erp-input" placeholder="Amount" value="{{ $item['amount'] ?? '' }}" required>
                        </div>
                        <div class="col-span-1 flex items-center">
                            <button type="button" class="text-xs text-red-600 remove-item-row" title="Remove">×</button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div>
            <label class="erp-label">Notes</label>
            <textarea name="notes" class="erp-input" rows="2">{{ old('notes', $bill->notes) }}</textarea>
        </div>

        <div class="flex gap-2 pt-2">
            <button type="submit" class="erp-btn-primary">Save Bill</button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('items-container');
    let itemIndex = {{ count($items) }};
    const units = @json(array_values($units));
    const unitOptions = units.map(u => `<option value="${u}">${u}</option>`).join('');

    document.getElementById('add-item-row')?.addEventListener('click', () => {
        const row = document.createElement('div');
        row.className = 'grid grid-cols-12 gap-2 item-row';
        row.innerHTML = `
            <div class="col-span-12 md:col-span-5"><input type="text" name="items[${itemIndex}][item_name]" class="erp-input" placeholder="Item / service" required></div>
            <div class="col-span-4 md:col-span-2"><input type="number" step="0.001" min="0" name="items[${itemIndex}][quantity]" class="erp-input" placeholder="Qty"></div>
            <div class="col-span-4 md:col-span-2"><select name="items[${itemIndex}][unit]" class="erp-input"><option value="">—</option>${unitOptions}</select></div>
            <div class="col-span-3 md:col-span-2"><input type="number" step="0.01" min="0" name="items[${itemIndex}][amount]" class="erp-input" placeholder="Amount" required></div>
            <div class="col-span-1 flex items-center"><button type="button" class="text-xs text-red-600 remove-item-row" title="Remove">×</button></div>`;
        container.appendChild(row);
        itemIndex++;
    });

    container?.addEventListener('click', (e) => {
        if (e.target.classList.contains('remove-item-row')) {
            const rows = container.querySelectorAll('.item-row');
            if (rows.length > 1) e.target.closest('.item-row')?.remove();
        }
    });
});
</script>
@endsection
