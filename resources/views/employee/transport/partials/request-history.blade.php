@if($histories->isNotEmpty())
    <div class="emp-card p-4 {{ $class ?? '' }}">
        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-3">Status History</p>
        <ul class="space-y-3 text-sm">
            @foreach($histories->sortByDesc('created_at') as $history)
                <li class="border-b border-gray-100 pb-3 last:border-0 last:pb-0">
                    <p class="font-medium text-gray-900">{{ $history->fromStatusLabel() }} → {{ $history->toStatusLabel() }}</p>
                    <p class="text-xs text-gray-500 mt-0.5">{{ $history->created_at?->format('d M Y, H:i') }} · {{ $history->actorLabel() }}</p>
                    @if($history->notes)
                        <p class="text-xs text-gray-600 mt-1">{{ $history->notes }}</p>
                    @endif
                </li>
            @endforeach
        </ul>
    </div>
@endif
