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
        . ($posting->isOpen() ? ' <a href="' . route('careers.show', $posting) . '" target="_blank" class="erp-btn-primary !py-2 !px-4 text-xs">Public Page</a>' : ''),
])

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <div class="lg:col-span-2 space-y-4">
        @foreach([
            'requirements' => 'Requirements',
            'responsibilities' => 'Responsibilities',
            'skills_expertise' => 'Skills & Expertise',
            'employment_status' => 'Employment Status',
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
    </div>
    <div class="erp-panel">
        <div class="erp-panel-body space-y-3 text-sm">
            <div><p class="text-[10px] text-gray-400 uppercase">Status</p><p>{{ $posting->statusLabel() }}</p></div>
            <div><p class="text-[10px] text-gray-400 uppercase">Slots</p><p>{{ $posting->openings_filled }} filled / {{ $posting->slots }} total</p></div>
            <div><p class="text-[10px] text-gray-400 uppercase">Applications</p><p>{{ $posting->applications_count ?? $posting->applications()->count() }}</p></div>
            @if($posting->closes_at)<div><p class="text-[10px] text-gray-400 uppercase">Closes</p><p>{{ $posting->closes_at->format('d M Y') }}</p></div>@endif
            @if($canManage)
                <a href="{{ route('admin.hrm.recruitment.applications.create', ['job_posting_id' => $posting->id]) }}" class="erp-btn-sm-primary w-full justify-center">Manual Application Entry</a>
                <a href="{{ route('admin.hrm.recruitment.applications.index', ['job_posting_id' => $posting->id]) }}" class="erp-btn-sm-secondary w-full justify-center">View Applications</a>
            @endif
        </div>
    </div>
</div>
@endsection
