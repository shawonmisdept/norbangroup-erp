<?php

namespace App\Http\Controllers\Admin\Hrm\Leave;

use App\Http\Controllers\Admin\Hrm\Concerns\ScopesHrmFactory;
use App\Http\Controllers\Controller;
use App\Models\Hrm\EmploymentType;
use App\Models\Hrm\LeaveRule;
use App\Models\Hrm\LeaveType;
use App\Models\Hrm\WorkerCategory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RuleController extends Controller
{
    use ScopesHrmFactory;

    public function index(Request $request)
    {
        $query = LeaveRule::query()
            ->with(['factory', 'leaveType', 'workerCategory', 'employmentType'])
            ->latest('id');

        $this->scopeToUserFactory($query, $request);

        if ($request->filled('factory_id')) {
            $query->where('factory_id', $request->factory_id);
        }

        $rules = $query->paginate(25)->withQueryString();

        return view('admin.hrm.leave.rules.index', [
            'rules'     => $rules,
            'factories' => $this->factoryOptions($request),
            'filters'   => $request->only(['factory_id']),
        ]);
    }

    public function create(Request $request)
    {
        return view('admin.hrm.leave.rules.form', [
            'rule'            => new LeaveRule(['is_active' => true, 'allow_probation' => true]),
            'factories'       => $this->factoryOptions($request),
            'leaveTypes'      => LeaveType::where('is_active', true)->orderBy('name')->get(),
            'workerCategories'=> WorkerCategory::where('is_active', true)->orderBy('name')->get(),
            'employmentTypes' => EmploymentType::where('is_active', true)->orderBy('name')->get(),
            'genders'         => LeaveRule::GENDERS,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateRule($request);
        $this->authorizeFactoryAccess($request, (int) $validated['factory_id']);

        LeaveRule::create($validated);

        return redirect()->route('admin.hrm.leave.rules.index')->with('success', 'Leave rule created.');
    }

    public function edit(Request $request, LeaveRule $rule)
    {
        $this->authorizeFactoryAccess($request, $rule->factory_id);

        return view('admin.hrm.leave.rules.form', [
            'rule'            => $rule,
            'factories'       => $this->factoryOptions($request),
            'leaveTypes'      => LeaveType::where('is_active', true)->orderBy('name')->get(),
            'workerCategories'=> WorkerCategory::where('is_active', true)->orderBy('name')->get(),
            'employmentTypes' => EmploymentType::where('is_active', true)->orderBy('name')->get(),
            'genders'         => LeaveRule::GENDERS,
        ]);
    }

    public function update(Request $request, LeaveRule $rule)
    {
        $this->authorizeFactoryAccess($request, $rule->factory_id);
        $rule->update($this->validateRule($request));

        return redirect()->route('admin.hrm.leave.rules.index')->with('success', 'Leave rule updated.');
    }

    public function destroy(Request $request, LeaveRule $rule)
    {
        $this->authorizeFactoryAccess($request, $rule->factory_id);
        $rule->delete();

        return redirect()->route('admin.hrm.leave.rules.index')->with('success', 'Leave rule deleted.');
    }

    private function validateRule(Request $request): array
    {
        return $request->validate([
            'factory_id'         => ['required', 'exists:factories,id'],
            'leave_type_id'      => ['required', 'exists:hrm_leave_types,id'],
            'worker_category_id' => ['nullable', 'exists:hrm_worker_categories,id'],
            'employment_type_id' => ['nullable', 'exists:hrm_employment_types,id'],
            'min_tenure_days'    => ['nullable', 'integer', 'min:0'],
            'gender'             => ['nullable', Rule::in(array_keys(LeaveRule::GENDERS))],
            'allow_probation'    => ['nullable', 'boolean'],
            'notes'              => ['nullable', 'string', 'max:1000'],
            'is_active'          => ['nullable', 'boolean'],
        ]) + [
            'allow_probation' => $request->boolean('allow_probation', true),
            'is_active'       => $request->boolean('is_active', true),
        ];
    }
}
