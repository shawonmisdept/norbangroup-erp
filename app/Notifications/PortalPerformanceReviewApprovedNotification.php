<?php

namespace App\Notifications;

use App\Models\Hrm\PerformanceReview;
use App\Notifications\Concerns\DeliversEmployeeWebPush;
use Illuminate\Notifications\Notification;

class PortalPerformanceReviewApprovedNotification extends Notification
{
    use DeliversEmployeeWebPush;

    public function __construct(public PerformanceReview $review) {}
    public function toDatabase(object $notifiable): array
    {
        $score = $this->review->overall_score !== null
            ? number_format((float) $this->review->overall_score, 1) . '%'
            : '—';

        return [
            'type'    => 'performance_approved',
            'title'   => 'Performance Review Approved',
            'message' => $this->review->cycleTypeLabel() . ' review approved — overall score ' . $score,
            'url'     => route('employee.performance.show', $this->review),
        ];
    }
}
