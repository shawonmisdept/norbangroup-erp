<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Controllers\Admin\Hrm\Concerns\ScopesHrmFactory;
use App\Http\Controllers\Controller;
use App\Models\Hrm\Employee;
use App\Models\Hrm\HrLetterTemplate;
use App\Models\Hrm\IssuedLetter;
use App\Services\Hrm\HrLetterService;
use Illuminate\Http\Request;

class LetterController extends Controller
{
    use ScopesHrmFactory;

    public function __construct(private HrLetterService $service) {}

    public function index(Request $request)
    {
        $this->ensureCanView($request);

        $query = IssuedLetter::query()
            ->with(['employee', 'template', 'issuer'])
            ->latest('issued_at');

        $this->scopeToUserFactory($query, $request);

        if ($request->filled('factory_id')) {
            $query->where('factory_id', $request->factory_id);
        }

        if ($request->filled('letter_type')) {
            $query->where('letter_type', $request->letter_type);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('reference_no', 'like', "%{$search}%")
                    ->orWhereHas('employee', fn ($eq) => $eq
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('employee_code', 'like', "%{$search}%"));
            });
        }

        $templates = HrLetterTemplate::query()
            ->where('is_active', true)
            ->when($request->user()?->factory_id, fn ($q, $fid) => $q->where(fn ($inner) => $inner
                ->whereNull('factory_id')->orWhere('factory_id', $fid)))
            ->orderBy('name')
            ->get();

        return view('admin.hrm.letters.index', [
            'letters'     => $query->paginate(20)->withQueryString(),
            'templates'   => $templates,
            'factories'   => $this->factoryOptions($request),
            'letterTypes' => config('hrm.letter_types', []),
            'filters'     => $request->only(['factory_id', 'letter_type', 'search']),
            'canManage'   => $request->user()?->hasPermission('hrm.employees.letters.manage') ?? false,
        ]);
    }

    public function create(Request $request)
    {
        $this->ensureCanManage($request);

        $selectedEmployeeId = $request->integer('employee_id') ?: null;
        $selectedTemplateId = $request->integer('template_id') ?: null;

        $employee = $selectedEmployeeId ? Employee::find($selectedEmployeeId) : null;
        if ($employee) {
            $this->authorizeFactoryAccess($request, $employee->factory_id);
        }

        $templates = $employee
            ? $this->service->templatesForEmployee($employee)
            : HrLetterTemplate::query()->where('is_active', true)->orderBy('name')->get();

        $preview = null;
        if ($employee && $selectedTemplateId) {
            $template = $templates->firstWhere('id', $selectedTemplateId);
            if ($template) {
                $preview = $this->service->renderTemplate($template, $employee);
            }
        }

        return view('admin.hrm.letters.form', [
            'employees'          => $this->employeeOptions($request, $selectedEmployeeId),
            'templates'          => $templates,
            'selectedEmployeeId' => $selectedEmployeeId,
            'selectedTemplateId' => $selectedTemplateId,
            'preview'            => $preview,
        ]);
    }

    public function store(Request $request)
    {
        $this->ensureCanManage($request);

        $validated = $request->validate([
            'employee_id' => ['required', 'exists:hrm_employees,id'],
            'template_id' => ['required', 'exists:hrm_hr_letter_templates,id'],
            'notes'       => ['nullable', 'string', 'max:2000'],
        ]);

        $employee = Employee::findOrFail($validated['employee_id']);
        $this->authorizeFactoryAccess($request, $employee->factory_id);

        $template = HrLetterTemplate::findOrFail($validated['template_id']);
        if (! $this->service->templatesForEmployee($employee)->contains('id', $template->id)) {
            abort(422, 'Selected template is not available for this employee.');
        }

        $letter = $this->service->issue(
            $employee,
            $template,
            $request->user(),
            $validated['notes'] ?? null,
        );

        return redirect()->route('admin.hrm.letters.show', $letter)
            ->with('success', 'Letter issued successfully.');
    }

    public function show(Request $request, IssuedLetter $letter)
    {
        $this->ensureCanView($request);
        $this->authorizeFactoryAccess($request, $letter->factory_id);

        $letter->load(['employee.factory', 'employee.department', 'employee.designation', 'template', 'issuer']);

        return view('admin.hrm.letters.show', [
            'letter'    => $letter,
            'canManage' => $request->user()?->hasPermission('hrm.employees.letters.manage') ?? false,
        ]);
    }

    public function print(Request $request, IssuedLetter $letter)
    {
        $this->ensureCanView($request);
        $this->authorizeFactoryAccess($request, $letter->factory_id);

        $letter->load(['employee.factory', 'employee.department', 'employee.designation', 'issuer']);

        return view('admin.hrm.letters.print', compact('letter'));
    }

    /** @return array<int, string> */
    private function employeeOptions(Request $request, ?int $includeId = null): array
    {
        $query = Employee::query()->orderBy('name');
        $this->scopeToUserFactory($query, $request);

        if ($includeId) {
            $query->where(function ($q) use ($includeId) {
                $q->whereIn('status', ['active', 'probation', 'suspended', 'resigned', 'terminated'])
                    ->orWhere('id', $includeId);
            });
        } else {
            $query->whereIn('status', ['active', 'probation', 'suspended', 'resigned', 'terminated']);
        }

        return $query->get(['id', 'employee_code', 'name'])
            ->mapWithKeys(fn (Employee $e) => [$e->id => $e->employee_code . ' — ' . $e->name])
            ->all();
    }

    private function ensureCanView(Request $request): void
    {
        if (! $request->user()?->hasPermission('hrm.employees.letters.view')) {
            abort(403, 'You do not have permission to view HR letters.');
        }
    }

    private function ensureCanManage(Request $request): void
    {
        if (! $request->user()?->hasPermission('hrm.employees.letters.manage')) {
            abort(403, 'You do not have permission to issue HR letters.');
        }
    }
}
