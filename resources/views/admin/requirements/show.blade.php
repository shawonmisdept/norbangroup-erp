@extends('layouts.admin')

@section('title', $order->ref_code . ' — Requirements')

@section('breadcrumbs')
    <a href="{{ route('admin.requirements.index') }}" class="hover:text-brand">Requirements</a>
    <span>/</span>
    <span class="text-gray-800 font-medium">{{ $order->ref_code }}</span>
@endsection

@section('admin-content')

@include('partials.erp.page-header', [
    'title' => $order->ref_code,
    'subtitle' => $order->name . ($order->company ? ' · ' . $order->company : ''),
    'actions' => view('admin.requirements._status-badge', ['order' => $order])->render(),
])

<div class="grid grid-cols-1 xl:grid-cols-3 gap-4">

    <div class="xl:col-span-2 space-y-4">

        <div class="erp-panel">
            <div class="erp-panel-head">
                <h2 class="text-xs font-semibold text-gray-700 uppercase tracking-wide">Contact Information</h2>
            </div>
            <div class="erp-panel-body grid grid-cols-2 lg:grid-cols-4 gap-4 text-sm">
                <div><p class="erp-form-label !mb-0.5">Name</p><p class="font-medium">{{ $order->name }}</p></div>
                <div><p class="erp-form-label !mb-0.5">Company</p><p class="font-medium">{{ $order->company ?: '—' }}</p></div>
                <div><p class="erp-form-label !mb-0.5">Email</p><p class="font-medium text-brand">{{ $order->email }}</p></div>
                <div><p class="erp-form-label !mb-0.5">Phone</p><p class="font-medium">{{ $order->phone }}</p></div>
            </div>
        </div>

        <div class="erp-panel">
            <div class="erp-panel-head">
                <h2 class="text-xs font-semibold text-gray-700 uppercase tracking-wide">Product Requirement</h2>
            </div>
            <div class="erp-panel-body">
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div><p class="erp-form-label !mb-0.5">Item Name</p><p class="font-medium">{{ $order->item_name }}</p></div>
                    <div><p class="erp-form-label !mb-0.5">Quantity</p><p class="font-medium tabular-nums">{{ $order->quantity ? $order->quantity . ' pcs' : '—' }}</p></div>
                </div>
                @if($order->notes)
                    <div class="mt-4 pt-4 border-t border-erp-border">
                        <p class="erp-form-label !mb-1">Notes</p>
                        <p class="text-sm text-gray-600 leading-relaxed whitespace-pre-line">{{ $order->notes }}</p>
                    </div>
                @endif
            </div>
        </div>

        @if($order->hasQuoteBreakdown())
        <div class="erp-panel">
            <div class="erp-panel-head">
                <h2 class="text-xs font-semibold text-gray-700 uppercase tracking-wide">Quotation Summary</h2>
            </div>
            <div class="erp-panel-body text-sm space-y-3">
                <div class="flex flex-wrap gap-2 text-xs">
                    @if($order->quote_garment_type)
                        <span class="erp-badge bg-gray-100 text-gray-700">{{ ucfirst($order->quote_garment_type) }}</span>
                    @endif
                    @if($order->quote_basis)
                        <span class="erp-badge bg-purple-100 text-purple-800">{{ strtoupper($order->quote_basis) }}</span>
                    @endif
                    @if($order->quote_currency)
                        <span class="erp-badge bg-gray-100 text-gray-600">{{ $order->quote_currency }}</span>
                    @endif
                </div>
                @if($order->quote_price_per_pc)
                    <p class="text-lg font-bold text-brand tabular-nums">{{ $order->currencySymbol() }}{{ number_format((float) $order->quote_price_per_pc, 2) }} <span class="text-sm font-normal text-gray-500">/ pc</span></p>
                @endif
                @if($order->quote_amount)
                    <p class="text-sm text-gray-600">Order total: <strong class="tabular-nums">{{ $order->currencySymbol() }}{{ number_format((float) $order->quote_amount, 2) }}</strong>@if($order->quantity) <span class="text-gray-400">({{ number_format($order->quantity) }} pcs)</span>@endif</p>
                @endif
                @if($order->quote_lead_time_days || $order->quote_valid_until || $order->quote_payment_terms)
                    <ul class="text-xs text-gray-500 space-y-1">
                        @if($order->quote_lead_time_days)
                            <li>Lead time: {{ $order->quote_lead_time_days }} days</li>
                        @endif
                        @if($order->quote_valid_until)
                            <li>Valid until: {{ $order->quote_valid_until->format('d M Y') }}</li>
                        @endif
                        @if($order->quote_payment_terms)
                            <li>Payment: {{ $order->quote_payment_terms }}</li>
                        @endif
                    </ul>
                @endif
                <div class="border-t border-erp-border pt-3 space-y-1">
                    @foreach($order->quote_breakdown['sections'] ?? [] as $section)
                        @if(($section['subtotal_pc'] ?? 0) > 0)
                            <div class="flex justify-between text-xs">
                                <span class="text-gray-500">{{ $section['label'] ?? $section['code'] }}</span>
                                <span class="tabular-nums font-medium">{{ $order->currencySymbol() }}{{ number_format((float) $section['subtotal_pc'], 2) }}</span>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        @if(auth()->user()->hasPermission('orders.update'))
            @if($order->showsCommercialQuoteEditor())
        <div class="erp-panel ring-2 ring-brand/20" id="commercial-quote">
            <div class="erp-panel-head flex items-center justify-between gap-2 bg-brand/5">
                <div>
                    <h2 class="text-xs font-semibold text-brand uppercase tracking-wide">Commercial Quote</h2>
                    <p class="text-[11px] text-gray-500 mt-0.5">Status: <strong class="text-indigo-700">Commercial Quote</strong> — fill costing below, then send from Terms tab.</p>
                </div>
                @if($order->quote_amount)
                    <span class="text-xs font-semibold tabular-nums text-brand">{{ $order->currencySymbol() }}{{ number_format((float) $order->quote_amount, 2) }}</span>
                @endif
            </div>
            <div class="erp-panel-body">
                <form method="POST" action="{{ route('admin.requirements.workflow', $order) }}">
                    @csrf @method('PATCH')
                    <input type="hidden" name="assigned_to_user_id" value="{{ $order->assigned_to_user_id ?? '' }}">
                    @include('admin.requirements.partials.quote-breakdown')
                </form>
            </div>
        </div>
            @else
        <div class="erp-panel border-dashed border-2 border-gray-200 bg-gray-50/50" id="commercial-quote-locked">
            <div class="erp-panel-body text-sm text-gray-600 space-y-3">
                <div class="flex items-start gap-3">
                    <span class="text-2xl leading-none opacity-40">📋</span>
                    <div>
                        <p class="font-semibold text-gray-800">Commercial Quote (locked)</p>
                        <p class="text-xs text-gray-500 mt-1 leading-relaxed">
                            Costing sheet opens only when Requirement Status is set to
                            <strong class="text-indigo-700">Commercial Quote</strong>.
                        </p>
                    </div>
                </div>
                <ol class="text-xs text-gray-600 space-y-1.5 list-decimal list-inside bg-white rounded-lg border border-erp-border p-3">
                    <li>Right sidebar → <strong>Requirement Status</strong></li>
                    <li>Select <strong>Commercial Quote</strong></li>
                    <li>Click <strong>Save & Notify Client</strong></li>
                    <li>This section will appear here for fabric, trims, CM/FOB costing</li>
                </ol>
                @if($order->status === 'Quoted' && $order->hasQuoteBreakdown())
                    <p class="text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded px-3 py-2">
                        Already quoted. Change status back to <strong>Commercial Quote</strong> to revise costing.
                    </p>
                @endif
            </div>
        </div>
            @endif
        @endif

        <div class="erp-panel">
            <div class="erp-panel-head">
                <h2 class="text-xs font-semibold text-gray-700 uppercase tracking-wide">Reference Files</h2>
            </div>
            <div class="erp-panel-body grid grid-cols-1 md:grid-cols-2 gap-6">
                @include('partials.reference-files', [
                    'files' => $order->normalizedFiles('techpack'),
                    'type'  => 'techpack',
                    'order' => $order,
                    'icon'  => '📋',
                    'label' => 'Tech Pack',
                ])
                @include('partials.reference-files', [
                    'files' => $order->normalizedFiles('artwork'),
                    'type'  => 'artwork',
                    'order' => $order,
                    'icon'  => '🎨',
                    'label' => 'Artwork',
                ])
            </div>
        </div>
    </div>

    <div>
        @if(auth()->user()->hasPermission('orders.update'))
        <div class="erp-panel erp-sidebar-sticky">
            <div class="erp-panel-head">
                <h2 class="text-xs font-semibold text-gray-700 uppercase tracking-wide">Status & Assignment</h2>
            </div>
            <div class="erp-panel-body space-y-5" x-data="{ selectedStatus: @js($order->status) }">
                <form method="POST" action="{{ route('admin.requirements.update', $order) }}">
                    @csrf @method('PATCH')
                    <label class="erp-form-label">Requirement Status</label>
                    <select name="status" class="erp-input !text-xs mb-2" x-model="selectedStatus">
                        @foreach(\App\Models\Order::availableStatuses() as $s)
                            <option value="{{ $s }}" {{ $order->status === $s ? 'selected' : '' }}>{{ $s }}</option>
                        @endforeach
                    </select>

                    <div x-show="selectedStatus === @js(\App\Models\Order::STATUS_COMMERCIAL_QUOTE)" x-cloak
                        class="mb-3 rounded-lg border border-indigo-200 bg-indigo-50/80 px-3 py-2 text-[11px] text-indigo-900 leading-relaxed">
                        <strong>Commercial Quote</strong> — Save to unlock the costing sheet below (fabric, trims, CM/FOB breakdown).
                    </div>
                    <div x-show="selectedStatus === 'Quoted'" x-cloak
                        class="mb-3 rounded-lg border border-purple-200 bg-purple-50/80 px-3 py-2 text-[11px] text-purple-900 leading-relaxed">
                        Client has been quoted. Use <strong>Quotation Summary</strong> above to review. Revise costing by setting status back to <strong>Commercial Quote</strong>.
                    </div>

                    <p class="text-[11px] text-gray-400 mb-4">
                        Status change triggers email to <strong class="text-gray-600">{{ $order->email }}</strong>.
                    </p>
                    <button type="submit" class="erp-btn-primary w-full justify-center !py-2.5">
                        Save & Notify Client
                    </button>
                </form>

                <div class="border-t border-erp-border pt-5">
                    <form method="POST" action="{{ route('admin.requirements.workflow', $order) }}">
                        @csrf @method('PATCH')
                        <label class="erp-form-label">Assigned To</label>
                        <select name="assigned_to_user_id" class="erp-input !text-xs mb-3" data-searchable="true" data-placeholder="Search assignee…">
                            <option value="">— Unassigned —</option>
                            @foreach($assignees as $id => $label)
                                <option value="{{ $id }}" {{ (string) $order->assigned_to_user_id === (string) $id ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @if($order->assignedTo)
                            <p class="text-[11px] text-gray-400 mb-3">Currently: {{ \App\Support\RequirementAssigneeOptions::label($order->assignedTo) }}</p>
                        @endif
                        <button type="submit" class="erp-btn-secondary w-full justify-center !py-2.5">Save Assignment</button>
                    </form>
                </div>
            </div>
        </div>
        @endif

        @if(auth()->user()->hasPermission('orders.delete'))
        <div class="erp-panel mt-4 border-red-100">
            <div class="erp-panel-head bg-red-50/50">
                <h2 class="text-xs font-semibold text-red-700 uppercase tracking-wide">Danger Zone</h2>
            </div>
            <div class="erp-panel-body">
                <p class="text-xs text-gray-500 mb-3">Permanently delete this requirement and all uploaded reference files.</p>
                <form method="POST" action="{{ route('admin.requirements.destroy', $order) }}" data-confirm="Delete requirement {{ $order->ref_code }}? This cannot be undone.">
                    @csrf @method('DELETE')
                    <button type="submit" class="erp-btn-danger w-full justify-center !py-2.5">Delete Requirement</button>
                </form>
            </div>
        </div>
        @endif

        <div class="erp-panel mt-4">
            <div class="erp-panel-head">
                <h2 class="text-xs font-semibold text-gray-700 uppercase tracking-wide">Meta</h2>
            </div>
            <div class="erp-panel-body space-y-2 text-xs">
                <div class="flex justify-between"><span class="text-gray-400">Submitted</span><span class="tabular-nums">{{ $order->created_at->format('d M Y H:i') }}</span></div>
                <div class="flex justify-between"><span class="text-gray-400">Last Updated</span><span class="tabular-nums">{{ $order->updated_at->format('d M Y H:i') }}</span></div>
            </div>
        </div>
    </div>
</div>
@endsection
