<?php

namespace App\Notifications;

use App\Models\Hrm\RecruitmentApplication;
use Illuminate\Notifications\Notification;

class RecruitmentApplicationSubmittedNotification extends Notification
{
    public function __construct(public RecruitmentApplication $application) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $this->application->loadMissing(['jobPosting']);

        return [
            'type'    => 'recruitment_application',
            'title'   => 'New job application',
            'message' => ($this->application->name ?? 'Candidate') . ' — ' . ($this->application->jobPosting?->title ?? 'Position'),
            'url'     => route('admin.hrm.recruitment.applications.show', $this->application),
        ];
    }
}
