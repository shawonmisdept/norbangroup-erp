@extends('layouts.admin')
@section('title', 'Bill For Posting')
@section('admin-content')
@include('partials.erp.page-header', [
    'title' => 'Bill For Posting',
    'subtitle' => 'Vendor-wise maintenance summary for accounts',
    'actions' => '<a href="' . route('admin.tms.maintenance.index') . '" class="erp-btn-secondary">← Maintenance</a>',
])

<form method="GET" class="erp-panel p-4 mb-4 grid grid-cols-2 md:grid-cols-5 gap-3 items-end">
    @if($factories !== [])
        <div>
            <label class="erp-label">Unit</label>
            <select name="factory_id" class="erp-input">
                <option value="">All</option>
                @foreach($factories as $id => $name)
                    <option value="{{ $id }}" @selected(($filters['factory_id'] ?? '') == $id)>{{ $name }}</option>
                @endforeach
            </select>
        </div>
    @endif

    <div>
        <label class="erp-label">Workshop / Vendor</label>
        <input type="text" name="workshop" list="workshop-list" class="erp-input" value="{{ $filters['workshop'] ?? '' }}" required placeholder="JK Motors">
        <datalist id="workshop-list">
            @foreach($workshops as $w)
                <option value="{{ $w }}">
            @endforeach
        </datalist>
    </div>

    <div>
        <label class="erp-label">From</label>
        <input type="date" name="from" class="erp-input" value="{{ $filters['from'] ?? '' }}" required>
    </div>

    <div>
        <label class="erp-label">To</label>
        <input type="date" name="to" class="erp-input" value="{{ $filters['to'] ?? '' }}" required>
    </div>

    <div>
        <button type="submit" class="erp-btn-primary w-full">Generate</button>
    </div>
</form>

@if($report)
    <div class="flex flex-wrap gap-2 mb-4">
        <a href="{{ route('admin.tms.maintenance.posting.print', request()->query()) }}" target="_blank" class="erp-btn-secondary">Print</a>
        <a href="{{ route('admin.tms.maintenance.posting.export', request()->query()) }}" class="erp-btn-secondary">Export CSV</a>
    </div>

    @include('admin.tms.maintenance.partials.posting-table', ['report' => $report, 'interactive' => true])
@endif
@endsection
