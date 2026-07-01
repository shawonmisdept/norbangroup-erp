@extends('layouts.admin')

@section('title', 'Recruitment Dashboard')

@section('breadcrumbs')
    <a href="{{ route('admin.hrm.dashboard') }}" class="hover:text-brand">HRM</a>
    <span>/</span>
    <span class="text-gray-800 font-medium">Recruitment Dashboard</span>
@endsection

@section('admin-content')
@include('partials.erp.page-header', [
    'title' => 'Recruitment Dashboard',
    'subtitle' => $period_label,
    'actions' => ($canManagePostings ?? false
        ? '<a href="' . route('admin.hrm.recruitment.postings.create') . '" class="erp-btn-primary !py-2 !px-4 text-xs">New Posting</a> '
        : '')
        . '<a href="' . route('admin.hrm.recruitment.applications.index') . '" class="erp-btn-secondary">Applications</a>'
        . ' <a href="' . route('admin.hrm.recruitment.applications.export', $filters) . '" class="erp-btn-secondary">Export Applications</a>'
        . (($canManagePostings ?? false)
            ? ' <a href="' . route('admin.hrm.recruitment.postings.export', $filters) . '" class="erp-btn-secondary">Export Postings</a>'
            : ''),
])

@if(count($factories) > 1 && ! auth()->user()->factory_id)
<div class="flex flex-wrap gap-2 mb-4">
    <a href="{{ route('admin.hrm.recruitment.dashboard', array_merge($filters, ['factory_id' => null])) }}"
       class="px-3 py-1.5 text-[11px] font-semibold rounded-sm border transition-colors {{ empty($filters['factory_id']) ? 'bg-brand text-white border-brand' : 'bg-white text-gray-600 border-erp-border hover:border-brand/40' }}">
        All Companies
    </a>
    @foreach($factories as $id => $name)
        <a href="{{ route('admin.hrm.recruitment.dashboard', array_merge($filters, ['factory_id' => $id])) }}"
           class="px-3 py-1.5 text-[11px] font-semibold rounded-sm border transition-colors {{ (int) ($filters['factory_id'] ?? 0) === (int) $id ? 'bg-brand text-white border-brand' : 'bg-white text-gray-600 border-erp-border hover:border-brand/40' }}">
            {{ $name }}
        </a>
    @endforeach
</div>
@endif

<div class="erp-panel mb-4">
    <form method="GET" class="erp-panel-body flex flex-wrap items-end gap-3">
        @if($filters['factory_id'])
            <input type="hidden" name="factory_id" value="{{ $filters['factory_id'] }}">
        @endif
        <div>
            <label class="erp-form-label">From</label>
            <input type="date" name="from" value="{{ $filters['from'] }}" class="erp-input !text-xs">
        </div>
        <div>
            <label class="erp-form-label">To</label>
            <input type="date" name="to" value="{{ $filters['to'] }}" class="erp-input !text-xs">
        </div>
        <button type="submit" class="erp-btn-primary">Apply</button>
    </form>
</div>

<div class="grid grid-cols-2 md:grid-cols-4 xl:grid-cols-8 gap-3 mb-6">
    @foreach([
        ['Open Postings', $open_postings, 'text-brand', 'border-brand/20 bg-brand/5'],
        ['Applications', $total_applications, 'text-sky-700', 'border-sky-200 bg-sky-50/60'],
        ['Hired', $hired, 'text-emerald-700', 'border-emerald-200 bg-emerald-50/60'],
        ['Offered', $offered, 'text-indigo-700', 'border-indigo-200 bg-indigo-50/60'],
        ['Rejected', $rejected, 'text-red-700', 'border-red-200 bg-red-50/60'],
        ['Conversion', $conversion_rate . '%', 'text-violet-700', 'border-violet-200 bg-violet-50/60'],
        ['Fill Rate', $fill_rate . '%', 'text-amber-700', 'border-amber-200 bg-amber-50/60'],
        ['Avg Days to Hire', $avg_days_to_hire ?? '—', 'text-gray-700', 'border-gray-200 bg-gray-50/60'],
    ] as [$label, $value, $text, $panel])
        <div class="erp-kpi {{ $panel }}">
            <p class="erp-kpi-value {{ $text }}">{{ is_numeric($value) ? number_format($value) : $value }}</p>
            <p class="erp-kpi-label {{ $text }}">{{ $label }}</p>
        </div>
    @endforeach
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
    <div class="xl:col-span-2 space-y-4">
        <div class="erp-panel">
            <div class="erp-panel-head"><h2 class="text-xs font-semibold uppercase">Pipeline</h2></div>
            <div class="erp-panel-body grid grid-cols-2 md:grid-cols-4 gap-3">
                @foreach($statuses as $key => $label)
                    <div class="border border-erp-border rounded-sm p-3 text-center">
                        <p class="text-xl font-bold text-gray-800">{{ $pipeline[$key] ?? 0 }}</p>
                        <p class="text-[10px] uppercase text-gray-500 mt-1">{{ $label }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="erp-panel">
            <div class="erp-panel-head"><h2 class="text-xs font-semibold uppercase">Top Postings by Applications</h2></div>
            <div class="erp-panel-body divide-y divide-erp-border">
                @forelse($top_postings as $posting)
                    <div class="flex items-center justify-between py-2 text-sm">
                        <div>
                            <a href="{{ route('admin.hrm.recruitment.postings.show', $posting) }}" class="font-medium hover:text-brand">{{ $posting->title }}</a>
                            <p class="text-xs text-gray-500">{{ $posting->statusLabel() }} · {{ $posting->remainingSlots() }} slots left</p>
                        </div>
                        <span class="font-bold text-brand">{{ $posting->applications_count }}</span>
                    </div>
                @empty
                    <p class="text-sm text-gray-400">No postings in this period.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="space-y-4">
        <div class="erp-panel">
            <div class="erp-panel-head"><h2 class="text-xs font-semibold uppercase">Upcoming Interviews</h2></div>
            <div class="erp-panel-body space-y-2">
                @forelse($upcoming_interviews as $interview)
                    <a href="{{ route('admin.hrm.recruitment.applications.show', $interview->application) }}" class="block border border-erp-border rounded-sm p-2 hover:border-brand/40 text-sm">
                        <p class="font-medium">{{ $interview->application?->name }}</p>
                        <p class="text-xs text-gray-500">{{ $interview->scheduled_at->format('d M, h:i A') }}</p>
                        <p class="text-xs text-gray-400">{{ $interview->application?->jobPosting?->title }}</p>
                    </a>
                @empty
                    <p class="text-sm text-gray-400">No interviews in the next 7 days.</p>
                @endforelse
            </div>
        </div>

        <div class="erp-panel">
            <div class="erp-panel-head"><h2 class="text-xs font-semibold uppercase">Recent Applications</h2></div>
            <div class="erp-panel-body space-y-2">
                @forelse($recent_applications as $app)
                    <a href="{{ route('admin.hrm.recruitment.applications.show', $app) }}" class="block border border-erp-border rounded-sm p-2 hover:border-brand/40 text-sm">
                        <p class="font-medium">{{ $app->name }}</p>
                        <p class="text-xs text-gray-500">{{ $app->application_no }} · {{ $app->statusLabel() }}</p>
                    </a>
                @empty
                    <p class="text-sm text-gray-400">No applications in this period.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
