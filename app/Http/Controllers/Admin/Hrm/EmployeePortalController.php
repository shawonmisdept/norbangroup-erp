<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Controllers\Controller;
use App\Models\Hrm\Employee;
use App\Services\EmployeePortalService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class EmployeePortalController extends Controller
{
    public function store(Request $request, Employee $employee)
    {
        $this->authorizeEmployeeAccess($request, $employee);

        $data = $request->validate([
            'portal_password' => ['nullable', 'confirmed', Password::defaults()],
        ]);

        $password = $data['portal_password'] ?? null;
        $result = EmployeePortalService::createForEmployee($employee, $password);

        return redirect()->route('admin.hrm.employees.show', $employee)
            ->with('success', 'Employee portal access enabled.')
            ->with('portal_password', $password ? null : $result['plainPassword']);
    }

    public function update(Request $request, Employee $employee)
    {
        $this->authorizeEmployeeAccess($request, $employee);

        $data = $request->validate([
            'portal_password' => ['required', 'confirmed', Password::defaults()],
        ]);

        EmployeePortalService::resetPassword($employee, $data['portal_password']);

        return redirect()->route('admin.hrm.employees.show', $employee)
            ->with('success', 'Employee portal password reset.');
    }

    public function destroy(Request $request, Employee $employee)
    {
        $this->authorizeEmployeeAccess($request, $employee);

        $employee->portalUser?->update(['is_active' => false]);

        return redirect()->route('admin.hrm.employees.show', $employee)
            ->with('success', 'Employee portal access disabled.');
    }

    private function authorizeEmployeeAccess(Request $request, Employee $employee): void
    {
        if (! $request->user()?->hasPermission('hrm.employees.manage')) {
            abort(403);
        }

        if ($request->user()?->factory_id && $request->user()->factory_id !== $employee->factory_id) {
            abort(403, 'You do not have access to this employee record.');
        }
    }
}
