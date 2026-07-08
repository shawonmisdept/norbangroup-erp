<?php

namespace App\Http\Controllers\Admin\Hrm\Salary;

use App\Http\Controllers\Admin\Hrm\Concerns\ScopesHrmFactory;
use App\Http\Controllers\Controller;
use App\Models\Hrm\Employee;
use App\Models\Hrm\SalaryGrade;
use App\Models\Hrm\SalaryStructure;
use App\Services\Hrm\MinimumWageValidator;
use App\Services\Hrm\SalaryFormulaCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UploadController extends Controller
{
    use ScopesHrmFactory;

    private const HEADERS = [
        'employee_code',
        'pay_type',
        'salary_grade_code',
        'gross_salary',
        'daily_wage',
        'hra',
        'medical',
        'conveyance',
        'other_allowance',
        'payment_method',
        'bank_account',
    ];

    public function index(Request $request)
    {
        return view('admin.hrm.salary.upload.index', [
            'factories' => $this->factoryOptions($request),
        ]);
    }

    public function template(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, self::HEADERS);
            fputcsv($handle, ['EMP001', 'salary', 'SR-01', '28000', '0', '0', '0', '0', '0', 'bank', '1234567890']);
            fputcsv($handle, ['EMP002', 'wages', '', '0', '500', '1000', '500', '0', '0', 'bank', '9876543210']);
            fclose($handle);
        }, 'salary-upload-template.csv', ['Content-Type' => 'text/csv']);
    }

    public function store(Request $request, SalaryFormulaCalculator $calculator)
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
                'employee_code'      => ['required', 'string'],
                'pay_type'           => ['required', 'in:salary,wages'],
                'salary_grade_code'  => ['nullable', 'string', 'max:20'],
                'gross_salary'       => ['nullable', 'numeric', 'min:0'],
                'daily_wage'         => ['nullable', 'numeric', 'min:0'],
                'hra'                => ['nullable', 'numeric', 'min:0'],
                'medical'            => ['nullable', 'numeric', 'min:0'],
                'conveyance'         => ['nullable', 'numeric', 'min:0'],
                'other_allowance'    => ['nullable', 'numeric', 'min:0'],
                'payment_method'     => ['nullable', 'in:bank,cash,split'],
                'bank_account'       => ['nullable', 'string', 'max:40'],
            ]);

            if ($validator->fails()) {
                $errors[] = "Row {$rowNum}: " . implode(' ', $validator->errors()->all());
                $skipped++;

                continue;
            }

            $payType = $data['pay_type'];

            if ($payType === 'salary') {
                if ($data['salary_grade_code'] === '' || ! is_numeric($data['gross_salary']) || (float) $data['gross_salary'] <= 0) {
                    $errors[] = "Row {$rowNum}: salary rows require salary_grade_code and gross_salary > 0.";
                    $skipped++;

                    continue;
                }
            } elseif ((float) ($data['daily_wage'] ?: 0) <= 0) {
                $errors[] = "Row {$rowNum}: wages rows require daily_wage > 0.";
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

            $payload = [
                'factory_id'       => $employee->factory_id,
                'payment_method'   => $data['payment_method'] ?: 'bank',
                'bank_account'     => $data['bank_account'] ?: null,
                'is_active'        => true,
            ];

            if ($payType === 'wages') {
                $dailyWage = (float) ($data['daily_wage'] ?: 0);

                try {
                    app(MinimumWageValidator::class)->validateDailyWage($employee, $dailyWage);
                } catch (\Illuminate\Validation\ValidationException $e) {
                    $errors[] = "Row {$rowNum}: " . collect($e->errors())->flatten()->first();
                    $skipped++;

                    continue;
                }

                $payload = array_merge($payload, [
                    'salary_grade_id'  => null,
                    'gross_salary'     => 0,
                    'head_amounts'     => null,
                    'pay_type'         => 'wages',
                    'daily_wage'       => (float) ($data['daily_wage'] ?: 0),
                    'basic_salary'     => 0,
                    'hra'              => (float) ($data['hra'] ?: 0),
                    'medical'          => (float) ($data['medical'] ?: 0),
                    'conveyance'       => (float) ($data['conveyance'] ?: 0),
                    'other_allowance'  => (float) ($data['other_allowance'] ?: 0),
                ]);
            } else {
                $grade = SalaryGrade::query()
                    ->where('factory_id', $request->factory_id)
                    ->where('code', $data['salary_grade_code'])
                    ->where('is_active', true)
                    ->first();

                if (! $grade) {
                    $errors[] = "Row {$rowNum}: Grade {$data['salary_grade_code']} not found.";
                    $skipped++;

                    continue;
                }

                $gross = (float) $data['gross_salary'];
                $amounts = $calculator->calculate($grade, $gross);

                $payload = array_merge($payload, [
                    'salary_grade_id' => $grade->id,
                    'gross_salary'    => $gross,
                    'payment_method'  => $data['payment_method'] ?: 'bank',
                ]);

                $structure = SalaryStructure::updateOrCreate(
                    ['employee_id' => $employee->id],
                    $payload
                );

                $structure->syncLegacyFromHeads($amounts);
                $structure->save();

                $imported++;

                continue;
            }

            SalaryStructure::updateOrCreate(
                ['employee_id' => $employee->id],
                $payload
            );

            $imported++;
        }

        fclose($handle);

        $message = "Imported {$imported} salary record(s). Skipped: {$skipped}.";

        if ($errors !== []) {
            return redirect()->back()
                ->with('success', $message)
                ->with('error', implode("\n", array_slice($errors, 0, 10)) . (count($errors) > 10 ? "\n…and more." : ''));
        }

        return redirect()->back()->with('success', $message);
    }
}
