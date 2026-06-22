@extends('layouts.admin')

@section('title', 'Master Data — ' . config('app.name'))

@section('breadcrumbs')
    <span class="text-gray-600 font-medium">Master Data</span>
    <span>/</span>
    <span class="text-gray-800 font-medium">All Modules</span>
@endsection

@section('admin-content')

@include('partials.erp.page-header', [
    'title' => 'Master Data Registry',
    'subtitle' => 'Reference data for organization, commercial, product, material and sample modules',
])

<div class="space-y-5">
    @foreach($groups as $groupName => $modules)
        <section class="erp-panel overflow-hidden">
            <div class="erp-panel-head">
                <h2 class="text-xs font-semibold text-gray-700 uppercase tracking-wide">{{ $groupName }}</h2>
                <span class="text-[11px] text-gray-400">{{ count($modules) }} module(s)</span>
            </div>
            <div class="p-3 grid gap-2 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @foreach($modules as $moduleKey)
                    @php $module = config("masters.modules.{$moduleKey}"); @endphp
                    @if($module)
                        <a href="{{ route('admin.masters.index', $moduleKey) }}"
                           class="flex items-center gap-3 px-3 py-2.5 rounded-sm border border-transparent hover:border-brand/30 hover:bg-blue-50/50 transition group">
                            <div class="w-8 h-8 rounded-sm bg-brand/10 text-brand flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                    <path d="M4 6h16M4 10h16M4 14h16" stroke-linecap="round"/>
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-gray-800 group-hover:text-brand truncate">{{ $module['label_plural'] }}</p>
                                <p class="text-[10px] text-gray-400 uppercase tracking-wide">{{ $module['label'] }}</p>
                            </div>
                        </a>
                    @endif
                @endforeach
            </div>
        </section>
    @endforeach
</div>
@endsection
