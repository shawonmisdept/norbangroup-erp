@extends('layouts.admin')

@section('title', $employee->name)

@section('breadcrumbs')
    <a href="{{ route('admin.hrm.employees.index') }}" class="hover:text-brand">Employees</a>
    <span>/</span>
    <span class="text-gray-800 font-medium">{{ $employee->employee_code }}</span>
@endsection

@section('admin-content')
@include('partials.erp.page-header', [
    'title' => $employee->name,
    'subtitle' => $employee->employee_code . ' · ' . ($employee->factory?->name ?? 'No unit'),
    'actions' => auth()->user()->canManageEmployeeSubmodule('employees')
        ? '<a href="' . route('admin.hrm.employees.edit', $employee) . '" class="erp-btn-primary">Edit</a>'
          . '<a href="' . route('admin.hrm.employees.id-card', $employee) . '" class="erp-btn-secondary" target="_blank">ID Card</a>'
        : (auth()->user()->canViewEmployeeSubmodule('employees')
            ? '<a href="' . route('admin.hrm.employees.id-card', $employee) . '" class="erp-btn-secondary" target="_blank">ID Card</a>'
            : ''),
])

@php
    $statusBadge = match($employee->status) {
        'active' => 'bg-green-100 text-green-800',
        'probation' => 'bg-amber-100 text-amber-800',
        'suspended' => 'bg-orange-100 text-orange-800',
        default => 'bg-gray-100 text-gray-600',
    };
@endphp

