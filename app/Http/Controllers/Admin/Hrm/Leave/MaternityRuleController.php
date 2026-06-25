<?php

namespace App\Http\Controllers\Admin\Hrm\Leave;

use App\Http\Controllers\Admin\Hrm\Concerns\ScopesHrmFactory;
use App\Http\Controllers\Controller;
use App\Models\Hrm\MaternityRule;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MaternityRuleController extends Controller
{
    use ScopesHrmFactory;

    public function index(Request $request)
    {
        $query = MaternityRule::query()->with('factory')->latest('id');
        $this->scopeToUserFactory($query, $request);

        $rules = $query->paginate(25)->withQueryString();

        return view('admin.hrm.leave.maternity-rules.index', [
            'rules'     => $rules,
            'factories' => $this->factoryOptions($request),
        ]);
    }

    public function create(Request $request)
    {
        return view('admin.hrm.leave.maternity-rules.form', [
            'rule'      => new MaternityRule([
                'total_weeks'      => 16,
                'paid_weeks'       => 8,
                'unpaid_weeks'     => 8,
                'min_service_days' => 180,
                'is_active'        => true,
            ]),
            'factories' => $this->factoryOptions($request),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateRule($request);
        $this->authorizeFactoryAccess($request, (int) $validated['factory_id']);

        MaternityRule::create($validated);

        return redirect()->route('admin.hrm.leave.maternity-rules.index')->with('success', 'Maternity rule created.');
    }

    public function edit(Request $request, MaternityRule $maternityRule)
    {
        $this->authorizeFactoryAccess($request, $maternityRule->factory_id);

        return view('admin.hrm.leave.maternity-rules.form', [
            'rule'      => $maternityRule,
            'factories' => $this->factoryOptions($request),
        ]);
    }

    public function update(Request $request, MaternityRule $maternityRule)
    {
        $this->authorizeFactoryAccess($request, $maternityRule->factory_id);
        $maternityRule->update($this->validateRule($request, $maternityRule));

        return redirect()->route('admin.hrm.leave.maternity-rules.index')->with('success', 'Maternity rule updated.');
    }

    public function destroy(Request $request, MaternityRule $maternityRule)
    {
        $this->authorizeFactoryAccess($request, $maternityRule->factory_id);
        $maternityRule->delete();

        return redirect()->route('admin.hrm.leave.maternity-rules.index')->with('success', 'Maternity rule deleted.');
    }

    private function validateRule(Request $request, ?MaternityRule $existing = null): array
    {
        return $request->validate([
            'factory_id'       => [
                'required', 'exists:factories,id',
                Rule::unique('hrm_maternity_rules', 'factory_id')->ignore($existing?->id),
            ],
            'total_weeks'      => ['required', 'integer', 'min:1', 'max:52'],
            'paid_weeks'       => ['required', 'integer', 'min:0', 'max:52'],
            'unpaid_weeks'     => ['required', 'integer', 'min:0', 'max:52'],
            'min_service_days' => ['required', 'integer', 'min:0'],
            'notes'            => ['nullable', 'string', 'max:1000'],
            'is_active'        => ['nullable', 'boolean'],
        ]) + ['is_active' => $request->boolean('is_active', true)];
    }
}
