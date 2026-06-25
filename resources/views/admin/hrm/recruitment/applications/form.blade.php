@extends('layouts.admin')

@section('title', 'Manual Application')

@section('breadcrumbs')
    <a href="{{ route('admin.hrm.recruitment.applications.index') }}" class="hover:text-brand">Applications</a>
    <span>/</span>
    <span class="text-gray-800 font-medium">Manual Entry</span>
@endsection

@section('admin-content')
@include('partials.erp.page-header', [
    'title' => 'Manual Application Entry',
    'subtitle' => 'Walk-in, referral or other offline applications',
    'actions' => '<a href="' . route('admin.hrm.recruitment.applications.index') . '" class="erp-btn-secondary">← Back</a>',
])

@include('partials.hrm.recruitment-application-form', [
    'formAction'      => route('admin.hrm.recruitment.applications.store'),
    'application'     => $application,
    'postings'        => $postings,
    'selectedPosting' => $selectedPosting,
    'genders'         => $genders,
    'referralSources' => $referralSources,
    'isPublic'        => false,
    'submitLabel'     => 'Save Application',
])
@endsection
