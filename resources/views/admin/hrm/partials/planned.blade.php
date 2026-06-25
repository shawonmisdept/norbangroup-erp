@extends('layouts.admin')

@section('title', ($section ?? 'Module') . ' — ' . config('app.name'))

@section('admin-content')
@include('partials.erp.page-header', [
    'title' => $label,
    'subtitle' => $description,
    'actions' => '<a href="' . route($hubRoute) . '" class="erp-btn-secondary">← ' . e($section) . ' Hub</a>',
])

<div class="erp-panel max-w-lg">
    <div class="erp-panel-body text-center py-12">
        <p class="text-sm font-semibold text-gray-700">{{ $label }}</p>
        <p class="text-xs text-gray-500 mt-2 max-w-sm mx-auto">{{ $description }}</p>
        <span class="inline-block mt-4 erp-badge bg-amber-100 text-amber-800">Coming soon</span>
    </div>
</div>
@endsection
