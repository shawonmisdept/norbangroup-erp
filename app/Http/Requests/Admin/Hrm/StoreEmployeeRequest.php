<?php

namespace App\Http\Requests\Admin\Hrm;

use App\Http\Requests\Concerns\EnforcesUserFactoryScope;
use App\Models\Hrm\AttendancePolicy;
use App\Models\Hrm\Employee;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class StoreEmployeeRequest extends FormRequest
{
    use EnforcesUserFactoryScope;

    public function authorize(): bool
    {
        return $this->user()?->hasPermission('hrm.employees.manage') ?? false;
    }

    public function rules(): array
    {
        return $this->baseRules();
    }

    protected function prepareForValidation(): void
    {
        $nullableIds = [
            'department_id', 'designation_id', 'worker_category_id', 'employment_type_id',
            'building_id', 'floor_id', 'line_id', 'shift_id', 'reporting_to_id',
        ];

        $merge = [];

        foreach ($nullableIds as $field) {
            if ($this->has($field) && $this->input($field) === '') {
                $merge[$field] = null;
            }
        }

        if ($merge !== []) {
            $this->merge($merge);
        }

        $this->mergeUserFactoryScope();

        if ($this->has('weekend_days') && is_array($this->input('weekend_days'))) {
            $this->merge([
                'weekend_days' => array_values(array_unique(array_map('intval', $this->input('weekend_days')))),
            ]);
        }
    }

    protected function baseRules(?Employee $employee = null): array
    {
        $factoryId = $this->input('factory_id');

        return [
            'factory_id'               => $this->userFactoryIdRule(),
            'department_id'            => ['nullable', Rule::exists('departments', 'id')->where('factory_id', $factoryId)],
            'designation_id'           => ['nullable', Rule::exists('designations', 'id')],
            'worker_category_id'       => ['nullable', Rule::exists('hrm_worker_categories', 'id')],
            'employment_type_id'       => ['nullable', Rule::exists('hrm_employment_types', 'id')],
            'building_id'              => ['nullable', Rule::exists('hrm_buildings', 'id')->where('factory_id', $factoryId)],
            'floor_id'                 => ['nullable', Rule::exists('hrm_floors', 'id')->where('factory_id', $factoryId)],
            'line_id'                  => ['nullable', Rule::exists('hrm_lines', 'id')->where('factory_id', $factoryId)],
            'shift_id'                 => ['nullable', Rule::exists('hrm_shifts', 'id')->where('factory_id', $factoryId)],
            'reporting_to_id'          => [
                'nullable',
                Rule::exists('hrm_employees', 'id')->where('factory_id', $factoryId),
                Rule::notIn(array_filter([$employee?->id])),
            ],
            'employee_code'            => ['required', 'string', 'max:30', Rule::unique('hrm_employees', 'employee_code')->ignore($employee?->id)],
            'name'                     => ['required', 'string', 'max:255'],
            'name_bangla'              => ['nullable', 'string', 'max:255'],
            'gender'                   => ['nullable', Rule::in(array_keys(config('hrm.employee_options.genders', [])))],
            'date_of_birth'            => ['nullable', 'date', 'before:today'],
            'blood_group'              => ['nullable', Rule::in(array_keys(config('hrm.employee_options.blood_groups', [])))],
            'nid_number'               => ['nullable', 'string', 'max:30', Rule::unique('hrm_employees', 'nid_number')->ignore($employee?->id)],
            'birth_certificate_no'     => ['nullable', 'string', 'max:30'],
            'phone'                    => ['nullable', 'string', 'max:20'],
            'email'                    => ['nullable', 'email', 'max:255'],
            'present_address'          => ['nullable', 'string', 'max:2000'],
            'permanent_address'        => ['nullable', 'string', 'max:2000'],
            'emergency_contact_name'   => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone'  => ['nullable', 'string', 'max:20'],
            'emergency_contact_relation' => ['nullable', 'string', 'max:100'],
            'nominee_name'             => ['nullable', 'string', 'max:255'],
            'nominee_relation'         => ['nullable', 'string', 'max:100'],
            'nominee_nid'              => ['nullable', 'string', 'max:30'],
            'nid_document'             => ['nullable', 'file', 'mimes:jpg,jpeg,png,gif,webp,pdf', 'max:5120'],
            'birth_certificate_document' => ['nullable', 'file', 'mimes:jpg,jpeg,png,gif,webp,pdf', 'max:5120'],
            'nominee_nid_document'     => ['nullable', 'file', 'mimes:jpg,jpeg,png,gif,webp,pdf', 'max:5120'],
            'nominee_photo'            => ['nullable', 'image', 'max:5120'],
            'biometric_user_id'        => ['nullable', 'string', 'max:50'],
            'joining_date'             => ['nullable', 'date'],
            'confirmation_date'        => ['nullable', 'date', 'after_or_equal:joining_date'],
            'probation_end_date'       => ['nullable', 'date', 'after_or_equal:joining_date'],
            'contract_end_date'        => ['nullable', 'date', 'after_or_equal:joining_date'],
            'status'                   => ['required', Rule::in(array_keys(Employee::STATUSES))],
            'weekend_days'             => ['nullable', 'array'],
            'weekend_days.*'           => ['integer', 'min:0', 'max:6'],
            'weekend_ot_allowed'       => ['nullable', 'boolean'],
            'half_day_pay_ratio'       => ['nullable', 'numeric', 'min:0.01', 'max:1'],
            'notes'                    => ['nullable', 'string', 'max:5000'],
            'photo'                    => ['nullable', 'image', 'max:5120'],
            'enable_portal'            => ['nullable', 'boolean'],
            'portal_password'          => ['nullable', 'confirmed', Password::defaults()],
            'education_history'        => ['nullable', 'array'],
            'education_history.*.degree' => ['nullable', 'string', 'max:255'],
            'education_history.*.institution' => ['nullable', 'string', 'max:255'],
            'education_history.*.board_or_university' => ['nullable', 'string', 'max:255'],
            'education_history.*.passing_year' => ['nullable', 'string', 'max:10'],
            'education_history.*.result' => ['nullable', 'string', 'max:100'],
            'employment_history'       => ['nullable', 'array'],
            'employment_history.*.company_name' => ['nullable', 'string', 'max:255'],
            'employment_history.*.designation' => ['nullable', 'string', 'max:255'],
            'employment_history.*.department' => ['nullable', 'string', 'max:255'],
            'employment_history.*.joining_date' => ['nullable', 'date'],
            'employment_history.*.leaving_date' => ['nullable', 'date'],
            'employment_history.*.reason_for_leaving' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            /** @var Employee|null $employee */
            $employee = $this->route('employee');

            if ($employee && in_array($this->input('status'), Employee::SEPARATED_STATUSES, true) && $employee->status !== $this->input('status')) {
                $validator->errors()->add(
                    'status',
                    'Resigned / Terminated status must be set through the Separation module (HRM → Separations).'
                );
            }

            $dob = $this->input('date_of_birth');
            $factoryId = (int) $this->input('factory_id');

            if (! $dob || ! $factoryId) {
                return;
            }

            $policy = AttendancePolicy::forFactory($factoryId);
            $minAge = (int) ($policy->min_employment_age ?? 18);
            $age = Carbon::parse($dob)->age;

            if ($age < $minAge) {
                $validator->errors()->add(
                    'date_of_birth',
                    "Employee must be at least {$minAge} years old (current age: {$age}). Bangladesh child labour prevention."
                );
            }
        });
    }
}
