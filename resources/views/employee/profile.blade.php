@extends('layouts.employee')

@section('title', 'Profile')
@section('no-header')
@endsection

@section('content')
<div class="space-y-5">

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
                    <p class="emp-label !mb-0.5">Designation</p>
                    <p class="font-semibold text-gray-900">{{ $employee->designation?->name ?? '—' }}</p>
                </div>
                <div>
                    <p class="emp-label !mb-0.5">Salary Grade</p>
                    <p class="font-semibold text-gray-900">{{ $employee->salaryStructure?->salaryGrade?->name ?? '—' }}</p>
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

    <div>
        <p class="emp-section-title">Official Info</p>
        <div class="emp-card divide-y divide-gray-100">
            @foreach([
                ['Email', $employee->email ?? '—'],
                ['NID', $employee->nid_number ?? '—'],
                ['Joining Date', $employee->joining_date?->format('d M Y') ?? '—'],
                ['Employment Type', $employee->employmentType?->name ?? '—'],
                ['Contract End', $employee->contract_end_date?->format('d M Y') ?? '—'],
            ] as [$label, $value])
                <div class="flex items-center justify-between gap-3 px-4 py-3.5">
                    <span class="text-xs text-gray-500">{{ $label }}</span>
                    <span class="text-sm font-semibold text-gray-900 text-right">{{ $value }}</span>
                </div>
            @endforeach
        </div>
    </div>

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

    @if($letters->isNotEmpty())
        <div>
            <p class="emp-section-title">HR Letters</p>
            <div class="emp-card overflow-hidden divide-y divide-gray-100">
                @foreach($letters as $letter)
                    <a href="{{ route('employee.letters.show', $letter) }}" class="flex items-center justify-between gap-3 px-4 py-3.5 active:bg-gray-50">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-gray-900">{{ $letter->typeLabel() }}</p>
                            <p class="text-[10px] text-gray-500">{{ $letter->reference_no }} · {{ $letter->issued_at->format('d M Y') }}</p>
                        </div>
                        @include('employee.partials.tab-icon', ['icon' => 'chevron-right'])
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    @if($employee->serviceHistories->isNotEmpty())
        <div>
            <p class="emp-section-title">Service History</p>
            <div class="emp-card overflow-hidden">
                @foreach($employee->serviceHistories as $entry)
                    <div class="px-4 py-3.5 {{ !$loop->last ? 'border-b border-gray-100' : '' }}">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-gray-900">{{ $entry->description }}</p>
                                @if($entry->old_value || $entry->new_value)
                                    <p class="mt-0.5 text-[10px] text-gray-500">{{ $entry->old_value ?? '—' }} → {{ $entry->new_value ?? '—' }}</p>
                                @endif
                            </div>
                            <span class="shrink-0 text-[10px] text-gray-400 tabular-nums">{{ $entry->effective_date?->format('d M Y') ?? $entry->created_at->format('d M Y') }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @if($employee->employmentHistories->isNotEmpty())
        <div>
            <p class="emp-section-title">Prior Employment</p>
            <div class="emp-card overflow-hidden">
                @foreach($employee->employmentHistories as $job)
                    <div class="px-4 py-3.5 {{ !$loop->last ? 'border-b border-gray-100' : '' }}">
                        <p class="text-sm font-semibold text-gray-900">{{ $job->company_name }}</p>
                        <p class="text-xs text-gray-600">{{ $job->designation ?? '—' }} · {{ $job->department ?? '—' }}</p>
                        <p class="mt-1 text-[10px] text-gray-500 tabular-nums">
                            {{ $job->joining_date?->format('M Y') ?? '—' }} – {{ $job->leaving_date?->format('M Y') ?? 'Present' }}
                        </p>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div>
        <p class="emp-section-title">Career & Exit</p>
        <div class="emp-card divide-y divide-gray-100">
            <a href="{{ route('employee.career.promotions') }}" class="flex items-center justify-between px-4 py-3.5 text-sm font-semibold text-gray-900">
                Promotions & Movements
                @include('employee.partials.tab-icon', ['icon' => 'chevron-right'])
            </a>
            <a href="{{ route('employee.career.increments') }}" class="flex items-center justify-between px-4 py-3.5 text-sm font-semibold text-gray-900">
                Salary Increments
                @include('employee.partials.tab-icon', ['icon' => 'chevron-right'])
            </a>
            <a href="{{ route('employee.exit') }}" class="flex items-center justify-between px-4 py-3.5 text-sm font-semibold text-gray-900">
                Exit & Clearance
                @include('employee.partials.tab-icon', ['icon' => 'chevron-right'])
            </a>
            @if($employee->contract_end_date)
                <a href="{{ route('employee.exit.contracts') }}" class="flex items-center justify-between px-4 py-3.5 text-sm font-semibold text-gray-900">
                    Contract Renewals
                    @include('employee.partials.tab-icon', ['icon' => 'chevron-right'])
                </a>
            @endif
        </div>
    </div>

    @if($employee->isLineManager())
        <a href="{{ route('employee.team') }}" class="emp-card flex items-center justify-between px-4 py-3.5 text-sm font-semibold text-gray-900">
            Team Approvals
            @include('employee.partials.tab-icon', ['icon' => 'chevron-right'])
        </a>
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
