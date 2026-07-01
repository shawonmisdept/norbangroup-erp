@extends('layouts.admin')

@section('title', $letter->reference_no)

@section('breadcrumbs')
    <a href="{{ route('admin.hrm.letters.index') }}" class="hover:text-brand">HR Letters</a>
    <span>/</span>
    <span class="text-gray-800 font-medium">{{ $letter->reference_no }}</span>
@endsection

@section('admin-content')
@include('partials.hrm.letter-document-styles')

@include('partials.erp.page-header', [
    'title' => $letter->typeLabel(),
    'subtitle' => $letter->employee->name . ' · ' . $letter->reference_no,
    'actions' => '<a href="' . route('admin.hrm.letters.index') . '" class="erp-btn-secondary">← Back</a>'
        . ' <a href="' . route('admin.hrm.letters.print', $letter) . '" target="_blank" class="erp-btn-primary !py-2 !px-4 text-xs">Print</a>'
        . ' <a href="' . route('admin.hrm.employees.show', $letter->employee) . '" class="erp-btn-secondary !py-2 !px-4 text-xs">Employee Profile</a>',
])

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <div class="lg:col-span-2">
        @include('partials.hrm.letter-document', [
            'content'      => $letter->content,
            'title'        => $letter->typeLabel(),
            'factoryName'  => $letter->employee->factory?->name,
            'referenceNo'  => $letter->reference_no,
            'issuedAt'     => $letter->issued_at,
        ])
    </div>
    <div class="erp-panel">
        <div class="erp-panel-head"><h2 class="text-xs font-semibold text-gray-700 uppercase">Details</h2></div>
        <div class="erp-panel-body space-y-3 text-sm">
            @if($letter->isVoided())
                <div class="p-3 bg-red-50 border border-red-100 rounded-lg">
                    <p class="text-xs font-semibold text-red-700 uppercase">Voided</p>
                    <p class="text-xs text-red-800 mt-1">{{ $letter->voided_at->format('d M Y H:i') }}</p>
                    @if($letter->void_reason)
                        <p class="text-xs text-red-700 mt-1">{{ $letter->void_reason }}</p>
                    @endif
                </div>
            @endif
            <div>
                <p class="text-[10px] text-gray-400 uppercase">Employee</p>
                <p class="font-medium">{{ $letter->employee->name }}</p>
                <code class="text-xs text-gray-500">{{ $letter->employee->employee_code }}</code>
            </div>
            <div>
                <p class="text-[10px] text-gray-400 uppercase">Factory</p>
                <p>{{ $letter->employee->factory?->name ?? '—' }}</p>
            </div>
            <div>
                <p class="text-[10px] text-gray-400 uppercase">Template</p>
                <p>{{ $letter->template?->name ?? '—' }}</p>
            </div>
            <div>
                <p class="text-[10px] text-gray-400 uppercase">Issued</p>
                <p>{{ $letter->issued_at->format('d M Y H:i') }}</p>
                @if($letter->issuer)
                    <p class="text-xs text-gray-500">By {{ $letter->issuer->name }}</p>
                @endif
            </div>
            @if($letter->notes)
                <div>
                    <p class="text-[10px] text-gray-400 uppercase">Notes</p>
                    <p class="text-gray-700">{{ $letter->notes }}</p>
                </div>
            @endif
            @if($letter->reissuedFrom)
                <div>
                    <p class="text-[10px] text-gray-400 uppercase">Reissued From</p>
                    <a href="{{ route('admin.hrm.letters.show', $letter->reissuedFrom) }}" class="text-brand text-xs">{{ $letter->reissuedFrom->reference_no }}</a>
                </div>
            @endif

            @if($canManage && ! $letter->isVoided())
                <div class="pt-3 border-t border-erp-border">
                    <form method="POST" action="{{ route('admin.hrm.letters.void', $letter) }}" class="space-y-2" data-confirm="Void this letter? It will no longer be valid.">
                        @csrf
                        <label class="erp-form-label">Void Reason (optional)</label>
                        <textarea name="void_reason" rows="2" class="erp-input !text-xs"></textarea>
                        <button type="submit" class="erp-btn-secondary w-full !text-red-600">Void Letter</button>
                    </form>
                </div>
            @elseif($canManage && $letter->isVoided())
                <div class="pt-3 border-t border-erp-border">
                    <form method="POST" action="{{ route('admin.hrm.letters.reissue', $letter) }}" data-confirm="Reissue this letter with a new reference number?">
                        @csrf
                        <button type="submit" class="erp-btn-primary w-full">Reissue Letter</button>
                    </form>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
