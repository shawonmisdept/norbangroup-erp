<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StatusUpdatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order, public string $previousStatus) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Status Update: ' . $this->order->ref_code . ' — ' . $this->order->status,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.status-updated',
        );
    }
}
