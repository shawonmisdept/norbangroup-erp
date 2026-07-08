<?php

namespace App\Notifications;

use App\Models\Hrm\WorkerTransfer;
use Illuminate\Notifications\Notification;

class WorkerTransferPendingNotification extends Notification
{
    public function __construct(public WorkerTransfer $transfer) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $this->transfer->loadMissing('employee');

        return [
            'type'    => 'worker_transfer_pending',
            'title'   => 'Worker Transfer Pending',
            'message' => ($this->transfer->employee?->name ?? 'Employee') . ' transfer pending approval — effective ' . $this->transfer->effective_date?->format('d M Y'),
            'url'     => route('admin.hrm.rmg.worker-transfer.index', ['status' => 'pending']),
        ];
    }
}
