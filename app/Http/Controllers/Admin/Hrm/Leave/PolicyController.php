<?php

namespace App\Http\Controllers\Admin\Hrm\Leave;

use App\Http\Controllers\Admin\Hrm\Concerns\ScopesHrmFactory;
use App\Http\Controllers\Controller;
use App\Models\Hrm\LeavePolicy;
use App\Models\Hrm\LeaveType;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PolicyController extends Controller
{
    use ScopesHrmFactory;

    public function index(Request $request)
    {
        $query = LeavePolicy::query()->with(['factory', 'leaveType'])->latest('id');
        $this->scopeToUserFactory($query, $request);

        if ($request->filled('factory_id')) {
            $query->where('factory_id', $request->factory_id);
        }

        $policies = $query->paginate(25)->withQueryString();

        return view('admin.hrm.leave.policies.index', [
            'policies'  => $policies,
            'factories' => $this->factoryOptions($request),
            'filters'   => $request->only(['factory_id']),
        ]);
    }

    public function create(Request $request)
    {
        return view('admin.hrm.leave.policies.form', [
            'policy'     => new LeavePolicy(['is_active' => true, 'days_per_year' => 0]),
            'factories'  => $this->factoryOptions($request),
            'leaveTypes' => LeaveType::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validatePolicy($request);
        $this->authorizeFactoryAccess($request, (int) $validated['factory_id']);

        LeavePolicy::create($validated);

        return redirect()->route('admin.hrm.leave.policies.index')->with('success', 'Leave policy created.');
    }

    public function edit(Request $request, LeavePolicy $policy)
    {
        $this->authorizeFactoryAccess($request, $policy->factory_id);
        $policy->load(['factory', 'leaveType']);

        return view('admin.hrm.leave.policies.form', [
            'policy'     => $policy,
            'factories'  => $this->factoryOptions($request),
            'leaveTypes' => LeaveType::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, LeavePolicy $policy)
    {
        $this->authorizeFactoryAccess($request, $policy->factory_id);
        $policy->update($this->validatePolicy($request, $policy));

        return redirect()->route('admin.hrm.leave.policies.index')->with('success', 'Leave policy updated.');
    }

    public function destroy(Request $request, LeavePolicy $policy)
    {
        $this->authorizeFactoryAccess($request, $policy->factory_id);
        $policy->delete();

        return redirect()->route('admin.hrm.leave.policies.index')->with('success', 'Leave policy deleted.');
    }

    private function validatePolicy(Request $request, ?LeavePolicy $policy = null): array
    {
        return $request->validate([
            'factory_id'                  => ['required', 'exists:factories,id'],
            'leave_type_id'               => [
                'required', 'exists:hrm_leave_types,id',
                Rule::unique('hrm_leave_policies', 'leave_type_id')
                    ->where('factory_id', $request->input('factory_id'))
                    ->ignore($policy?->id),
            ],
            'days_per_year'               => ['required', 'numeric', 'min:0', 'max:365'],
            'min_days_notice'             => ['nullable', 'integer', 'min:0', 'max:90'],
            'requires_medical_after_days' => ['nullable', 'integer', 'min:1', 'max:30'],
            'requires_attachment'         => ['nullable', 'boolean'],
            'is_active'                   => ['nullable', 'boolean'],
        ]) + ['is_active' => $request->boolean('is_active', true), 'requires_attachment' => $request->boolean('requires_attachment')];
    }
}
