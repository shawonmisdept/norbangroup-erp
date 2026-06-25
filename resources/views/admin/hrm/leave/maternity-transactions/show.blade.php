@extends('layouts.admin')
@section('title', 'Maternity Case')
@section('admin-content')
@include('partials.erp.page-header', ['title' => 'Maternity — '.$transaction->employee?->name, 'actions' => '<a href="'.route('admin.hrm.leave.maternity-transactions.index').'" class="erp-btn-secondary">← Back</a>'])
<div class="erp-panel max-w-2xl"><div class="erp-panel-body space-y-2 text-sm">
    <p><span class="text-gray-500">Employee:</span> {{ $transaction->employee?->employee_code }} — {{ $transaction->employee?->name }}</p>
    <p><span class="text-gray-500">Period:</span> {{ $transaction->start_date->format('d M Y') }} → {{ $transaction->end_date->format('d M Y') }}</p>
    <p><span class="text-gray-500">Paid / Unpaid weeks:</span> {{ $transaction->paid_weeks }} / {{ $transaction->unpaid_weeks }}</p>
    <p><span class="text-gray-500">Status:</span> {{ $transaction->statusLabel() }}</p>
    @if($transaction->leaveApplication)
    <p><span class="text-gray-500">Linked leave:</span> {{ $transaction->leaveApplication->leaveType?->name }} ({{ $transaction->leaveApplication->status }})</p>
    @endif
    @if($transaction->notes)<p class="text-xs text-gray-500">{{ $transaction->notes }}</p>@endif
</div></div>
@endsection
