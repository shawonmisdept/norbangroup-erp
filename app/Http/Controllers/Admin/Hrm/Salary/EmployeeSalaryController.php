<?php

namespace App\Http\Controllers\Admin\Hrm\Salary;

use App\Http\Controllers\Admin\Hrm\Concerns\ScopesHrmFactory;
use App\Http\Controllers\Controller;
use App\Models\Hrm\Employee;
use App\Models\Hrm\SalaryGrade;
use App\Models\Hrm\SalaryGradeDetail;
use App\Models\Hrm\SalaryHead;
use App\Models\Hrm\SalaryStructure;
use App\Services\Hrm\SalaryFormulaCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EmployeeSalaryController extends Controller
{
    use ScopesHrmFactory;

    public function index(Request $request)
    {
        $grades = SalaryGrade::query()
            ->when($request->user()?->factory_id, fn ($q) => $q->where('factory_id', $request->user()->factory_id))
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'factory_id', 'code', 'name']);

        $selectedEmployee = null;
        $structure = null;

        if ($request->filled('employee_id')) {
            $selectedEmployee = Employee::with('salaryStructure')->find($request->employee_id);
            $structure = $selectedEmployee?->salaryStructure;
        }

        $selectedGradeId = $request->integer('salary_grade_id')
            ?: $structure?->salary_grade_id
            ?: $grades->first()?->id;
        $selectedGrade = $grades->firstWhere('id', $selectedGradeId);

        $employees = collect();
        $gradeDetails = collect();
        $heads = collect();

        if ($selectedGrade) {
            $gradeDetails = SalaryGradeDetail::query()
                ->where('salary_grade_id', $selectedGrade->id)
                ->with(['salaryHead', 'percentageOfHead'])
                ->get()
                ->sortBy(fn ($d) => $d->salaryHead?->sort_order ?? 999);

            $heads = SalaryHead::query()
                ->where('factory_id', $selectedGrade->factory_id)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get();

            $employeeQuery = Employee::query()
                ->where('factory_id', $selectedGrade->factory_id)
                ->whereIn('status', ['active', 'probation'])
                ->whereHas('salaryStructure', fn ($q) => $q->where('salary_grade_id', $selectedGrade->id))
                ->with('salaryStructure')
                ->orderBy('employee_code');

            if ($request->filled('search')) {
                $search = $request->search;
                $employeeQuery->where(fn ($q) => $q
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('employee_code', 'like', "%{$search}%"));
            }

            $employees = $employeeQuery->get(['id', 'employee_code', 'name', 'factory_id']);

            if ($request->filled('employee_id')) {
                $selectedEmployee = $employees->firstWhere('id', (int) $request->employee_id)
                    ?? Employee::with('salaryStructure')->find($request->employee_id);
                $structure = $selectedEmployee?->salaryStructure;
            }
        }

        return view('admin.hrm.salary.employee-salary.index', [
            'grades'            => $grades,
            'selectedGrade'     => $selectedGrade,
            'selectedGradeId'   => (string) ($selectedGradeId ?? ''),
            'employees'         => $employees,
            'selectedEmployee'  => $selectedEmployee,
            'structure'         => $structure,
            'gradeDetails'      => $gradeDetails,
            'heads'             => $heads,
            'filterSearch'      => (string) $request->input('search', ''),
            'canManage'         => $request->user()?->hasPermission('hrm.salary.manage') ?? false,
        ]);
    }

    public function calculate(Request $request, SalaryFormulaCalculator $calculator): JsonResponse
    {
        $validated = $request->validate([
            'salary_grade_id' => ['required', 'exists:hrm_salary_grades,id'],
            'gross_salary'    => ['required', 'numeric', 'min:0'],
            'overrides'       => ['nullable', 'array'],
            'overrides.*'     => ['nullable', 'numeric', 'min:0'],
        ]);

        $grade = SalaryGrade::findOrFail($validated['salary_grade_id']);
        $this->authorizeFactoryAccess($request, $grade->factory_id);

        $amounts = $calculator->calculate(
            $grade,
            (float) $validated['gross_salary'],
            $validated['overrides'] ?? []
        );

        $details = SalaryGradeDetail::query()
            ->where('salary_grade_id', $grade->id)
            ->with('salaryHead')
            ->get()
            ->sortBy(fn ($d) => $d->salaryHead?->sort_order ?? 999);

        $rows = $details->map(fn (SalaryGradeDetail $detail) => [
            'code'       => strtoupper($detail->salaryHead?->code ?? ''),
            'name'       => $detail->salaryHead?->name ?? '',
            'type'       => $detail->detail_type,
            'is_fixed'   => $detail->is_fixed,
            'amount'     => $amounts[strtoupper($detail->salaryHead?->code ?? '')] ?? 0,
        ])->values();

        return response()->json([
            'amounts' => $amounts,
            'rows'    => $rows,
            'gross'   => $amounts['GROSS'] ?? (float) $validated['gross_salary'],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateAssignment($request);
        $employee = Employee::findOrFail($validated['employee_id']);
        $grade = SalaryGrade::findOrFail($validated['salary_grade_id']);

        $this->authorizeFactoryAccess($request, $employee->factory_id);

        $calculator = app(SalaryFormulaCalculator::class);
        $amounts = $calculator->calculate($grade, (float) $validated['gross_salary'], $validated['overrides'] ?? []);

        $structure = SalaryStructure::updateOrCreate(
            ['employee_id' => $employee->id],
            [
                'factory_id'      => $employee->factory_id,
                'salary_grade_id' => $grade->id,
                'gross_salary'    => (float) $validated['gross_salary'],
                'payment_method'  => $validated['payment_method'],
                'bank_account'    => $validated['bank_account'] ?? null,
                'effective_from'  => $validated['effective_from'] ?? null,
                'is_active'       => true,
            ]
        );

        $structure->syncLegacyFromHeads($amounts);
        $structure->save();

        return redirect()
            ->route('admin.hrm.salary.employee-salary.index', [
                'salary_grade_id' => $grade->id,
                'employee_id'     => $employee->id,
            ])
            ->with('success', 'Employee salary saved.');
    }

    public function create(Request $request)
    {
        return redirect()->route('admin.hrm.salary.employee-salary.index', $request->query());
    }

    public function edit(Request $request, SalaryStructure $employeeSalary)
    {
        $employeeSalary->load('employee');

        return redirect()->route('admin.hrm.salary.employee-salary.index', [
            'salary_grade_id' => $employeeSalary->salary_grade_id,
            'employee_id'     => $employeeSalary->employee_id,
        ]);
    }

    public function update(Request $request, SalaryStructure $employeeSalary)
    {
        return $this->store($request);
    }

    private function validateAssignment(Request $request): array
    {
        return $request->validate([
            'employee_id'     => ['required', 'exists:hrm_employees,id'],
            'salary_grade_id' => ['required', 'exists:hrm_salary_grades,id'],
            'gross_salary'    => ['required', 'numeric', 'min:0'],
            'overrides'       => ['nullable', 'array'],
            'overrides.*'     => ['nullable', 'numeric', 'min:0'],
            'payment_method'  => ['required', Rule::in(array_keys(SalaryStructure::PAYMENT_METHODS))],
            'bank_account'    => ['nullable', 'string', 'max:40'],
            'effective_from'  => ['nullable', 'date'],
        ]);
    }
}
