<?php

namespace App\Http\Controllers\Admin\Hrm\Leave;

use App\Http\Controllers\Admin\Hrm\Concerns\ScopesHrmFactory;
use App\Http\Controllers\Controller;
use App\Models\Hrm\Employee;
use App\Models\Hrm\LeaveType;
use App\Services\Hrm\LeaveService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BulkEntryController extends Controller
{
    use ScopesHrmFactory;

    private const HEADERS = [
        'employee_code',
        'leave_type_code',
        'start_date',
        'end_date',
        'reason',
    ];

    public function index(Request $request)
    {
        return view('admin.hrm.leave.bulk-entry.index', [
            'factories' => $this->factoryOptions($request),
        ]);
    }

    public function template(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, self::HEADERS);
            fputcsv($handle, ['LV-P001', 'LVT-CL001', '2026-06-20', '2026-06-20', 'Family event']);
            fclose($handle);
        }, 'leave-bulk-entry-template.csv', ['Content-Type' => 'text/csv']);
    }

    public function store(Request $request, LeaveService $leaveService)
    {
        $request->validate([
            'factory_id' => ['required', 'exists:factories,id'],
            'file'       => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $this->authorizeFactoryAccess($request, (int) $request->factory_id);

        $handle = fopen($request->file('file')->getRealPath(), 'r');
        $header = fgetcsv($handle);

        if (! $header || array_map('strtolower', array_map('trim', $header)) !== self::HEADERS) {
            fclose($handle);

            return redirect()->back()->with('error', 'Invalid CSV format. Download the template and use exact column headers.');
        }

        $imported = 0;
        $skipped = 0;
        $errors = [];
        $rowNum = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNum++;

            if ($row === [null] || trim(implode('', $row)) === '') {
                continue;
            }

            $data = array_combine(self::HEADERS, array_pad(array_map('trim', $row), count(self::HEADERS), ''));

            $validator = Validator::make($data, [
                'employee_code'   => ['required', 'string'],
                'leave_type_code' => ['required', 'string'],
                'start_date'      => ['required', 'date'],
                'end_date'        => ['required', 'date', 'after_or_equal:start_date'],
                'reason'          => ['nullable', 'string', 'max:500'],
            ]);

            if ($validator->fails()) {
                $errors[] = "Row {$rowNum}: " . implode(' ', $validator->errors()->all());
                $skipped++;

                continue;
            }

            $employee = Employee::query()
                ->where('factory_id', $request->factory_id)
                ->where('employee_code', $data['employee_code'])
                ->first();

            if (! $employee) {
                $errors[] = "Row {$rowNum}: Employee {$data['employee_code']} not found.";
                $skipped++;

                continue;
            }

            $leaveType = LeaveType::query()
                ->where('code', $data['leave_type_code'])
                ->where('is_active', true)
                ->first();

            if (! $leaveType) {
                $errors[] = "Row {$rowNum}: Leave type {$data['leave_type_code']} not found.";
                $skipped++;

                continue;
            }

            try {
                $leaveService->recordBulkEntry($employee, [
                    'leave_type_id' => $leaveType->id,
                    'start_date'    => $data['start_date'],
                    'end_date'      => $data['end_date'],
                    'reason'        => $data['reason'] ?: null,
                ], $request->user());

                $imported++;
            } catch (ValidationException $e) {
                $messages = collect($e->errors())->flatten()->implode(' ');
                $errors[] = "Row {$rowNum}: {$messages}";
                $skipped++;
            }
        }

        fclose($handle);

        $message = "Imported {$imported} leave record(s). Skipped: {$skipped}.";

        if ($errors !== []) {
            return redirect()->back()
                ->with('success', $message)
                ->with('error', implode("\n", array_slice($errors, 0, 10)) . (count($errors) > 10 ? "\n…and more." : ''));
        }

        return redirect()->back()->with('success', $message);
    }
}
