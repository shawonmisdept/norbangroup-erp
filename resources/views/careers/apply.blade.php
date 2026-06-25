@extends('layouts.careers')

@section('title', 'Apply — ' . $posting->title)

@section('content')
<a href="{{ route('careers.show', $posting) }}" class="careers-back">← Back to job details</a>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-[var(--careers-navy)] tracking-tight">Apply: {{ $posting->title }}</h1>
    <p class="text-sm text-[var(--careers-muted)] mt-1">{{ $posting->factory?->name }} · Fill in your details below</p>
</div>

@include('partials.hrm.recruitment-application-form', [
    'formAction'      => route('careers.apply.store', $posting),
    'posting'         => $posting,
    'isPublic'        => true,
    'genders'         => $genders,
    'referralSources' => $referralSources,
    'submitLabel'     => 'Submit Application',
    'otpSendUrl'      => $otpSendUrl ?? null,
])
@endsection
