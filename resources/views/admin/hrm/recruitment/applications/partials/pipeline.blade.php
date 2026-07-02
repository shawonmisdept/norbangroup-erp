@php
    $stages = [
        'applied'   => 'Applied',
        'screening' => 'Screening',
        'interview' => 'Interview',
        'selected'  => 'Selected',
        'offered'   => 'Offered',
        'hired'     => 'Hired',
    ];
    $stageKeys = array_keys($stages);
    $currentIndex = array_search($application->status, $stageKeys, true);
    $isTerminal = in_array($application->status, ['rejected', 'withdrawn'], true);
@endphp

@if($isTerminal)
    <div class="rounded-lg border px-4 py-3 text-sm {{ $application->status === 'rejected' ? 'bg-red-50 border-red-200 text-red-800' : 'bg-gray-50 border-gray-200 text-gray-700' }}">
        <p class="font-semibold">{{ $application->statusLabel() }}</p>
        @if($application->rejection_reason)
            <p class="text-xs mt-1 opacity-90">{{ $application->rejection_reason }}</p>
        @endif
    </div>
@else
    <div class="overflow-x-auto pb-1">
        <div class="flex items-center min-w-[520px] gap-0">
            @foreach($stages as $key => $label)
                @php
                    $index = $loop->index;
                    $isComplete = $currentIndex !== false && $index < $currentIndex;
                    $isCurrent = $application->status === $key;
                    $dotClass = $isCurrent
                        ? 'bg-brand text-white ring-brand/30 scale-110'
                        : ($isComplete ? 'bg-emerald-500 text-white ring-emerald-200' : 'bg-gray-100 text-gray-400 ring-gray-200');
                    $lineClass = $isComplete ? 'bg-emerald-300' : 'bg-gray-200';
                    $textClass = $isCurrent ? 'text-brand font-semibold' : ($isComplete ? 'text-emerald-700' : 'text-gray-400');
                @endphp
                <div class="flex items-center flex-1 min-w-0 {{ $loop->last ? 'flex-none' : '' }}">
                    <div class="flex flex-col items-center gap-1.5 shrink-0">
                        <div class="w-7 h-7 rounded-full flex items-center justify-center text-[10px] font-bold ring-2 transition-all {{ $dotClass }}">
                            @if($isComplete)
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            @else
                                {{ $loop->iteration }}
                            @endif
                        </div>
                        <span class="text-[10px] uppercase tracking-wide whitespace-nowrap {{ $textClass }}">{{ $label }}</span>
                    </div>
                    @if(! $loop->last)
                        <div class="h-0.5 flex-1 mx-1 rounded-full {{ $lineClass }}"></div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
@endif
