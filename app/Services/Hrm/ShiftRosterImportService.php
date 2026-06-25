<?php

namespace App\Services\Hrm;

use App\Models\Hrm\Employee;
use App\Models\Hrm\Line;
use App\Models\Hrm\Shift;
use App\Models\Hrm\ShiftRoster;
use App\Models\Hrm\ShiftRosterEntry;
use Carbon\Carbon;

class ShiftRosterImportService
{
    private const HEADERS = ['employee_code', 'roster_date', 'shift_code', 'line_code'];

    /** @return list<string> */
    public function expectedHeaders(): array
    {
        return self::HEADERS;
    }

    /**
     * @return array{imported: int, skipped: int, errors: list<string>}
     */
    public function importFromRows(ShiftRoster $roster, array $rows): array
    {
        if ($roster->status === 'published') {
            return ['imported' => 0, 'skipped' => 0, 'errors' => ['Published rosters cannot be edited.']];
        }

        $result = ['imported' => 0, 'skipped' => 0, 'errors' => []];

        $employees = Employee::query()
            ->where('factory_id', $roster->factory_id)
            ->whereIn('status', ['active', 'probation'])
            ->get()
            ->keyBy(fn (Employee $e) => strtoupper($e->employee_code));

        $shifts = Shift::query()
            ->where('factory_id', $roster->factory_id)
            ->where('is_active', true)
            ->get()
            ->keyBy(fn (Shift $s) => strtoupper($s->code));

        $lines = Line::query()
            ->where('factory_id', $roster->factory_id)
            ->where('is_active', true)
            ->get()
            ->keyBy(fn (Line $l) => strtoupper($l->code));

        $start = $roster->start_date->toDateString();
        $end = $roster->end_date->toDateString();

        foreach ($rows as $rowNum => $data) {
            $code = strtoupper(trim($data['employee_code'] ?? ''));
            $date = trim($data['roster_date'] ?? '');
            $shiftCode = strtoupper(trim($data['shift_code'] ?? ''));
            $lineCode = strtoupper(trim($data['line_code'] ?? ''));

            if ($code === '' && $date === '') {
                continue;
            }

            if (! $employees->has($code)) {
                $result['skipped']++;
                $result['errors'][] = "Row {$rowNum}: employee code '{$code}' not found.";

                continue;
            }

            try {
                $parsed = Carbon::parse($date)->toDateString();
            } catch (\Throwable) {
                $result['skipped']++;
                $result['errors'][] = "Row {$rowNum}: invalid date '{$date}'.";

                continue;
            }

            if ($parsed < $start || $parsed > $end) {
                $result['skipped']++;
                $result['errors'][] = "Row {$rowNum}: date {$parsed} is outside roster range.";

                continue;
            }

            if (! $shifts->has($shiftCode)) {
                $result['skipped']++;
                $result['errors'][] = "Row {$rowNum}: shift code '{$shiftCode}' not found.";

                continue;
            }

            $lineId = null;
            if ($lineCode !== '') {
                if (! $lines->has($lineCode)) {
                    $result['skipped']++;
                    $result['errors'][] = "Row {$rowNum}: line code '{$lineCode}' not found.";

                    continue;
                }
                $lineId = $lines[$lineCode]->id;
            }

            ShiftRosterEntry::updateOrCreate(
                [
                    'employee_id' => $employees[$code]->id,
                    'roster_date' => $parsed,
                ],
                [
                    'roster_id' => $roster->id,
                    'shift_id'  => $shifts[$shiftCode]->id,
                    'line_id'   => $lineId,
                ]
            );

            $result['imported']++;
        }

        return $result;
    }
}
