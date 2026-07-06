@extends('layouts.admin')

@section('title', $application->application_no)

@section('breadcrumbs')
    <a href="{{ route('admin.hrm.recruitment.applications.index') }}" class="hover:text-brand">Applications</a>
    <span>/</span>
    <span class="text-gray-800 font-medium">{{ $application->application_no }}</span>
@endsection

@section('admin-content')
@php
    $initials = collect(explode(' ', trim($application->name)))
        ->filter()
        ->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');

    $headerActions = '<a href="' . route('admin.hrm.recruitment.applications.index') . '" class="erp-btn-secondary">← Back</a>';
    if ($canEdit) {
        $headerActions .= ' <a href="' . route('admin.hrm.recruitment.applications.edit', $application) . '" class="erp-btn-primary !py-2 !px-4 text-xs">Edit</a>';
    }
    if ($canDelete) {
        $headerActions .= '<form method="POST" action="' . route('admin.hrm.recruitment.applications.destroy', $application) . '" class="inline" data-confirm="Delete this application permanently? All interviews and offer letters will be removed.">'
            . csrf_field()
            . method_field('DELETE')
            . '<button type="submit" class="erp-btn-secondary !py-2 !px-4 text-xs !text-red-600">Delete</button></form>';
    }

    $sourceBadge = match($application->source) {
        'online'    => 'bg-sky-50 text-sky-700 border-sky-200',
        'walk_in'   => 'bg-orange-50 text-orange-700 border-orange-200',
        'referral'  => 'bg-teal-50 text-teal-700 border-teal-200',
        default     => 'bg-gray-50 text-gray-600 border-gray-200',
    };
@endphp

@include('partials.erp.page-header', [
    'title' => $application->name,
    'subtitle' => $application->application_no . ' · ' . ($application->jobPosting?->title ?? 'Application'),
    'actions' => $headerActions,
])

@if($formerEmployee)
    <div class="mb-4 flex items-start gap-3 rounded-lg border border-blue-200 bg-gradient-to-r from-blue-50 to-sky-50 px-4 py-3 text-sm text-blue-900">
        <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-blue-100 text-blue-700">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
        </span>
        <div>
            <p class="font-semibold">Former employee — rehire allowed</p>
            <p class="text-xs text-blue-700/80 mt-0.5">
                Previously <strong>{{ $formerEmployee->employee_code }}</strong> ({{ $formerEmployee->statusLabel() }})
            </p>
        </div>
    </div>
@endif

