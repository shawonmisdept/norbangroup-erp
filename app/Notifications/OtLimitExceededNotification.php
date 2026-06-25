<?php

namespace App\Notifications;

use App\Models\Hrm\Employee;
use Illuminate\Notifications\Notification;

class OtLimitExceededNotification extends Notification
{
    public function __construct(
        public Employee $employee,
        public float $otHours,
        public float $limitHours,
        public string $periodLabel,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'    => 'hrm_ot_limit',
            'title'   => 'OT Limit Exceeded',
            'message' => $this->employee->name . ' (' . $this->employee->employee_code . ') — '
                . number_format($this->otHours, 1) . ' OT hrs in ' . $this->periodLabel
                . ' (limit ' . number_format($this->limitHours, 1) . ' hrs)',
            'url'     => route('admin.hrm.employees.show', $this->employee),
        ];
    }
}
