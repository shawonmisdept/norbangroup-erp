@php
    use Illuminate\Support\ViewErrorBag;

    $employee = $employee ?? null;
    $wizardSteps = config('hrm.employee_wizard_steps', []);
    $initialStep = array_key_first($wizardSteps) ?: 'setup';

    /** @var ViewErrorBag $errors */
    foreach (config('hrm.employee_tab_fields', []) as $stepKey => $fields) {
        foreach ($fields as $field) {
            if ($errors->has($field) || collect($errors->keys())->contains(fn ($key) => str_starts_with($key, $field . '.'))) {
                $initialStep = $stepKey;
                break 2;
            }
        }
    }

    $defaultEducationRow = ['degree' => '', 'institution' => '', 'board_or_university' => '', 'passing_year' => '', 'result' => ''];
    $recruitmentPrefill = $recruitmentPrefill ?? session('recruitment_prefill', []);
    $educationRows = old('education_history');
    if ($educationRows === null && $employee) {
        $educationRows = $employee->educationHistories->map(fn ($row) => [
            'degree'              => $row->degree ?? '',
            'institution'         => $row->institution ?? '',
            'board_or_university' => $row->board_or_university ?? '',
            'passing_year'        => $row->passing_year ?? '',
            'result'              => $row->result ?? '',
        ])->all();
    }
    if (empty($educationRows)) {
        $educationRows = [$defaultEducationRow];
    }
    if ($educationRows === [$defaultEducationRow] && ! empty($recruitmentPrefill['education_history'])) {
        $educationRows = $recruitmentPrefill['education_history'];
    }

    $defaultEmploymentRow = ['company_name' => '', 'designation' => '', 'department' => '', 'joining_date' => '', 'leaving_date' => '', 'reason_for_leaving' => ''];
    $employmentRows = old('employment_history');
    if ($employmentRows === null && $employee) {
        $employmentRows = $employee->employmentHistories->map(fn ($row) => [
            'company_name'       => $row->company_name ?? '',
            'designation'        => $row->designation ?? '',
            'department'         => $row->department ?? '',
            'joining_date'       => optional($row->joining_date)->format('Y-m-d') ?? '',
            'leaving_date'       => optional($row->leaving_date)->format('Y-m-d') ?? '',
            'reason_for_leaving' => $row->reason_for_leaving ?? '',
        ])->all();
    }
    if (empty($employmentRows)) {
        $employmentRows = [$defaultEmploymentRow];
    }
    if ($employmentRows === [$defaultEmploymentRow] && ! empty($recruitmentPrefill['employment_history'])) {
        $employmentRows = $recruitmentPrefill['employment_history'];
    }

    $employeeFormConfig = [
        'tab'               => $initialStep,
        'steps'             => array_keys($wizardSteps),
        'factoryId'         => (string) old('factory_id', $defaultFactoryId ?? $employee->factory_id ?? ''),
        'departmentId'      => (string) old('department_id', $employee->department_id ?? ''),
        'designationId'     => (string) old('designation_id', $employee->designation_id ?? ''),
        'buildingId'        => (string) old('building_id', $employee->building_id ?? ''),
        'floorId'           => (string) old('floor_id', $employee->floor_id ?? ''),
        'shiftId'           => (string) old('shift_id', $employee->shift_id ?? ''),
        'lineId'            => (string) old('line_id', $employee->line_id ?? ''),
        'departments'       => $departments,
        'designations'      => $designations,
        'buildings'         => $buildings,
        'floors'            => $floors,
        'lines'             => $lines,
        'shifts'            => $shifts,
        'photoPreview'      => null,
        'displayName'       => old('name', $employee->name ?? ''),
        'displayEmployeeId' => old('employee_code', $employee->employee_code ?? ''),
        'displayPhone'      => old('phone', $employee->phone ?? ''),
        'status'            => old('status', $employee->status ?? 'active'),
        'educationRows'     => $educationRows,
        'employmentRows'    => $employmentRows,
        'nomineePhotoPreview' => null,
    ];
@endphp

