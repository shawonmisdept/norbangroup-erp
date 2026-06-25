<?php

namespace App\Http\Controllers\Admin\Hrm\Attendance;

use App\Http\Controllers\Admin\Hrm\Concerns\ScopesHrmFactory;
use App\Http\Controllers\Controller;
use App\Models\Hrm\Employee;
use App\Models\Hrm\Line;
use App\Models\Hrm\Shift;
use App\Models\Hrm\ShiftRoster;
use App\Models\Hrm\ShiftRosterEntry;
use App\Services\Hrm\HrmNotificationService;
use App\Services\Hrm\ShiftRosterImportService;
use App\Services\Hrm\ShiftRosterVarianceService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RosterController extends Controller
{
    use ScopesHrmFactory;

    public function index(Request $request)
    {
        $query = ShiftRoster::query()->with('factory')->withCount('entries')->latest('start_date');
        $this->scopeToUserFactory($query, $request);

        if ($request->filled('factory_id')) {
            $query->where('factory_id', $request->factory_id);
        }

        return view('admin.hrm.attendance.roster.index', [
            'rosters'   => $query->paginate(20)->withQueryString(),
            'factories' => $this->factoryOptions($request),
            'filters'   => $request->only(['factory_id']),
            'canManage' => $request->user()?->canManageAttendanceSubmodule('roster') ?? false,
        ]);
    }

    public function create(Request $request)
    {
        $start = now()->startOfWeek(Carbon::SUNDAY);

        return view('admin.hrm.attendance.roster.form', [
            'roster'    => new ShiftRoster([
                'start_date' => $start->toDateString(),
                'end_date'   => $start->copy()->addDays(6)->toDateString(),
                'status'     => 'draft',
            ]),
            'factories' => $this->factoryOptions($request),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'factory_id' => ['required', 'exists:factories,id'],
            'start_date' => ['required', 'date'],
            'end_date'   => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        $this->authorizeFactoryAccess($request, (int) $validated['factory_id']);

        $roster = ShiftRoster::create($validated + [
            'status'     => 'draft',
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('admin.hrm.attendance.roster.show', $roster)
            ->with('success', 'Roster created. Assign shifts to employees.');
    }

    public function show(Request $request, ShiftRoster $roster)
    {
        $this->authorizeFactoryAccess($request, $roster->factory_id);
        $roster->load(['entries.employee', 'entries.shift', 'entries.line']);

        $employees = Employee::query()
            ->where('factory_id', $roster->factory_id)
            ->whereIn('status', ['active', 'probation'])
            ->orderBy('name')
            ->get();

        $shifts = Shift::query()->where('factory_id', $roster->factory_id)->where('is_active', true)->orderBy('name')->get();
        $lines = Line::query()->where('factory_id', $roster->factory_id)->where('is_active', true)->orderBy('name')->get();

        $dates = collect(CarbonPeriod::create($roster->start_date, $roster->end_date))
            ->map(fn ($d) => $d->toDateString());

        $entryMap = $roster->entries->keyBy(
            fn ($e) => $e->employee_id . '|' . $e->roster_date->toDateString()
        );

        return view('admin.hrm.attendance.roster.show', [
            'roster'    => $roster,
            'employees' => $employees,
            'shifts'    => $shifts,
            'lines'     => $lines,
            'dates'     => $dates,
            'entryMap'  => $entryMap,
            'canManage' => $request->user()?->canManageAttendanceSubmodule('roster') ?? false,
        ]);
    }

    public function assign(Request $request, ShiftRoster $roster)
    {
        $this->authorizeFactoryAccess($request, $roster->factory_id);

        if ($roster->status === 'published') {
            return back()->with('error', 'Published rosters cannot be edited.');
        }

        $validated = $request->validate([
            'employee_id' => ['required', 'exists:hrm_employees,id'],
            'roster_date' => ['required', 'date'],
            'shift_id'    => ['required', 'exists:hrm_shifts,id'],
            'line_id'     => ['nullable', 'exists:hrm_lines,id'],
        ]);

        ShiftRosterEntry::updateOrCreate(
            [
                'employee_id' => $validated['employee_id'],
                'roster_date' => $validated['roster_date'],
            ],
            [
                'roster_id' => $roster->id,
                'shift_id'  => $validated['shift_id'],
                'line_id'   => $validated['line_id'],
            ]
        );

        return back()->with('success', 'Shift assignment saved.');
    }

    public function publish(Request $request, ShiftRoster $roster, HrmNotificationService $notifier)
    {
        $this->authorizeFactoryAccess($request, $roster->factory_id);

        if ($roster->entries()->count() === 0) {
            return back()->with('error', 'Add at least one assignment before publishing.');
        }

        $roster->update(['status' => 'published']);

        $notifier->rosterPublished($roster->fresh(['entries.employee.portalUser']));

        return back()->with('success', 'Roster published. Employees notified via portal.');
    }

    public function variance(Request $request, ShiftRosterVarianceService $variance)
    {
        $factoryId = $request->integer('factory_id') ?: $request->user()?->factory_id;
        $rosterId = $request->integer('roster_id') ?: null;

        $rows = collect();
        $rosters = collect();

        if ($factoryId) {
            $this->authorizeFactoryAccess($request, (int) $factoryId);
            $rosters = $variance->publishedRosters((int) $factoryId);
            $rows = $variance->buildReport((int) $factoryId, $rosterId);
        }

        return view('admin.hrm.attendance.roster.variance', [
            'rows'            => $rows,
            'rosters'         => $rosters,
            'factories'       => $this->factoryOptions($request),
            'filterFactoryId' => (string) ($factoryId ?? ''),
            'filterRosterId'  => (string) ($rosterId ?? ''),
        ]);
    }

    public function exportVariance(Request $request, ShiftRosterVarianceService $variance): StreamedResponse
    {
        $validated = $request->validate([
            'factory_id' => ['required', 'exists:factories,id'],
            'roster_id'  => ['nullable', 'exists:hrm_shift_rosters,id'],
        ]);

        $this->authorizeFactoryAccess($request, (int) $validated['factory_id']);

        $rows = $variance->buildReport(
            (int) $validated['factory_id'],
            $validated['roster_id'] ?? null
        );

        $filename = 'roster-variance-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Date', 'Employee Code', 'Employee Name', 'Roster Shift', 'Actual Shift', 'Attendance', 'Variance']);

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row['roster_date'],
                    $row['employee']?->employee_code ?? '',
                    $row['employee']?->name ?? '',
                    $row['roster_shift'],
                    $row['actual_shift'] ?? '—',
                    $row['attendance_status'] ?? '—',
                    $row['variance_type'] === 'shift_mismatch' ? 'Shift mismatch' : 'No attendance',
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function importTemplate(Request $request, ShiftRoster $roster): StreamedResponse
    {
        $this->authorizeFactoryAccess($request, $roster->factory_id);

        $filename = 'roster-import-' . $roster->id . '.csv';

        return response()->streamDownload(function () use ($roster) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, app(ShiftRosterImportService::class)->expectedHeaders());
            fputcsv($handle, ['EMP001', $roster->start_date->toDateString(), 'SFT-A', 'LN-01']);
            fputcsv($handle, ['EMP002', $roster->start_date->toDateString(), 'SFT-B', '']);
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function import(Request $request, ShiftRoster $roster, ShiftRosterImportService $importer)
    {
        $this->authorizeFactoryAccess($request, $roster->factory_id);

        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:4096'],
        ]);

        $handle = fopen($request->file('file')->getRealPath(), 'r');
        $header = fgetcsv($handle);
        $expected = array_map('strtolower', $importer->expectedHeaders());

        if (! $header || array_map('strtolower', array_map('trim', $header)) !== $expected) {
            fclose($handle);

            return back()->with('error', 'Invalid file format. Download the template — open in Excel, fill rows, save as CSV.');
        }

        $rows = [];
        $rowNum = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNum++;

            if ($row === [null] || trim(implode('', $row)) === '') {
                continue;
            }

            $rows[$rowNum] = array_combine(
                $importer->expectedHeaders(),
                array_pad(array_map('trim', $row), count($importer->expectedHeaders()), '')
            );
        }

        fclose($handle);

        $result = $importer->importFromRows($roster, $rows);

        $message = "Imported {$result['imported']} assignment(s). Skipped: {$result['skipped']}.";

        if ($result['errors'] !== []) {
            return back()
                ->with('success', $message)
                ->with('error', implode("\n", array_slice($result['errors'], 0, 10)));
        }

        return back()->with('success', $message);
    }
}
