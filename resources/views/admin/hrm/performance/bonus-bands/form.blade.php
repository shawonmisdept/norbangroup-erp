@extends('layouts.admin')

@section('title', 'Edit Bonus Bands')

@section('breadcrumbs')
    <a href="{{ route('admin.hrm.performance.bonus-bands.index', ['factory_id' => $factoryId]) }}" class="hover:text-brand">Bonus Bands</a>
    <span>/</span>
    <span class="text-gray-800 font-medium">Edit</span>
@endsection

@section('admin-content')
@include('partials.erp.page-header', [
    'title' => 'Edit Bonus Bands',
    'subtitle' => $factoryName,
])

<form method="POST" action="{{ route('admin.hrm.performance.bonus-bands.update') }}" class="max-w-3xl space-y-4">
    @csrf @method('PUT')
    <input type="hidden" name="factory_id" value="{{ $factoryId }}">

    <div class="erp-panel">
        <div class="erp-panel-head"><h2 class="text-xs font-semibold text-gray-700 uppercase">Score Bands</h2></div>
        <div class="erp-panel-body space-y-3">
            @foreach(old('bands', $bands) as $i => $band)
                <div class="grid grid-cols-12 gap-2 items-end border-b border-gray-50 pb-3">
                    <div class="col-span-3">
                        @if($i === 0)<label class="erp-form-label">Band Name</label>@endif
                        <input type="text" name="bands[{{ $i }}][name]" value="{{ $band['name'] ?? '' }}" class="erp-input !text-xs" required>
                    </div>
                    <div class="col-span-2">
                        @if($i === 0)<label class="erp-form-label">Min %</label>@endif
                        <input type="number" name="bands[{{ $i }}][min_score]" value="{{ $band['min_score'] ?? 0 }}" class="erp-input !text-xs" min="0" max="100" step="0.01" required>
                    </div>
                    <div class="col-span-2">
                        @if($i === 0)<label class="erp-form-label">Max %</label>@endif
                        <input type="number" name="bands[{{ $i }}][max_score]" value="{{ $band['max_score'] ?? 100 }}" class="erp-input !text-xs" min="0" max="100" step="0.01" required>
                    </div>
                    <div class="col-span-2">
                        @if($i === 0)<label class="erp-form-label">Bonus %</label>@endif
                        <input type="number" name="bands[{{ $i }}][bonus_percent]" value="{{ $band['bonus_percent'] ?? 0 }}" class="erp-input !text-xs" min="0" max="200" step="0.01" required>
                    </div>
                    <div class="col-span-2 flex items-center pt-4">
                        <label class="flex items-center gap-1 text-xs"><input type="checkbox" name="bands[{{ $i }}][is_active]" value="1" {{ ($band['is_active'] ?? true) ? 'checked' : '' }}> Active</label>
                    </div>
                    <input type="hidden" name="bands[{{ $i }}][sort_order]" value="{{ $band['sort_order'] ?? $i }}">
                </div>
            @endforeach
        </div>
    </div>

    <div class="flex gap-2">
        <button type="submit" class="erp-btn-primary">Save Bands</button>
        <a href="{{ route('admin.hrm.performance.bonus-bands.index', ['factory_id' => $factoryId]) }}" class="erp-btn-secondary">Cancel</a>
    </div>
</form>

@if($canManage ?? true)
    <form method="POST" action="{{ route('admin.hrm.performance.bonus-bands.reset') }}" class="mt-4" data-confirm="Reset to default bands?">
        @csrf
        <input type="hidden" name="factory_id" value="{{ $factoryId }}">
        <button type="submit" class="erp-btn-secondary !text-xs text-red-600">Reset to Defaults</button>
    </form>
@endif
@endsection
