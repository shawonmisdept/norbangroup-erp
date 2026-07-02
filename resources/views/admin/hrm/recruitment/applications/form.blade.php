@extends('layouts.admin')

@section('title', ($isEdit ?? false) ? 'Edit Application' : 'Manual Application')

@section('breadcrumbs')
    <a href="{{ route('admin.hrm.recruitment.applications.index') }}" class="hover:text-brand">Applications</a>
    <span>/</span>
    @if($isEdit ?? false)
        <a href="{{ route('admin.hrm.recruitment.applications.show', $application) }}" class="hover:text-brand">{{ $application->application_no }}</a>
        <span>/</span>
        <span class="text-gray-800 font-medium">Edit</span>
    @else
        <span class="text-gray-800 font-medium">Manual Entry</span>
    @endif
@endsection

@section('admin-content')
@include('partials.erp.page-header', [
    'title' => ($isEdit ?? false) ? 'Edit Application' : 'Manual Application Entry',
    'subtitle' => ($isEdit ?? false)
        ? ($application->application_no . ' · ' . ($application->jobPosting?->title ?? 'Application'))
        : 'Walk-in, referral or other offline applications',
    'actions' => '<a href="' . (($isEdit ?? false) ? route('admin.hrm.recruitment.applications.show', $application) : route('admin.hrm.recruitment.applications.index')) . '" class="erp-btn-secondary">← Back</a>',
])

@include('partials.hrm.recruitment-application-form', [
    'formAction'      => ($isEdit ?? false)
        ? route('admin.hrm.recruitment.applications.update', $application)
        : route('admin.hrm.recruitment.applications.store'),
    'formMethod'      => ($isEdit ?? false) ? 'PUT' : null,
    'application'     => $application,
    'postings'        => $postings,
    'selectedPosting' => $selectedPosting,
    'genders'         => $genders,
    'referralSources' => $referralSources,
    'isPublic'        => false,
    'isEdit'          => $isEdit ?? false,
    'submitLabel'     => ($isEdit ?? false) ? 'Save Changes' : 'Save Application',
])
@endsection
