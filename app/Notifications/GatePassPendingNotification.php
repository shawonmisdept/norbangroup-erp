<?php

namespace App\Notifications;

use App\Models\Hrm\GatePass;
use Illuminate\Notifications\Notification;

class GatePassPendingNotification extends Notification
{
    public function __construct(public GatePass $gatePass) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $this->gatePass->loadMissing('employee');

        return [
            'type'    => 'gate_pass_pending',
            'title'   => 'Gate Pass Pending',
            'message' => ($this->gatePass->employee?->name ?? 'Employee') . ' gate pass for ' . $this->gatePass->pass_date?->format('d M Y'),
            'url'     => route('admin.hrm.rmg.gate-pass.index'),
        ];
    }
}
