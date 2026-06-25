@extends('layouts.employee')

@section('title', 'Profile')
@section('no-header')
@endsection

@section('content')
<div class="space-y-5">

    {{-- Profile card --}}
    <div class="emp-card overflow-hidden -mt-1">
        <div class="bg-gradient-to-br from-brand to-brand-light px-5 pb-12 pt-6 text-white">
            <div class="flex items-center gap-4">
                <div class="emp-avatar-ring">
                    @include('partials.employee-avatar', ['employee' => $employee, 'size' => '80', 'round' => true])
                </div>
                <div class="min-w-0">
                    <p class="truncate text-lg font-bold">{{ $employee->name }}</p>
                    @if($employee->name_bangla)
                        <p class="truncate text-sm text-white/70">{{ $employee->name_bangla }}</p>
                    @endif
                    <code class="mt-1 inline-block rounded-lg bg-white/15 px-2 py-0.5 text-[11px] font-mono">{{ $employee->employee_code }}</code>
                </div>
            </div>
        </div>
        <div class="-mt-8 mx-4 mb-4 rounded-2xl bg-white p-4 shadow-sm">
            <div class="grid grid-cols-2 gap-3 text-sm">
                <div>
                    <p class="emp-label !mb-0.5">Factory</p>
                    <p class="font-semibold text-gray-900">{{ $employee->factory?->name ?? '—' }}</p>
                </div>
                <div>
                    <p class="emp-label !mb-0.5">Department</p>
                    <p class="font-semibold text-gray-900">{{ $employee->department?->name ?? '—' }}</p>
                </div>
                <div>
                    <p class="emp-label !mb-0.5">Line</p>
                    <p class="font-semibold text-gray-900">{{ $employee->line?->name ?? '—' }}</p>
                </div>
                <div>
                    <p class="emp-label !mb-0.5">Shift</p>
                    <p class="font-semibold text-gray-900">{{ $employee->shift?->name ?? '—' }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Contact & emergency --}}
    <div>
        <p class="emp-section-title">Personal Info</p>
        <div class="emp-card divide-y divide-gray-100">
            @foreach([
                ['Phone', $employee->phone ?? '—'],
                ['Blood Group', $employee->blood_group ?? '—'],
                ['Emergency Contact', $employee->emergency_contact_name ?? '—'],
                ['Emergency Phone', $employee->emergency_contact_phone ?? '—'],
            ] as [$label, $value])
                <div class="flex items-center justify-between gap-3 px-4 py-3.5">
                    <span class="text-xs text-gray-500">{{ $label }}</span>
                    <span class="text-sm font-semibold text-gray-900">{{ $value }}</span>
                </div>
            @endforeach
        </div>
    </div>

    @if($employee->canInitiateSeparation())
        <div>
            <p class="emp-section-title">Employment</p>
            <a href="{{ route('employee.separation') }}" class="emp-card flex items-center justify-between px-4 py-3.5 text-sm font-semibold text-gray-900">
                Resignation / Separation
                @include('employee.partials.tab-icon', ['icon' => 'chevron-right'])
            </a>
        </div>
    @endif

    <form method="POST" action="{{ route('employee.logout') }}">
        @csrf
        <button type="submit" class="emp-btn-secondary flex w-full items-center justify-center gap-2 !text-red-600">
            @include('employee.partials.tab-icon', ['icon' => 'logout'])
            Sign Out
        </button>
    </form>
</div>
@endsection
