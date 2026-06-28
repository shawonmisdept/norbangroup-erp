<?php

namespace App\Notifications;

use App\Models\Tms\TmsTransportRequest;
use Illuminate\Notifications\Notification;

class TmsRequestSubmittedNotification extends Notification
{
    public function __construct(public TmsTransportRequest $request) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $employee = $this->request->employee;

        return [
            'type'    => 'tms_request_submitted',
            'title'   => 'Transport Request Submitted',
            'message' => ($employee?->name ?? 'Employee') . ' submitted a transport request for '
                . $this->request->pickup_at->format('d M Y H:i'),
            'url'     => route('admin.tms.requests.show', $this->request),
        ];
    }
}
