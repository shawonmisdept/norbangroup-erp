<?php

namespace App\Jobs\Hrm;

use App\Models\Hrm\AttendancePeriod;
use App\Services\Hrm\AttendanceProcessor;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessAttendanceJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 600;

    public function __construct(
        public int $factoryId,
        public ?string $date = null,
        public bool $markAbsences = false,
    ) {
        $this->onQueue(config('hrm.queues.attendance', 'hrm-attendance'));
    }

    public function handle(AttendanceProcessor $processor): void
    {
        $date = $this->date
            ? Carbon::parse($this->date)->startOfDay()
            : now()->startOfDay();

        $period = AttendancePeriod::getOrCreateForMonth($this->factoryId, $date->year, $date->month);
        $processor->processDate($this->factoryId, $date, $period);

        if ($this->markAbsences) {
            $processor->markAbsences($this->factoryId, $date, $date, $period);
        }
    }

    public function failed(?\Throwable $exception): void
    {
        Log::error('Attendance processing job failed', [
            'factory_id' => $this->factoryId,
            'date'       => $this->date,
            'message'    => $exception?->getMessage(),
        ]);
    }
}
