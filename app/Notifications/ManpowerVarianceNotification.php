<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class ManpowerVarianceNotification extends Notification
{
    public function __construct(
        public int $factoryId,
        public string $planDate,
        public int $shortfallLines,
        public int $totalShortfall,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'    => 'manpower_variance',
            'title'   => 'Manpower Shortfall',
            'message' => "{$this->shortfallLines} line(s) below plan on {$this->planDate} — total shortfall {$this->totalShortfall} workers",
            'url'     => route('admin.hrm.rmg.manpower-planning.index', [
                'factory_id' => $this->factoryId,
                'plan_date'  => $this->planDate,
            ]),
        ];
    }
}
