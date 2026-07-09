<?php

namespace App\Notifications;

use App\Models\Hrm\Employee;
use App\Support\NotificationUrl;
use Illuminate\Notifications\Notification;

class WorkingHoursExceededNotification extends Notification
{
    public function __construct(
        public Employee $employee,
        public float $hours,
        public float $limitHours,
        public string $periodLabel,
        public string $periodType,
        public ?int $factoryId = null,
        public ?int $year = null,
        public ?int $month = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $typeLabel = $this->periodType === 'weekly' ? 'Weekly' : 'Daily';

        return [
            'type'    => 'hrm_working_hours',
            'title'   => "{$typeLabel} Working Hour Limit Exceeded",
            'message' => $this->employee->name . ' (' . $this->employee->employee_code . ') — '
                . number_format($this->hours, 1) . ' hrs on ' . $this->periodLabel
                . ' (limit ' . number_format($this->limitHours, 1) . ' hrs)',
            'url'     => NotificationUrl::route('admin.hrm.compliance.working-hours.index', [], array_filter([
                'factory_id' => $this->factoryId,
                'year'       => $this->year,
                'month'      => $this->month,
            ])),
        ];
    }
}
