<?php

namespace App\Http\Controllers\Admin\Hrm;

use App\Http\Controllers\Admin\Hrm\Concerns\ScopesHrmFactory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Hrm\StoreEmployeeRequest;
use App\Http\Requests\Admin\Hrm\UpdateEmployeeRequest;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Factory;
use App\Models\Hrm\Building;
use App\Models\Hrm\Employee;
use App\Models\Hrm\RecruitmentApplication;
use App\Models\Hrm\EmploymentType;
use App\Models\Hrm\Floor;
use App\Models\Hrm\Line;
use App\Models\Hrm\Shift;
use App\Models\Hrm\WorkerCategory;
use App\Services\EmployeeDocumentService;
use App\Services\EmployeePhotoService;
use App\Services\EmployeePortalService;
use App\Services\Hrm\EmployeeServiceHistoryService;
use App\Services\Hrm\RecruitmentService;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    use ScopesHrmFactory;

    public function __construct(
        private EmployeeServiceHistoryService $serviceHistory,
    ) {}
    public function index(Request $request)
    {
        $query = Employee::query()
            ->with(['factory', 'department', 'designation', 'line', 'workerCategory', 'shift', 'reportingTo'])
            ->latest();

        $this->scopeToUserFactory($query, $request);

        if ($request->filled('factory_id')) {
            $query->where('factory_id', $request->factory_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('employee_code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('nid_number', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('biometric_user_id', 'like', "%{$search}%");
            });
        }

        $employees = $query->paginate(20)->withQueryString();

        return view('admin.hrm.employees.index', [
            'employees'  => $employees,
            'factories'  => $this->factoryOptions($request),
            'statuses'   => Employee::STATUSES,
            'filters'    => $request->only(['search', 'factory_id', 'status']),
        ]);
    }

    public function create(Request $request)
    {
        $options = $this->formOptions($request);

        if ($prefill = session('recruitment_prefill')) {
            $options['employee'] = new Employee($prefill);
            $options['recruitmentPrefill'] = $prefill;
            $options['recruitmentApplicationId'] = session('recruitment_application_id');
        }

        return view('admin.hrm.employees.create', $options);
    }

    public function store(StoreEmployeeRequest $request)
    {
        $data = $request->validated();
        unset($data['enable_portal'], $data['portal_password'], $data['portal_password_confirmation']);

        $educationHistory = $data['education_history'] ?? [];
        $employmentHistory = $data['employment_history'] ?? [];
        unset($data['education_history'], $data['employment_history']);

        $data = $this->normalizeNullableIds($data);
        $data = $this->normalizeScheduleFields($data, $request);

        $this->authorizeFactoryAccess($request, (int) $data['factory_id']);

        $this->handleDocumentUploads($request, $data);

        if ($request->hasFile('photo')) {
            $data['photo'] = EmployeePhotoService::store($request->file('photo'));
        }

        $employee = Employee::create($data);
        $this->serviceHistory->recordEnrollment($employee);
        $this->syncEducationHistories($employee, $educationHistory);
        $this->syncEmploymentHistories($employee, $employmentHistory);

        $flash = ['success' => 'Employee enrolled successfully.'];

        if ($request->boolean('enable_portal')) {
            $result = EmployeePortalService::createForEmployee(
                $employee,
                $request->filled('portal_password') ? $request->input('portal_password') : null
            );
            $flash['success'] = 'Employee enrolled and portal access enabled.';
            if (! $request->filled('portal_password')) {
                $flash['portal_password'] = $result['plainPassword'];
            }
        }

        if ($applicationId = session('recruitment_application_id')) {
            $application = RecruitmentApplication::find($applicationId);
            if ($application) {
                app(RecruitmentService::class)->markConverted($application, $employee, $request->user());
            }
            session()->forget(['recruitment_prefill', 'recruitment_application_id']);
        }

        return redirect()->route('admin.hrm.employees.show', $employee)->with($flash);
    }

    public function show(Request $request, Employee $employee)
    {
        $this->authorizeEmployeeAccess($request, $employee);

        $employee->load([
            'factory', 'department', 'designation', 'workerCategory', 'employmentType',
            'building', 'floor', 'line', 'shift', 'reportingTo', 'portalUser',
            'educationHistories', 'employmentHistories', 'serviceHistories.recordedByUser',
            'gratuitySettlement', 'finalSettlement', 'pendingSeparation', 'latestSeparation',
            'issuedLetters.template', 'disciplinaryRecords.recorder',
        ]);

        $canViewGratuity = $request->user()?->canViewComplianceSubmodule('gratuity') ?? false;
        $canViewSettlement = $request->user()?->canViewFinanceSubmodule('final-settlement') ?? false;
        $canManageSettlement = $request->user()?->canManageFinanceSubmodule('final-settlement') ?? false;

        return view('admin.hrm.employees.show', compact(
            'employee',
            'canViewGratuity',
            'canViewSettlement',
            'canManageSettlement',
        ));
    }

    public function edit(Request $request, Employee $employee)
    {
        $this->authorizeEmployeeAccess($request, $employee);

        $employee->load(['educationHistories', 'employmentHistories']);

        return view('admin.hrm.employees.edit', array_merge(
            ['employee' => $employee],
            $this->formOptions($request, $employee)
        ));
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee)
    {
        $this->authorizeEmployeeAccess($request, $employee);

        $data = $request->validated();
        unset($data['enable_portal'], $data['portal_password'], $data['portal_password_confirmation']);

        $educationHistory = $data['education_history'] ?? [];
        $employmentHistory = $data['employment_history'] ?? [];
        unset($data['education_history'], $data['employment_history']);

        $data = $this->normalizeNullableIds($data);
        $data = $this->normalizeScheduleFields($data, $request);

        $this->handleDocumentUploads($request, $data, $employee);

        if ($request->hasFile('photo')) {
            $data['photo'] = EmployeePhotoService::store($request->file('photo'), $employee->photo);
        }

        $original = $employee->getOriginal();
        $employee->update($data);
        $this->serviceHistory->recordChanges($employee->fresh(), $original);

        if ($request->has('education_history')) {
            $this->syncEducationHistories($employee, $educationHistory);
        }

        if ($request->has('employment_history')) {
            $this->syncEmploymentHistories($employee, $employmentHistory);
        }

        EmployeePortalService::syncPortalState($employee);

        return redirect()->route('admin.hrm.employees.show', $employee)
            ->with('success', 'Employee record updated.');
    }

    public function destroy(Request $request, Employee $employee)
    {
        $this->authorizeEmployeeAccess($request, $employee);

        $employee->delete();

        return redirect()->route('admin.hrm.employees.index')
            ->with('success', 'Employee record removed.');
    }

    public function idCard(Request $request, Employee $employee)
    {
        $this->authorizeEmployeeAccess($request, $employee);

        $employee->load(['factory', 'department', 'designation', 'line', 'shift']);

        return view('admin.hrm.employees.id-card', compact('employee'));
    }

    private function handleDocumentUploads(Request $request, array &$data, ?Employee $employee = null): void
    {
        $map = [
            'nid_document'               => ['folder' => 'nid', 'column' => 'nid_document'],
            'birth_certificate_document'   => ['folder' => 'birth-certificates', 'column' => 'birth_certificate_document'],
            'nominee_nid_document'       => ['folder' => 'nominee-nid', 'column' => 'nominee_nid_document'],
            'nominee_photo'              => ['folder' => 'nominee-photos', 'column' => 'nominee_photo'],
        ];

        foreach ($map as $input => $config) {
            unset($data[$input]);

            if (! $request->hasFile($input)) {
                continue;
            }

            $data[$config['column']] = EmployeeDocumentService::store(
                $request->file($input),
                $config['folder'],
                $employee?->{$config['column']}
            );
        }
    }

    private function syncEducationHistories(Employee $employee, array $rows): void
    {
        $employee->educationHistories()->delete();

        foreach (array_values($rows) as $index => $row) {
            if ($this->rowIsEmpty($row, ['degree', 'institution', 'board_or_university', 'passing_year', 'result'])) {
                continue;
            }

            $employee->educationHistories()->create([
                'degree'              => $row['degree'] ?? null,
                'institution'         => $row['institution'] ?? null,
                'board_or_university' => $row['board_or_university'] ?? null,
                'passing_year'        => $row['passing_year'] ?? null,
                'result'              => $row['result'] ?? null,
                'sort_order'          => $index,
            ]);
        }
    }

    private function syncEmploymentHistories(Employee $employee, array $rows): void
    {
        $employee->employmentHistories()->delete();

        foreach (array_values($rows) as $index => $row) {
            if ($this->rowIsEmpty($row, ['company_name', 'designation', 'department', 'joining_date', 'leaving_date', 'reason_for_leaving'])) {
                continue;
            }

            $employee->employmentHistories()->create([
                'company_name'       => $row['company_name'] ?? null,
                'designation'        => $row['designation'] ?? null,
                'department'         => $row['department'] ?? null,
                'joining_date'       => $row['joining_date'] ?? null,
                'leaving_date'       => $row['leaving_date'] ?? null,
                'reason_for_leaving' => $row['reason_for_leaving'] ?? null,
                'sort_order'         => $index,
            ]);
        }
    }

    private function rowIsEmpty(array $row, array $fields): bool
    {
        foreach ($fields as $field) {
            if (filled($row[$field] ?? null)) {
                return false;
            }
        }

        return true;
    }

    private function normalizeNullableIds(array $data): array
    {
        foreach ([
            'department_id', 'designation_id', 'worker_category_id', 'employment_type_id',
            'building_id', 'floor_id', 'line_id', 'shift_id', 'reporting_to_id',
        ] as $field) {
            if (array_key_exists($field, $data) && $data[$field] === '') {
                $data[$field] = null;
            }
        }

        return $data;
    }

    private function formOptions(Request $request, ?Employee $employee = null): array
    {
        $factoryId = old('factory_id', $employee?->factory_id ?? $request->user()?->factory_id);

        $reportingQuery = Employee::query()
            ->whereIn('status', ['active', 'probation'])
            ->orderBy('name');

        if ($factoryId) {
            $reportingQuery->where('factory_id', $factoryId);
        } elseif ($request->user()?->factory_id) {
            $reportingQuery->where('factory_id', $request->user()->factory_id);
        }

        if ($employee) {
            $reportingQuery->where('id', '!=', $employee->id);
        }

        $orgScope = $request->user()?->factory_id ?? $factoryId;

        return [
            'employee'              => $employee,
            'factories'             => $this->factoryOptions($request),
            'departments'           => Department::where('is_active', true)->when($orgScope, fn ($q) => $q->where('factory_id', $orgScope))->orderBy('name')->get(['id', 'name', 'factory_id']),
            'designations'          => Designation::where('is_active', true)->orderBy('name')->get(['id', 'name', 'department_id']),
            'workerCategories'      => WorkerCategory::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'employmentTypes'       => EmploymentType::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'buildings'             => Building::where('is_active', true)->when($orgScope, fn ($q) => $q->where('factory_id', $orgScope))->orderBy('name')->get(['id', 'name', 'factory_id']),
            'floors'                => Floor::where('is_active', true)->when($orgScope, fn ($q) => $q->where('factory_id', $orgScope))->orderBy('name')->get(['id', 'name', 'factory_id', 'building_id']),
            'lines'                 => Line::where('is_active', true)->when($orgScope, fn ($q) => $q->where('factory_id', $orgScope))->orderBy('name')->get(['id', 'name', 'factory_id', 'floor_id']),
            'shifts'                => Shift::where('is_active', true)->when($orgScope, fn ($q) => $q->where('factory_id', $orgScope))->orderBy('name')->get(['id', 'name', 'factory_id']),
            'reportingCandidates'   => $reportingQuery->get(['id', 'name', 'employee_code', 'factory_id']),
            'statuses'              => $this->editableStatuses($employee),
            'weekdayLabels'         => \App\Services\Hrm\EmployeeScheduleService::WEEKDAY_LABELS,
            'genders'               => config('hrm.employee_options.genders', []),
            'bloodGroups'           => config('hrm.employee_options.blood_groups', []),
            'defaultFactoryId'      => $factoryId,
        ];
    }

    private function normalizeScheduleFields(array $data, Request $request): array
    {
        $weekendDays = $data['weekend_days'] ?? [0];
        $data['weekend_days'] = array_values(array_unique(array_map('intval', (array) $weekendDays)));
        $data['weekend_ot_allowed'] = $request->boolean('weekend_ot_allowed');

        if (array_key_exists('half_day_pay_ratio', $data) && ($data['half_day_pay_ratio'] === '' || $data['half_day_pay_ratio'] === null)) {
            $data['half_day_pay_ratio'] = null;
        }

        return $data;
    }

    private function editableStatuses(?Employee $employee = null): array
    {
        if ($employee?->isSeparated()) {
            return array_intersect_key(Employee::STATUSES, array_flip([$employee->status]));
        }

        return array_diff_key(Employee::STATUSES, array_flip(Employee::SEPARATED_STATUSES));
    }

    private function authorizeEmployeeAccess(Request $request, Employee $employee): void
    {
        if ($request->user()?->factory_id && $request->user()->factory_id !== $employee->factory_id) {
            abort(403, 'You do not have access to this employee record.');
        }
    }
}
