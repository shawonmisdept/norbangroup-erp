<?php

namespace App\Mail;

use App\Models\Hrm\RecruitmentApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RecruitmentStatusUpdatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public RecruitmentApplication $application,
        public string $statusLabel,
        public ?string $notes = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Application Update — ' . $this->application->application_no,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.recruitment-status-updated',
        );
    }
}
