@if($letter->response)
    @php
        $responseBadge = match($letter->response) {
            'accepted' => 'bg-green-100 text-green-700',
            'declined' => 'bg-red-100 text-red-700',
            default    => 'bg-gray-100 text-gray-600',
        };
    @endphp
    <span class="erp-badge {{ $responseBadge }} text-[10px]">{{ $letter->responseLabel() }}</span>
    @if($letter->responded_at)
        <span class="text-[10px] text-gray-500">{{ $letter->responded_at->format('d M Y') }}</span>
    @endif
    @if($letter->response === 'declined' && $letter->decline_reason)
        <p class="text-[10px] text-red-700 mt-1">{{ $letter->decline_reason }}</p>
    @endif
@else
    <span class="erp-badge bg-amber-100 text-amber-700 text-[10px]">Awaiting Response</span>
@endif
