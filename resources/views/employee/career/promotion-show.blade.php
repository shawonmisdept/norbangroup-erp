@extends('layouts.employee')

@section('title', $promotion->movementTypeLabel())
@section('page-title', $promotion->movementTypeLabel())
@section('page-subtitle', $promotion->effective_date->format('d M Y'))
@section('back', route('employee.career.promotions'))

@section('content')
<div class="space-y-4">
    <div class="emp-card-padded">
        <span class="emp-badge {{ match($promotion->status) {
            'approved' => 'bg-emerald-100 text-emerald-700',
            'rejected' => 'bg-red-100 text-red-700',
            default => 'bg-amber-100 text-amber-700',
        } }}">{{ $promotion->statusLabel() }}</span>
        @if($promotion->rejection_reason)
            <p class="mt-3 text-xs text-red-600">{{ $promotion->rejection_reason }}</p>
        @endif
    </div>

    <div class="emp-card overflow-hidden divide-y divide-gray-100 text-sm">
        @foreach([
            'Designation' => [$promotion->fromDesignation?->name, $promotion->toDesignation?->name],
            'Department' => [$promotion->fromDepartment?->name, $promotion->toDepartment?->name],
            'Salary grade' => [$promotion->fromSalaryGrade?->name, $promotion->toSalaryGrade?->name],
            'Gross salary' => [
                $promotion->from_gross_salary !== null ? '৳' . number_format((float) $promotion->from_gross_salary, 2) : null,
                $promotion->to_gross_salary !== null ? '৳' . number_format((float) $promotion->to_gross_salary, 2) : null,
            ],
        ] as $label => [$from, $to])
            @if($from || $to)
                <div class="px-4 py-3">
                    <p class="text-[10px] uppercase text-gray-400">{{ $label }}</p>
                    <p class="mt-0.5 font-semibold text-gray-900">{{ $from ?? '—' }} → {{ $to ?? '—' }}</p>
                </div>
            @endif
        @endforeach
    </div>

    @if($promotion->reason)
        <div class="emp-card-padded text-sm text-gray-600">{{ $promotion->reason }}</div>
    @endif
</div>
@endsection
