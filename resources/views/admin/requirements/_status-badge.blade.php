<span class="erp-badge {{ \App\Models\Order::statusColors()[$order->status] ?? 'bg-gray-100 text-gray-600' }}">
    {{ $order->status }}
</span>
