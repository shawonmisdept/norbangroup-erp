@props(['approvals', 'pendingStep' => null])

@php
    $steps = $approvals instanceof \Illuminate\Support\Collection ? $approvals : collect($approvals);
@endphp

<div class="mt-2 space-y-1.5">
    @foreach($steps as $approval)
        @php
            $tone = match($approval->status) {
                'approved' => 'text-emerald-700',
                'rejected' => 'text-red-600',
                'skipped'  => 'text-gray-400',
                default    => ((int) $pendingStep === (int) $approval->step) ? 'text-amber-700' : 'text-gray-400',
            };
            $icon = match($approval->status) {
                'approved' => '✓',
                'rejected' => '✕',
                'skipped'  => '—',
                default    => ((int) $pendingStep === (int) $approval->step) ? '●' : '○',
            };
        @endphp
        <div class="flex items-start gap-2 text-[10px] {{ $tone }}">
            <span class="mt-0.5 w-3 shrink-0 text-center font-bold">{{ $icon }}</span>
            <div class="min-w-0">
                <p class="font-semibold">{{ $approval->step_label ?? (\App\Models\Hrm\LeaveApproval::STEPS[$approval->step] ?? 'Step ' . $approval->step) }}</p>
                <p>
                    {{ $approval->statusLabel() }}
                    @if($approval->actorName())
                        · {{ $approval->actorName() }}
                    @endif
                    @if($approval->acted_at)
                        · {{ $approval->acted_at->format('d M Y') }}
                    @endif
                </p>
            </div>
        </div>
    @endforeach
</div>
