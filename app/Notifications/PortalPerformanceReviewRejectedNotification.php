<?php

namespace App\Notifications;

use App\Models\Hrm\PerformanceReview;
use App\Notifications\Concerns\DeliversEmployeeWebPush;
use Illuminate\Notifications\Notification;

class PortalPerformanceReviewRejectedNotification extends Notification
{
    use DeliversEmployeeWebPush;

    public function __construct(public PerformanceReview $review) {}
    public function toDatabase(object $notifiable): array
    {
        return [
            'type'    => 'performance_rejected',
            'title'   => 'Performance Review Rejected',
            'message' => $this->review->cycleTypeLabel() . ' review was not approved by HR',
            'url'     => route('employee.performance.show', $this->review),
        ];
    }
}
