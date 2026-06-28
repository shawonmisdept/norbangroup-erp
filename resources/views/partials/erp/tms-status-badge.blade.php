@props(['status', 'label' => null, 'type' => 'request', 'variant' => 'admin'])
@php
    $label = $label ?? config("tms.{$type}_statuses.{$status}", ucfirst((string) $status));
    $class = config("tms.{$type}_status_colors.{$status}", 'bg-gray-100 text-gray-600');
    $badgeClass = $variant === 'employee' ? 'emp-badge' : 'erp-badge';
@endphp
<span {{ $attributes->merge(['class' => "{$badgeClass} {$class}"]) }}>{{ $label }}</span>
