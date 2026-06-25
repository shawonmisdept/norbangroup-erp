@extends('layouts.careers')

@section('title', 'Application Status')

@section('content')
@php
    $pipeline = ['applied', 'screening', 'interview', 'selected', 'offered', 'hired'];
    $currentIdx = array_search($application->status, $pipeline, true);
    if ($currentIdx === false) { $currentIdx = -1; }
    $badge = match($application->status) {
        'hired' => 'background:#dcfce7;color:#166534;',
        'rejected', 'withdrawn' => 'background:#fee2e2;color:#991b1b;',
        default => 'background:#fef3c7;color:#92400e;',
    };
@endphp

<div class="careers-centered">
    <a href="{{ route('careers.track') }}" class="careers-back">← Track another application</a>

    <div class="careers-card">
        <div class="careers-card-body">
        <div class="flex flex-wrap items-start justify-between gap-4 mb-6">
            <div>
                <p class="careers-section-title">Application</p>
                <p class="text-xl font-bold font-mono text-[var(--careers-blue)]">{{ $application->application_no }}</p>
                <p class="text-sm text-[var(--careers-muted)] mt-1">{{ $application->name }}</p>
            </div>
            <span class="careers-tag text-sm !px-4 !py-1.5" style="{{ $badge }}">{{ $application->statusLabel() }}</span>
        </div>

        @if(! in_array($application->status, ['rejected', 'withdrawn'], true))
            <p class="careers-section-title mb-3">Application Progress</p>
            <div class="careers-pipeline">
                @foreach(['applied' => 'Applied', 'screening' => 'Screening', 'interview' => 'Interview', 'selected' => 'Selected', 'offered' => 'Offered', 'hired' => 'Hired'] as $step => $label)
                    @php
                        $idx = array_search($step, $pipeline, true);
                        $class = $currentIdx !== false && $idx < $currentIdx ? 'done' : ($application->status === $step ? 'current' : '');
                    @endphp
                    <div class="careers-pipeline-step {{ $class }}">
                        <div class="careers-pipeline-dot">{{ $idx + 1 }}</div>
                        <div class="careers-pipeline-label">{{ $label }}</div>
                    </div>
                @endforeach
            </div>
        @endif

        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm mt-4">
            <div><dt class="careers-section-title">Position</dt><dd class="font-medium">{{ $application->jobPosting?->title }}</dd></div>
            <div><dt class="careers-section-title">Factory</dt><dd>{{ $application->factory?->name }}</dd></div>
            <div><dt class="careers-section-title">Applied On</dt><dd>{{ $application->applied_at->format('d M Y') }}</dd></div>
            @if($upcomingInterview ?? null)
                <div><dt class="careers-section-title">Interview Scheduled</dt><dd class="font-medium text-[var(--careers-blue)]">{{ $upcomingInterview->scheduled_at->format('d M Y, h:i A') }}</dd></div>
            @endif
        </dl>

        @if($application->status === 'rejected' && $application->rejection_reason)
            <div class="mt-4 p-3 bg-red-50 border border-red-100 rounded-lg text-sm text-red-800">{{ $application->rejection_reason }}</div>
        @endif
        </div>
    </div>
</div>
@endsection
