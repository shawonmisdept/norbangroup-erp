@extends('layouts.admin')

@section('title', 'Attendance — ' . config('app.name'))

@section('breadcrumbs')
    <span class="text-gray-600 font-medium">HRM</span><span>/</span>
    <span class="text-gray-800 font-medium">Attendance</span>
@endsection

@section('admin-content')
@include('partials.erp.page-header', [
    'title' => 'Attendance Management',
    'subtitle' => 'Device sync, daily logs, periods, policy, and late acceptance',
    'actions' => view('partials.admin.kb-workflow-link', ['module' => 'hrm-attendance'])->render(),
])

<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3">
    @foreach($modules as $key => $mod)
        <a href="{{ ($mod['status'] ?? '') === 'planned' ? route('admin.hrm.attendance.planned', $key) : route($mod['route']) }}"
           class="erp-panel hover:border-brand/40 transition-colors group">
            <div class="erp-panel-body">
                <div class="flex items-start justify-between gap-2">
                    <h3 class="text-sm font-semibold text-gray-900 group-hover:text-brand">{{ $mod['label'] }}</h3>
                    <span class="erp-badge bg-green-100 text-green-700 text-[10px]">Active</span>
                </div>
                <p class="text-[11px] text-gray-500 mt-2 leading-relaxed">{{ $mod['description'] }}</p>
            </div>
        </a>
    @endforeach
</div>
@endsection
