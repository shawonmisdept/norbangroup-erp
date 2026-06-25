@extends('layouts.admin')

@section('title', $employee->employee_code . ' Payslip — ' . $period->periodLabel())

@section('breadcrumbs')
    <a href="{{ route('admin.hrm.salary.hub') }}" class="hover:text-brand">Payroll</a>
    <span>/</span>
    <a href="{{ route('admin.hrm.salary.process.index') }}" class="hover:text-brand">Periods</a>
    <span>/</span>
    <a href="{{ route('admin.hrm.salary.process.show', $period) }}" class="hover:text-brand">{{ $period->periodLabel() }}</a>
    <span>/</span>
    <span class="text-gray-800 font-medium">{{ $employee->employee_code }}</span>
@endsection

@push('styles')
<style>
@media print {
    .erp-topbar, .erp-sidebar, .erp-breadcrumbs, .no-print, .erp-page-header-actions { display: none !important; }
    .erp-main { margin: 0 !important; padding: 0 !important; }
    .erp-panel { break-inside: avoid; box-shadow: none !important; }
}
</style>
@endpush

@section('admin-content')
@include('partials.erp.page-header', [
    'title' => $employee->name,
    'subtitle' => $employee->employee_code . ' · ' . $period->periodLabel() . ' Payslip',
    'actions' => '<div class="flex flex-wrap gap-2 no-print">'
        . '<a href="' . route('admin.hrm.salary.process.show', $period) . '" class="erp-btn-secondary">← Back to Period</a>'
        . '<a href="' . route('admin.hrm.salary.process.payslip.print', [$period, $payslip]) . '" class="erp-btn-secondary">Print View</a>'
        . '<a href="' . route('admin.hrm.salary.process.payslip.print', [$period, $payslip, 'download' => 1]) . '" class="erp-btn-primary">Save PDF</a>'
        . '</div>',
])

@include('hrm.payslip.details', ['payslip' => $payslip, 'employee' => $employee])
@endsection
