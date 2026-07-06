@extends('layouts.employee')

@section('title', $letter->typeLabel())
@section('page-title', $letter->typeLabel())
@section('page-subtitle', $letter->reference_no)
@section('back', route('employee.profile'))

@section('header-action')
    <a href="{{ route('employee.letters.print', $letter) }}" target="_blank"
       class="rounded-xl bg-white/15 px-3 py-1.5 text-xs font-semibold text-white backdrop-blur transition hover:bg-white/25">
        Download
    </a>
@endsection

@section('content')
@include('partials.hrm.letter-document-styles')
<div class="emp-card overflow-hidden p-4">
    @include('partials.hrm.letter-document', [
        'content'      => $letter->content,
        'title'        => $letter->typeLabel(),
        'factoryName'  => $employee->factory?->name,
        'referenceNo'  => $letter->reference_no,
        'issuedAt'     => $letter->issued_at,
    ])
</div>
@endsection
