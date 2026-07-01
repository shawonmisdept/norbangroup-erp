<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderQuoteMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Quotation: ' . $this->order->ref_code . ' — ' . $this->order->item_name,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.order-quote',
        );
    }
}
