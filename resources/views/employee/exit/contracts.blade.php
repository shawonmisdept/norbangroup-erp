@extends('layouts.employee')

@section('title', 'Contract Renewals')
@section('page-title', 'Contract Renewals')
@section('page-subtitle', $employee->contract_end_date?->format('d M Y') . ' current end')
@section('back', route('employee.exit'))

@section('content')
<div class="space-y-5">
    <div class="emp-card-padded">
        <p class="text-[10px] font-semibold uppercase tracking-wide text-gray-400">Current Contract End</p>
        <p class="mt-1 text-lg font-bold text-gray-900">{{ $employee->contract_end_date->format('d M Y') }}</p>
        @if($employee->pendingContractRenewal)
            <p class="mt-2 text-xs text-amber-700">Renewal pending HR approval to {{ $employee->pendingContractRenewal->new_end_date->format('d M Y') }}</p>
        @endif
    </div>

    <div>
        <p class="emp-section-title">Renewal History</p>
        <div class="emp-card overflow-hidden">
            @forelse($renewals as $renewal)
                <div class="p-4 {{ !$loop->last ? 'border-b border-gray-100' : '' }}">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-gray-900">{{ $renewal->previous_end_date?->format('d M Y') ?? '—' }} → {{ $renewal->new_end_date->format('d M Y') }}</p>
                            @if($renewal->notes)
                                <p class="mt-1 text-xs text-gray-500">{{ $renewal->notes }}</p>
                            @endif
                            @if($renewal->rejection_reason)
                                <p class="mt-1 text-[10px] text-red-600">{{ $renewal->rejection_reason }}</p>
                            @endif
                        </div>
                        <span class="emp-badge {{ match($renewal->status) {
                            'approved' => 'bg-emerald-100 text-emerald-700',
                            'rejected' => 'bg-red-100 text-red-700',
                            default => 'bg-amber-100 text-amber-700',
                        } }}">{{ $renewal->statusLabel() }}</span>
                    </div>
                </div>
            @empty
                <p class="px-4 py-8 text-center text-sm text-gray-400">No contract renewals recorded yet.</p>
            @endforelse
        </div>
        @if($renewals->hasPages())
            <div class="mt-3 text-center">{{ $renewals->links() }}</div>
        @endif
    </div>
</div>
@endsection
