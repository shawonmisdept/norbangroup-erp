@extends('layouts.admin')

@section('title', 'Disciplinary #' . $record->id)

@section('breadcrumbs')
    <a href="{{ route('admin.hrm.discipline.index') }}" class="hover:text-brand">Disciplinary</a>
    <span>/</span>
    <span class="text-gray-800 font-medium">#{{ $record->id }}</span>
@endsection

@section('admin-content')
@php
    $badge = $record->status === 'open' ? 'bg-amber-100 text-amber-800' : 'bg-gray-100 text-gray-600';
@endphp

@include('partials.erp.page-header', [
    'title' => $record->employee->name . ' — ' . $record->typeLabel(),
    'subtitle' => 'Incident ' . $record->incident_date->format('d M Y'),
    'actions' => '<a href="' . route('admin.hrm.discipline.index') . '" class="erp-btn-secondary">← Back</a>'
        . ' <a href="' . route('admin.hrm.employees.show', $record->employee) . '" class="erp-btn-secondary !py-2 !px-4 text-xs">Employee Profile</a>',
])

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <div class="lg:col-span-2 erp-panel">
        <div class="erp-panel-head"><h2 class="text-xs font-semibold text-gray-700 uppercase">Incident</h2></div>
        <div class="erp-panel-body space-y-4 text-sm">
            <div>
                <p class="text-[10px] text-gray-400 uppercase">Description</p>
                <p class="text-gray-800 whitespace-pre-wrap">{{ $record->description }}</p>
            </div>
            @if($record->action_taken)
                <div>
                    <p class="text-[10px] text-gray-400 uppercase">Action Taken</p>
                    <p class="text-gray-800 whitespace-pre-wrap">{{ $record->action_taken }}</p>
                </div>
            @endif
            @if($record->action_type === 'suspension')
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-[10px] text-gray-400 uppercase">Suspension From</p>
                        <p>{{ $record->suspension_from?->format('d M Y') ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] text-gray-400 uppercase">Suspension To</p>
                        <p>{{ $record->suspension_to?->format('d M Y') ?? '—' }}</p>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <div class="space-y-3">
        <div class="erp-panel">
            <div class="erp-panel-body space-y-3 text-sm">
                <div>
                    <p class="text-[10px] text-gray-400 uppercase">Status</p>
                    <span class="erp-badge {{ $badge }}">{{ $record->statusLabel() }}</span>
                </div>
                <div>
                    <p class="text-[10px] text-gray-400 uppercase">Employee</p>
                    <p class="font-medium">{{ $record->employee->name }}</p>
                    <code class="text-xs text-gray-500">{{ $record->employee->employee_code }}</code>
                </div>
                <div>
                    <p class="text-[10px] text-gray-400 uppercase">Recorded</p>
                    <p>@portalDateTime($record->created_at)</p>
                    @if($record->recorder)
                        <p class="text-xs text-gray-500">By {{ $record->recorder->name }}</p>
                    @endif
                </div>
            </div>
        </div>

        @if($canManage && $record->status === 'open')
            <form method="POST" action="{{ route('admin.hrm.discipline.close', $record) }}" data-confirm="Close this disciplinary record?">
                @csrf
                <button type="submit" class="erp-btn-secondary w-full justify-center">Close Record</button>
            </form>
        @endif
    </div>
</div>
@endsection
