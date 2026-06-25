<?php

namespace App\Notifications;

use App\Models\Hrm\Employee;
use Illuminate\Notifications\Notification;

class ContractExpiryNotification extends Notification
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
        return [
            'type'    => 'hrm_contract_expiry',
            'title'   => 'Contract Expiring Soon',
            'message' => $this->employee->name . ' (' . $this->employee->employee_code . ') — contract ends in '
                . $this->daysRemaining . ' day(s) on ' . $this->employee->contract_end_date->format('d M Y'),
            'url'     => route('admin.hrm.employees.show', $this->employee),
        ];
    }
}