<div class="grid grid-cols-1 xl:grid-cols-3 gap-4" x-data="employeeTabs('setup')">
    <div class="xl:col-span-2 space-y-4">

        {{-- Profile header --}}
        <div class="erp-panel">
            <div class="erp-panel-body">
                <div class="flex items-start gap-6">
                    @include('partials.employee-avatar', ['employee' => $employee, 'size' => '180', 'round' => true])
                    <div class="space-y-2 min-w-0">
                        @if($employee->name_bangla)
                            <p class="text-sm text-gray-500">{{ $employee->name_bangla }}</p>
                        @endif
                        <div class="flex flex-wrap items-center gap-2">
                            <code class="text-xs bg-gray-100 px-2 py-1 rounded-sm font-mono">{{ $employee->employee_code }}</code>
                            <span class="erp-badge {{ $statusBadge }}">{{ $employee->statusLabel() }}</span>
                            @if($employee->workerCategory)
                                <span class="erp-badge bg-gold-light text-gold-dark">{{ $employee->workerCategory->name }}</span>
                            @endif
                        </div>
                        <div class="flex flex-wrap gap-x-4 gap-y-1 text-xs text-gray-500">
                            @if($employee->department)
                                <span>{{ $employee->department->name }}</span>
                            @endif
                            @if($employee->designation)
                                <span>{{ $employee->designation->name }}</span>
                            @endif
                            @if($employee->line)
                                <span>{{ $employee->line->name }}</span>
                            @endif
                            @if($employee->shift)
                                <span>{{ $employee->shift->name }}</span>
                            @endif
                            @if($employee->phone)
                                <span>{{ $employee->phone }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tabbed detail --}}
        <div class="erp-panel overflow-hidden">
            @include('admin.hrm.employees.partials.tabs-nav')

            <div class="erp-panel-body">

                {{-- Employee Setup --}}
                <div x-show="tab === 'setup'" class="grid grid-cols-2 md:grid-cols-3 gap-4 text-sm">
                    <div><p class="erp-form-label !mb-0.5">Employee Name</p><p class="font-medium">{{ $employee->name }}</p></div>
                    <div><p class="erp-form-label !mb-0.5">Email</p><p class="font-medium">{{ $employee->email ?? '—' }}</p></div>
                    <div><p class="erp-form-label !mb-0.5">Phone</p><p class="font-medium">{{ $employee->phone ?? '—' }}</p></div>
                    <div><p class="erp-form-label !mb-0.5">Factory</p><p class="font-medium">{{ $employee->factory?->name ?? '—' }}</p></div>
                    <div><p class="erp-form-label !mb-0.5">Department</p><p class="font-medium">{{ $employee->department?->name ?? '—' }}</p></div>
                    <div><p class="erp-form-label !mb-0.5">Designation</p><p class="font-medium">{{ $employee->designation?->name ?? '—' }}</p></div>
                    <div><p class="erp-form-label !mb-0.5">Worker Category</p><p class="font-medium">{{ $employee->workerCategory?->name ?? '—' }}</p></div>
                    <div><p class="erp-form-label !mb-0.5">Employment Type</p><p class="font-medium">{{ $employee->employmentType?->name ?? '—' }}</p></div>
                    <div><p class="erp-form-label !mb-0.5">Shift</p><p class="font-medium">{{ $employee->shift?->name ?? '—' }}</p></div>
                    <div><p class="erp-form-label !mb-0.5">Weekend Days</p><p class="font-medium">{{ app(\App\Services\Hrm\EmployeeScheduleService::class)->weekendDaysLabel($employee) }}</p></div>
                    <div><p class="erp-form-label !mb-0.5">Weekend OT</p><p class="font-medium">{{ $employee->weekend_ot_allowed ? 'Allowed' : 'Not allowed' }}</p></div>
                    <div><p class="erp-form-label !mb-0.5">Half Day Pay Ratio</p><p class="font-medium tabular-nums">{{ $employee->half_day_pay_ratio !== null ? number_format((float) $employee->half_day_pay_ratio, 2) : 'Policy default' }}</p></div>
                    <div><p class="erp-form-label !mb-0.5">Attendance Bonus</p><p class="font-medium">{{ $employee->attendance_bonus_enabled ? '৳' . number_format((float) $employee->attendance_bonus_amount, 2) : 'Not eligible' }}</p></div>
                    <div><p class="erp-form-label !mb-0.5">Salary Grade</p><p class="font-medium">{{ $employee->salaryStructure?->salaryGrade?->name ?? '—' }}@if($employee->salaryStructure?->salaryGrade) <code class="text-[10px] text-gray-400">{{ $employee->salaryStructure->salaryGrade->code }}</code>@endif</p></div>
                    @if($employee->salaryStructure?->salary_grade_id)
                        <div class="md:col-span-2">
                            <a href="{{ route('admin.hrm.salary.employee-salary.index', ['employee_id' => $employee->id, 'salary_grade_id' => $employee->salaryStructure->salary_grade_id]) }}" class="text-xs text-brand hover:underline">Configure salary (gross, payment, heads) →</a>
                        </div>
                    @endif
                    <div><p class="erp-form-label !mb-0.5">Reporting Person</p><p class="font-medium">{{ $employee->reportingTo?->name ?? '—' }}@if($employee->reportingTo) <code class="text-[10px] text-gray-400">{{ $employee->reportingTo->employee_code }}</code>@endif</p></div>
                    <div><p class="erp-form-label !mb-0.5">Joining Date</p><p class="font-medium tabular-nums">{{ $employee->joining_date?->format('d M Y') ?? '—' }}</p></div>
                    <div><p class="erp-form-label !mb-0.5">Confirmation Date</p><p class="font-medium tabular-nums">{{ $employee->confirmation_date?->format('d M Y') ?? '—' }}</p></div>
                    <div><p class="erp-form-label !mb-0.5">Status</p><p class="font-medium">{{ $employee->statusLabel() }}</p></div>
                    <div><p class="erp-form-label !mb-0.5">Building</p><p class="font-medium">{{ $employee->building?->name ?? '—' }}</p></div>
                    <div><p class="erp-form-label !mb-0.5">Floor</p><p class="font-medium">{{ $employee->floor?->name ?? '—' }}</p></div>
                    <div><p class="erp-form-label !mb-0.5">Line</p><p class="font-medium">{{ $employee->line?->name ?? '—' }}</p></div>
                    @if($employee->notes)
                        <div class="md:col-span-3 pt-2 border-t border-erp-border">
                            <p class="erp-form-label !mb-0.5">Notes</p>
                            <p class="font-medium text-gray-700">{{ $employee->notes }}</p>
                        </div>
                    @endif
                </div>

                {{-- Official Setup --}}
                <div x-show="tab === 'official'" class="grid grid-cols-2 md:grid-cols-3 gap-4 text-sm">
                    <div><p class="erp-form-label !mb-0.5">NID</p><p class="font-medium font-mono text-xs">{{ $employee->nid_number ?? '—' }}</p></div>
                    <div><p class="erp-form-label !mb-0.5">NID Copy</p>
                        @if($employee->documentUrl('nid_document'))
                            <a href="{{ $employee->documentUrl('nid_document') }}" target="_blank" class="erp-btn-sm-secondary">View file</a>
                        @else
                            <p class="font-medium">—</p>
                        @endif
                    </div>
                    <div><p class="erp-form-label !mb-0.5">Birth Certificate</p><p class="font-medium font-mono text-xs">{{ $employee->birth_certificate_no ?? '—' }}</p></div>
                    <div><p class="erp-form-label !mb-0.5">Birth Certificate Copy</p>
                        @if($employee->documentUrl('birth_certificate_document'))
                            <a href="{{ $employee->documentUrl('birth_certificate_document') }}" target="_blank" class="erp-btn-sm-secondary">View file</a>
                        @else
                            <p class="font-medium">—</p>
                        @endif
                    </div>
                    <div><p class="erp-form-label !mb-0.5">Biometric ID</p><p class="font-medium font-mono text-xs">{{ $employee->biometric_user_id ?? '—' }}</p></div>
                </div>

                {{-- Personal Info --}}
                <div x-show="tab === 'personal'" class="grid grid-cols-2 md:grid-cols-3 gap-4 text-sm">
                    <div><p class="erp-form-label !mb-0.5">Name (Bangla)</p><p class="font-medium">{{ $employee->name_bangla ?? '—' }}</p></div>
                    <div><p class="erp-form-label !mb-0.5">Gender</p><p class="font-medium">{{ config('hrm.employee_options.genders.' . $employee->gender, $employee->gender ?? '—') }}</p></div>
                    <div><p class="erp-form-label !mb-0.5">Date of Birth</p><p class="font-medium tabular-nums">{{ $employee->date_of_birth?->format('d M Y') ?? '—' }}</p></div>
                    <div><p class="erp-form-label !mb-0.5">Blood Group</p><p class="font-medium">{{ $employee->blood_group ?? '—' }}</p></div>
                </div>

                {{-- Contact --}}
                <div x-show="tab === 'contact'" class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div><p class="erp-form-label !mb-0.5">Present Address</p><p class="font-medium">{{ $employee->present_address ?? '—' }}</p></div>
                    <div><p class="erp-form-label !mb-0.5">Permanent Address</p><p class="font-medium">{{ $employee->permanent_address ?? '—' }}</p></div>
                </div>

                {{-- Emergency & Nominee --}}
                <div x-show="tab === 'family'" class="grid grid-cols-2 md:grid-cols-3 gap-4 text-sm">
                    <div><p class="erp-form-label !mb-0.5">Emergency Contact</p><p class="font-medium">{{ $employee->emergency_contact_name ?? '—' }}</p></div>
                    <div><p class="erp-form-label !mb-0.5">Emergency Phone</p><p class="font-medium">{{ $employee->emergency_contact_phone ?? '—' }}</p></div>
                    <div><p class="erp-form-label !mb-0.5">Relation</p><p class="font-medium">{{ $employee->emergency_contact_relation ?? '—' }}</p></div>
                    <div><p class="erp-form-label !mb-0.5">Nominee</p><p class="font-medium">{{ $employee->nominee_name ?? '—' }}</p></div>
                    <div><p class="erp-form-label !mb-0.5">Nominee Relation</p><p class="font-medium">{{ $employee->nominee_relation ?? '—' }}</p></div>
                    <div><p class="erp-form-label !mb-0.5">Nominee NID</p><p class="font-medium font-mono text-xs">{{ $employee->nominee_nid ?? '—' }}</p></div>
                    <div><p class="erp-form-label !mb-0.5">Nominee ID Copy</p>
                        @if($employee->documentUrl('nominee_nid_document'))
                            <a href="{{ $employee->documentUrl('nominee_nid_document') }}" target="_blank" class="erp-btn-sm-secondary">View file</a>
                        @else
                            <p class="font-medium">—</p>
                        @endif
                    </div>
                    <div><p class="erp-form-label !mb-0.5">Nominee Photo</p>
                        @if($employee->documentUrl('nominee_photo'))
                            <img src="{{ $employee->documentUrl('nominee_photo') }}" alt="Nominee photo" class="w-16 h-16 rounded-full object-cover border border-gray-200">
                        @else
                            <p class="font-medium">—</p>
                        @endif
                    </div>
                </div>

                {{-- Educational History --}}
                <div x-show="tab === 'education'" class="space-y-3 text-sm">
                    @forelse($employee->educationHistories as $history)
                        <div class="border border-erp-border rounded-sm p-4 grid grid-cols-2 md:grid-cols-3 gap-4">
                            <div><p class="erp-form-label !mb-0.5">Degree</p><p class="font-medium">{{ $history->degree ?? '—' }}</p></div>
                            <div><p class="erp-form-label !mb-0.5">Institution</p><p class="font-medium">{{ $history->institution ?? '—' }}</p></div>
                            <div><p class="erp-form-label !mb-0.5">Board / University</p><p class="font-medium">{{ $history->board_or_university ?? '—' }}</p></div>
                            <div><p class="erp-form-label !mb-0.5">Passing Year</p><p class="font-medium">{{ $history->passing_year ?? '—' }}</p></div>
                            <div><p class="erp-form-label !mb-0.5">Result</p><p class="font-medium">{{ $history->result ?? '—' }}</p></div>
                        </div>
                    @empty
                        <p class="text-gray-400">No educational history recorded.</p>
                    @endforelse
                </div>

                {{-- Employment History --}}
                <div x-show="tab === 'employment'" class="space-y-3 text-sm">
                    @forelse($employee->employmentHistories as $history)
                        <div class="border border-erp-border rounded-sm p-4 grid grid-cols-2 md:grid-cols-3 gap-4">
                            <div><p class="erp-form-label !mb-0.5">Company</p><p class="font-medium">{{ $history->company_name ?? '—' }}</p></div>
                            <div><p class="erp-form-label !mb-0.5">Designation</p><p class="font-medium">{{ $history->designation ?? '—' }}</p></div>
                            <div><p class="erp-form-label !mb-0.5">Department</p><p class="font-medium">{{ $history->department ?? '—' }}</p></div>
                            <div><p class="erp-form-label !mb-0.5">Joining Date</p><p class="font-medium tabular-nums">{{ $history->joining_date?->format('d M Y') ?? '—' }}</p></div>
                            <div><p class="erp-form-label !mb-0.5">Leaving Date</p><p class="font-medium tabular-nums">{{ $history->leaving_date?->format('d M Y') ?? '—' }}</p></div>
                            <div><p class="erp-form-label !mb-0.5">Reason for Leaving</p><p class="font-medium">{{ $history->reason_for_leaving ?? '—' }}</p></div>
                        </div>
                    @empty
                        <p class="text-gray-400">No previous employment recorded.</p>
                    @endforelse
                </div>

                {{-- Internal Service History --}}
                <div x-show="tab === 'service'" class="space-y-3 text-sm">
                    @forelse($employee->serviceHistories as $history)
                        <div class="border border-erp-border rounded-sm p-4">
                            <div class="flex flex-wrap items-start justify-between gap-2 mb-2">
                                <p class="font-medium text-gray-800">{{ $history->description }}</p>
                                <span class="erp-badge bg-brand/10 text-brand text-[10px] uppercase">{{ str_replace('_', ' ', $history->event_type) }}</span>
                            </div>
                            <div class="flex flex-wrap gap-x-4 gap-y-1 text-xs text-gray-500">
                                <span>{{ $history->created_at->format('d M Y H:i') }}</span>
                                @if($history->recordedByUser)
                                    <span>By {{ $history->recordedByUser->name }}</span>
                                @endif
                                @if($history->effective_date)
                                    <span>Effective {{ $history->effective_date->format('d M Y') }}</span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="text-gray-400">No internal service history recorded yet.</p>
                    @endforelse
                </div>

            </div>
        </div>
    </div>

    <div class="space-y-3">
        @include('admin.hrm.employees.partials.separation-card')
        @include('admin.hrm.employees.partials.letters-card')
        @include('admin.hrm.employees.partials.discipline-card')
        @include('admin.hrm.employees.partials.gratuity-card')
        @include('admin.hrm.employees.partials.final-settlement-card')
        @include('admin.hrm.employees.partials.portal-card')

        @if(auth()->user()->canManageEmployeeSubmodule('employees'))
            <a href="{{ route('admin.hrm.employees.edit', $employee) }}" class="erp-btn-primary w-full justify-center !py-2.5">Edit Employee</a>
            <form method="POST" action="{{ route('admin.hrm.employees.destroy', $employee) }}"
                  data-confirm="Remove this employee record?"
                  data-confirm-title="Remove employee"
                  data-confirm-ok="Yes, remove">
                @csrf @method('DELETE')
                <button type="submit" class="erp-btn-danger w-full justify-center !py-2.5">Remove Record</button>
            </form>
        @endif
        <div class="erp-panel">
            <div class="erp-panel-body text-xs text-gray-500 space-y-1">
                <p>Enrolled {{ $employee->created_at->format('d M Y') }}</p>
                <p>Last updated {{ $employee->updated_at->format('d M Y H:i') }}</p>
            </div>
        </div>
    </div>
</div>
@endsection
