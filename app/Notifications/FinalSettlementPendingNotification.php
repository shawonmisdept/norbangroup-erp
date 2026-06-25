<?php

namespace App\Notifications;

use App\Models\Hrm\Employee;
use Illuminate\Notifications\Notification;

class FinalSettlementPendingNotification extends Notification
{
    public function __construct(public Employee $employee) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'    => 'final_settlement_pending',
            'title'   => 'F&F Required — Employee Separated',
            'message' => ($this->employee->name ?? 'Employee') . ' (' . ($this->employee->employee_code ?? '') . ') is '
                . ucfirst($this->employee->status) . '. Create final settlement.',
            'url'     => route('admin.hrm.finance.final-settlement.create', ['employee_id' => $this->employee->id]),
        ];
    }
}
