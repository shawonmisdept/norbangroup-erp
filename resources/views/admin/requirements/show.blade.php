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
        <div class="erp-panel sticky top-[4.5rem]">
            <div class="erp-panel-head">
                <h2 class="text-xs font-semibold text-gray-700 uppercase tracking-wide">Status Control</h2>
            </div>
            <div class="erp-panel-body">
                <form method="POST" action="{{ route('admin.requirements.update', $order) }}">
                    @csrf @method('PATCH')
                    <label class="erp-form-label">Requirement Status</label>
                    <select name="status" class="erp-input !text-xs mb-3">
                        @foreach(\App\Models\Order::STATUSES as $s)
                            <option value="{{ $s }}" {{ $order->status === $s ? 'selected' : '' }}>{{ $s }}</option>
                        @endforeach
                    </select>
                    <p class="text-[11px] text-gray-400 mb-4">
                        Status change triggers email to <strong class="text-gray-600">{{ $order->email }}</strong>.
                    </p>
                    <button type="submit" class="erp-btn-primary w-full justify-center !py-2.5">
                        Save & Notify Client
                    </button>
                </form>
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
