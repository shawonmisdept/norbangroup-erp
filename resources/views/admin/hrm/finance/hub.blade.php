@extends('layouts.admin')
@section('title', 'Finance')
@section('breadcrumbs')
    <span class="text-gray-600 font-medium">HRM</span><span>/</span>
    <span class="text-gray-800 font-medium">Finance</span>
@endsection
@section('admin-content')
@include('partials.erp.page-header', [
    'title' => 'Finance & Statutory Deductions',
    'subtitle' => 'Income tax (TDS), provident fund, loans & payroll integration',
])
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3">
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
