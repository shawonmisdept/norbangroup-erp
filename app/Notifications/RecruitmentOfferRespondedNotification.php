<?php

namespace App\Notifications;

use App\Models\Hrm\RecruitmentApplication;
use App\Models\Hrm\RecruitmentOfferLetter;
use Illuminate\Notifications\Notification;

class RecruitmentOfferRespondedNotification extends Notification
{
    public function __construct(
        public RecruitmentApplication $application,
        public RecruitmentOfferLetter $offerLetter,
        public string $response,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $this->application->loadMissing(['jobPosting']);

        $accepted = $this->response === 'accepted';

        return [
            'type'    => 'recruitment_offer_response',
            'title'   => $accepted ? 'Offer accepted' : 'Offer declined',
            'message' => ($this->application->name ?? 'Candidate') . ' '
                . ($accepted ? 'accepted' : 'declined') . ' offer '
                . $this->offerLetter->reference_no,
            'url'     => route('admin.hrm.recruitment.applications.show', $this->application),
        ];
    }
}
