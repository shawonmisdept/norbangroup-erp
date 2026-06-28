<?php

namespace App\Notifications;

use App\Models\Hrm\PerformanceReview;
use Illuminate\Notifications\Notification;

class PerformanceReviewPendingRatingNotification extends Notification
{
    public function __construct(public PerformanceReview $review) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $employee = $this->review->employee;

        return [
            'type'    => 'hrm_performance_pending_rating',
            'title'   => 'Performance Review Pending Rating',
            'message' => ($employee?->name ?? 'Employee') . ' — ' . $this->review->cycleTypeLabel()
                . ' review needs reporting person rating',
            'url'     => route('admin.hrm.performance.reviews.show', $this->review),
        ];
    }
}
