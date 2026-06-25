@extends('layouts.admin')

@section('title', 'Issue Offer Letter')

@section('breadcrumbs')
    <a href="{{ route('admin.hrm.recruitment.applications.index') }}" class="hover:text-brand">Applications</a>
    <span>/</span>
    <a href="{{ route('admin.hrm.recruitment.applications.show', $application) }}" class="hover:text-brand">{{ $application->application_no }}</a>
    <span>/</span>
    <span class="text-gray-800 font-medium">Offer Letter</span>
@endsection

@section('admin-content')
@include('partials.erp.page-header', [
    'title' => 'Issue Offer Letter',
    'subtitle' => $application->name . ' · ' . $application->jobPosting?->title,
    'actions' => '<a href="' . route('admin.hrm.recruitment.applications.show', $application) . '" class="erp-btn-secondary">← Back</a>',
])

<form method="POST" action="{{ route('admin.hrm.recruitment.applications.offer-letter.store', $application) }}" class="grid grid-cols-1 xl:grid-cols-2 gap-4">
    @csrf
    <div class="erp-panel">
        <div class="erp-panel-head"><h2 class="text-xs font-semibold uppercase">Offer Details</h2></div>
        <div class="erp-panel-body space-y-4">
            <div>
                <label class="erp-form-label">Offered Salary (Monthly)</label>
                <input type="number" step="0.01" name="offered_salary" value="{{ old('offered_salary', $application->expected_salary) }}" class="erp-input !text-xs">
            </div>
            <div>
                <label class="erp-form-label">Joining Date</label>
                <input type="date" name="joining_date" value="{{ old('joining_date', now()->addDays(7)->toDateString()) }}" class="erp-input !text-xs">
            </div>
            <div>
                <label class="erp-form-label">Internal Notes</label>
                <textarea name="notes" rows="3" class="erp-input !text-xs">{{ old('notes') }}</textarea>
            </div>
            <button type="submit" class="erp-btn-primary">Issue Offer Letter</button>
            <p class="text-[11px] text-gray-400">Status will move to <strong>Offered</strong> and candidate will be notified if email/SMS is enabled.</p>
        </div>
    </div>

    <div class="erp-panel">
        <div class="erp-panel-head"><h2 class="text-xs font-semibold uppercase">Preview</h2></div>
        <div class="erp-panel-body">
            @include('partials.hrm.letter-document', [
                'content'     => $preview,
                'title'       => 'Offer of Employment',
                'factoryName' => $application->factory?->name,
                'referenceNo' => 'Preview',
                'issuedAt'    => now(),
            ])
        </div>
    </div>
</form>
@endsection
