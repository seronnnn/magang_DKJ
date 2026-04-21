<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ArReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public object $invoice;
    public string $collectorName;

    public function __construct(object $invoice, string $collectorName)
    {
        $this->invoice       = $invoice;
        $this->collectorName = $collectorName;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Pengingat Jatuh Tempo Invoice #' . $this->invoice->invoice_id . ' – ' . $this->invoice->customer_name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.ar-reminder',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}