<?php

namespace App\Mail;

use App\Models\Hrm\PayrollItem;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PayslipReadyMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public PayrollItem $payslip) {}

    public function envelope(): Envelope
    {
        $label = $this->payslip->period?->periodLabel() ?? 'Payroll';

        return new Envelope(
            subject: 'Payslip Ready — ' . $label,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.payslip-ready',
        );
    }
}
