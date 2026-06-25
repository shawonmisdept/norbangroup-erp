@php
    $routePrefix = $routePrefix ?? 'admin.masters';
    $hubLabel = $hubLabel ?? 'Master Data';
@endphp
@extends('layouts.admin')

@section('title', 'Edit ' . $config['label'])

@section('breadcrumbs')
    <a href="{{ route("{$routePrefix}.hub") }}" class="hover:text-brand">{{ $hubLabel }}</a>
    <span>/</span>
    <a href="{{ route("{$routePrefix}.index", $module) }}" class="hover:text-brand">{{ $config['label_plural'] }}</a>
    <span>/</span>
    <a href="{{ route("{$routePrefix}.show", [$module, $record]) }}" class="hover:text-brand">{{ $record->code }}</a>
    <span>/</span>
    <span class="text-gray-800 font-medium">Edit</span>
@endsection

@section('admin-content')

@include('partials.erp.page-header', [
    'title' => 'Edit ' . $config['label'],
    'subtitle' => $record->name,
    'actions' => '<a href="' . route("{$routePrefix}.show", [$module, $record]) . '" class="erp-btn-secondary">← View Record</a>',
])

<form method="POST" action="{{ route("{$routePrefix}.update", [$module, $record]) }}" enctype="multipart/form-data">
    @csrf @method('PUT')
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-4 items-start">
        <div class="xl:col-span-2 space-y-4">
            @include('admin.masters.partials.form-body', ['record' => $record])
        </div>
        <div>
            @include('admin.masters.partials.form-sidebar', ['record' => $record])
        </div>
    </div>
</form>
@endsection
