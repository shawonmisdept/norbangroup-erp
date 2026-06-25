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
@endsection
