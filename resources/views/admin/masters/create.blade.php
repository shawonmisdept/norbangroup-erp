@php
    $routePrefix = $routePrefix ?? 'admin.masters';
    $hubLabel = $hubLabel ?? 'Master Data';
@endphp
@extends('layouts.admin')

@section('title', 'Add ' . $config['label'])

@section('breadcrumbs')
    <a href="{{ route("{$routePrefix}.hub") }}" class="hover:text-brand">{{ $hubLabel }}</a>
    <span>/</span>
    <a href="{{ route("{$routePrefix}.index", $module) }}" class="hover:text-brand">{{ $config['label_plural'] }}</a>
    <span>/</span>
    <span class="text-gray-800 font-medium">New</span>
@endsection

@section('admin-content')

@include('partials.erp.page-header', [
    'title' => 'New ' . $config['label'],
    'subtitle' => 'Fill in the details below to create a new record',
    'actions' => '<a href="' . route("{$routePrefix}.index", $module) . '" class="erp-btn-secondary">← Back to List</a>',
])

<form method="POST" action="{{ route("{$routePrefix}.store", $module) }}" enctype="multipart/form-data">
    @csrf
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-4 items-start">
        <div class="xl:col-span-2 space-y-4">
            @include('admin.masters.partials.form-body')
        </div>
        <div>
            @include('admin.masters.partials.form-sidebar')
        </div>
    </div>
</form>
@endsection
