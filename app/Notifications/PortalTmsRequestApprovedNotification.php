<?php

namespace App\Notifications;

use App\Models\Tms\TmsTransportRequest;
use App\Notifications\Concerns\DeliversEmployeeWebPush;
use Illuminate\Notifications\Notification;

class PortalTmsRequestApprovedNotification extends Notification
{
    use DeliversEmployeeWebPush;

    public function __construct(public TmsTransportRequest $request) {}
    public function toDatabase(object $notifiable): array
    {
        return [
            'type'    => 'tms_request_approved',
            'title'   => 'Transport Request Approved',
            'message' => 'Your transport request for ' . $this->request->pickup_at->format('d M Y H:i') . ' has been approved.',
            'url'     => route('employee.transport.requests.show', $this->request),
        ];
    }
}
