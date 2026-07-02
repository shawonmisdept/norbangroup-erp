@php
    $quoteService = app(\App\Services\Commercial\QuoteBreakdownService::class);
    $quoteQuantity = max(1, (int) ($order->quantity ?? 1));
    $initialGarmentType = old('quote_garment_type', $order->quote_garment_type ?? 'woven');
    $initialQuoteBasis = old('quote_basis', $order->quote_basis ?? 'fob');
    $initialCurrency = old('quote_currency', $order->quote_currency ?? 'BDT');

    $initialBreakdown = $order->hasQuoteBreakdown()
        ? $quoteService->calculate($order->quote_breakdown)
        : $quoteService->template($initialGarmentType, $initialQuoteBasis, $quoteQuantity);

    $quoteTemplates = [];
    foreach (array_keys($quoteService->garmentTypes()) as $type) {
        foreach (array_keys($quoteService->quoteBases()) as $basis) {
            $quoteTemplates[$type][$basis] = $quoteService->template($type, $basis, $quoteQuantity);
        }
    }
@endphp

<div
    x-data="commercialQuoteBreakdown({
        garmentType: @js($initialGarmentType),
        quoteBasis: @js($initialQuoteBasis),
        currency: @js($initialCurrency),
        quantity: @js($quoteQuantity),
        breakdown: @js($initialBreakdown),
        templates: @js($quoteTemplates),
    })"
    x-init="init()"
