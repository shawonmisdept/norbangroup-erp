@extends('layouts.admin')
@section('title', 'Compliance — ' . config('app.name'))
@section('breadcrumbs')
    <span class="text-gray-600 font-medium">HRM</span><span>/</span>
    <span class="text-gray-800 font-medium">Compliance</span>
@endsection
@section('admin-content')
@include('partials.erp.page-header', [
    'title' => 'Bangladesh Compliance',
    'subtitle' => 'Statutory registers, festival bonus, gratuity & labour law reports',
])
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3">
    @foreach($modules as $key => $mod)
        <a href="{{ route($mod['route']) }}" class="erp-panel hover:border-brand/40 transition-colors group">
            <div class="erp-panel-body">
                <h3 class="text-sm font-semibold text-gray-900 group-hover:text-brand">{{ $mod['label'] }}</h3>
                <p class="text-[11px] text-gray-500 mt-2 leading-relaxed">{{ $mod['description'] }}</p>
            </div>
        </a>
    @endforeach
</div>
@endsection
