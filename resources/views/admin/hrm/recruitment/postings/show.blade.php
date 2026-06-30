@extends('layouts.admin')

@section('title', $posting->title)

@section('breadcrumbs')
    <a href="{{ route('admin.hrm.recruitment.postings.index') }}" class="hover:text-brand">Job Postings</a>
    <span>/</span>
    <span class="text-gray-800 font-medium">{{ $posting->title }}</span>
@endsection

@section('admin-content')
@include('partials.erp.page-header', [
    'title' => $posting->title,
    'subtitle' => $posting->factory?->name . ' · ' . $posting->statusLabel(),
    'actions' => '<a href="' . route('admin.hrm.recruitment.postings.index') . '" class="erp-btn-secondary">← Back</a>'
        . ($canManage ? ' <a href="' . route('admin.hrm.recruitment.postings.edit', $posting) . '" class="erp-btn-secondary !py-2 !px-4 text-xs">Edit</a>' : '')
        . ($posting->isPubliclyOpen() ? ' <a href="' . route('careers.show', $posting) . '" target="_blank" class="erp-btn-primary !py-2 !px-4 text-xs">Public Page</a>' : ''),
])

@include('admin.hrm.recruitment.postings.partials.quick-actions')

<div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3 mb-4">
    <div class="erp-panel p-3"><p class="text-[10px] text-gray-400 uppercase">Page Views</p><p class="text-lg font-bold tabular-nums">{{ number_format($analytics['page_views']) }}</p></div>
    <div class="erp-panel p-3"><p class="text-[10px] text-gray-400 uppercase">Applications</p><p class="text-lg font-bold tabular-nums">{{ $analytics['applications'] }}</p></div>
    <div class="erp-panel p-3"><p class="text-[10px] text-gray-400 uppercase">Hired</p><p class="text-lg font-bold tabular-nums">{{ $analytics['hired'] }}</p></div>
    <div class="erp-panel p-3"><p class="text-[10px] text-gray-400 uppercase">Apply Rate</p><p class="text-lg font-bold tabular-nums">{{ $analytics['conversion_rate'] !== null ? $analytics['conversion_rate'] . '%' : '—' }}</p></div>
    <div class="erp-panel p-3"><p class="text-[10px] text-gray-400 uppercase">Avg Days to Hire</p><p class="text-lg font-bold tabular-nums">{{ $analytics['avg_days_to_hire'] ?? '—' }}</p></div>
    <div class="erp-panel p-3"><p class="text-[10px] text-gray-400 uppercase">Remaining Slots</p><p class="text-lg font-bold tabular-nums">{{ $analytics['remaining_slots'] }}</p></div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <div class="lg:col-span-2 space-y-4">
        @if($posting->description)
            <div class="erp-panel">
                <div class="erp-panel-head"><h2 class="text-xs font-semibold uppercase">Overview</h2></div>
                <div class="erp-panel-body text-sm prose prose-sm max-w-none">{!! $posting->description !!}</div>
            </div>
        @endif
        @if($posting->description_bn)
            <div class="erp-panel">
                <div class="erp-panel-head"><h2 class="text-xs font-semibold uppercase">বাংলা বিবরণ</h2></div>
                <div class="erp-panel-body text-sm prose prose-sm max-w-none">{!! $posting->description_bn !!}</div>
            </div>
        @endif
        @foreach([
            'requirements' => 'Requirements',
            'responsibilities' => 'Responsibilities',
            'skills_expertise' => 'Skills & Expertise',
            'employment_status' => 'Employment Status',
            'benefits' => 'Benefits',
        ] as $field => $label)
            @if($posting->{$field})
                <div class="erp-panel">
                    <div class="erp-panel-head"><h2 class="text-xs font-semibold uppercase">{{ $label }}</h2></div>
                    <div class="erp-panel-body text-sm prose prose-sm max-w-none">{!! $posting->{$field} !!}</div>
                </div>
            @endif
        @endforeach
        @if($posting->salaryDisplay())
            <div class="erp-panel">
                <div class="erp-panel-head"><h2 class="text-xs font-semibold uppercase">Salary</h2></div>
                <div class="erp-panel-body text-sm">{{ $posting->salaryDisplay() }}</div>
            </div>
        @endif

        @if($pipeline !== [])
            <div class="erp-panel">
                <div class="erp-panel-head"><h2 class="text-xs font-semibold uppercase">Application Pipeline</h2></div>
                <div class="erp-panel-body">
                    <div class="flex flex-wrap gap-2">
                        @foreach($statuses as $key => $label)
                            @if(isset($pipeline[$key]))
                                <span class="erp-badge bg-gray-100 text-gray-700">{{ $label }}: {{ $pipeline[$key] }}</span>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        @if($posting->logs->isNotEmpty())
            <div class="erp-panel">
                <div class="erp-panel-head"><h2 class="text-xs font-semibold uppercase">Activity Log</h2></div>
                <div class="erp-panel-body space-y-2 text-xs">
                    @foreach($posting->logs as $log)
                        <div class="border-b border-erp-border pb-2 last:border-0">
                            <p class="font-medium">{{ ucfirst(str_replace('_', ' ', $log->action)) }} · {{ $log->created_at->format('d M Y H:i') }}</p>
                            @if($log->notes)<p class="text-gray-600">{{ $log->notes }}</p>@endif
                            @if($log->user)<p class="text-gray-400">By {{ $log->user->name }}</p>@endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
    <div class="space-y-4">
        <div class="erp-panel">
            <div class="erp-panel-body space-y-3 text-sm">
                <div><p class="text-[10px] text-gray-400 uppercase">Status</p><p>{{ $posting->statusLabel() }}</p></div>
                <div><p class="text-[10px] text-gray-400 uppercase">Slots</p><p>{{ $posting->openings_filled }} filled / {{ $posting->slots }} total</p></div>
                <div><p class="text-[10px] text-gray-400 uppercase">Applications</p><p>{{ $posting->applications_count ?? $posting->applications()->count() }}</p></div>
                @if($posting->shiftLabel())<div><p class="text-[10px] text-gray-400 uppercase">Shift</p><p>{{ $posting->shiftLabel() }}</p></div>@endif
                @if($posting->ageRequirementLabel())<div><p class="text-[10px] text-gray-400 uppercase">Age</p><p>{{ $posting->ageRequirementLabel() }}</p></div>@endif
                @if($posting->requiredGenderLabel())<div><p class="text-[10px] text-gray-400 uppercase">Gender</p><p>{{ $posting->requiredGenderLabel() }}</p></div>@endif
                @if($posting->rehire_eligible)<div><p class="text-[10px] text-gray-400 uppercase">Rehire</p><p>Former employees eligible</p></div>@endif
                @if($posting->is_internal)<div><p class="text-[10px] text-gray-400 uppercase">Visibility</p><p>Internal only (hidden from portal)</p></div>@endif
                @if($posting->closes_at)<div><p class="text-[10px] text-gray-400 uppercase">Closes</p><p>{{ $posting->closes_at->format('d M Y') }}</p></div>@endif
                @if($posting->approved_at)<div><p class="text-[10px] text-gray-400 uppercase">Approved</p><p>{{ $posting->approved_at->format('d M Y') }}@if($posting->approver) · {{ $posting->approver->name }}@endif</p></div>@endif
                @if($canManage)
                    <a href="{{ route('admin.hrm.recruitment.applications.create', ['job_posting_id' => $posting->id]) }}" class="erp-btn-sm-primary w-full justify-center">Manual Application Entry</a>
                    <a href="{{ route('admin.hrm.recruitment.applications.index', ['job_posting_id' => $posting->id]) }}" class="erp-btn-sm-secondary w-full justify-center">View Applications</a>
                @endif
            </div>
        </div>

        @if($posting->isPubliclyOpen())
            @php $shareUrl = urlencode($posting->publicShareUrl()); $shareTitle = urlencode($posting->title); @endphp
            <div class="erp-panel">
                <div class="erp-panel-head"><h2 class="text-xs font-semibold uppercase">Share</h2></div>
                <div class="erp-panel-body flex flex-wrap gap-2">
                    <a href="https://www.facebook.com/sharer/sharer.php?u={{ $shareUrl }}" target="_blank" rel="noopener" class="erp-btn-sm-secondary">Facebook</a>
                    <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ $shareUrl }}" target="_blank" rel="noopener" class="erp-btn-sm-secondary">LinkedIn</a>
                    <button type="button" class="erp-btn-sm-secondary" onclick="navigator.clipboard.writeText('{{ $posting->publicShareUrl() }}')">Copy Link</button>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