>
    <input type="hidden" name="quote_breakdown" x-ref="breakdownJson" value="">
    <input type="hidden" name="quote_price_per_pc" x-ref="pricePerPc" value="">
    <input type="hidden" name="quote_amount" x-ref="quoteAmount" value="{{ old('quote_amount', $order->quote_amount) }}">

    {{-- Sticky summary --}}
    <div class="sticky top-[4.5rem] z-10 -mx-1 mb-4 px-3 py-2.5 rounded-lg border border-brand/25 bg-white shadow-sm flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap gap-2 text-[11px]">
            <span class="erp-badge bg-gray-100 text-gray-700" x-text="garmentType.charAt(0).toUpperCase() + garmentType.slice(1)"></span>
            <span class="erp-badge bg-purple-100 text-purple-800" x-text="quoteBasis.toUpperCase()"></span>
            <span class="erp-badge bg-gray-100 text-gray-600" x-text="currency"></span>
            <span class="text-gray-400">{{ number_format($quoteQuantity) }} pcs</span>
        </div>
        <div class="text-right">
            <p class="text-lg font-bold tabular-nums text-brand leading-tight" x-text="formatMoney(breakdown.summary?.price_per_pc) + ' / pc'"></p>
            <p class="text-[11px] text-gray-500 tabular-nums" x-text="'Order total: ' + formatMoney(breakdown.summary?.order_total)"></p>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="flex gap-1 mb-4 border-b border-erp-border">
        <button type="button" class="px-3 py-2 text-xs font-semibold border-b-2 -mb-px transition"
            :class="activeTab === 'setup' ? 'border-brand text-brand' : 'border-transparent text-gray-500 hover:text-gray-700'"
            @click="activeTab = 'setup'">Setup</button>
        <button type="button" class="px-3 py-2 text-xs font-semibold border-b-2 -mb-px transition"
            :class="activeTab === 'costing' ? 'border-brand text-brand' : 'border-transparent text-gray-500 hover:text-gray-700'"
            @click="activeTab = 'costing'">Cost Breakdown</button>
        <button type="button" class="px-3 py-2 text-xs font-semibold border-b-2 -mb-px transition"
            :class="activeTab === 'terms' ? 'border-brand text-brand' : 'border-transparent text-gray-500 hover:text-gray-700'"
            @click="activeTab = 'terms'">Terms & Send</button>
    </div>

    {{-- Tab: Setup --}}
    <div x-show="activeTab === 'setup'" class="space-y-3">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <div>
                <label class="erp-form-label">Garment Type</label>
                <select name="quote_garment_type" class="erp-input !text-sm" x-model="garmentType" @change="onGarmentTypeChange()">
                    @foreach($quoteService->garmentTypes() as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="erp-form-label">Quote Basis</label>
                <select name="quote_basis" class="erp-input !text-sm" x-model="quoteBasis" @change="onQuoteBasisChange()">
                    @foreach($quoteService->quoteBases() as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="erp-form-label">Currency</label>
                <select name="quote_currency" class="erp-input !text-sm" x-model="currency" @change="onCurrencyChange()">
                    @foreach($quoteService->currencies() as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="erp-form-label">Quantity</label>
                <input type="text" class="erp-input !text-sm bg-gray-50" readonly value="{{ number_format($quoteQuantity) }} pcs">
            </div>
        </div>
        <p class="text-xs text-gray-500">Choose template under <strong>Cost Breakdown</strong>. CM hides logistics; FOB includes shipment. Custom lines can be added in any section or under <strong>Other / Custom Items</strong>.</p>
        <button type="button" class="erp-btn-secondary !text-xs" @click="activeTab = 'costing'">Continue to Cost Breakdown →</button>
    </div>

    {{-- Tab: Costing --}}
    <div x-show="activeTab === 'costing'" class="space-y-2">
        <template x-for="section in breakdown.sections" :key="section.code">
            <div class="rounded-lg border border-erp-border overflow-hidden bg-white">
                <button type="button" class="w-full flex items-center justify-between gap-3 px-3 py-2.5 text-left hover:bg-gray-50/80 transition"
                    @click="toggleSection(section.code)">
                    <div class="flex items-center gap-2 min-w-0">
                        <svg class="w-4 h-4 text-gray-400 shrink-0 transition-transform" :class="isSectionOpen(section.code) ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        <span class="text-xs font-semibold text-gray-800 truncate" x-text="section.label"></span>
                        <span x-show="sectionHasActiveLines(section)" class="w-1.5 h-1.5 rounded-full bg-brand shrink-0"></span>
                    </div>
                    <span class="text-xs font-semibold tabular-nums text-brand shrink-0" x-text="formatMoney(section.subtotal_pc)"></span>
                </button>

                <div x-show="isSectionOpen(section.code)" class="border-t border-erp-border">
                    <div class="overflow-x-auto">
                        <table class="w-full text-xs">
                            <thead class="bg-gray-50 text-[10px] uppercase tracking-wide text-gray-500">
                                <tr>
                                    <th class="px-2 py-1.5 text-left w-8"></th>
                                    <th class="px-2 py-1.5 text-left min-w-[140px]">Item</th>
                                    <th class="px-2 py-1.5 text-left w-20">Type</th>
                                    <th class="px-2 py-1.5 text-left">Values</th>
                                    <th class="px-2 py-1.5 text-right w-24">/ pc</th>
                                    <th class="px-2 py-1.5 w-8"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(line, lineIdx) in section.lines" :key="line.code">
                                    <tr class="border-t border-erp-border/60" :class="!line.enabled ? 'opacity-40 bg-gray-50/50' : ''">
                                        <td class="px-2 py-1.5 align-top">
                                            <input type="checkbox" class="rounded border-gray-300" :checked="line.enabled"
                                                @change="toggleLine(line)" x-show="line.optional || line.custom">
                                        </td>
                                        <td class="px-2 py-1.5 align-top">
                                            <input x-show="line.custom" type="text" class="erp-input !text-xs !py-1 w-full" placeholder="Item name…"
                                                x-model="line.label" @input="recalculate()">
                                            <span x-show="!line.custom" class="font-medium text-gray-700" x-text="line.label"></span>
                                        </td>
                                        <td class="px-2 py-1.5 align-top">
                                            <select x-show="line.custom" class="erp-input !text-xs !py-1" x-model="line.calc" @change="recalculate()">
                                                <option value="amount">Amount</option>
                                                <option value="consumption">Consumption</option>
                                                <option value="lump">Lump sum</option>
                                            </select>
                                            <span x-show="!line.custom" class="text-gray-500 capitalize" x-text="line.calc"></span>
                                        </td>
                                        <td class="px-2 py-1.5 align-top">
                                            <div class="space-y-1" x-show="line.calc === 'consumption' && line.enabled">
                                                <div class="flex flex-wrap items-end gap-1.5">
                                                    <div>
                                                        <label class="text-[10px] text-gray-500 block mb-0.5">Consumption</label>
                                                        <div class="flex items-center gap-1">
                                                            <input type="number" step="0.0001" min="0" class="erp-input !text-xs !py-1 w-20"
                                                                x-model.number="line.consumption" @input="recalculate()">
                                                            <span class="text-[10px] text-gray-400 whitespace-nowrap" x-text="'/' + (line.unit || 'unit') + ' pc'"></span>
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <label class="text-[10px] text-gray-500 block mb-0.5">Rate</label>
                                                        <div class="flex items-center gap-1">
                                                            <span class="text-[10px] text-gray-400" x-text="currency === 'USD' ? '$' : '৳'"></span>
                                                            <input type="number" step="0.01" min="0" class="erp-input !text-xs !py-1 w-20"
                                                                x-model.number="line.rate" @input="recalculate()">
                                                            <span class="text-[10px] text-gray-400" x-text="'/' + (line.unit || 'unit')"></span>
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <label class="text-[10px] text-gray-500 block mb-0.5">Wastage</label>
                                                        <div class="flex items-center gap-1">
                                                            <input type="number" step="0.01" min="0" class="erp-input !text-xs !py-1 w-16"
                                                                x-model.number="line.wastage_pct" @input="recalculate()">
                                                            <span class="text-[10px] text-gray-400">%</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <p class="text-[10px] text-gray-400">Formula: consumption × rate × (1 + wastage%)</p>
                                            </div>
                                            <div x-show="line.calc === 'amount' && line.enabled">
                                                <input type="number" step="0.01" min="0" placeholder="Amount / pc" class="erp-input !text-xs !py-1 w-28"
                                                    x-model.number="line.amount_pc" @input="recalculate()">
                                            </div>
                                            <div x-show="line.calc === 'lump' && line.enabled">
                                                <input type="number" step="0.01" min="0" placeholder="Lump total" class="erp-input !text-xs !py-1 w-28"
                                                    x-model.number="line.lump_total" @input="recalculate()">
                                            </div>
                                            <div x-show="line.calc === 'percent' && line.enabled" class="flex items-center gap-1">
                                                <input type="number" step="0.01" min="0" class="erp-input !text-xs !py-1 w-16"
                                                    x-model.number="line.percent" @input="recalculate()">
                                                <span class="text-gray-400">%</span>
                                            </div>
                                        </td>
                                        <td class="px-2 py-1.5 align-top text-right font-semibold tabular-nums text-brand" x-text="formatMoney(line.computed_pc)"></td>
                                        <td class="px-2 py-1.5 align-top">
                                            <button type="button" x-show="line.custom" class="text-red-500 hover:text-red-700 text-sm leading-none" title="Remove"
                                                @click="removeCustomLine(section, lineIdx)">×</button>
                                        </td>
                                    </tr>
                                </template>
                                <tr x-show="!section.lines.length">
                                    <td colspan="6" class="px-3 py-4 text-center text-gray-400">No items yet.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="px-3 py-2 bg-gray-50/80 border-t border-erp-border flex justify-end">
                        <button type="button" class="text-xs text-brand font-semibold hover:underline"
                            @click="addCustomLine(section.code)">+ Add custom item</button>
                    </div>
                </div>
            </div>
        </template>
    </div>

    {{-- Tab: Terms --}}
    <div x-show="activeTab === 'terms'" class="space-y-4 max-w-xl">
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="erp-form-label">Lead Time (days)</label>
                <input type="number" min="0" name="quote_lead_time_days" value="{{ old('quote_lead_time_days', $order->quote_lead_time_days) }}" class="erp-input !text-sm">
            </div>
            <div>
                <label class="erp-form-label">Valid Until</label>
                <input type="date" name="quote_valid_until" value="{{ old('quote_valid_until', optional($order->quote_valid_until)->format('Y-m-d')) }}" class="erp-input !text-sm">
            </div>
        </div>
        <div>
            <label class="erp-form-label">Payment Terms</label>
            <input type="text" name="quote_payment_terms" value="{{ old('quote_payment_terms', $order->quote_payment_terms) }}" class="erp-input !text-sm" placeholder="e.g. 30% advance, 70% before shipment">
        </div>
        <div>
            <label class="erp-form-label">Remarks to Client</label>
            <textarea name="quote_notes" rows="4" class="erp-input !text-sm" placeholder="Notes included in the quotation email…">{{ old('quote_notes', $order->quote_notes) }}</textarea>
        </div>
        @if($order->quoted_at)
            <p class="text-xs text-gray-500">Last sent: {{ $order->quoted_at->format('d M Y H:i') }}</p>
        @endif
        <label class="flex items-center gap-2 text-sm">
            <input type="hidden" name="send_quote" value="0">
            <input type="checkbox" name="send_quote" value="1" class="rounded border-gray-300">
            Mark as Quoted & email quote to client
        </label>
    </div>

    <div class="mt-6 pt-4 border-t border-erp-border flex flex-wrap items-center justify-between gap-3">
        <p class="text-xs text-gray-500">Total updates automatically from breakdown.</p>
        <button type="submit" class="erp-btn-primary !px-6" @click="syncHiddenFields()">Save Quote</button>
    </div>
</div>
