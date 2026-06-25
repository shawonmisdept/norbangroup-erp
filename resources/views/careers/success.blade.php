@extends('layouts.careers')

@section('title', 'Application Submitted')

@section('content')
<div class="max-w-lg mx-auto text-center py-8">
    <div class="w-20 h-20 mx-auto mb-6 rounded-full bg-green-100 flex items-center justify-center">
        <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M5 13l4 4L19 7" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </div>
    <h1 class="text-2xl font-bold text-[var(--careers-navy)]">Application Submitted!</h1>
    <p class="text-[var(--careers-muted)] mt-2">Thank you, <strong class="text-gray-800">{{ $application->name }}</strong>. HR will review your application shortly.</p>

    <div class="careers-card mt-8 text-left">
        <div class="careers-card-body">
            <p class="careers-section-title">Your Application Number</p>
            <p class="text-3xl font-bold font-mono text-[var(--careers-blue)] tracking-wide">{{ $application->application_no }}</p>
            <p class="text-xs text-[var(--careers-muted)] mt-2">Save this number with your phone to track status anytime.</p>
        </div>
    </div>

    <div class="mt-8 flex flex-col sm:flex-row gap-3 justify-center">
        <a href="{{ route('careers.track') }}" class="careers-btn careers-btn-primary">Track Application</a>
        <a href="{{ route('careers.index') }}" class="careers-btn careers-btn-secondary">Browse More Jobs</a>
    </div>
</div>
@endsection
