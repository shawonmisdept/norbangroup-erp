<?php

namespace App\Http\Controllers\Admin\Hrm\Salary;

use App\Http\Controllers\Admin\Hrm\Concerns\ScopesHrmFactory;
use App\Http\Controllers\Controller;
use App\Models\Hrm\SalaryHead;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class HeadController extends Controller
{
    use ScopesHrmFactory;

    public function index(Request $request)
    {
        $query = SalaryHead::query()->with('factory')->orderBy('sort_order')->orderBy('name');
        $this->scopeToUserFactory($query, $request);

        if ($request->filled('factory_id')) {
            $query->where('factory_id', $request->factory_id);
        }

        $heads = $query->paginate(25)->withQueryString();

        return view('admin.hrm.salary.heads.index', [
            'heads'           => $heads,
            'factories'       => $this->factoryOptions($request),
            'filterFactoryId' => (string) $request->input('factory_id', ''),
        ]);
    }

    public function create(Request $request)
    {
        return view('admin.hrm.salary.heads.form', [
            'head'      => new SalaryHead(['head_type' => 'E', 'is_active' => true, 'is_disburse' => true]),
            'factories' => $this->factoryOptions($request),
            'headTypes' => SalaryHead::HEAD_TYPES,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateHead($request);
        $this->authorizeFactoryAccess($request, (int) $validated['factory_id']);
        SalaryHead::create($validated);

        return redirect()->route('admin.hrm.salary.heads.index')->with('success', 'Salary head created.');
    }

    public function show(Request $request, SalaryHead $head)
    {
        $this->authorizeFactoryAccess($request, $head->factory_id);
        $head->load('factory');

        $data = [
            'head'      => $head,
            'canManage' => $request->user()?->canManageSalarySubmodule('heads') ?? false,
        ];

        if ($request->ajax() || $request->boolean('modal')) {
            return view('admin.hrm.salary.heads.show-modal', $data);
        }

        return redirect()->route('admin.hrm.salary.heads.index');
    }

    public function edit(Request $request, SalaryHead $head)
    {
        $this->authorizeFactoryAccess($request, $head->factory_id);

        return view('admin.hrm.salary.heads.form', [
            'head'      => $head,
            'factories' => $this->factoryOptions($request),
            'headTypes' => SalaryHead::HEAD_TYPES,
        ]);
    }

    public function update(Request $request, SalaryHead $head)
    {
        $this->authorizeFactoryAccess($request, $head->factory_id);
        $head->update($this->validateHead($request, $head));

        return redirect()->route('admin.hrm.salary.heads.index')->with('success', 'Salary head updated.');
    }

    public function destroy(Request $request, SalaryHead $head)
    {
        $this->authorizeFactoryAccess($request, $head->factory_id);
        $head->delete();

        return redirect()->route('admin.hrm.salary.heads.index')->with('success', 'Salary head deleted.');
    }

    private function validateHead(Request $request, ?SalaryHead $head = null): array
    {
        return $request->validate([
            'factory_id'    => ['required', 'exists:factories,id'],
            'code'          => [
                'required', 'string', 'max:20',
                Rule::unique('hrm_salary_heads', 'code')->where('factory_id', $request->input('factory_id'))->ignore($head?->id),
            ],
            'name'          => ['required', 'string', 'max:80'],
            'name_bangla'   => ['nullable', 'string', 'max:120'],
            'description'   => ['nullable', 'string', 'max:500'],
            'head_type'     => ['required', Rule::in(array_keys(SalaryHead::HEAD_TYPES))],
            'sort_order'    => ['nullable', 'integer', 'min:0'],
            'sort_code'     => ['nullable', 'string', 'max:20'],
            'is_taxable'    => ['nullable', 'boolean'],
            'is_perquisite' => ['nullable', 'boolean'],
            'is_disburse'   => ['nullable', 'boolean'],
            'is_active'     => ['nullable', 'boolean'],
        ]) + [
            'is_taxable'    => $request->boolean('is_taxable'),
            'is_perquisite' => $request->boolean('is_perquisite'),
            'is_disburse'   => $request->boolean('is_disburse', true),
            'is_active'     => $request->boolean('is_active', true),
            'sort_order'    => (int) $request->input('sort_order', 0),
        ];
    }
}
