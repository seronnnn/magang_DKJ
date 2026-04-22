<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ArReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public object $invoice;
    public string $collectorName;

    /**
     * Map collector name (case-insensitive) → sender Gmail address.
     * Add or edit entries here as needed.
     */
    private const COLLECTOR_EMAILS = [
        'miya'  => 'testing_miya@gmail.com',
        'mega'  => 'testing_mega@gmail.com',
        'risa'  => 'testing_risa@gmail.com',
        'viona' => 'testing_viona@gmail.com',
    ];

    public function __construct(object $invoice, string $collectorName)
    {
        $this->invoice       = $invoice;
        $this->collectorName = $collectorName;
    }

    /**
     * Resolve the sender email for the given collector name.
     * Falls back to the default MAIL_FROM_ADDRESS if no match is found.
     */
    private function senderEmail(): string
    {
        $key = strtolower(trim($this->collectorName));

        // Try exact key first
        if (isset(self::COLLECTOR_EMAILS[$key])) {
            return self::COLLECTOR_EMAILS[$key];
        }

        // Then check if the collector name *contains* a known key (e.g. "Miya Collector" → miya)
        foreach (self::COLLECTOR_EMAILS as $name => $email) {
            if (str_contains($key, $name)) {
                return $email;
            }
        }

        // Fallback to the default configured sender
        return config('mail.from.address');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(
                $this->senderEmail(),
                $this->collectorName . ' – PT. Dunia Kimia Jaya'
            ),
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