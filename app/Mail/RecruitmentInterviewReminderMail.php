<?php

namespace App\Mail;

use App\Models\Hrm\RecruitmentInterview;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RecruitmentInterviewReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public RecruitmentInterview $interview) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Interview Reminder — ' . ($this->interview->application?->application_no ?? 'Application'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.recruitment-interview-reminder',
        );
    }
}
