<?php

namespace App\Http\Controllers\Admin\Hrm\Salary;

use App\Http\Controllers\Admin\Hrm\Concerns\ScopesHrmFactory;
use App\Http\Controllers\Controller;
use App\Models\Hrm\SalaryGrade;
use App\Models\Hrm\SalaryGradeDetail;
use App\Models\Hrm\SalaryHead;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GradeDetailController extends Controller
{
    use ScopesHrmFactory;

    public function index(Request $request)
    {
        $query = SalaryGradeDetail::query()
            ->with(['grade.factory', 'salaryHead', 'percentageOfHead'])
            ->latest('id');

        if ($request->user()?->factory_id) {
            $query->whereHas('grade', fn ($q) => $q->where('factory_id', $request->user()->factory_id));
        }

        if ($request->filled('factory_id')) {
            $query->whereHas('grade', fn ($q) => $q->where('factory_id', $request->factory_id));
        }

        if ($request->filled('salary_grade_id')) {
            $query->where('salary_grade_id', $request->salary_grade_id);
        }

        $details = $query->paginate(25)->withQueryString();

        $grades = SalaryGrade::query()
            ->when($request->user()?->factory_id, fn ($q) => $q->where('factory_id', $request->user()->factory_id))
            ->when($request->filled('factory_id'), fn ($q) => $q->where('factory_id', $request->factory_id))
            ->orderBy('name')
            ->pluck('name', 'id');

        return view('admin.hrm.salary.grade-details.index', [
            'details'          => $details,
            'grades'           => $grades,
            'factories'        => $this->factoryOptions($request),
            'filterFactoryId'  => (string) $request->input('factory_id', ''),
            'filterGradeId'    => (string) $request->input('salary_grade_id', ''),
        ]);
    }

    public function create(Request $request)
    {
        $factoryId = $request->user()?->factory_id ?? $request->integer('factory_id') ?: null;

        return view('admin.hrm.salary.grade-details.form', [
            'detail'      => new SalaryGradeDetail(['detail_type' => 'F', 'is_fixed' => true, 'amount' => 0]),
            'grades'      => $this->gradeOptions($request, $factoryId),
            'heads'       => $this->headOptions($request, $factoryId),
            'detailTypes' => SalaryGradeDetail::DETAIL_TYPES,
            'factories'   => $this->factoryOptions($request),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateDetail($request);
        $grade = SalaryGrade::findOrFail($validated['salary_grade_id']);
        $this->authorizeFactoryAccess($request, $grade->factory_id);

        SalaryGradeDetail::create($validated);

        return redirect()->route('admin.hrm.salary.grade-details.index')->with('success', 'Grade detail saved.');
    }

    public function show(Request $request, SalaryGradeDetail $gradeDetail)
    {
        $gradeDetail->load(['grade.factory', 'salaryHead', 'percentageOfHead']);
        $this->authorizeFactoryAccess($request, $gradeDetail->grade->factory_id);

        $data = [
            'detail'    => $gradeDetail,
            'canManage' => $request->user()?->canManageSalarySubmodule('grade-details') ?? false,
        ];

        if ($request->ajax() || $request->boolean('modal')) {
            return view('admin.hrm.salary.grade-details.show-modal', $data);
        }

        return redirect()->route('admin.hrm.salary.grade-details.index', [
            'salary_grade_id' => $gradeDetail->salary_grade_id,
        ]);
    }

    public function edit(Request $request, SalaryGradeDetail $gradeDetail)
    {
        $gradeDetail->load(['grade', 'salaryHead', 'percentageOfHead']);
        $this->authorizeFactoryAccess($request, $gradeDetail->grade->factory_id);

        return view('admin.hrm.salary.grade-details.form', [
            'detail'      => $gradeDetail,
            'grades'      => $this->gradeOptions($request, $gradeDetail->grade->factory_id),
            'heads'       => $this->headOptions($request, $gradeDetail->grade->factory_id),
            'detailTypes' => SalaryGradeDetail::DETAIL_TYPES,
            'factories'   => $this->factoryOptions($request),
        ]);
    }

    public function update(Request $request, SalaryGradeDetail $gradeDetail)
    {
        $gradeDetail->load('grade');
        $validated = $this->validateDetail($request, $gradeDetail);
        $grade = SalaryGrade::findOrFail($validated['salary_grade_id']);
        $this->authorizeFactoryAccess($request, $grade->factory_id);

        $gradeDetail->update($validated);

        return redirect()->route('admin.hrm.salary.grade-details.index')->with('success', 'Grade detail updated.');
    }

    public function destroy(Request $request, SalaryGradeDetail $gradeDetail)
    {
        $gradeDetail->load('grade');
        $this->authorizeFactoryAccess($request, $gradeDetail->grade->factory_id);
        $gradeDetail->delete();

        return redirect()->route('admin.hrm.salary.grade-details.index')->with('success', 'Grade detail deleted.');
    }

    private function validateDetail(Request $request, ?SalaryGradeDetail $existing = null): array
    {
        $type = $request->input('detail_type', 'F');

        $rules = [
            'salary_grade_id' => ['required', 'exists:hrm_salary_grades,id'],
            'salary_head_id'  => [
                'required', 'exists:hrm_salary_heads,id',
                Rule::unique('hrm_salary_grade_details', 'salary_head_id')
                    ->where('salary_grade_id', $request->input('salary_grade_id'))
                    ->ignore($existing?->id),
            ],
            'detail_type'     => ['required', Rule::in(array_keys(SalaryGradeDetail::DETAIL_TYPES))],
            'is_fixed'        => ['nullable', 'boolean'],
        ];

        if ($type === 'F') {
            $rules['amount'] = ['required', 'numeric', 'min:0'];
        } elseif ($type === 'P') {
            $rules['percentage'] = ['required', 'numeric', 'min:0', 'max:100'];
            $rules['percentage_of_head_id'] = ['required', 'exists:hrm_salary_heads,id', 'different:salary_head_id'];
            $rules['amount'] = ['nullable'];
        } else {
            $rules['formula'] = ['required', 'string', 'max:500'];
            $rules['amount'] = ['nullable'];
        }

        $validated = $request->validate($rules);
        $validated['is_fixed'] = $request->boolean('is_fixed', $type === 'F');

        if ($type === 'F') {
            $validated['percentage'] = null;
            $validated['percentage_of_head_id'] = null;
            $validated['formula'] = null;
        } elseif ($type === 'P') {
            $validated['amount'] = 0;
            $validated['formula'] = null;
        } else {
            $validated['amount'] = 0;
            $validated['percentage'] = null;
            $validated['percentage_of_head_id'] = null;
        }

        return $validated;
    }

    private function gradeOptions(Request $request, ?int $factoryId): array
    {
        return SalaryGrade::query()
            ->when($factoryId, fn ($q) => $q->where('factory_id', $factoryId))
            ->when($request->user()?->factory_id, fn ($q) => $q->where('factory_id', $request->user()->factory_id))
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    private function headOptions(Request $request, ?int $factoryId): array
    {
        return SalaryHead::query()
            ->when($factoryId, fn ($q) => $q->where('factory_id', $factoryId))
            ->when($request->user()?->factory_id, fn ($q) => $q->where('factory_id', $request->user()->factory_id))
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->pluck('name', 'id')
            ->all();
    }
}
