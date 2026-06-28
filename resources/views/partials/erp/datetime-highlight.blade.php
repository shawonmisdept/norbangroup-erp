@props(['at' => null, 'variant' => 'admin'])
@php
    use App\Support\PortalDateTime;
    $dt = PortalDateTime::inAppTimezone($at);
@endphp
@if($dt)
<span class="inline-flex flex-wrap items-center gap-x-1 tabular-nums text-xs">
    <span class="font-semibold text-gray-900">{{ $dt->format('d M Y') }}</span>
    <span @class([
        'font-bold',
        'text-amber-700' => $variant === 'admin',
        'text-indigo-700' => $variant === 'employee',
    ])>{{ $dt->format('g:i A') }}</span>
</span>
@else
<span class="text-gray-400">—</span>
@endif
