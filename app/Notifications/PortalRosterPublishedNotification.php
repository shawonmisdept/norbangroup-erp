<?php

namespace App\Notifications;

use App\Models\Hrm\ShiftRoster;
use App\Notifications\Concerns\DeliversEmployeeWebPush;
use Illuminate\Notifications\Notification;

class PortalRosterPublishedNotification extends Notification
{
    use DeliversEmployeeWebPush;

    public function __construct(public ShiftRoster $roster) {}
    public function toDatabase(object $notifiable): array
    {
        $this->roster->loadMissing('factory');

        return [
            'type'    => 'roster_published',
            'title'   => 'Shift Roster Published',
            'message' => 'Your shift roster for ' . $this->roster->start_date->format('d M') . ' – ' . $this->roster->end_date->format('d M Y') . ' is now available.',
            'url'     => route('employee.roster'),
        ];
    }
}
