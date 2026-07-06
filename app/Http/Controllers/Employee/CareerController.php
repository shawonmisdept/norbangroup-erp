<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Employee\Concerns\ResolvesPortalEmployee;
use App\Models\Hrm\EmployeePromotion;
use App\Models\Hrm\SalaryIncrementLog;
use Illuminate\Http\Request;

class CareerController extends Controller
{
    use ResolvesPortalEmployee;

    public function promotions(Request $request)
    {
        $employee = $this->portalEmployee($request);

        $promotions = EmployeePromotion::query()
            ->with(['fromDesignation', 'toDesignation', 'fromDepartment', 'toDepartment', 'fromSalaryGrade', 'toSalaryGrade'])
            ->where('employee_id', $employee->id)
            ->whereNotIn('status', ['cancelled'])
            ->latest('id')
            ->paginate(15);

        return view('employee.career.promotions', compact('employee', 'promotions'));
    }

    public function showPromotion(Request $request, EmployeePromotion $promotion)
    {
        $employee = $this->portalEmployee($request);
        $this->authorizeRecord($employee, $promotion);

        $promotion->load([
            'fromDesignation', 'toDesignation', 'fromDepartment', 'toDepartment',
            'fromSalaryGrade', 'toSalaryGrade', 'fromWorkerCategory', 'toWorkerCategory',
            'approvedByUser', 'rejectedByUser',
        ]);

        return view('employee.career.promotion-show', compact('employee', 'promotion'));
    }

    public function increments(Request $request)
    {
        $employee = $this->portalEmployee($request);

        $increments = SalaryIncrementLog::query()
            ->with(['rule', 'performanceReview.cycle'])
            ->where('employee_id', $employee->id)
            ->latest('applied_at')
            ->paginate(15);

        return view('employee.career.increments', compact('employee', 'increments'));
    }

    public function showIncrement(Request $request, SalaryIncrementLog $increment)
    {
        $employee = $this->portalEmployee($request);

        if ($increment->employee_id !== $employee->id) {
            abort(403);
        }

        $increment->load(['rule', 'performanceReview.cycle', 'appliedByUser']);

        return view('employee.career.increment-show', compact('employee', 'increment'));
    }

    private function authorizeRecord(\App\Models\Hrm\Employee $employee, EmployeePromotion $promotion): void
    {
        if ($promotion->employee_id !== $employee->id || $promotion->status === 'cancelled') {
            abort(404);
        }
    }
}