<form method="POST" action="{{ $formAction }}" enctype="multipart/form-data" novalidate
      x-data="employeeForm(@js($employeeFormConfig))"
      @submit="prepareSubmit($event)">
    @csrf
    @if(($formMethod ?? 'POST') !== 'POST')
        @method($formMethod)
    @endif

    {{-- Hidden fields synced from Alpine so all wizard steps submit reliably --}}
    <input type="hidden" name="name" :value="displayName">
    <input type="hidden" name="phone" :value="displayPhone">
    <input type="hidden" name="employee_code" :value="displayEmployeeId">
    <input type="hidden" name="factory_id" :value="factoryId">
    <input type="hidden" name="department_id" :value="departmentId">
    <input type="hidden" name="designation_id" :value="designationId">
    <input type="hidden" name="shift_id" :value="shiftId">
    <input type="hidden" name="building_id" :value="buildingId">
    <input type="hidden" name="floor_id" :value="floorId">
    <input type="hidden" name="line_id" :value="lineId">

    <div x-show="submitError" x-cloak class="mb-4 bg-red-50 border border-red-200 rounded-sm p-3 text-xs text-red-700" x-text="submitError"></div>

    {{-- Top summary header --}}
    <div class="erp-employee-wizard-header">
        <div class="flex flex-col lg:flex-row lg:items-center gap-4">
            <div class="flex items-center gap-4 flex-1 min-w-0">
                <template x-if="photoPreview">
                    <img :src="photoPreview" alt="Preview" class="w-14 h-14 rounded-full object-cover border-2 border-white shadow-sm shrink-0">
                </template>
                <template x-if="! photoPreview">
                    <div>
                        @if($employee && $employee->photoUrl())
                            @include('partials.employee-avatar', ['employee' => $employee, 'size' => '56', 'round' => true])
                        @else
                            <div class="w-14 h-14 rounded-full flex items-center justify-center bg-brand/10 text-brand text-sm font-semibold border-2 border-white shadow-sm shrink-0"
                                 x-text="(displayName || '?').split(' ').filter(Boolean).slice(0, 2).map(p => p[0]?.toUpperCase() ?? '').join('') || '?'"></div>
                        @endif
                    </div>
                </template>
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-gray-900 truncate" x-text="displayName || '{{ $employee ? $employee->name : 'New Employee' }}'"></p>
                    <p class="text-xs text-gray-500 truncate">{{ $employee?->email ?? old('email') ?: '—' }}</p>
                </div>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4 flex-1 text-center lg:text-left">
                <div>
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Employee ID</p>
                    <p class="text-xs font-mono font-medium text-gray-800 mt-0.5" x-text="displayEmployeeId || '—'">—</p>
                </div>
                <div>
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Department</p>
                    <p class="text-xs text-gray-800 mt-0.5" x-text="selectedDepartmentName()">—</p>
                </div>
                <div>
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Phone</p>
                    <p class="text-xs text-gray-800 mt-0.5" x-text="displayPhone || '—'">—</p>
                </div>
                <div>
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Designation</p>
                    <p class="text-xs text-gray-800 mt-0.5" x-text="selectedDesignationName()">—</p>
                </div>
                <div>
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Duty Schedule</p>
                    <p class="text-xs text-gray-800 mt-0.5" x-text="selectedShiftName()">—</p>
                </div>
            </div>
        </div>
    </div>

    <div class="erp-employee-wizard-layout">

        {{-- Sidebar --}}
        <aside class="erp-employee-wizard-sidebar">
            <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wide text-center mb-3">Complete Profile</p>
            <div class="erp-employee-wizard-progress">
                <svg viewBox="0 0 36 36">
                    <path class="text-gray-200" stroke="currentColor" stroke-width="3" fill="none"
                          d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                    <path class="text-brand" stroke="currentColor" stroke-width="3" fill="none" stroke-linecap="round"
                          :stroke-dasharray="progressPercent() + ', 100'"
                          d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                </svg>
                <div class="absolute inset-0 flex items-center justify-center">
                    <span class="text-xs font-bold text-brand" x-text="progressPercent() + '%'"></span>
                </div>
            </div>
            <nav class="space-y-1">
                @foreach($wizardSteps as $key => $label)
                    <button type="button" @click="goToStep('{{ $key }}')"
                            class="erp-employee-wizard-step"
                            :class="step === '{{ $key }}' ? 'erp-employee-wizard-step-active' : 'erp-employee-wizard-step-idle'">
                        <span class="erp-employee-wizard-step-dot"
                              :class="step === '{{ $key }}' ? 'border-brand bg-brand' : 'border-gray-300'"></span>
                        <span class="flex-1">{{ $label }}</span>
                    </button>
                @endforeach
            </nav>
        </aside>

        {{-- Main form panel --}}
        <div class="erp-panel erp-panel-form">
            <div class="erp-panel-head">
                <h2 class="text-sm font-semibold text-gray-800" x-text="{
                    setup: 'Employee Setup',
                    official: 'Official Setup',
                    personal: 'Personal Info',
                    contact: 'Contact & Address',
                    family: 'Emergency & Nominee',
                    education: 'Educational History',
                    employment: 'Employment History',
                }[step] || 'Employee'"></h2>
            </div>

            <div class="erp-panel-body space-y-4">

                {{-- Step 1: Employee Setup --}}
                <div x-show="step === 'setup'" data-wizard-step="setup" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="erp-form-label">Employee Name <span class="text-red-500">*</span></label>
                        <input type="text" x-model="displayName" data-step-required="setup"
                               placeholder="Enter here" class="erp-input !text-xs">
                        @error('name')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="erp-form-label">Email Address</label>
                        <input type="email" name="email" value="{{ old('email', $employee->email ?? '') }}"
                               placeholder="Enter here" class="erp-input !text-xs">
                        @error('email')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="erp-form-label">Phone</label>
                        <input type="text" x-model="displayPhone"
                               placeholder="Enter here" class="erp-input !text-xs">
                        @error('phone')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="erp-form-label">Employee ID <span class="text-red-500">*</span></label>
                        <input type="text" x-model="displayEmployeeId" data-step-required="setup"
                               placeholder="e.g. M-E123" class="erp-input !text-xs font-mono"
                               @if($employee) readonly @endif>
                        @error('employee_code')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                        <p class="text-[11px] text-gray-400 mt-1">Used for employee portal login</p>
                    </div>
                    <div>
                        <label class="erp-form-label">Factory / Unit <span class="text-red-500">*</span></label>
                        <select data-step-required="setup" class="erp-input !text-xs" x-model="factoryId" @change="onFactoryChange()">
                            <option value="">Choose one</option>
                            @foreach($factories as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                        @error('factory_id')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="erp-form-label">Joining Date</label>
                        <input type="date" name="joining_date"
                               value="{{ old('joining_date', optional($employee?->joining_date)->format('Y-m-d')) }}"
                               class="erp-input !text-xs">
                        @error('joining_date')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="erp-form-label">Department</label>
                        <select class="erp-input !text-xs" x-model="departmentId" @change="designationId = ''; syncTomSelects()">
                            <option value="">Choose one</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                            @endforeach
                        </select>
                        @error('department_id')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="erp-form-label">Designation</label>
                        <select class="erp-input !text-xs" x-model="designationId" data-searchable="true">
                            <option value="">Choose one</option>
                            @foreach($designations as $des)
                                <option value="{{ $des->id }}">{{ $des->name }}</option>
                            @endforeach
                        </select>
                        @error('designation_id')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="erp-form-label">Duty Schedule (Shift)</label>
                        <select class="erp-input !text-xs" x-model="shiftId">
                            <option value="">Choose one</option>
                            @foreach($shifts as $shift)
                                <option value="{{ $shift->id }}">{{ $shift->name }}</option>
                            @endforeach
                        </select>
                        @error('shift_id')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="erp-form-label">Worker Category</label>
                        <select name="worker_category_id" class="erp-input !text-xs">
                            <option value="">Choose one</option>
                            @foreach($workerCategories as $cat)
                                <option value="{{ $cat->id }}" {{ (string) old('worker_category_id', $employee->worker_category_id ?? '') === (string) $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                        @error('worker_category_id')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="erp-form-label">Employment Type</label>
                        <select name="employment_type_id" class="erp-input !text-xs">
                            <option value="">Choose one</option>
                            @foreach($employmentTypes as $type)
                                <option value="{{ $type->id }}" {{ (string) old('employment_type_id', $employee->employment_type_id ?? '') === (string) $type->id ? 'selected' : '' }}>{{ $type->name }}</option>
                            @endforeach
                        </select>
                        @error('employment_type_id')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="erp-form-label">Reporting Person</label>
                        <select name="reporting_to_id" class="erp-input !text-xs">
                            <option value="">Choose one</option>
                            @foreach($reportingCandidates as $candidate)
                                <option value="{{ $candidate->id }}" {{ (string) old('reporting_to_id', $employee->reporting_to_id ?? '') === (string) $candidate->id ? 'selected' : '' }}>
                                    {{ $candidate->name }} ({{ $candidate->employee_code }})
                                </option>
                            @endforeach
                        </select>
                        <p class="text-[10px] text-gray-400 mt-1">First approver for leave applications</p>
                        @error('reporting_to_id')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="erp-form-label">Status <span class="text-red-500">*</span></label>
                        <select name="status" x-model="status" data-step-required="setup" data-searchable="false" class="erp-input !text-xs">
                            @foreach($statuses as $value => $label)
                                <option value="{{ $value }}" {{ old('status', $employee->status ?? 'active') === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('status')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div class="md:col-span-2 pt-2 border-t border-erp-border">
                        <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wide mb-2">Work Schedule</p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="md:col-span-2">
                                <label class="erp-form-label">Weekend Days</label>
                                <div class="flex flex-wrap gap-3 mt-1">
                                    @php $selectedWeekends = old('weekend_days', $employee->weekend_days ?? [0]); @endphp
                                    @foreach($weekdayLabels as $dayNum => $dayLabel)
                                        <label class="flex items-center gap-1.5 text-xs text-gray-700">
                                            <input type="checkbox" name="weekend_days[]" value="{{ $dayNum }}"
                                                {{ in_array($dayNum, array_map('intval', (array) $selectedWeekends), true) ? 'checked' : '' }}
                                                class="rounded border-gray-300 text-brand">
                                            {{ $dayLabel }}
                                        </label>
                                    @endforeach
                                </div>
                                @error('weekend_days')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="flex items-center gap-2 text-xs text-gray-700 mt-1">
                                    <input type="hidden" name="weekend_ot_allowed" value="0">
                                    <input type="checkbox" name="weekend_ot_allowed" value="1"
                                        {{ old('weekend_ot_allowed', $employee->weekend_ot_allowed ?? false) ? 'checked' : '' }}
                                        class="rounded border-gray-300 text-brand">
                                    Weekend / holiday OT allowed
                                </label>
                                <p class="text-[10px] text-gray-400 mt-1">If enabled, weekend punches count as OT only</p>
                            </div>
                            <div>
                                <label class="erp-form-label">Half Day Pay Ratio (override)</label>
                                <input type="number" step="0.01" min="0.01" max="1" name="half_day_pay_ratio"
                                    value="{{ old('half_day_pay_ratio', $employee->half_day_pay_ratio ?? '') }}"
                                    placeholder="Default 0.50"
                                    class="erp-input !text-xs">
                                <p class="text-[10px] text-gray-400 mt-1">Leave blank to use factory policy default</p>
                                @error('half_day_pay_ratio')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </div>
                    <div class="md:col-span-2 pt-2 border-t border-erp-border">
                        <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wide mb-2">Line Placement</p>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="erp-form-label">Building</label>
                                <select class="erp-input !text-xs" x-model="buildingId" @change="onBuildingChange()">
                                    <option value="">Choose one</option>
                                    @foreach($buildings as $building)
                                        <option value="{{ $building->id }}">{{ $building->name }}</option>
                                    @endforeach
                                </select>
                                @error('building_id')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="erp-form-label">Floor</label>
                                <select class="erp-input !text-xs" x-model="floorId">
                                    <option value="">Choose one</option>
                                    @foreach($floors as $floor)
                                        <option value="{{ $floor->id }}">{{ $floor->name }}</option>
                                    @endforeach
                                </select>
                                @error('floor_id')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="erp-form-label">Line / Section</label>
                                <select class="erp-input !text-xs" x-model="lineId">
                                    <option value="">Choose one</option>
                                    @foreach($lines as $line)
                                        <option value="{{ $line->id }}">{{ $line->name }}</option>
                                    @endforeach
                                </select>
                                @error('line_id')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="erp-form-label">Confirmation Date</label>
                        <input type="date" name="confirmation_date"
                               value="{{ old('confirmation_date', optional($employee?->confirmation_date)->format('Y-m-d')) }}"
                               class="erp-input !text-xs">
                        @error('confirmation_date')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="erp-form-label">Probation End Date</label>
                        <input type="date" name="probation_end_date"
                               value="{{ old('probation_end_date', optional($employee?->probation_end_date)->format('Y-m-d')) }}"
                               class="erp-input !text-xs">
                        @error('probation_end_date')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="erp-form-label">Contract End Date</label>
                        <input type="date" name="contract_end_date"
                               value="{{ old('contract_end_date', optional($employee?->contract_end_date)->format('Y-m-d')) }}"
                               class="erp-input !text-xs">
                        @error('contract_end_date')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div class="md:col-span-2">
                        <label class="erp-form-label">Notes</label>
                        <textarea name="notes" rows="2" class="erp-input !text-xs">{{ old('notes', $employee->notes ?? '') }}</textarea>
                        @error('notes')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>

                    @if(! $employee)
                        <div class="md:col-span-2 pt-4 border-t border-erp-border space-y-3">
                            <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wide">Employee Portal</p>
                            <label class="flex items-center gap-2 text-xs text-gray-700">
                                <input type="hidden" name="enable_portal" value="0">
                                <input type="checkbox" name="enable_portal" value="1"
                                       {{ old('enable_portal', true) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-brand focus:ring-brand">
                                Create portal login (Employee ID + password)
                            </label>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="erp-form-label">Portal password (optional)</label>
                                    <input type="password" name="portal_password" class="erp-input !text-xs"
                                           placeholder="Leave blank to auto-generate">
                                    @error('portal_password')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label class="erp-form-label">Confirm portal password</label>
                                    <input type="password" name="portal_password_confirmation" class="erp-input !text-xs">
                                </div>
                            </div>
                            <p class="text-xs text-amber-600">Default password: 12345678 (if auto-generated)</p>
                        </div>
                    @endif
                </div>

                {{-- Step 2: Official Setup --}}
                <div x-show="step === 'official'" data-wizard-step="official" class="space-y-4">
                    <div class="flex items-start gap-4 pb-4 border-b border-erp-border">
                        <div>
                            <template x-if="photoPreview">
                                <img :src="photoPreview" alt="Preview" class="w-[120px] h-[120px] rounded-full object-cover border border-gray-200">
                            </template>
                            <template x-if="! photoPreview">
                                <div>
                                    @if($employee)
                                        @include('partials.employee-avatar', ['employee' => $employee, 'size' => '80', 'round' => true])
                                    @else
                                        <div class="w-20 h-20 rounded-full flex items-center justify-center border border-dashed border-gray-300 bg-gray-50 text-xs text-gray-400">Photo</div>
                                    @endif
                                </div>
                            </template>
                        </div>
                        <div class="flex-1">
                            <label class="erp-form-label">Employee Photo</label>
                            <input type="file" name="photo" accept="image/jpeg,image/png,image/gif,image/webp"
                                   @change="onPhotoSelected($event)"
                                   class="w-full text-xs text-gray-600 file:mr-2 file:py-1.5 file:px-3 file:rounded-sm file:border-0 file:text-xs file:font-medium file:bg-brand file:text-white">
                            <p class="text-[11px] text-gray-400 mt-1">Square image recommended. Max 5 MB.</p>
                            @error('photo')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="erp-form-label">NID Number</label>
                            <input type="text" name="nid_number" value="{{ old('nid_number', $employee->nid_number ?? '') }}"
                                   placeholder="Enter here" class="erp-input !text-xs">
                            @error('nid_number')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            @include('partials.employee-document-upload', [
                                'name' => 'nid_document',
                                'label' => 'NID Copy Upload',
                                'currentUrl' => $employee?->documentUrl('nid_document'),
                            ])
                        </div>
                        <div>
                            <label class="erp-form-label">Birth Certificate No.</label>
                            <input type="text" name="birth_certificate_no" value="{{ old('birth_certificate_no', $employee->birth_certificate_no ?? '') }}"
                                   placeholder="Enter here" class="erp-input !text-xs">
                            @error('birth_certificate_no')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            @include('partials.employee-document-upload', [
                                'name' => 'birth_certificate_document',
                                'label' => 'Birth Certificate Upload',
                                'currentUrl' => $employee?->documentUrl('birth_certificate_document'),
                            ])
                        </div>
                        <div class="md:col-span-2">
                            <label class="erp-form-label">ZKTeco Biometric User ID</label>
                            <input type="text" name="biometric_user_id" value="{{ old('biometric_user_id', $employee->biometric_user_id ?? '') }}"
                                   placeholder="Device user ID for ADMS sync" class="erp-input !text-xs">
                            @error('biometric_user_id')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>

                {{-- Step 3: Personal Info --}}
                <div x-show="step === 'personal'" data-wizard-step="personal" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="erp-form-label">Name (Bangla)</label>
                        <input type="text" name="name_bangla" value="{{ old('name_bangla', $employee->name_bangla ?? '') }}"
                               placeholder="Enter here" class="erp-input !text-xs">
                        @error('name_bangla')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="erp-form-label">Gender</label>
                        <select name="gender" class="erp-input !text-xs">
                            <option value="">Choose one</option>
                            @foreach($genders as $value => $label)
                                <option value="{{ $value }}" {{ old('gender', $employee->gender ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('gender')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="erp-form-label">Date of Birth</label>
                        <input type="date" name="date_of_birth" value="{{ old('date_of_birth', optional($employee?->date_of_birth)->format('Y-m-d')) }}"
                               class="erp-input !text-xs">
                        @error('date_of_birth')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="erp-form-label">Blood Group</label>
                        <select name="blood_group" class="erp-input !text-xs">
                            <option value="">Choose one</option>
                            @foreach($bloodGroups as $value => $label)
                                <option value="{{ $value }}" {{ old('blood_group', $employee->blood_group ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('blood_group')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>

                {{-- Step 4: Contact --}}
                <div x-show="step === 'contact'" data-wizard-step="contact" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="erp-form-label">Present Address</label>
                        <textarea name="present_address" rows="3" class="erp-input !text-xs"
                                  placeholder="Enter here">{{ old('present_address', $employee->present_address ?? '') }}</textarea>
                        @error('present_address')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div class="md:col-span-2">
                        <label class="erp-form-label">Permanent Address</label>
                        <textarea name="permanent_address" rows="3" class="erp-input !text-xs"
                                  placeholder="Enter here">{{ old('permanent_address', $employee->permanent_address ?? '') }}</textarea>
                        @error('permanent_address')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>

                {{-- Step 5: Emergency & Nominee --}}
                <div x-show="step === 'family'" data-wizard-step="family" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="md:col-span-3">
                        <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wide">Emergency Contact</p>
                    </div>
                    <div>
                        <label class="erp-form-label">Contact Name</label>
                        <input type="text" name="emergency_contact_name" value="{{ old('emergency_contact_name', $employee->emergency_contact_name ?? '') }}"
                               class="erp-input !text-xs">
                        @error('emergency_contact_name')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="erp-form-label">Phone</label>
                        <input type="text" name="emergency_contact_phone" value="{{ old('emergency_contact_phone', $employee->emergency_contact_phone ?? '') }}"
                               class="erp-input !text-xs">
                        @error('emergency_contact_phone')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="erp-form-label">Relation</label>
                        <input type="text" name="emergency_contact_relation" value="{{ old('emergency_contact_relation', $employee->emergency_contact_relation ?? '') }}"
                               class="erp-input !text-xs">
                        @error('emergency_contact_relation')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div class="md:col-span-3 pt-2 border-t border-erp-border">
                        <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wide mb-1">Nominee</p>
                    </div>
                    <div>
                        <label class="erp-form-label">Nominee Name</label>
                        <input type="text" name="nominee_name" value="{{ old('nominee_name', $employee->nominee_name ?? '') }}"
                               class="erp-input !text-xs">
                        @error('nominee_name')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="erp-form-label">Relation</label>
                        <input type="text" name="nominee_relation" value="{{ old('nominee_relation', $employee->nominee_relation ?? '') }}"
                               class="erp-input !text-xs">
                        @error('nominee_relation')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="erp-form-label">Nominee NID</label>
                        <input type="text" name="nominee_nid" value="{{ old('nominee_nid', $employee->nominee_nid ?? '') }}"
                               class="erp-input !text-xs">
                        @error('nominee_nid')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        @include('partials.employee-document-upload', [
                            'name' => 'nominee_nid_document',
                            'label' => 'Nominee ID Upload',
                            'currentUrl' => $employee?->documentUrl('nominee_nid_document'),
                        ])
                    </div>
                    <div class="md:col-span-3">
                        <label class="erp-form-label">Nominee Photo</label>
                        <div class="flex items-start gap-4">
                            <template x-if="nomineePhotoPreview">
                                <img :src="nomineePhotoPreview" alt="Nominee preview" class="w-20 h-20 rounded-full object-cover border border-gray-200">
                            </template>
                            @if($employee && $employee->nominee_photo)
                                <template x-if="! nomineePhotoPreview">
                                    <img src="{{ $employee->documentUrl('nominee_photo') }}" alt="Nominee photo" class="w-20 h-20 rounded-full object-cover border border-gray-200">
                                </template>
                            @endif
                            <div class="flex-1">
                                <input type="file" name="nominee_photo" accept="image/jpeg,image/png,image/gif,image/webp"
                                       @change="onNomineePhotoSelected($event)"
                                       class="w-full text-xs text-gray-600 file:mr-2 file:py-1.5 file:px-3 file:rounded-sm file:border-0 file:text-xs file:font-medium file:bg-brand file:text-white">
                                <p class="text-[11px] text-gray-400 mt-1">JPG, PNG, GIF or WEBP. Max 5 MB.</p>
                                @error('nominee_photo')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Step 6: Educational History --}}
                <div x-show="step === 'education'" data-wizard-step="education" class="space-y-4">
                    <template x-for="(row, index) in educationRows" :key="'edu-' + index">
                        <div class="border border-erp-border rounded-sm p-4 space-y-3">
                            <div class="flex items-center justify-between">
                                <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wide" x-text="'Education #' + (index + 1)"></p>
                                <button type="button" @click="removeEducationRow(index)" x-show="educationRows.length > 1"
                                        class="text-xs text-red-500 hover:text-red-700">Remove</button>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="erp-form-label">Degree / Qualification</label>
                                    <input type="text" :name="'education_history[' + index + '][degree]'" x-model="row.degree"
                                           class="erp-input !text-xs" placeholder="e.g. SSC, HSC, BSc">
                                </div>
                                <div>
                                    <label class="erp-form-label">Institution</label>
                                    <input type="text" :name="'education_history[' + index + '][institution]'" x-model="row.institution"
                                           class="erp-input !text-xs" placeholder="School / College / University">
                                </div>
                                <div>
                                    <label class="erp-form-label">Board / University</label>
                                    <input type="text" :name="'education_history[' + index + '][board_or_university]'" x-model="row.board_or_university"
                                           class="erp-input !text-xs">
                                </div>
                                <div>
                                    <label class="erp-form-label">Passing Year</label>
                                    <input type="text" :name="'education_history[' + index + '][passing_year]'" x-model="row.passing_year"
                                           class="erp-input !text-xs" placeholder="e.g. 2020">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="erp-form-label">Result / GPA</label>
                                    <input type="text" :name="'education_history[' + index + '][result]'" x-model="row.result"
                                           class="erp-input !text-xs" placeholder="e.g. 4.50 / A+">
                                </div>
                            </div>
                        </div>
                    </template>
                    <button type="button" @click="addEducationRow()" class="erp-btn-secondary !py-1.5 !px-3 text-xs">+ Add Education</button>
                    @error('education_history')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>

                {{-- Step 7: Employment History --}}
                <div x-show="step === 'employment'" data-wizard-step="employment" class="space-y-4">
                    <template x-for="(row, index) in employmentRows" :key="'emp-' + index">
                        <div class="border border-erp-border rounded-sm p-4 space-y-3">
                            <div class="flex items-center justify-between">
                                <p class="text-[11px] font-semibold text-gray-500 uppercase tracking-wide" x-text="'Employment #' + (index + 1)"></p>
                                <button type="button" @click="removeEmploymentRow(index)" x-show="employmentRows.length > 1"
                                        class="text-xs text-red-500 hover:text-red-700">Remove</button>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="erp-form-label">Company Name</label>
                                    <input type="text" :name="'employment_history[' + index + '][company_name]'" x-model="row.company_name"
                                           class="erp-input !text-xs">
                                </div>
                                <div>
                                    <label class="erp-form-label">Designation</label>
                                    <input type="text" :name="'employment_history[' + index + '][designation]'" x-model="row.designation"
                                           class="erp-input !text-xs">
                                </div>
                                <div>
                                    <label class="erp-form-label">Department</label>
                                    <input type="text" :name="'employment_history[' + index + '][department]'" x-model="row.department"
                                           class="erp-input !text-xs">
                                </div>
                                <div>
                                    <label class="erp-form-label">Joining Date</label>
                                    <input type="date" :name="'employment_history[' + index + '][joining_date]'" x-model="row.joining_date"
                                           class="erp-input !text-xs">
                                </div>
                                <div>
                                    <label class="erp-form-label">Leaving Date</label>
                                    <input type="date" :name="'employment_history[' + index + '][leaving_date]'" x-model="row.leaving_date"
                                           class="erp-input !text-xs">
                                </div>
                                <div>
                                    <label class="erp-form-label">Reason for Leaving</label>
                                    <input type="text" :name="'employment_history[' + index + '][reason_for_leaving]'" x-model="row.reason_for_leaving"
                                           class="erp-input !text-xs">
                                </div>
                            </div>
                        </div>
                    </template>
                    <button type="button" @click="addEmploymentRow()" class="erp-btn-secondary !py-1.5 !px-3 text-xs">+ Add Employment</button>
                    @error('employment_history')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                </div>

            </div>

            {{-- Footer navigation --}}
            <div class="px-4 py-3 border-t border-erp-border bg-gray-50/50 flex items-center justify-between gap-3">
                <button type="button" @click="prevStep()" x-show="! isFirstStep()" x-cloak
                        class="erp-btn-secondary !py-1.5 !px-4 text-xs">← Previous</button>
                <div x-show="isFirstStep()" class="w-px"></div>

                <div class="flex items-center gap-2 ml-auto">
                    <button type="button" @click="nextStep()" x-show="! isLastStep()" x-cloak
                            class="erp-btn-primary !py-1.5 !px-5 text-xs">Next Step →</button>
                    <button type="submit" x-show="isLastStep()" x-cloak
                            class="erp-btn-primary !py-1.5 !px-5 text-xs">
                        {{ $employee ? 'Save Changes' : 'Enroll Employee' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>
