<?php

namespace App\Http\Controllers\Admin\Hrm\Salary;

use App\Http\Controllers\Admin\Hrm\Concerns\ScopesHrmFactory;
use App\Http\Controllers\Controller;
use App\Models\Hrm\SalaryGrade;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GradeController extends Controller
{
    use ScopesHrmFactory;

    public function index(Request $request)
    {
        $query = SalaryGrade::query()->with('factory')->withCount('details')->latest('id');
        $this->scopeToUserFactory($query, $request);

        if ($request->filled('factory_id')) {
            $query->where('factory_id', $request->factory_id);
        }

        $grades = $query->paginate(25)->withQueryString();

        return view('admin.hrm.salary.grades.index', [
            'grades'    => $grades,
            'factories' => $this->factoryOptions($request),
            'filters'   => $request->only(['factory_id']),
        ]);
    }

    public function create(Request $request)
    {
        return view('admin.hrm.salary.grades.form', [
            'grade'     => new SalaryGrade(['is_active' => true]),
            'factories' => $this->factoryOptions($request),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateGrade($request);
        $this->authorizeFactoryAccess($request, (int) $validated['factory_id']);
        SalaryGrade::create($validated);

        return redirect()->route('admin.hrm.salary.grades.index')->with('success', 'Salary grade created.');
    }

    public function show(Request $request, SalaryGrade $grade)
    {
        $this->authorizeFactoryAccess($request, $grade->factory_id);

        $grade->load(['factory', 'details.salaryHead', 'details.percentageOfHead']);

        $data = [
            'grade'     => $grade,
            'canManage' => $request->user()?->canManageSalarySubmodule('grades') ?? false,
        ];

        if ($request->ajax() || $request->boolean('modal')) {
            return view('admin.hrm.salary.grades.show-modal', $data);
        }

        return redirect()->route('admin.hrm.salary.grades.index');
    }

    public function edit(Request $request, SalaryGrade $grade)
    {
        $this->authorizeFactoryAccess($request, $grade->factory_id);

        return view('admin.hrm.salary.grades.form', [
            'grade'     => $grade,
            'factories' => $this->factoryOptions($request),
        ]);
    }

    public function update(Request $request, SalaryGrade $grade)
    {
        $this->authorizeFactoryAccess($request, $grade->factory_id);
        $grade->update($this->validateGrade($request, $grade));

        return redirect()->route('admin.hrm.salary.grades.index')->with('success', 'Salary grade updated.');
    }

    public function destroy(Request $request, SalaryGrade $grade)
    {
        $this->authorizeFactoryAccess($request, $grade->factory_id);
        $grade->delete();

        return redirect()->route('admin.hrm.salary.grades.index')->with('success', 'Salary grade deleted.');
    }

    private function validateGrade(Request $request, ?SalaryGrade $grade = null): array
    {
        return $request->validate([
            'factory_id'  => ['required', 'exists:factories,id'],
            'code'        => [
                'required', 'string', 'max:20',
                Rule::unique('hrm_salary_grades', 'code')->where('factory_id', $request->input('factory_id'))->ignore($grade?->id),
            ],
            'name'        => ['required', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active'   => ['nullable', 'boolean'],
        ]) + ['is_active' => $request->boolean('is_active', true)];
    }
}
