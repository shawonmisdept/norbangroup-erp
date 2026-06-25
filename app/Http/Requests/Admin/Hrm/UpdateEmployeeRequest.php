<?php

namespace App\Http\Requests\Admin\Hrm;

use App\Models\Hrm\Employee;

class UpdateEmployeeRequest extends StoreEmployeeRequest
{
    public function rules(): array
    {
        /** @var Employee $employee */
        $employee = $this->route('employee');
        $rules = $this->baseRules($employee);

        unset($rules['enable_portal'], $rules['portal_password']);

        return $rules;
    }
}
