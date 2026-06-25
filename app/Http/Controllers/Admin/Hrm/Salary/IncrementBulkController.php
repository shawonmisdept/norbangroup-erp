<?php

namespace App\Http\Controllers\Admin\Hrm\Salary;

use App\Http\Controllers\Admin\Hrm\Concerns\ScopesHrmFactory;
use App\Http\Controllers\Controller;
use App\Models\Hrm\Employee;
use App\Models\Hrm\SalaryGrade;
use App\Models\Hrm\SalaryIncrementLog;
use App\Models\Hrm\SalaryIncrementRule;
use App\Services\Hrm\SalaryIncrementService;
use Illuminate\Http\Request;

class IncrementBulkController extends Controller
{
    use ScopesHrmFactory;

    public function index(Request $request, SalaryIncrementService $service)
    {
        $factoryId = $request->integer('factory_id') ?: $request->user()?->factory_id;
        $gradeId = $request->integer('salary_grade_id') ?: null;
        $ruleId = $request->integer('rule_id') ?: null;

        $grades = SalaryGrade::query()
            ->when($factoryId, fn ($q) => $q->where('factory_id', $factoryId))
            ->when(! $factoryId, fn ($q) => $this->scopeToUserFactory($q, $request))
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'factory_id', 'code', 'name']);

        $selectedGrade = $gradeId ? $grades->firstWhere('id', $gradeId) : $grades->first();
        $selectedGradeId = $selectedGrade?->id;

        $rules = SalaryIncrementRule::query()
            ->where('is_active', true)
            ->when($selectedGrade, fn ($q) => $q->where('factory_id', $selectedGrade->factory_id))
            ->when($selectedGradeId, fn ($q) => $q->where(fn ($inner) => $inner
                ->whereNull('salary_grade_id')
                ->orWhere('salary_grade_id', $selectedGradeId)))
            ->orderBy('name')
            ->get();

        $selectedRule = $ruleId ? $rules->firstWhere('id', $ruleId) : $rules->first();

        $employees = collect();

        if ($selectedGrade) {
            $employees = Employee::query()
                ->where('factory_id', $selectedGrade->factory_id)
                ->whereIn('status', ['active', 'probation'])
                ->whereHas('salaryStructure', fn ($q) => $q
                    ->where('pay_type', 'salary')
                    ->where('is_active', true)
                    ->where('salary_grade_id', $selectedGrade->id))
                ->with('salaryStructure')
                ->orderBy('employee_code')
                ->get()
                ->map(fn (Employee $emp) => [
                    'employee'  => $emp,
                    'eligible'  => $selectedRule ? $service->eligibleForRule($emp, $selectedRule) : false,
                    'tenure'    => $service->employeeTenureMonths($emp),
                    'gross'     => (float) ($emp->salaryStructure?->gross_salary ?? 0),
                    'new_gross' => $selectedRule ? $selectedRule->applyToGross((float) ($emp->salaryStructure?->gross_salary ?? 0)) : 0,
                ]);
        }

        $recentLogs = SalaryIncrementLog::query()
            ->with(['employee', 'rule', 'appliedByUser'])
            ->when($selectedGrade, fn ($q) => $q->where('factory_id', $selectedGrade->factory_id))
            ->latest('applied_at')
            ->limit(15)
            ->get();

        return view('admin.hrm.salary.increment-bulk.index', [
            'factories'       => $this->factoryOptions($request),
            'grades'          => $grades,
            'selectedGrade'   => $selectedGrade,
            'selectedGradeId' => (string) ($selectedGradeId ?? ''),
            'rules'           => $rules,
            'selectedRule'    => $selectedRule,
            'selectedRuleId'  => (string) ($selectedRule?->id ?? ''),
            'employees'       => $employees,
            'recentLogs'      => $recentLogs,
            'filterFactoryId' => (string) ($factoryId ?? ''),
            'canManage'       => $request->user()?->hasPermission('hrm.salary.manage') ?? false,
        ]);
    }

    public function apply(Request $request, SalaryIncrementService $service)
    {
        $validated = $request->validate([
            'rule_id'      => ['required', 'exists:hrm_salary_increment_rules,id'],
            'employee_ids' => ['required', 'array', 'min:1'],
            'employee_ids.*' => ['integer', 'exists:hrm_employees,id'],
        ]);

        $rule = SalaryIncrementRule::findOrFail($validated['rule_id']);
        $this->authorizeFactoryAccess($request, $rule->factory_id);

        $employees = Employee::query()
            ->whereIn('id', $validated['employee_ids'])
            ->where('factory_id', $rule->factory_id)
            ->get();

        $result = $service->applyRule($rule, $employees, $request->user());

        $message = "Increment applied to {$result['applied']} employee(s). Skipped: {$result['skipped']}.";

        if ($result['errors'] !== []) {
            return redirect()->back()
                ->with('success', $message)
                ->with('error', implode("\n", array_slice($result['errors'], 0, 8)));
        }

        return redirect()->back()->with('success', $message);
    }
}