<div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
    <div class="xl:col-span-2 space-y-4">

        {{-- Profile hero --}}
        <div class="erp-panel overflow-hidden">
            <div class="bg-gradient-to-br from-slate-50 via-white to-brand/5 px-5 py-5 sm:px-6">
                <div class="flex flex-col sm:flex-row gap-5">
                    <div class="shrink-0 mx-auto sm:mx-0">
                        @if($application->photoUrl())
                            <img src="{{ $application->photoUrl() }}" alt="{{ $application->name }}"
                                 class="w-28 h-28 sm:w-32 sm:h-32 object-cover rounded-2xl border-2 border-white shadow-md ring-1 ring-gray-200/80">
                        @else
                            <div class="w-28 h-28 sm:w-32 sm:h-32 flex items-center justify-center rounded-2xl border-2 border-white bg-gradient-to-br from-brand to-brand/80 text-white text-3xl font-bold shadow-md ring-1 ring-brand/20">
                                {{ $initials ?: '?' }}
                            </div>
                        @endif
                    </div>
                    <div class="flex-1 min-w-0 text-center sm:text-left">
                        <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2 mb-2">
                            @include('admin.hrm.recruitment.applications.partials.status-badge', ['application' => $application])
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium border {{ $sourceBadge }}">
                                {{ $application->sourceLabel() }}
                            </span>
                            @if($application->phone_verified_at)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                    Phone verified
                                </span>
                            @endif
                        </div>
                        <code class="inline-block text-[11px] font-mono bg-white/80 border border-gray-200 rounded px-2 py-0.5 text-gray-600 mb-2">{{ $application->application_no }}</code>
                        <p class="text-sm text-gray-600 leading-relaxed">
                            Applied for <strong class="text-gray-900">{{ $application->jobPosting?->title ?? '—' }}</strong>
                            @if($application->factory)
                                · {{ $application->factory->name }}
                            @endif
                        </p>
                        <div class="mt-3 flex flex-wrap items-center justify-center sm:justify-start gap-x-4 gap-y-1 text-xs text-gray-500">
                            <a href="tel:{{ $application->phone }}" class="inline-flex items-center gap-1.5 hover:text-brand transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                {{ $application->phone }}
                            </a>
                            @if($application->email)
                                <a href="mailto:{{ $application->email }}" class="inline-flex items-center gap-1.5 hover:text-brand transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                    {{ $application->email }}
                                </a>
                            @endif
                            <span class="inline-flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                {{ $application->applied_at->format('d M Y') }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="border-t border-erp-border px-5 py-4 sm:px-6 bg-white">
                @include('admin.hrm.recruitment.applications.partials.pipeline', ['application' => $application])
            </div>
        </div>

        {{-- Quick stats --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            @foreach([
                ['Gender', config('hrm.employee_options.genders.' . $application->gender, $application->gender ?? '—'), 'text-gray-700'],
                ['DOB', $application->date_of_birth?->format('d M Y') ?? '—', 'text-gray-700'],
                ['NID', $application->nid_number ?? '—', 'font-mono text-xs'],
                ['Expected Salary', $application->expected_salary ? number_format((float) $application->expected_salary, 0) . ' BDT' : '—', 'text-brand font-semibold tabular-nums'],
            ] as [$label, $value, $valueClass])
                <div class="erp-panel">
                    <div class="erp-panel-body py-3">
                        <p class="text-[10px] uppercase tracking-wide text-gray-400 mb-1">{{ $label }}</p>
                        <p class="text-sm {{ $valueClass }} truncate">{{ $value }}</p>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Candidate details --}}
        <div class="erp-panel">
            <div class="erp-panel-head">
                <h2 class="text-xs font-semibold text-gray-700 uppercase tracking-wide">Candidate Details</h2>
            </div>
            <div class="erp-panel-body">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div class="rounded-lg bg-gray-50/80 border border-gray-100 p-3">
                        <p class="erp-form-label !mb-1">Present Address</p>
                        <p class="text-gray-700 leading-relaxed">{{ $application->present_address ?: '—' }}</p>
                    </div>
                    <div class="rounded-lg bg-gray-50/80 border border-gray-100 p-3">
                        <p class="erp-form-label !mb-1">Permanent Address</p>
                        <p class="text-gray-700 leading-relaxed">{{ $application->permanent_address ?: '—' }}</p>
                    </div>
                </div>

                @if($application->notes)
                    <div class="mt-4 pt-4 border-t border-erp-border">
                        <p class="erp-form-label !mb-1">HR Notes</p>
                        <p class="text-sm text-gray-600 leading-relaxed whitespace-pre-line">{{ $application->notes }}</p>
                    </div>
                @endif

                @if($application->photoUrl() || $application->nidDocumentUrl() || $application->cvUrl())
                    <div class="mt-4 pt-4 border-t border-erp-border">
                        <p class="erp-form-label !mb-3">Documents</p>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            @if($application->photoUrl())
                                <a href="{{ $application->photoUrl() }}" target="_blank"
                                   class="group flex items-center gap-3 rounded-lg border border-erp-border bg-white p-3 hover:border-brand/40 hover:shadow-sm transition-all">
                                    <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-violet-50 text-violet-600 group-hover:bg-violet-100">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    </span>
                                    <span>
                                        <span class="block text-sm font-medium text-gray-900">Photo</span>
                                        <span class="text-[10px] text-gray-400">View file</span>
                                    </span>
                                </a>
                            @endif
                            @if($application->nidDocumentUrl())
                                <a href="{{ $application->nidDocumentUrl() }}" target="_blank"
                                   class="group flex items-center gap-3 rounded-lg border border-erp-border bg-white p-3 hover:border-brand/40 hover:shadow-sm transition-all">
                                    <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-50 text-amber-600 group-hover:bg-amber-100">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"/></svg>
                                    </span>
                                    <span>
                                        <span class="block text-sm font-medium text-gray-900">NID Document</span>
                                        <span class="text-[10px] text-gray-400">View file</span>
                                    </span>
                                </a>
                            @endif
                            @if($application->cvUrl())
                                <a href="{{ $application->cvUrl() }}" target="_blank"
                                   class="group flex items-center gap-3 rounded-lg border border-erp-border bg-white p-3 hover:border-brand/40 hover:shadow-sm transition-all">
                                    <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-sky-50 text-sky-600 group-hover:bg-sky-100">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    </span>
                                    <span>
                                        <span class="block text-sm font-medium text-gray-900">CV / Resume</span>
                                        <span class="text-[10px] text-gray-400">View file</span>
                                    </span>
                                </a>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>

        @if($application->education_history)
            <div class="erp-panel">
                <div class="erp-panel-head">
                    <h2 class="text-xs font-semibold text-gray-700 uppercase tracking-wide">Education</h2>
                </div>
                <div class="erp-panel-body">
                    <div class="space-y-0">
                        @foreach($application->education_history as $edu)
                            <div class="flex gap-3 {{ $loop->last ? '' : 'pb-4' }}">
                                <div class="flex flex-col items-center shrink-0 w-3">
                                    <span class="mt-1.5 block h-2.5 w-2.5 rounded-full bg-brand ring-2 ring-white"></span>
                                    @if(! $loop->last)
                                        <span class="mt-1 w-px flex-1 min-h-[2.5rem] bg-brand/25"></span>
                                    @endif
                                </div>
                                <div class="flex-1 min-w-0 pt-0.5">
                                    <p class="text-sm font-medium text-gray-900">{{ $edu['degree'] ?? '—' }}</p>
                                    <p class="text-xs text-gray-500 mt-0.5 leading-relaxed">
                                        {{ $edu['institution'] ?? '—' }}
                                        @if($edu['board_or_university'] ?? null) · {{ $edu['board_or_university'] }} @endif
                                        @if($edu['passing_year'] ?? null) · {{ $edu['passing_year'] }} @endif
                                        @if($edu['result'] ?? null) · {{ $edu['result'] }} @endif
                                    </p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        @if($application->employment_history)
            <div class="erp-panel">
                <div class="erp-panel-head">
                    <h2 class="text-xs font-semibold text-gray-700 uppercase tracking-wide">Employment History</h2>
                </div>
                <div class="erp-panel-body space-y-3">
                    @foreach($application->employment_history as $emp)
                        @php
                            $isPresent = blank($emp['leaving_date'] ?? null)
                                && (filled($emp['company_name'] ?? null) || filled($emp['joining_date'] ?? null));
                        @endphp
                        <div class="rounded-lg border border-erp-border p-4 bg-gray-50/50">
                            <div class="flex flex-wrap items-start justify-between gap-2 mb-3">
                                <p class="text-[10px] font-semibold uppercase tracking-wide text-gray-400">Experience {{ $loop->iteration }}</p>
                                @if(($emp['joining_date'] ?? null) || ($emp['leaving_date'] ?? null) || $isPresent)
                                    <div class="flex flex-wrap items-center gap-1.5 text-[11px] tabular-nums">
                                        @if($emp['joining_date'] ?? null)
                                            <span class="text-gray-500">{{ $emp['joining_date'] }}</span>
                                            <span class="text-gray-300">→</span>
                                        @endif
                                        @if($isPresent)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-100 text-emerald-800">Present</span>
                                        @elseif($emp['leaving_date'] ?? null)
                                            <span class="text-gray-600 font-medium">{{ $emp['leaving_date'] }}</span>
                                        @endif
                                    </div>
                                @endif
                            </div>
                            <dl class="grid grid-cols-2 md:grid-cols-4 gap-x-4 gap-y-2.5 text-sm">
                                <div>
                                    <dt class="text-[10px] uppercase tracking-wide text-gray-400 mb-0.5">Company</dt>
                                    <dd class="font-medium text-gray-900">{{ $emp['company_name'] ?? '—' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-[10px] uppercase tracking-wide text-gray-400 mb-0.5">Department</dt>
                                    <dd class="font-medium text-gray-900">{{ $emp['department'] ?? '—' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-[10px] uppercase tracking-wide text-gray-400 mb-0.5">Designation</dt>
                                    <dd class="font-medium text-gray-900">{{ $emp['designation'] ?? '—' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-[10px] uppercase tracking-wide text-gray-400 mb-0.5">Reason</dt>
                                    <dd class="text-gray-700 leading-relaxed">{{ $emp['reason_for_leaving'] ?? '—' }}</dd>
                                </div>
                            </dl>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Interviews --}}
        <div class="erp-panel">
            <div class="erp-panel-head flex items-center justify-between">
                <h2 class="text-xs font-semibold text-gray-700 uppercase tracking-wide">Interviews</h2>
                <span class="text-[10px] text-gray-400">{{ $application->interviews->count() }} scheduled</span>
            </div>
            <div class="erp-panel-body space-y-3">
                @forelse($application->interviews as $interview)
                    @php
                        $resultStyle = match($interview->result) {
                            'passed'  => 'border-l-emerald-500 bg-emerald-50/40',
                            'failed'  => 'border-l-red-500 bg-red-50/40',
                            default   => 'border-l-amber-400 bg-amber-50/30',
                        };
                        $resultBadge = match($interview->result) {
                            'passed'  => 'bg-emerald-100 text-emerald-800',
                            'failed'  => 'bg-red-100 text-red-800',
                            default   => 'bg-amber-100 text-amber-800',
                        };
                    @endphp
                    <div class="rounded-lg border border-erp-border border-l-4 p-4 {{ $resultStyle }}">
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <div>
                                <p class="text-sm font-semibold text-gray-900">@portalDateTime($interview->scheduled_at)</p>
                                <p class="text-xs text-gray-500 mt-1">
                                    {{ $interview->typeLabel() }}
                                    @if($interview->location) · {{ $interview->location }} @endif
                                    @if($interview->score !== null) · Score: <strong>{{ $interview->score }}</strong> @endif
                                </p>
                            </div>
                            <span class="erp-badge {{ $resultBadge }} text-[10px]">{{ $interview->resultLabel() }}</span>
                        </div>
                        @if($interview->result === 'pending' && $canManage)
                            <form method="POST" action="{{ route('admin.hrm.recruitment.applications.interviews.complete', [$application, $interview]) }}" class="mt-3 flex flex-wrap gap-2">
                                @csrf
                                <select name="result" required class="erp-input !text-xs flex-1 min-w-[120px]">
                                    @foreach($interviewResults as $val => $label)
                                        @if($val !== 'pending')
                                            <option value="{{ $val }}">{{ $label }}</option>
                                        @endif
                                    @endforeach
                                </select>
                                <input type="number" name="score" min="0" max="100" placeholder="Score" class="erp-input !text-xs w-24">
                                <button type="submit" class="erp-btn-sm-primary">Save Result</button>
                            </form>
                        @endif
                    </div>
                @empty
                    <div class="text-center py-8 text-gray-400">
                        <svg class="w-10 h-10 mx-auto mb-2 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <p class="text-sm">No interviews scheduled yet</p>
                    </div>
                @endforelse

                @if($canManage && ! $application->isTerminal())
                    <details class="group rounded-lg border border-dashed border-gray-300 bg-gray-50/50 open:bg-white open:border-brand/30 transition-colors">
                        <summary class="cursor-pointer list-none px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-600 group-open:text-brand flex items-center justify-between">
                            Schedule Interview
                            <svg class="w-4 h-4 transition-transform group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </summary>
                        <form method="POST" action="{{ route('admin.hrm.recruitment.applications.interviews.store', $application) }}" class="px-4 pb-4 space-y-3 border-t border-gray-200 pt-3">
                            @csrf
                            <input type="datetime-local" name="scheduled_at" required class="erp-input !text-xs">
                            <select name="interview_type" class="erp-input !text-xs">
                                @foreach($interviewTypes as $val => $label)
                                    <option value="{{ $val }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            <input type="text" name="location" placeholder="Location (optional)" class="erp-input !text-xs">
                            <button type="submit" class="erp-btn-sm-primary w-full justify-center">Schedule Interview</button>
                        </form>
                    </details>
                @endif
            </div>
        </div>

        {{-- Activity timeline --}}
        <div class="erp-panel">
            <div class="erp-panel-head">
                <h2 class="text-xs font-semibold text-gray-700 uppercase tracking-wide">Activity Timeline</h2>
            </div>
            <div class="erp-panel-body">
                <div class="relative space-y-0">
                    @foreach($application->logs as $log)
                        <div class="relative flex gap-4 pb-6 last:pb-0">
                            @if(! $loop->last)
                                <div class="absolute left-[11px] top-6 bottom-0 w-px bg-gray-200"></div>
                            @endif
                            <div class="relative z-10 mt-1 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-white border-2 border-brand/30">
                                <div class="h-2 w-2 rounded-full bg-brand"></div>
                            </div>
                            <div class="flex-1 min-w-0 pt-0.5">
                                <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                    <span class="text-sm font-medium text-gray-900">
                                        @if($log->from_status && $log->from_status !== $log->to_status)
                                            {{ ucfirst($log->from_status) }} → {{ ucfirst($log->to_status) }}
                                        @else
                                            {{ ucfirst($log->to_status) }}
                                        @endif
                                    </span>
                                    <span class="text-[10px] text-gray-400">@portalDateCommaTime($log->created_at)</span>
                                </div>
                                @if($log->user)
                                    <p class="text-[11px] text-gray-500 mt-0.5">by {{ $log->user->name }}</p>
                                @endif
                                @if($log->notes)
                                    <p class="text-xs text-gray-600 mt-1.5 rounded-md bg-gray-50 border border-gray-100 px-3 py-2 leading-relaxed">{{ $log->notes }}</p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- Sticky sidebar --}}
    <div class="space-y-4">
        <div class="erp-panel erp-sidebar-sticky">
            <div class="erp-panel-head bg-gradient-to-r from-gray-50 to-white">
                <h2 class="text-xs font-semibold text-gray-700 uppercase tracking-wide">Pipeline Actions</h2>
            </div>
            <div class="erp-panel-body space-y-4">
                <div class="rounded-lg bg-gray-50 border border-gray-100 p-3 text-center">
                    @include('admin.hrm.recruitment.applications.partials.status-badge', ['application' => $application])
                    <p class="text-[10px] text-gray-400 mt-2 uppercase tracking-wide">Current Status</p>
                    <p class="text-xs text-gray-500 mt-1">Applied {{ $application->applied_at->diffForHumans() }}</p>
                </div>

                @if($application->convertedEmployee)
                    <a href="{{ route('admin.hrm.employees.show', $application->convertedEmployee) }}"
                       class="flex items-center gap-3 rounded-lg border border-emerald-200 bg-emerald-50 p-3 hover:bg-emerald-100/80 transition-colors group">
                        <span class="flex h-10 w-10 items-center justify-center rounded-full bg-emerald-100 text-emerald-700 group-hover:bg-emerald-200">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        </span>
                        <span class="text-left">
                            <span class="block text-sm font-semibold text-emerald-900">View Employee</span>
                            <span class="text-[10px] text-emerald-700">{{ $application->convertedEmployee->employee_code }}</span>
                        </span>
                    </a>
                @endif

                @if($application->latestOfferLetter())
                    <div class="rounded-lg border border-amber-100 bg-amber-50/50 p-3">
                        <p class="text-[10px] uppercase tracking-wide text-amber-700/80 mb-2">Latest Offer Response</p>
                        @include('admin.hrm.recruitment.partials.offer-response-badge', ['letter' => $application->latestOfferLetter()])
                    </div>
                @endif

                @if($application->offerLetters->isNotEmpty() || ($canManage && ! $application->isTerminal()))
                    <div class="pt-2 border-t border-erp-border space-y-2">
                        <p class="text-[10px] uppercase tracking-wide text-gray-400 font-semibold">Offer Letters</p>
                        @forelse($application->offerLetters as $letter)
                            <div class="rounded-lg border border-erp-border p-2.5 bg-white">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium truncate">{{ $letter->reference_no }}</p>
                                        <p class="text-[10px] text-gray-400">{{ $letter->issued_at->format('d M Y') }}</p>
                                    </div>
                                    <div class="flex shrink-0 gap-1">
                                        <a href="{{ route('admin.hrm.recruitment.offer-letters.show', $letter) }}" class="erp-btn-sm-secondary !px-2">View</a>
                                        <a href="{{ route('admin.hrm.recruitment.offer-letters.print', $letter) }}" target="_blank" class="erp-btn-sm-secondary !px-2">Print</a>
                                    </div>
                                </div>
                                <div class="mt-2">@include('admin.hrm.recruitment.partials.offer-response-badge', ['letter' => $letter])</div>
                            </div>
                        @empty
                            <p class="text-xs text-gray-400">No offer letter issued yet.</p>
                        @endforelse
                        @if($canManage && ! $application->isTerminal())
                            <a href="{{ route('admin.hrm.recruitment.applications.offer-letter.create', $application) }}" class="erp-btn-sm-primary w-full justify-center">Issue Offer Letter</a>
                        @endif
                    </div>
                @endif

                @if($canManage && ! $application->isTerminal())
                    <div class="pt-2 border-t border-erp-border">
                        <p class="text-[10px] uppercase tracking-wide text-gray-400 font-semibold mb-3">Update Status</p>
                        <form method="POST" action="{{ route('admin.hrm.recruitment.applications.status', $application) }}" class="space-y-3">
                            @csrf
                            <select name="status" required class="erp-input !text-xs">
                                @foreach($statuses as $val => $label)
                                    <option value="{{ $val }}" {{ $application->status === $val ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            <textarea name="notes" rows="2" class="erp-input !text-xs" placeholder="Notes (optional)…"></textarea>
                            <textarea name="rejection_reason" rows="2" class="erp-input !text-xs" placeholder="Rejection reason (if rejecting)…"></textarea>
                            <p class="text-[10px] text-gray-500 leading-relaxed">
                                <strong class="text-gray-700">Hired</strong> automatically enrolls an employee from application data.
                            </p>
                            <button type="submit" class="erp-btn-primary w-full justify-center">Update Status</button>
                        </form>
                    </div>
                @endif

                @if($canConvert && $application->canConvert())
                    <form method="POST" action="{{ route('admin.hrm.recruitment.applications.convert', $application) }}" class="pt-2 border-t border-erp-border">
                        @csrf
                        <button type="submit" class="erp-btn-secondary w-full justify-center">Convert to Employee (Manual)</button>
                        <p class="text-[10px] text-gray-400 text-center mt-2">Review & edit details before enrollment</p>
                    </form>
                @endif
            </div>
        </div>

        @if($application->jobPosting)
            <div class="erp-panel">
                <div class="erp-panel-head">
                    <h2 class="text-xs font-semibold text-gray-700 uppercase tracking-wide">Job Posting</h2>
                </div>
                <div class="erp-panel-body text-sm space-y-2">
                    <p class="font-medium text-gray-900">{{ $application->jobPosting->title }}</p>
                    @if($application->jobPosting->department)
                        <p class="text-xs text-gray-500">{{ $application->jobPosting->department->name }}</p>
                    @endif
                    @if($application->jobPosting->designation)
                        <p class="text-xs text-gray-500">{{ $application->jobPosting->designation->name }}</p>
                    @endif
                    <a href="{{ route('admin.hrm.recruitment.postings.show', $application->jobPosting) }}" class="erp-btn-sm-secondary w-full justify-center mt-2">View Posting</a>
                </div>
            </div>
        @endif

        @if($application->referral_source)
            <div class="erp-panel">
                <div class="erp-panel-body text-sm">
                    <p class="text-[10px] uppercase tracking-wide text-gray-400 mb-1">Referral Source</p>
                    <p class="font-medium">{{ config('hrm.recruitment_referral_sources.' . $application->referral_source, $application->referral_source) }}</p>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
