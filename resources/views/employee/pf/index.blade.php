@extends('layouts.employee')

@section('title', 'Provident Fund')
@section('page-title', 'Provident Fund')

@section('content')
<div class="space-y-4 pb-4">
    @if($account)
        <div class="emp-card p-4 bg-emerald-50/80 ring-1 ring-emerald-100">
            <p class="text-[10px] font-semibold uppercase tracking-widest text-emerald-700/70">PF Balance</p>
            <p class="mt-1 text-2xl font-bold tabular-nums text-gray-900">৳{{ number_format((float) $account->balance, 2) }}</p>
            <div class="mt-3 grid grid-cols-2 gap-3 text-xs">
                <div>
                    <p class="text-gray-500">Your contribution rate</p>
                    <p class="font-medium">{{ number_format((float) $account->employee_rate_pct, 2) }}%</p>
                </div>
                <div>
                    <p class="text-gray-500">Employer rate</p>
                    <p class="font-medium">{{ number_format((float) $account->employer_rate_pct, 2) }}%</p>
                </div>
            </div>
        </div>

        @if($contributions->isNotEmpty())
            <div>
                <p class="emp-section-title">Recent Contributions</p>
                <div class="emp-card overflow-hidden divide-y divide-gray-100">
                    @foreach($contributions as $row)
                        <div class="p-4 flex items-center justify-between gap-3">
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ $row->year }}-{{ str_pad($row->month, 2, '0', STR_PAD_LEFT) }}</p>
                                <p class="text-xs text-gray-500">Base: ৳{{ number_format((float) $row->base_amount, 2) }}</p>
                            </div>
                            <div class="text-right text-xs">
                                <p class="tabular-nums font-medium text-emerald-700">+৳{{ number_format((float) $row->employee_amount, 2) }}</p>
                                <p class="text-gray-400">ER: ৳{{ number_format((float) $row->employer_amount, 2) }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    @else
        <div class="emp-card text-center py-10 text-sm text-gray-400">No PF account opened yet.</div>
    @endif
</div>
@endsection
