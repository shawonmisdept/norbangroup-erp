@extends('layouts.employee')

@section('title', 'Payslips')
@section('page-title', 'My Payslips')
@section('page-subtitle', $employee->employee_code)

@section('content')
<div class="space-y-5">

    @if($payslips->isNotEmpty())
        @php $latest = $payslips->first(); @endphp
        <a href="{{ route('employee.payslips.show', $latest) }}" class="block overflow-hidden rounded-2xl bg-gradient-to-br from-brand to-brand-light p-5 text-white shadow-lg active:scale-[0.99] transition">
            <p class="text-[10px] font-semibold uppercase tracking-widest text-white/50">Latest Payslip</p>
            <p class="mt-2 text-3xl font-bold tabular-nums">৳{{ number_format((float) $latest->net_pay, 2) }}</p>
            <p class="mt-1 text-sm text-white/70">{{ $latest->period->periodLabel() }} · {{ $latest->paidDays() }} paid days</p>
            <span class="mt-3 inline-block rounded-full bg-white/15 px-3 py-1 text-xs font-semibold">View details →</span>
        </a>
    @endif

    <div>
        <p class="emp-section-title">All Payslips</p>
        <div class="emp-card overflow-hidden">
            @forelse($payslips as $payslip)
                <a href="{{ route('employee.payslips.show', $payslip) }}"
                   class="emp-list-item {{ !$loop->last ? 'border-b border-gray-100' : '' }}">
                    <div class="flex h-11 w-11 shrink-0 flex-col items-center justify-center rounded-xl bg-emerald-50 text-emerald-700">
                        <span class="text-[10px] font-bold">{{ $payslip->period->start_date->format('M') }}</span>
                        <span class="text-xs font-bold">{{ $payslip->period->start_date->format('y') }}</span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold text-gray-900">{{ $payslip->period->periodLabel() }}</p>
                        <p class="text-xs text-gray-500">{{ $payslip->paidDays() }} paid days · {{ ucfirst($payslip->pay_type) }}</p>
                        @unless($payslip->period->isFrozen())
                            <p class="text-[10px] font-semibold text-amber-700">Provisional — subject to HR freeze</p>
                        @endunless
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-bold tabular-nums text-gray-900">৳{{ number_format((float) $payslip->net_pay, 0) }}</p>
                        <p class="text-[10px] font-semibold text-gray-500">View</p>
                    </div>
                </a>
                <div class="border-t border-gray-100 px-4 py-2 text-right">
                    <a href="{{ route('employee.payslips.print', ['payslip' => $payslip, 'download' => 1]) }}"
                       class="emp-btn-sm-secondary">Save PDF</a>
                </div>
            @empty
                <div class="px-6 py-12 text-center">
                    <div class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-2xl bg-gray-100 text-gray-400">
                        @include('employee.partials.tab-icon', ['icon' => 'wallet'])
                    </div>
                    <p class="text-sm font-medium text-gray-600">No payslips yet</p>
                    <p class="mt-1 text-xs text-gray-400">Payslips appear after payroll is calculated by HR.</p>
                </div>
            @endforelse
        </div>
        @if($payslips->hasPages())
            <div class="mt-3 text-center">{{ $payslips->links() }}</div>
        @endif
    </div>
</div>
@endsection
