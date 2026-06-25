<?php

namespace App\Mail;

use App\Models\Hrm\LeaveApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LeaveStatusMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public LeaveApplication $application,
        public string $statusLabel,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Leave ' . $this->statusLabel . ' — ' . ($this->application->leaveType?->name ?? 'Leave'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.leave-status',
        );
    }
}
