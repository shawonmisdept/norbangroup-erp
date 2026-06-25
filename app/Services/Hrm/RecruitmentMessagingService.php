<?php

namespace App\Services\Hrm;

use App\Contracts\SmsGateway;
use App\Mail\RecruitmentApplicationReceivedMail;
use App\Mail\RecruitmentInterviewReminderMail;
use App\Mail\RecruitmentInterviewScheduledMail;
use App\Mail\RecruitmentStatusUpdatedMail;
use App\Models\AppSetting;
use App\Models\Hrm\RecruitmentApplication;
use App\Models\Hrm\RecruitmentInterview;
use Illuminate\Support\Facades\Mail;

class RecruitmentMessagingService
{
    public function __construct(private SmsGateway $sms) {}

    public function applicationReceived(RecruitmentApplication $application): void
    {
        $application->loadMissing(['jobPosting', 'factory']);

        if ($this->emailEnabled() && $application->email) {
            $this->sendEmail($application->email, new RecruitmentApplicationReceivedMail($application));
        }

        if ($this->smsEnabled()) {
            $this->sendSms($application->phone, $this->template('application_received', [
                'no'  => $application->application_no,
                'job' => $application->jobPosting?->title ?? 'position',
                'url' => route('careers.track'),
            ]));
        }
    }

    public function statusUpdated(
        RecruitmentApplication $application,
        string $fromStatus,
        ?string $notes = null,
    ): void {
        if ($fromStatus === $application->status) {
            return;
        }

        $application->loadMissing(['jobPosting', 'factory']);
        $statusLabel = $application->statusLabel();

        if ($this->emailEnabled() && $application->email) {
            $this->sendEmail(
                $application->email,
                new RecruitmentStatusUpdatedMail($application, $statusLabel, $notes)
            );
        }

        if ($this->smsEnabled()) {
            $this->sendSms($application->phone, $this->template('status_updated', [
                'no'     => $application->application_no,
                'status' => $statusLabel,
            ]));
        }
    }

    public function interviewScheduled(RecruitmentInterview $interview): void
    {
        $interview->loadMissing(['application.jobPosting', 'application.factory']);
        $application = $interview->application;

        if (! $application) {
            return;
        }

        if ($this->emailEnabled() && $application->email) {
            $this->sendEmail($application->email, new RecruitmentInterviewScheduledMail($interview));
        }

        if ($this->smsEnabled()) {
            $this->sendSms($application->phone, $this->template('interview_scheduled', [
                'no'       => $application->application_no,
                'job'      => $application->jobPosting?->title ?? 'position',
                'date'     => $interview->scheduled_at->format('d M Y, h:i A'),
                'location' => $interview->location ?: 'HR office',
            ]));
        }
    }

    public function interviewReminder(RecruitmentInterview $interview): void
    {
        $interview->loadMissing(['application.jobPosting', 'application.factory']);
        $application = $interview->application;

        if (! $application) {
            return;
        }

        if ($this->emailEnabled() && $application->email) {
            $this->sendEmail($application->email, new RecruitmentInterviewReminderMail($interview));
        }

        if ($this->smsEnabled()) {
            $this->sendSms($application->phone, $this->template('interview_reminder', [
                'no'       => $application->application_no,
                'job'      => $application->jobPosting?->title ?? 'position',
                'date'     => $interview->scheduled_at->format('d M Y, h:i A'),
                'location' => $interview->location ?: 'HR office',
            ]));
        }
    }

    public function sendOtp(string $phone, string $otp): void
    {
        $message = $this->template('otp', [
            'app' => config('portal.name', config('app.name')),
            'otp' => $otp,
        ]);

        $this->sms->send($phone, $message);
    }

    /** @param  array<string, string>  $replacements */
    private function template(string $key, array $replacements): string
    {
        $message = config("recruitment.messages.{$key}", '');

        foreach ($replacements as $name => $value) {
            $message = str_replace(':' . $name, $value, $message);
        }

        return $message;
    }

    private function emailEnabled(): bool
    {
        $settings = AppSetting::current();

        return $settings->notify_mail_hrm_recruitment_candidate && $settings->canSendMail();
    }

    private function smsEnabled(): bool
    {
        return AppSetting::current()->notify_sms_hrm_recruitment;
    }

    private function sendEmail(string $email, $mailable): void
    {
        try {
            Mail::to($email)->send($mailable);
        } catch (\Throwable) {
            // mail failures should not block workflows
        }
    }

    private function sendSms(string $phone, string $message): void
    {
        try {
            $this->sms->send($phone, $message);
        } catch (\Throwable) {
            // sms failures should not block workflows
        }
    }
}
