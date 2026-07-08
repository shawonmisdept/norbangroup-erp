@extends('layouts.admin')
@section('title', 'Vehicle Papers Status')
@section('admin-content')
@include('partials.erp.page-header', [
    'title' => 'Vehicle Papers Status',
    'subtitle' => 'Fitness, tax token, insurance & route permit expiry tracker',
    'actions' => collect([
        '<a href="' . route('admin.tms.vehicles.papers.print', request()->only(['factory_id', 'paper_status', 'search'])) . '" target="_blank" class="erp-btn-secondary">Print</a>',
        '<a href="' . route('admin.tms.vehicles.index') . '" class="erp-btn-secondary">← Vehicles</a>',
        auth()->user()->canManageTmsSubmodule('vehicles')
            ? '<a href="' . route('admin.tms.vehicles.create') . '" class="erp-btn-primary !py-2 !px-4 text-xs">Add Vehicle</a>'
            : null,
    ])->filter()->implode(' '),
])

<div x-data="vehiclePapersIndex()" x-init="init()">
    <form method="GET"
          action="{{ route('admin.tms.vehicles.papers') }}"
          x-ref="filterForm"
          @submit.prevent="load()"
          class="vehicle-papers-filters erp-panel">
        <div class="vehicle-papers-filters-grid">
            <div class="vehicle-papers-filter-field vehicle-papers-filter-search">
                <label class="erp-form-label" for="papers-search">Search</label>
                <input id="papers-search"
                       type="search"
                       name="search"
                       value="{{ $filters['search'] ?? '' }}"
                       placeholder="Vehicle name or registration no…"
                       class="erp-input"
                       autocomplete="off"
                       @input="debouncedSearch()">
            </div>
            @if($factories !== [])
                <div class="vehicle-papers-filter-field">
                    <label class="erp-form-label" for="papers-factory">Unit</label>
                    <select id="papers-factory" name="factory_id" class="erp-input" @change="onFilterChange()">
                        <option value="">All</option>
                        @foreach($factories as $id => $name)
                            <option value="{{ $id }}" @selected(($filters['factory_id'] ?? '') == $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="vehicle-papers-filter-field">
                <label class="erp-form-label" for="papers-status">Paper Status</label>
                <select id="papers-status" name="paper_status" class="erp-input" @change="onFilterChange()">
                    <option value="">All</option>
                    @foreach($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['paper_status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="vehicle-papers-filter-field">
                <label class="erp-form-label" for="papers-per-page">Show per page</label>
                <select id="papers-per-page" name="per_page" class="erp-input" @change="onFilterChange()">
                    @foreach($perPageOptions as $value => $label)
                        <option value="{{ $value }}" @selected((int) ($filters['per_page'] ?? 25) === (int) $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="vehicle-papers-filter-actions">
                <button type="submit" class="erp-btn-primary vehicle-papers-filter-btn" :disabled="loading">
                    <span x-show="!loading">Filter</span>
                    <span x-show="loading" x-cloak>Loading…</span>
                </button>
                @if(array_filter($filters ?? []))
                    <button type="button" class="erp-btn-secondary vehicle-papers-filter-btn" @click="resetFilters()">Reset</button>
                @endif
            </div>
        </div>
    </form>

    <div class="vehicle-papers-table-wrap erp-panel" x-ref="resultsWrap" :class="{ 'vehicle-papers-loading': loading }">
        <div class="vehicle-papers-results-host" x-ref="results">
            @include('admin.tms.vehicles.partials.papers-results')
        </div>
    </div>
</div>
@endsection
