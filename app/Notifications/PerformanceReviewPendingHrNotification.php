<?php

namespace App\Notifications;

use App\Models\Hrm\PerformanceReview;
use Illuminate\Notifications\Notification;

class PerformanceReviewPendingHrNotification extends Notification
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
            'type'    => 'hrm_performance_pending_hr',
            'title'   => 'Performance Review Pending HR',
            'message' => ($employee?->name ?? 'Employee') . ' — ' . $this->review->cycleTypeLabel()
                . ' rated at ' . number_format((float) $this->review->overall_score, 1) . '% — awaiting HR approval',
            'url'     => route('admin.hrm.performance.reviews.show', $this->review),
        ];
    }
}
