@extends('layouts.admin')

@section('title', 'Print Gate QR — ' . $point->code)

@section('admin-content')
<div class="max-w-md mx-auto py-8 print:py-0">
    <div class="erp-panel print:border-0 print:shadow-none">
        <div class="erp-panel-body text-center space-y-4">
            <p class="text-xs font-semibold uppercase tracking-widest text-gray-400">Scan to Check In</p>
            <h1 class="text-xl font-bold text-gray-900">{{ $point->name }}</h1>
            @if($point->location)
                <p class="text-sm text-gray-500">{{ $point->location }}</p>
            @endif
            <p class="text-xs text-gray-400">{{ $point->factory?->name }}</p>

            <div class="flex justify-center py-4">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=280x280&data={{ urlencode($checkInUrl) }}"
                     alt="Gate QR Code" width="280" height="280" class="rounded-lg border border-gray-200">
            </div>

            <p class="text-[10px] text-gray-400 break-all px-4">{{ $checkInUrl }}</p>

            <div class="flex justify-center gap-2 print:hidden">
                <button onclick="window.print()" class="erp-btn-primary">Print QR</button>
                <a href="{{ route('admin.hrm.attendance.gate-points.index') }}" class="erp-btn-secondary">Back</a>
            </div>
        </div>
    </div>

    <p class="mt-4 text-center text-xs text-gray-400 print:hidden">
        Employees scan this QR with their phone camera → opens check-in page (login required).
    </p>
</div>
@endsection
