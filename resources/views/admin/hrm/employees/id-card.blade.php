@extends('layouts.admin')

@section('title', 'ID Card — ' . $employee->employee_code)

@section('admin-content')
<div class="max-w-lg mx-auto py-6 print:py-0">
    <div class="erp-panel print:border-2 print:border-gray-800 print:shadow-none overflow-hidden">
        <div class="erp-panel-body p-0">
            {{-- Card header --}}
            <div class="bg-brand text-white px-5 py-3 flex items-center justify-between">
                <div>
                    <p class="text-[10px] uppercase tracking-widest text-white/60">{{ config('portal.name') }}</p>
                    <p class="text-sm font-bold">{{ $employee->factory?->name ?? 'Factory Unit' }}</p>
                </div>
                @if(config('portal.navbar_logo'))
                    <img src="{{ config('portal.navbar_logo') }}" alt="" class="h-8 w-auto max-w-[80px] object-contain">
                @endif
            </div>

            <div class="p-5 flex gap-4">
                {{-- Photo --}}
                <div class="shrink-0">
                    @include('partials.employee-avatar', ['employee' => $employee, 'size' => '96', 'round' => true])
                </div>

                {{-- Details --}}
                <div class="min-w-0 flex-1 space-y-1.5 text-sm">
                    <p class="text-lg font-bold text-gray-900 leading-tight">{{ $employee->name }}</p>
                    @if($employee->name_bangla)
                        <p class="text-xs text-gray-500">{{ $employee->name_bangla }}</p>
                    @endif
                    <p class="font-mono text-xs bg-gray-100 inline-block px-2 py-0.5 rounded-sm">{{ $employee->employee_code }}</p>
                    <div class="text-xs text-gray-600 space-y-0.5 pt-1">
                        @if($employee->designation)
                            <p><span class="text-gray-400">Designation:</span> {{ $employee->designation->name }}</p>
                        @endif
                        @if($employee->department)
                            <p><span class="text-gray-400">Department:</span> {{ $employee->department->name }}</p>
                        @endif
                        @if($employee->line)
                            <p><span class="text-gray-400">Line:</span> {{ $employee->line->name }}</p>
                        @endif
                        @if($employee->shift)
                            <p><span class="text-gray-400">Shift:</span> {{ $employee->shift->name }}</p>
                        @endif
                        @if($employee->blood_group)
                            <p><span class="text-gray-400">Blood:</span> <strong>{{ $employee->blood_group }}</strong></p>
                        @endif
                        @if($employee->emergency_contact_phone)
                            <p><span class="text-gray-400">Emergency:</span> {{ $employee->emergency_contact_name ?? 'Contact' }} — {{ $employee->emergency_contact_phone }}</p>
                        @endif
                    </div>
                </div>
            </div>

            {{-- QR Code --}}
            <div class="border-t border-erp-border px-5 py-4 flex items-center gap-4 bg-gray-50/50">
                @php
                    $qrData = $employee->employee_code;
                @endphp
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data={{ urlencode($qrData) }}"
                     alt="Employee QR" width="100" height="100" class="rounded border border-gray-200 bg-white shrink-0">
                <div class="text-xs text-gray-500">
                    <p class="font-semibold text-gray-700 mb-1">Employee QR Code</p>
                    <p>Scan for attendance / verification</p>
                    @if($employee->joining_date)
                        <p class="mt-1 text-gray-400">Joined {{ $employee->joining_date->format('d M Y') }}</p>
                    @endif
                </div>
            </div>

            <div class="border-t border-gold/30 bg-gold/5 px-5 py-2 text-center">
                <p class="text-[9px] uppercase tracking-widest text-gold-dark font-semibold">Employee Identity Card</p>
            </div>
        </div>
    </div>

    <div class="flex justify-center gap-2 mt-4 print:hidden">
        <button onclick="window.print()" class="erp-btn-primary">Print ID Card</button>
        <a href="{{ route('admin.hrm.employees.show', $employee) }}" class="erp-btn-secondary">Back to Profile</a>
    </div>
</div>

<style>
@media print {
    @page { size: 85mm 54mm; margin: 0; }
    body * { visibility: hidden; }
    .max-w-lg, .max-w-lg * { visibility: visible; }
    .max-w-lg { position: absolute; left: 0; top: 0; width: 85mm; }
    .print\\:hidden { display: none !important; }
}
</style>
@endsection
