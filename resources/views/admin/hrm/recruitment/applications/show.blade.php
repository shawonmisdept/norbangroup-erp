@extends('layouts.admin')

@section('title', $application->application_no)

@section('breadcrumbs')
    <a href="{{ route('admin.hrm.recruitment.applications.index') }}" class="hover:text-brand">Applications</a>
    <span>/</span>
    <span class="text-gray-800 font-medium">{{ $application->application_no }}</span>
@endsection

@section('admin-content')
@include('partials.erp.page-header', [
    'title' => $application->name,
    'subtitle' => $application->application_no . ' · ' . $application->jobPosting?->title,
    'actions' => '<a href="' . route('admin.hrm.recruitment.applications.index') . '" class="erp-btn-secondary">← Back</a>',
])

@if($formerEmployee)
    <div class="mb-4 bg-blue-50 border border-blue-200 rounded-sm p-3 text-xs text-blue-800">
        Former employee: <strong>{{ $formerEmployee->employee_code }}</strong> ({{ $formerEmployee->statusLabel() }}) — rehire allowed.
    </div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <div class="lg:col-span-2 space-y-4">
        <div class="erp-panel">
            <div class="erp-panel-head"><h2 class="text-xs font-semibold uppercase">Candidate Details</h2></div>
            <div class="erp-panel-body grid grid-cols-2 gap-4 text-sm">
                <div><p class="text-[10px] text-gray-400 uppercase">Phone</p><p>{{ $application->phone }}</p></div>
                <div><p class="text-[10px] text-gray-400 uppercase">Email</p><p>{{ $application->email ?? '—' }}</p></div>
                <div><p class="text-[10px] text-gray-400 uppercase">Gender</p><p>{{ config('hrm.employee_options.genders.' . $application->gender, $application->gender ?? '—') }}</p></div>
                <div><p class="text-[10px] text-gray-400 uppercase">DOB</p><p>{{ $application->date_of_birth?->format('d M Y') ?? '—' }}</p></div>
                <div><p class="text-[10px] text-gray-400 uppercase">NID</p><p>{{ $application->nid_number ?? '—' }}</p></div>
                <div><p class="text-[10px] text-gray-400 uppercase">Source</p><p>{{ $application->sourceLabel() }}</p></div>
                <div class="col-span-2"><p class="text-[10px] text-gray-400 uppercase">Present Address</p><p>{{ $application->present_address ?? '—' }}</p></div>
                @if($application->photoUrl() || $application->nidDocumentUrl())
                    <div class="col-span-2 flex gap-3">
                        @if($application->photoUrl())<a href="{{ $application->photoUrl() }}" target="_blank" class="erp-btn-sm-secondary">Photo</a>@endif
                        @if($application->nidDocumentUrl())<a href="{{ $application->nidDocumentUrl() }}" target="_blank" class="erp-btn-sm-secondary">NID Document</a>@endif
                    </div>
                @endif
            </div>
        </div>

        @if($application->education_history)
            <div class="erp-panel">
                <div class="erp-panel-head"><h2 class="text-xs font-semibold uppercase">Education</h2></div>
                <div class="erp-panel-body space-y-2 text-sm">
                    @foreach($application->education_history as $edu)
                        <div class="border border-erp-border rounded-sm p-2 text-xs">
                            {{ $edu['degree'] ?? '' }} — {{ $edu['institution'] ?? '' }} ({{ $edu['passing_year'] ?? '' }})
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="erp-panel">
            <div class="erp-panel-head"><h2 class="text-xs font-semibold uppercase">Interviews</h2></div>
            <div class="erp-panel-body space-y-3">
                @forelse($application->interviews as $interview)
                    <div class="border border-erp-border rounded-sm p-3 text-sm">
                        <div class="flex justify-between gap-2">
                            <p class="font-medium">{{ $interview->scheduled_at->format('d M Y, h:i A') }}</p>
                            <span class="erp-badge bg-gray-100 text-gray-700 text-[10px]">{{ $interview->resultLabel() }}</span>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">{{ $interview->typeLabel() }} @if($interview->location) · {{ $interview->location }} @endif</p>
                        @if($interview->result === 'pending' && $canManage)
                            <form method="POST" action="{{ route('admin.hrm.recruitment.applications.interviews.complete', [$application, $interview]) }}" class="mt-3 grid grid-cols-3 gap-2">
                                @csrf
                                <select name="result" required class="erp-input !text-xs col-span-1">
                                    @foreach($interviewResults as $val => $label)
                                        @if($val !== 'pending')
                                            <option value="{{ $val }}">{{ $label }}</option>
                                        @endif
                                    @endforeach
                                </select>
                                <input type="number" name="score" min="0" max="100" placeholder="Score" class="erp-input !text-xs">
                                <button type="submit" class="erp-btn-sm-primary">Save</button>
                            </form>
                        @endif
                    </div>
                @empty
                    <p class="text-gray-400 text-sm">No interviews scheduled.</p>
                @endforelse

                @if($canManage && ! $application->isTerminal())
                    <form method="POST" action="{{ route('admin.hrm.recruitment.applications.interviews.store', $application) }}" class="border-t border-erp-border pt-3 space-y-2">
                        @csrf
                        <p class="text-[10px] text-gray-400 uppercase font-semibold">Schedule Interview</p>
                        <input type="datetime-local" name="scheduled_at" required class="erp-input !text-xs">
                        <select name="interview_type" class="erp-input !text-xs">
                            @foreach($interviewTypes as $val => $label)
                                <option value="{{ $val }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <input type="text" name="location" placeholder="Location" class="erp-input !text-xs">
                        <button type="submit" class="erp-btn-sm-secondary w-full justify-center">Schedule</button>
                    </form>
                @endif
            </div>
        </div>

        <div class="erp-panel">
            <div class="erp-panel-head"><h2 class="text-xs font-semibold uppercase">Activity Log</h2></div>
            <div class="erp-panel-body space-y-2">
                @foreach($application->logs as $log)
                    <div class="text-xs border border-erp-border rounded-sm p-2">
                        <span class="font-medium">{{ $log->from_status ? ucfirst($log->from_status) . ' → ' : '' }}{{ ucfirst($log->to_status) }}</span>
                        · {{ $log->created_at->format('d M Y H:i') }}
                        @if($log->user) · {{ $log->user->name }} @endif
                        @if($log->notes)<p class="text-gray-600 mt-1">{{ $log->notes }}</p>@endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="space-y-3">
        <div class="erp-panel">
            <div class="erp-panel-body text-sm space-y-2">
                <div><p class="text-[10px] text-gray-400 uppercase">Status</p><p class="font-semibold">{{ $application->statusLabel() }}</p></div>
                <div><p class="text-[10px] text-gray-400 uppercase">Applied</p><p>{{ $application->applied_at->format('d M Y H:i') }}</p></div>
                @if($application->convertedEmployee)
                    <a href="{{ route('admin.hrm.employees.show', $application->convertedEmployee) }}" class="erp-btn-sm-primary w-full justify-center">View Employee</a>
                @endif
            </div>
        </div>

        @if($canManage && ! $application->isTerminal())
            <div class="erp-panel">
                <div class="erp-panel-head"><h2 class="text-xs font-semibold uppercase">Offer Letters</h2></div>
                <div class="erp-panel-body space-y-2">
                    @forelse($application->offerLetters as $letter)
                        <div class="flex items-center justify-between gap-2 border border-erp-border rounded-sm p-2 text-sm">
                            <div>
                                <p class="font-medium">{{ $letter->reference_no }}</p>
                                <p class="text-xs text-gray-500">{{ $letter->issued_at->format('d M Y') }}</p>
                            </div>
                            <div class="flex gap-2">
                                <a href="{{ route('admin.hrm.recruitment.offer-letters.show', $letter) }}" class="erp-btn-sm-secondary">View</a>
                                <a href="{{ route('admin.hrm.recruitment.offer-letters.print', $letter) }}" target="_blank" class="erp-btn-sm-secondary">Print</a>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-400">No offer letter issued yet.</p>
                    @endforelse
                    <a href="{{ route('admin.hrm.recruitment.applications.offer-letter.create', $application) }}" class="erp-btn-sm-primary w-full justify-center">Issue Offer Letter</a>
                </div>
            </div>
        @endif

        @if($canManage && ! $application->isTerminal())
            <div class="erp-panel">
                <div class="erp-panel-head"><h2 class="text-xs font-semibold uppercase">Update Status</h2></div>
                <form method="POST" action="{{ route('admin.hrm.recruitment.applications.status', $application) }}" class="erp-panel-body space-y-3">
                    @csrf
                    <select name="status" required class="erp-input !text-xs">
                        @foreach($statuses as $val => $label)
                            <option value="{{ $val }}" {{ $application->status === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    <textarea name="notes" rows="2" class="erp-input !text-xs" placeholder="Notes…"></textarea>
                    <textarea name="rejection_reason" rows="2" class="erp-input !text-xs" placeholder="Rejection reason (if rejecting)…"></textarea>
                    <button type="submit" class="erp-btn-primary w-full justify-center">Update</button>
                </form>
            </div>
        @endif

        @if($canConvert && $application->canConvert())
            <form method="POST" action="{{ route('admin.hrm.recruitment.applications.convert', $application) }}">
                @csrf
                <button type="submit" class="erp-btn-primary w-full justify-center">Convert to Employee</button>
            </form>
        @endif
    </div>
</div>
@endsection
