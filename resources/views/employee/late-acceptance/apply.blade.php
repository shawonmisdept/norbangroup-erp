@extends('layouts.employee')

@section('title', 'Apply Late Acceptance')
@section('page-title', 'Apply Late Acceptance')
@section('page-subtitle', 'Request salary forgiveness for a late day')
@section('back', route('employee.late-acceptance.index'))

@section('content')
<div class="space-y-5">

    <form method="POST" action="{{ route('employee.late-acceptance.apply.store') }}" class="emp-card-padded space-y-4">
        @csrf

        <div>
            <label class="emp-label">Late date</label>
            <select name="attendance_date" required class="emp-input">
                <option value="">Select date…</option>
                @foreach($lateDays as $log)
                    <option value="{{ $log->attendance_date->toDateString() }}" {{ old('attendance_date') === $log->attendance_date->toDateString() ? 'selected' : '' }}>
                        {{ $log->attendance_date->format('d M Y') }}
                        @if($log->late_minutes > 0) ({{ $log->late_minutes }}m late) @endif
                    </option>
                @endforeach
            </select>
            @error('attendance_date')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
            @if($lateDays->isEmpty())
                <p class="mt-2 text-xs text-amber-600">No eligible late days available to apply for.</p>
            @endif
        </div>

        <div>
            <label class="emp-label">Reason</label>
            <textarea name="reason" rows="4" required class="emp-input" placeholder="Explain why you were late…">{{ old('reason') }}</textarea>
            @error('reason')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
        </div>

        <button type="submit" class="emp-btn w-full" {{ $lateDays->isEmpty() ? 'disabled' : '' }}>
            Submit Application
        </button>
    </form>

    <p class="px-1 text-[10px] text-gray-400">Approved applications skip the consecutive late salary deduction for that day.</p>
</div>
@endsection
