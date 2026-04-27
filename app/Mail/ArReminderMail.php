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

    public object $invoice;          // kept for backward-compat (first invoice)
    public array  $invoices;         // all invoices to show in the email
    public string $collectorName;
    public float  $totalAR;
    public float  $totalPaid;
    public float  $totalRemaining;

    /**
     * Map collector name (case-insensitive) → sender Gmail address.
     */
    private const COLLECTOR_EMAILS = [
        'miya'  => 'testing_miya@gmail.com',
        'mega'  => 'testing_mega@gmail.com',
        'risa'  => 'testing_risa@gmail.com',
        'viona' => 'testing_viona@gmail.com',
    ];

    /**
     * @param object|array $invoiceOrInvoices  Single invoice object OR array of invoice objects
     * @param string       $collectorName
     */
    public function __construct(object|array $invoiceOrInvoices, string $collectorName)
    {
        $this->collectorName = $collectorName;

        if (is_array($invoiceOrInvoices)) {
            $this->invoices = $invoiceOrInvoices;
            $this->invoice  = $invoiceOrInvoices[0];   // first invoice for envelope subject
        } else {
            $this->invoices = [$invoiceOrInvoices];
            $this->invoice  = $invoiceOrInvoices;
        }

        // Pre-compute totals
        $this->totalAR        = array_sum(array_column($this->invoices, 'total_ar'));
        $this->totalPaid      = array_sum(array_column($this->invoices, 'ar_actual'));
        $this->totalRemaining = max(0, $this->totalAR - $this->totalPaid);
    }

    private function senderEmail(): string
    {
        $key = strtolower(trim($this->collectorName));
        if (isset(self::COLLECTOR_EMAILS[$key])) {
            return self::COLLECTOR_EMAILS[$key];
        }
        foreach (self::COLLECTOR_EMAILS as $name => $email) {
            if (str_contains($key, $name)) {
                return $email;
            }
        }
        return config('mail.from.address');
    }

    public function envelope(): Envelope
    {
        $count   = count($this->invoices);
        $subject = $count > 1
            ? "Invoice Due Date Reminder ({$count} Invoices) – {$this->invoice->customer_name}"
            : "Invoice Due Date Reminder #{$this->invoice->invoice_id} – {$this->invoice->customer_name}";

        return new Envelope(
            from: new Address(
                $this->senderEmail(),
                $this->collectorName . ' – PT. Dunia Kimia Jaya'
            ),
            subject: $subject,
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