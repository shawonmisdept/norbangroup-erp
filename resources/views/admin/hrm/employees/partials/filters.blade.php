<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3">
    @if($showUnitFilter)
        <div>
            <label class="erp-form-label" for="filter-factory">Unit</label>
            <select id="filter-factory" name="factory_id" class="erp-input !text-xs w-full"
                    @change="onFactoryChange()">
                <option value="">All units</option>
                @foreach($factories as $id => $name)
                    <option value="{{ $id }}" {{ (string) ($filters['factory_id'] ?? '') === (string) $id ? 'selected' : '' }}>{{ $name }}</option>
                @endforeach
            </select>
        </div>
    @elseif(count($factories) === 1)
        <input type="hidden" name="factory_id" value="{{ array_key_first($factories) }}">
    @endif

    <div>
        <label class="erp-form-label" for="filter-department">Department</label>
        <select id="filter-department" name="department_id" class="erp-input !text-xs w-full"
                @change="onDepartmentChange()">
            <option value="">All</option>
            @foreach($departments as $department)
                <option value="{{ $department->id }}" {{ (string) ($filters['department_id'] ?? '') === (string) $department->id ? 'selected' : '' }}>
                    {{ $department->displayLabel() }}
                </option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="erp-form-label" for="filter-designation">Designation</label>
        <select id="filter-designation" name="designation_id" class="erp-input !text-xs w-full"
                @change="onSelectChange()">
            <option value="">All</option>
            @foreach($designations as $designation)
                <option value="{{ $designation->id }}" {{ (string) ($filters['designation_id'] ?? '') === (string) $designation->id ? 'selected' : '' }}>
                    {{ $designation->displayLabel() }}
                </option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="erp-form-label" for="filter-building">Building</label>
        <select id="filter-building" name="building_id" class="erp-input !text-xs w-full"
                @change="onSelectChange()">
            <option value="">All</option>
            @foreach($buildings as $building)
                <option value="{{ $building->id }}" {{ (string) ($filters['building_id'] ?? '') === (string) $building->id ? 'selected' : '' }}>
                    {{ \App\Support\RelationDisplay::label($building, 'name', 'factory.name') }}
                </option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="erp-form-label" for="filter-worker-category">Worker Category</label>
        <select id="filter-worker-category" name="worker_category_id" class="erp-input !text-xs w-full"
                @change="onSelectChange()">
            <option value="">All</option>
            @foreach($workerCategories as $category)
                <option value="{{ $category->id }}" {{ (string) ($filters['worker_category_id'] ?? '') === (string) $category->id ? 'selected' : '' }}>
                    {{ $category->name }}
                </option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="erp-form-label" for="filter-employment-type">Employment Type</label>
        <select id="filter-employment-type" name="employment_type_id" class="erp-input !text-xs w-full"
                @change="onSelectChange()">
            <option value="">All</option>
            @foreach($employmentTypes as $type)
                <option value="{{ $type->id }}" {{ (string) ($filters['employment_type_id'] ?? '') === (string) $type->id ? 'selected' : '' }}>
                    {{ $type->name }}
                </option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="erp-form-label" for="filter-shift">Shift Plan</label>
        <select id="filter-shift" name="shift_id" class="erp-input !text-xs w-full"
                @change="onSelectChange()">
            <option value="">All</option>
            @foreach($shifts as $shift)
                <option value="{{ $shift->id }}" {{ (string) ($filters['shift_id'] ?? '') === (string) $shift->id ? 'selected' : '' }}>
                    {{ \App\Support\RelationDisplay::label($shift, 'name', 'factory.name') }}
                </option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="erp-form-label" for="filter-status">Status</label>
        <select id="filter-status" name="status" class="erp-input !text-xs w-full"
                @change="onSelectChange()">
            <option value="">All</option>
            @foreach($statuses as $value => $label)
                <option value="{{ $value }}" {{ ($filters['status'] ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>
</div>

<div class="flex flex-wrap items-end justify-between gap-3 border-t border-erp-border pt-4">
    <div class="text-[11px] text-gray-500">
        Showing {{ $employees->total() }} employee(s)
    </div>
    <div class="flex flex-wrap items-end gap-3 flex-1 justify-end min-w-[240px]">
        <div class="w-full sm:w-72">
            <label class="erp-form-label" for="filter-search">Search</label>
            <input id="filter-search"
                   type="search"
                   name="search"
                   value="{{ $filters['search'] ?? '' }}"
                   placeholder="Employee ID, name, NID, phone…"
                   class="erp-input !text-xs w-full"
                   @input="debouncedSearch()">
        </div>
        @if($hasActiveFilters)
            <button type="button" class="erp-btn-secondary" @click="clearFilters()">Clear filters</button>
        @endif
    </div>
</div>
