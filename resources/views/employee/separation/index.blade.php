@extends('layouts.employee')

@section('title', 'Separation')
@section('page-title', 'Resignation / Separation')
@section('page-subtitle', 'Apply or track your exit request')

@section('content')
<div class="space-y-5">

    @if($pending)
        <div class="emp-card p-4 border-amber-200/80">
            <div class="flex items-center justify-between gap-2 mb-2">
                <p class="text-sm font-semibold text-gray-900">{{ $pending->typeLabel() }}</p>
                <span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-[10px] font-semibold text-amber-800">{{ $pending->statusLabel() }}</span>
            </div>
            <p class="text-xs text-gray-500">Last working day: <strong>{{ $pending->last_working_day->format('d M Y') }}</strong></p>
            @if($pending->pendingStepLabel())
                <p class="text-xs text-amber-700 mt-1">{{ $pending->pendingStepLabel() }}</p>
            @endif
            @if($pending->reason)
                <p class="mt-2 rounded-xl bg-gray-50 px-3 py-2 text-xs text-gray-600">{{ $pending->reason }}</p>
            @endif
            <form method="POST" action="{{ route('employee.separation.cancel') }}" class="mt-3"
                  data-confirm="Cancel your separation request?"
                  data-confirm-title="Cancel request"
                  data-confirm-ok="Yes, cancel">
                @csrf
                @method('DELETE')
                <button type="submit" class="emp-btn-secondary w-full !py-2.5 !text-xs !text-amber-800">Cancel Request</button>
            </form>
        </div>
    @elseif($canApply)
        <div class="emp-card p-4">
            <p class="emp-section-title !mb-3">Apply for Resignation</p>
            <form method="POST" action="{{ route('employee.separation.store') }}" enctype="multipart/form-data" class="space-y-3">
                @csrf
                <input type="hidden" name="separation_type" value="resigned">
                <div>
                    <label class="emp-label">Application Date</label>
                    <input type="date" name="application_date" value="{{ old('application_date', now()->toDateString()) }}" required class="emp-input">
                </div>
                <div>
                    <label class="emp-label">Last Working Day</label>
                    <input type="date" name="last_working_day" value="{{ old('last_working_day') }}" required class="emp-input">
                </div>
                <div>
                    <label class="emp-label">Notice Period (days)</label>
                    <input type="number" name="notice_period_days" value="{{ old('notice_period_days') }}" min="0" class="emp-input" placeholder="Optional">
                </div>
                <div>
                    <label class="emp-label">Reason <span class="text-red-500">*</span></label>
                    <textarea name="reason" rows="3" required class="emp-input" placeholder="Why are you resigning?">{{ old('reason') }}</textarea>
                </div>
                <div>
                    <label class="emp-label">Resignation Letter (optional)</label>
                    <input type="file" name="attachment" accept=".jpg,.jpeg,.png,.pdf" class="emp-input !py-2">
                </div>
                <button type="submit" class="emp-btn w-full !py-3">Submit Resignation</button>
            </form>
        </div>
    @elseif($employee->isSeparated())
        <div class="emp-card p-4 text-center">
            <p class="text-sm font-semibold text-gray-900">You are no longer an active employee</p>
            <p class="text-xs text-gray-500 mt-1">Status: {{ $employee->statusLabel() }}</p>
            @if($employee->last_working_day)
                <p class="text-xs text-gray-500">Last working day: {{ $employee->last_working_day->format('d M Y') }}</p>
            @endif
        </div>
    @endif

    @if($history->isNotEmpty())
        <div>
            <p class="emp-section-title">History</p>
            <div class="emp-card divide-y divide-gray-100">
                @foreach($history as $item)
                    <div class="px-4 py-3.5">
                        <div class="flex items-center justify-between gap-2">
                            <p class="text-sm font-semibold text-gray-900">{{ $item->typeLabel() }}</p>
                            <span class="text-[10px] font-semibold uppercase text-gray-400">{{ $item->statusLabel() }}</span>
                        </div>
                        <p class="text-xs text-gray-500 mt-0.5">Last day {{ $item->last_working_day->format('d M Y') }} · {{ $item->applied_at?->format('d M Y') }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
@endsection
