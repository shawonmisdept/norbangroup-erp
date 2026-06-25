<?php

namespace App\Notifications;

use App\Models\Hrm\Employee;
use Illuminate\Notifications\Notification;

class ProbationEndNotification extends Notification
{
    public function __construct(
        public Employee $employee,
        public int $daysRemaining,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $date = $this->employee->probation_end_date ?? $this->employee->confirmation_date;

        return [
            'type'    => 'hrm_probation_end',
            'title'   => 'Probation Ending Soon',
            'message' => $this->employee->name . ' (' . $this->employee->employee_code . ') — probation ends in '
                . $this->daysRemaining . ' day(s)' . ($date ? ' on ' . $date->format('d M Y') : ''),
            'url'     => route('admin.hrm.employees.show', $this->employee),
        ];
    }
}
