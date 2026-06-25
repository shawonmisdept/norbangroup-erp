@extends('layouts.admin')

@section('title', 'Requirements — ' . config('app.name'))

@section('breadcrumbs')
    <span class="text-gray-600 font-medium">Operations</span>
    <span>/</span>
    <span class="text-gray-800 font-medium">Requirements</span>
@endsection

@section('admin-content')

@include('partials.erp.page-header', [
    'title' => 'Requirements Dashboard',
    'subtitle' => 'Client submissions from the public requirement form',
])

<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
    @foreach([
        ['Total', $stats['total'], 'text-brand', 'border-brand/20 bg-white'],
        ['New', $stats['new'], 'text-blue-700', 'border-blue-200 bg-blue-50/50'],
        ['In Production', $stats['production'], 'text-emerald-700', 'border-emerald-200 bg-emerald-50/50'],
        ['Approved', $stats['approved'], 'text-gold-dark', 'border-gold/30 bg-gold-light/30'],
    ] as [$label, $count, $text, $panel])
        <div class="erp-kpi {{ $panel }}">
            <p class="erp-kpi-value {{ $text }}">{{ $count }}</p>
            <p class="erp-kpi-label {{ $text }}">{{ $label }}</p>
        </div>
    @endforeach
</div>

<div class="erp-panel mb-4">
    <form method="GET" class="erp-panel-body flex flex-wrap gap-2 items-center">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search ref, name, company, email…"
               class="erp-input flex-1 min-w-48 !text-xs">
        <select name="status" class="erp-input !w-auto !text-xs">
            <option value="">All Status</option>
            @foreach(\App\Models\Order::STATUSES as $s)
                <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ $s }}</option>
            @endforeach
        </select>
        <button type="submit" class="erp-btn-primary">Apply Filter</button>
        <a href="{{ route('admin.requirements.index') }}" class="text-xs text-gray-400 hover:text-gray-600 px-2">Reset</a>
    </form>
</div>

<div class="erp-panel overflow-hidden">
    <div class="erp-panel-head">
        <h2 class="text-xs font-semibold text-gray-700 uppercase tracking-wide">Requirement List</h2>
        <span class="text-[11px] text-gray-400">{{ $orders->total() }} record(s)</span>
    </div>
    <div class="overflow-x-auto">
        <table class="erp-table">
            <thead>
                <tr>
                    <th>Ref #</th>
                    <th>Client</th>
                    <th class="hidden sm:table-cell">Item Name</th>
                    <th class="hidden sm:table-cell">Qty</th>
                    <th>Status</th>
                    <th class="hidden md:table-cell">Date</th>
                    <th class="text-right">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                    <tr>
                        <td><code class="text-[11px] bg-gray-100 px-1.5 py-0.5 rounded-sm font-mono">{{ $order->ref_code }}</code></td>
                        <td>
                            <p class="font-medium text-gray-900 text-sm">{{ $order->name }}</p>
                            <p class="text-[11px] text-gray-400">{{ $order->company ?: '—' }}</p>
                        </td>
                        <td class="hidden sm:table-cell text-xs">{{ $order->item_name }}</td>
                        <td class="hidden sm:table-cell text-xs tabular-nums">{{ $order->quantity ? $order->quantity . ' pcs' : '—' }}</td>
                        <td>
                            <span class="erp-badge {{ \App\Models\Order::statusColors()[$order->status] ?? 'bg-gray-100 text-gray-600' }}">
                                {{ $order->status }}
                            </span>
                        </td>
                        <td class="hidden md:table-cell text-[11px] text-gray-400 tabular-nums">
                            {{ $order->created_at->format('d M Y') }}
                        </td>
                        <td class="text-right">
                            <div class="erp-table-actions">
                                <a href="{{ route('admin.requirements.show', $order) }}" class="erp-btn-sm-secondary">Open</a>
                                @if(auth()->user()->hasPermission('orders.delete'))
                                    <form method="POST" action="{{ route('admin.requirements.destroy', $order) }}" class="inline"
                                          onsubmit="return confirm('Delete requirement {{ $order->ref_code }}? This cannot be undone.')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="erp-btn-danger !py-1 !px-2.5">Del</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-10 text-gray-400 text-sm">No requirements found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($orders->hasPages())
        <div class="px-4 py-3 border-t border-erp-border bg-gray-50/50">{{ $orders->withQueryString()->links() }}</div>
    @endif
</div>
@endsection
