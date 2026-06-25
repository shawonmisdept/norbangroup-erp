<?php

namespace App\Http\Controllers\Admin\Hrm\Salary;

use App\Http\Controllers\Admin\Hrm\Concerns\ScopesHrmFactory;
use App\Http\Controllers\Controller;
use App\Models\Hrm\Employee;
use App\Models\Hrm\SalaryIncrementRule;
use App\Services\Hrm\SalaryIncrementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\StreamedResponse;

class IncrementUploadController extends Controller
{
    use ScopesHrmFactory;

    private const HEADERS = [
        'employee_code',
        'new_gross',
        'rule_name',
    ];

    public function index(Request $request)
    {
        return view('admin.hrm.salary.increment-upload.index', [
            'factories' => $this->factoryOptions($request),
        ]);
    }

    public function template(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, self::HEADERS);
            fputcsv($handle, ['NCL-D016', '45000', '']);
            fputcsv($handle, ['NCL-D017', '', 'Annual Increment 5%']);
            fclose($handle);
        }, 'increment-upload-template.csv', ['Content-Type' => 'text/csv']);
    }

    public function store(Request $request, SalaryIncrementService $service)
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
        $user = $request->user();

        while (($row = fgetcsv($handle)) !== false) {
            $rowNum++;

            if ($row === [null] || trim(implode('', $row)) === '') {
                continue;
            }

            $data = array_combine(self::HEADERS, array_pad(array_map('trim', $row), count(self::HEADERS), ''));

            $validator = Validator::make($data, [
                'employee_code' => ['required', 'string'],
                'new_gross'     => ['nullable', 'numeric', 'min:0'],
                'rule_name'     => ['nullable', 'string', 'max:80'],
            ]);

            if ($validator->fails()) {
                $errors[] = "Row {$rowNum}: " . implode(' ', $validator->errors()->all());
                $skipped++;

                continue;
            }

            if ($data['new_gross'] === '' && $data['rule_name'] === '') {
                $errors[] = "Row {$rowNum}: Provide new_gross or rule_name.";
                $skipped++;

                continue;
            }

            $employee = Employee::query()
                ->where('factory_id', $request->factory_id)
                ->where('employee_code', $data['employee_code'])
                ->with('salaryStructure.salaryGrade')
                ->first();

            if (! $employee) {
                $errors[] = "Row {$rowNum}: Employee {$data['employee_code']} not found.";
                $skipped++;

                continue;
            }

            try {
                if ($data['rule_name'] !== '') {
                    $rule = SalaryIncrementRule::query()
                        ->where('factory_id', $request->factory_id)
                        ->where('name', $data['rule_name'])
                        ->where('is_active', true)
                        ->first();

                    if (! $rule) {
                        $errors[] = "Row {$rowNum}: Rule \"{$data['rule_name']}\" not found.";
                        $skipped++;

                        continue;
                    }

                    $service->applyToEmployee($employee, $rule, $user);
                } else {
                    $service->applyDirectGross($employee, (float) $data['new_gross'], $user);
                }

                $imported++;
            } catch (\Throwable $e) {
                $errors[] = "Row {$rowNum}: {$e->getMessage()}";
                $skipped++;
            }
        }

        fclose($handle);

        $message = "Increment applied to {$imported} employee(s). Skipped: {$skipped}.";

        if ($errors !== []) {
            return redirect()->back()
                ->with('success', $message)
                ->with('error', implode("\n", array_slice($errors, 0, 10)) . (count($errors) > 10 ? "\n…and more." : ''));
        }

        return redirect()->back()->with('success', $message);
    }
}
