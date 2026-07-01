@extends('layouts.admin')

@section('title', $letter->reference_no)

@section('breadcrumbs')
    <a href="{{ route('admin.hrm.recruitment.applications.index') }}" class="hover:text-brand">Applications</a>
    <span>/</span>
    <a href="{{ route('admin.hrm.recruitment.applications.show', $letter->application) }}" class="hover:text-brand">{{ $letter->application?->application_no }}</a>
    <span>/</span>
    <span class="text-gray-800 font-medium">{{ $letter->reference_no }}</span>
@endsection

@section('admin-content')
@include('partials.erp.page-header', [
    'title' => 'Offer Letter — ' . $letter->reference_no,
    'subtitle' => $letter->application?->name,
    'actions' => '<a href="' . route('admin.hrm.recruitment.applications.show', $letter->application) . '" class="erp-btn-secondary">← Application</a>'
        . ' <a href="' . route('admin.hrm.recruitment.offer-letters.print', $letter) . '" target="_blank" class="erp-btn-primary !py-2 !px-4 text-xs">Print / PDF</a>',
])

@include('partials.hrm.letter-document', [
    'content'     => $letter->content,
    'title'       => 'Offer of Employment',
    'factoryName' => $letter->application?->factory?->name,
    'referenceNo' => $letter->reference_no,
    'issuedAt'    => $letter->issued_at,
])

<div class="erp-panel mt-4 max-w-3xl">
    <div class="erp-panel-head"><h2 class="text-xs font-semibold uppercase">Candidate Response</h2></div>
    <div class="erp-panel-body text-sm space-y-2">
        @include('admin.hrm.recruitment.partials.offer-response-badge', ['letter' => $letter])
        @if($letter->offered_salary)
            <p><span class="text-gray-500">Offered Salary:</span> ৳{{ number_format($letter->offered_salary, 2) }}</p>
        @endif
        @if($letter->joining_date)
            <p><span class="text-gray-500">Joining Date:</span> {{ $letter->joining_date->format('d M Y') }}</p>
        @endif
    </div>
</div>
@endsection
