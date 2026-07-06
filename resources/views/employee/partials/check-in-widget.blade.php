@php
    $widgetAction = $widgetAction ?? 'link';
    $checkInUrl = $checkInUrl ?? route('employee.attendance.check-in');
@endphp

<div class="emp-card emp-checkin-widget p-6 text-center"
     x-data="checkInWidget(@js([
         'checkInIso' => $checkInStatus['check_in_iso'],
         'checkOutIso' => $checkInStatus['check_out_iso'],
         'shiftMinutes' => $checkInStatus['shift_minutes'],
         'shiftLabel' => $checkInStatus['shift_label'],
         'status' => $checkInStatus['status'],
         'nextAction' => $checkInStatus['next_action'],
         'checkInLabel' => $checkInStatus['check_in_label'],
         'checkOutLabel' => $checkInStatus['check_out_label'],
         'workMinutes' => $checkInStatus['work_minutes'],
     ]))">

    <div class="relative mx-auto h-44 w-44">
        <svg class="h-full w-full -rotate-90" viewBox="0 0 120 120" aria-hidden="true">
            <circle cx="60" cy="60" r="54" fill="none" stroke="#f3ede4" stroke-width="10"></circle>
            <circle cx="60" cy="60" r="54" fill="none"
                    stroke="#f59e0b"
                    stroke-width="10"
                    stroke-linecap="round"
                    stroke-dasharray="{{ 2 * 3.141592653589793 * 54 }}"
                    :stroke-dashoffset="ringOffset"></circle>
        </svg>
        <div class="absolute inset-0 flex flex-col items-center justify-center px-4">
            <p class="text-2xl font-bold tabular-nums text-brand" x-text="elapsedLabel">0h 0m</p>
            <p class="mt-1 text-xs text-gray-400" x-text="shiftLabel">{{ $checkInStatus['shift_label'] }}</p>
        </div>
    </div>

    <p class="mt-4 text-sm font-semibold"
       :class="status === 'active' ? 'text-emerald-700' : (status === 'done' ? 'text-gray-600' : 'text-gray-500')"
       x-text="statusText()">
        @if($checkInStatus['status'] === 'active')
            Checked in at {{ $checkInStatus['check_in_label'] }}
        @elseif($checkInStatus['status'] === 'done')
            Checked out at {{ $checkInStatus['check_out_label'] }}
        @else
            Not checked in yet today
        @endif
    </p>

    @if($checkInStatus['mobile_enabled'])
        @if($widgetAction === 'link')
            @if($checkInStatus['status'] === 'done')
                <button type="button" disabled class="emp-checkin-btn emp-checkin-btn-done mt-5 w-full">
                    Done for today
                </button>
            @elseif($checkInStatus['next_action'] === 'out')
                <a href="{{ $checkInUrl }}" class="emp-checkin-btn emp-checkin-btn-out mt-5 w-full">
                    Check out
                </a>
            @else
                <a href="{{ $checkInUrl }}" class="emp-checkin-btn emp-checkin-btn-in mt-5 w-full">
                    Check in
                </a>
            @endif
        @else
            @if($checkInStatus['status'] === 'done')
                <button type="button" disabled class="emp-checkin-btn emp-checkin-btn-done mt-5 w-full">
                    Done for today
                </button>
            @elseif($checkInStatus['next_action'] === 'out')
                <button type="button" @click="$dispatch('punch-submit')" class="emp-checkin-btn emp-checkin-btn-out mt-5 w-full">
                    Check out
                </button>
            @else
                <button type="button" @click="$dispatch('punch-submit')" class="emp-checkin-btn emp-checkin-btn-in mt-5 w-full">
                    Check in
                </button>
            @endif
        @endif
    @else
        <p class="mt-5 text-xs text-gray-400">Mobile check-in is not enabled for your factory.</p>
    @endif
</div>
