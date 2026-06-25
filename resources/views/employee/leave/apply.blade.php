@extends('layouts.employee')

@section('title', 'Apply Leave')
@section('page-title', 'Apply for Leave')
@section('page-subtitle', 'Reporting person → HR approval')
@section('back', route('employee.leave'))

@section('content')
<div class="space-y-5">

    @if($employee->reportingTo)
        <div class="emp-card-padded flex items-center gap-3">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-violet-50 text-violet-600">
                @include('employee.partials.tab-icon', ['icon' => 'user'])
            </span>
            <div>
                <p class="text-[10px] font-semibold uppercase tracking-wide text-gray-400">Reporting To</p>
                <p class="text-sm font-semibold text-gray-900">{{ $employee->reportingTo->name }}</p>
            </div>
        </div>
    @else
        <div class="emp-toast emp-toast-error">
            Your reporting person is not set. Please contact HR before applying for leave.
        </div>
    @endif

    @if($employee->reportingTo)
        <form method="POST" action="{{ route('employee.leave.apply.store') }}" enctype="multipart/form-data" class="emp-card-padded space-y-4">
            @csrf

            <div>
                <label class="emp-label">Leave Type <span class="text-red-500">*</span></label>
                <select name="leave_type_id" required class="emp-input">
                    <option value="">Select leave type</option>
                    @foreach($leaveTypes as $type)
                        @php $balance = $balances->get($type->id); @endphp
                        <option value="{{ $type->id }}" {{ old('leave_type_id') == $type->id ? 'selected' : '' }}>
                            {{ $type->name }}
                            @if($type->is_paid && $balance)
                                ({{ number_format($balance->availableDays(), 1) }} days available)
                            @elseif(!$type->is_paid)
                                (Unpaid)
                            @endif
                        </option>
                    @endforeach
                </select>
                @error('leave_type_id')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="emp-label">Start Date <span class="text-red-500">*</span></label>
                    <input type="date" name="start_date" value="{{ old('start_date') }}" required min="{{ today()->toDateString() }}" class="emp-input">
                    @error('start_date')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="emp-label">End Date <span class="text-red-500">*</span></label>
                    <input type="date" name="end_date" value="{{ old('end_date') }}" required min="{{ today()->toDateString() }}" class="emp-input">
                    @error('end_date')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>
            </div>

            <div>
                <label class="emp-label">Reason <span class="text-red-500">*</span></label>
                <textarea name="reason" rows="4" required class="emp-input" placeholder="Reason for leave…">{{ old('reason') }}</textarea>
                @error('reason')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="emp-label">Attachment (optional)</label>
                <input type="file" name="attachment" accept=".pdf,.jpg,.jpeg,.png" class="emp-input !py-2">
                <p class="mt-1 text-[10px] text-gray-400">PDF or image, max 5MB</p>
                @error('attachment')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
            </div>

            <button type="submit" class="emp-btn w-full">Submit Application</button>
        </form>
    @else
        <a href="{{ route('employee.leave') }}" class="emp-btn-secondary flex w-full justify-center">Back to Leave</a>
    @endif

    <p class="px-1 text-[10px] text-gray-400">Working days exclude your weekend days and factory holidays.</p>
</div>
@endsection
