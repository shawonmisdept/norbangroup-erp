<?php

namespace App\Http\Controllers\Admin\Hrm\Salary;

use App\Http\Controllers\Admin\Hrm\Concerns\ScopesHrmFactory;
use App\Http\Controllers\Controller;
use App\Models\Hrm\SalaryGrade;
use App\Models\Hrm\SalaryIncrementRule;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class IncrementRuleController extends Controller
{
    use ScopesHrmFactory;

    public function index(Request $request)
    {
        $query = SalaryIncrementRule::query()
            ->with(['factory', 'salaryGrade'])
            ->latest('id');

        $this->scopeToUserFactory($query, $request);

        if ($request->filled('factory_id')) {
            $query->where('factory_id', $request->factory_id);
        }

        $rules = $query->paginate(25)->withQueryString();

        return view('admin.hrm.salary.increment-rules.index', [
            'rules'     => $rules,
            'factories' => $this->factoryOptions($request),
            'filters'   => $request->only(['factory_id']),
        ]);
    }

    public function create(Request $request)
    {
        return view('admin.hrm.salary.increment-rules.form', [
            'rule'      => new SalaryIncrementRule(['is_active' => true, 'increment_type' => 'percentage']),
            'factories' => $this->factoryOptions($request),
            'grades'    => $this->gradeOptions($request),
            'types'     => SalaryIncrementRule::TYPES,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateRule($request);
        $this->authorizeFactoryAccess($request, (int) $validated['factory_id']);
        SalaryIncrementRule::create($validated);

        return redirect()->route('admin.hrm.salary.increment-rules.index')->with('success', 'Increment rule created.');
    }

    public function edit(Request $request, SalaryIncrementRule $incrementRule)
    {
        $this->authorizeFactoryAccess($request, $incrementRule->factory_id);

        return view('admin.hrm.salary.increment-rules.form', [
            'rule'      => $incrementRule,
            'factories' => $this->factoryOptions($request),
            'grades'    => $this->gradeOptions($request, $incrementRule->factory_id),
            'types'     => SalaryIncrementRule::TYPES,
        ]);
    }

    public function update(Request $request, SalaryIncrementRule $incrementRule)
    {
        $this->authorizeFactoryAccess($request, $incrementRule->factory_id);
        $incrementRule->update($this->validateRule($request, $incrementRule));

        return redirect()->route('admin.hrm.salary.increment-rules.index')->with('success', 'Increment rule updated.');
    }

    public function destroy(Request $request, SalaryIncrementRule $incrementRule)
    {
        $this->authorizeFactoryAccess($request, $incrementRule->factory_id);
        $incrementRule->delete();

        return redirect()->route('admin.hrm.salary.increment-rules.index')->with('success', 'Increment rule deleted.');
    }

    private function validateRule(Request $request, ?SalaryIncrementRule $rule = null): array
    {
        return $request->validate([
            'factory_id'          => ['required', 'exists:factories,id'],
            'salary_grade_id'     => ['nullable', 'exists:hrm_salary_grades,id'],
            'name'                => ['required', 'string', 'max:80'],
            'increment_type'      => ['required', Rule::in(array_keys(SalaryIncrementRule::TYPES))],
            'increment_value'     => ['required', 'numeric', 'min:0.01'],
            'min_tenure_months'   => ['nullable', 'integer', 'min:0', 'max:600'],
            'description'         => ['nullable', 'string', 'max:1000'],
            'is_active'           => ['nullable', 'boolean'],
        ]) + [
            'salary_grade_id'   => $request->input('salary_grade_id') ?: null,
            'min_tenure_months' => (int) $request->input('min_tenure_months', 0),
            'is_active'         => $request->boolean('is_active', true),
        ];
    }

    /** @return array<int, string> */
    private function gradeOptions(Request $request, ?int $factoryId = null): array
    {
        $query = SalaryGrade::query()->where('is_active', true)->orderBy('name');

        if ($factoryId) {
            $query->where('factory_id', $factoryId);
        } else {
            $this->scopeToUserFactory($query, $request);
        }

        return $query->pluck('name', 'id')->all();
    }
}
