<?php

namespace App\Http\Controllers\Admin\Hrm\Salary;

use App\Http\Controllers\Admin\Hrm\Concerns\ScopesHrmFactory;
use App\Http\Controllers\Controller;
use App\Models\Hrm\SalaryBank;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BankController extends Controller
{
    use ScopesHrmFactory;

    public function index(Request $request)
    {
        $query = SalaryBank::query()->with('factory')->orderBy('name');
        $this->scopeToUserFactory($query, $request);

        if ($request->filled('factory_id')) {
            $query->where('factory_id', $request->factory_id);
        }

        $banks = $query->paginate(25)->withQueryString();

        return view('admin.hrm.salary.banks.index', [
            'banks'           => $banks,
            'factories'       => $this->factoryOptions($request),
            'filterFactoryId' => (string) $request->input('factory_id', ''),
        ]);
    }

    public function create(Request $request)
    {
        return view('admin.hrm.salary.banks.form', [
            'bank'      => new SalaryBank(['is_active' => true]),
            'factories' => $this->factoryOptions($request),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateBank($request);
        $this->authorizeFactoryAccess($request, (int) $validated['factory_id']);
        SalaryBank::create($validated);

        return redirect()->route('admin.hrm.salary.banks.index')->with('success', 'Salary bank created.');
    }

    public function edit(Request $request, SalaryBank $bank)
    {
        $this->authorizeFactoryAccess($request, $bank->factory_id);

        return view('admin.hrm.salary.banks.form', [
            'bank'      => $bank,
            'factories' => $this->factoryOptions($request),
        ]);
    }

    public function update(Request $request, SalaryBank $bank)
    {
        $this->authorizeFactoryAccess($request, $bank->factory_id);
        $bank->update($this->validateBank($request, $bank));

        return redirect()->route('admin.hrm.salary.banks.index')->with('success', 'Salary bank updated.');
    }

    public function destroy(Request $request, SalaryBank $bank)
    {
        $this->authorizeFactoryAccess($request, $bank->factory_id);

        if ($bank->salaryStructures()->exists()) {
            return redirect()->back()->withErrors(['bank' => 'Cannot delete bank assigned to employee salaries.']);
        }

        $bank->delete();

        return redirect()->route('admin.hrm.salary.banks.index')->with('success', 'Salary bank deleted.');
    }

    private function validateBank(Request $request, ?SalaryBank $bank = null): array
    {
        return $request->validate([
            'factory_id' => ['required', 'exists:factories,id'],
            'code'       => [
                'required', 'string', 'max:20',
                Rule::unique('hrm_salary_banks', 'code')->where('factory_id', $request->input('factory_id'))->ignore($bank?->id),
            ],
            'name'       => ['required', 'string', 'max:120'],
            'short_name' => ['nullable', 'string', 'max:40'],
            'is_active'  => ['nullable', 'boolean'],
        ]) + [
            'is_active' => $request->boolean('is_active', true),
        ];
    }
}
