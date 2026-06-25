@extends('layouts.admin')
@section('title', 'Gratuity Settlement')
@section('admin-content')
@include('partials.erp.page-header', ['title' => 'Gratuity — '.$settlement->employee?->name, 'actions' => '<a href="'.route('admin.hrm.compliance.gratuity.index').'" class="erp-btn-secondary">← Back</a>'])
<div class="erp-panel max-w-2xl"><div class="erp-panel-body space-y-2 text-sm">
    <p><span class="text-gray-500">Employee:</span> {{ $settlement->employee?->employee_code }} — {{ $settlement->employee?->name }}</p>
    <p><span class="text-gray-500">Separation date:</span> {{ $settlement->separation_date->format('d M Y') }}</p>
    <p><span class="text-gray-500">Years of service:</span> {{ $settlement->years_of_service }}</p>
    <p><span class="text-gray-500">Last basic salary:</span> ৳{{ number_format($settlement->last_basic_salary, 2) }}</p>
    <p><span class="text-gray-500">Gratuity amount:</span> <strong class="text-brand">৳{{ number_format($settlement->gratuity_amount, 2) }}</strong></p>
    <p><span class="text-gray-500">Status:</span> {{ ucfirst($settlement->status) }}</p>
    @if($settlement->notes)<p class="text-xs text-gray-500">{{ $settlement->notes }}</p>@endif
    @if($canManage && $settlement->status === 'calculated' && $settlement->gratuity_amount > 0)
    <form method="POST" action="{{ route('admin.hrm.compliance.gratuity.paid', $settlement) }}" class="pt-2">@csrf
        <button type="submit" class="erp-btn-primary !text-xs">Mark as Paid</button>
    </form>
    @endif
</div></div>
@endsection
