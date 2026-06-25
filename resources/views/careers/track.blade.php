@extends('layouts.careers')

@section('title', 'Track Application')

@section('content')
<div class="max-w-md mx-auto">
    <div class="text-center mb-8">
        <h1 class="text-2xl font-bold text-[var(--careers-navy)]">Track Your Application</h1>
        <p class="text-sm text-[var(--careers-muted)] mt-2">Enter your application number and registered phone number.</p>
    </div>

    <form method="POST" action="{{ route('careers.track.submit') }}" class="careers-card">
        <div class="careers-card-body space-y-4">
            @csrf
            <div>
                <label class="careers-field"><span>Application Number *</span></label>
                <input type="text" name="application_no" required value="{{ old('application_no') }}" placeholder="APP-2026-00001" class="careers-input font-mono uppercase">
                @error('application_no')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="careers-field"><span>Phone Number *</span></label>
                <input type="text" name="phone" required value="{{ old('phone') }}" placeholder="01XXXXXXXXX" class="careers-input">
            </div>
            <button type="submit" class="careers-btn careers-btn-primary w-full !py-3">Check Status</button>
        </div>
    </form>
</div>
@endsection
