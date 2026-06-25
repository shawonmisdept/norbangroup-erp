<?php

namespace App\Mail;

use App\Models\Hrm\RecruitmentApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RecruitmentApplicationReceivedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public RecruitmentApplication $application) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Application Received — ' . $this->application->application_no,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.recruitment-application-received',
        );
    }
}
