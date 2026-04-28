<?php

namespace App\Http\Controllers;

use App\Models\Collector;
use App\Models\ArPeriod;
use App\Mail\ArReminderMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class ReminderController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!Auth::check() || !Auth::user()->isCollector()) {
                abort(403, 'Access denied. Collector role required.');
            }
            return $next($request);
        });
    }

    private function simulatedNow(): \Carbon\Carbon
    {
        return \Carbon\Carbon::create(2024, 12, 1, 0, 0, 0);
    }

    private function getCollector(): ?Collector
    {
        return Collector::where('user_id', Auth::id())->first();
    }

    public function index(Request $request)
    {
        $collector = $this->getCollector();

        if (!$collector) {
            return view('dashboard.reminder', [
                'invoices'       => collect(),
                'collectorName'  => Auth::user()->name,
                'totalDue'       => 0,
                'totalCustomers' => 0,
                'urgentCount'    => 0,
                'filterStatus'   => $request->input('status', 'all'),
            ]);
        }

        $query = DB::table('invoice as inv')
            ->join('customers as c',    'c.id',          '=', 'inv.customer_id')
            ->join('collectors as col', 'col.id',        '=', 'c.collector_id')
            ->join('plants as p',       'p.customer_id', '=', 'c.id')
            ->leftJoin('ar_records as r', function ($j) {
                $j->on('r.invoice_id', '=', 'inv.id');
            })
            ->where('col.id', $collector->id)
            ->select([
                'inv.id                  as invoice_id',
                'c.customer_id           as customer_code',
                'c.customer_name',
                'c.email',
                'c.whatsapp_number',
                'c.pic_name',
                'p.code                  as plant',
                'inv.due_date',
                'inv.baseline_date',
                'inv.currency_type',
                DB::raw('COALESCE(r.total_ar, 0)   as total_ar'),
                DB::raw('COALESCE(r.ar_actual, 0)  as ar_actual'),
                DB::raw('COALESCE(r.ar_target, 0)  as ar_target'),
                DB::raw('COALESCE(r.amount_over_90_days, 0) as overdue_90'),
                DB::raw('COALESCE(r.amount_60_90_days, 0)   as overdue_60'),
                'r.id as ar_record_id',
            ])
            ->orderBy('inv.due_date');

        $filterStatus = $request->input('status', 'all');
        $now          = $this->simulatedNow();

        if ($filterStatus === 'unpaid') {
            $query->where('inv.due_date', '<', $now->toDateString());
        } elseif ($filterStatus === 'overdue') {
            $query->where('inv.due_date', '>=', $now->toDateString());
        }

        $invoices = collect($query->get())->map(function ($row) use ($now) {
            $dueDate  = \Carbon\Carbon::parse($row->due_date);
            $daysLeft = (int) $now->diffInDays($dueDate, false);

            $row->days_left  = $daysLeft;
            $row->is_overdue = $daysLeft < 0;
            $row->is_urgent  = $daysLeft >= 0 && $daysLeft <= 7;

            $row->urgency_label = match(true) {
                $daysLeft < 0    => 'Overdue',
                $daysLeft <= 3   => 'Critical',
                $daysLeft <= 7   => 'Urgent',
                $daysLeft <= 14  => 'Soon',
                default          => 'Upcoming',
            };
            $row->urgency_class = match(true) {
                $daysLeft < 0    => 'urgency-overdue',
                $daysLeft <= 3   => 'urgency-critical',
                $daysLeft <= 7   => 'urgency-urgent',
                $daysLeft <= 14  => 'urgency-soon',
                default          => 'urgency-upcoming',
            };
            $row->collection_gap = max(0, ($row->ar_target ?? 0) - ($row->ar_actual ?? 0));
            return $row;
        });

        return view('dashboard.reminder', [
            'invoices'       => $invoices,
            'collectorName'  => $collector->name,
            'totalDue'       => $invoices->sum('total_ar'),
            'totalCustomers' => $invoices->pluck('customer_code')->unique()->count(),
            'urgentCount'    => $invoices->filter(fn($i) => $i->is_urgent || $i->is_overdue)->count(),
            'filterStatus'   => $filterStatus,
            'periods'        => collect(),
        ]);
    }

    /**
     * Send a reminder email for a SINGLE invoice.
     */
    public function sendEmail(Request $request, int $invoiceId)
    {
        $collector = $this->getCollector();
        if (!$collector) abort(403);

        $invoice = $this->fetchInvoiceForCollector($invoiceId, $collector->id);

        if (!$invoice) {
            return response()->json(['success' => false, 'message' => 'Invoice not found or access denied.'], 404);
        }

        if (empty($invoice->email)) {
            return response()->json(['success' => false, 'message' => 'No email address on record for this customer.'], 422);
        }

        try {
            Mail::to($invoice->email)->send(new ArReminderMail($invoice, $collector->name));
            return response()->json(['success' => true, 'message' => 'Reminder email sent to ' . $invoice->email]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Failed to send email: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Generate a WhatsApp link for one OR multiple invoices.
     *
     * For a single invoice, the message is the same as before.
     * For multiple invoices (passed via invoice_ids[] query param),
     * the message consolidates them into a numbered list.
     */
    public function whatsappLink(Request $request, int $invoiceId)
    {
        $collector = $this->getCollector();
        if (!$collector) abort(403);

        // Check if additional invoice IDs were passed (for multi-invoice WA from the modal)
        $extraIds = $request->input('invoice_ids', []);

        if (!empty($extraIds) && count($extraIds) > 1) {
            // Multi-invoice: use bulkRemind logic but targeted
            return $this->buildWhatsAppBulkResponse($extraIds, $collector);
        }

        // Single invoice
        $invoice = $this->fetchInvoiceForCollector($invoiceId, $collector->id);

        if (!$invoice) {
            return response()->json(['success' => false, 'message' => 'Invoice not found.'], 404);
        }

        if (empty($invoice->whatsapp_number)) {
            return response()->json(['success' => false, 'message' => 'No WhatsApp number on record for this customer.'], 422);
        }

        $url = $this->buildSingleInvoiceWaUrl($invoice, $collector->name);

        return response()->json(['success' => true, 'url' => $url, 'phone' => $this->normalisePhone($invoice->whatsapp_number)]);
    }

    /**
     * Bulk send — groups invoice IDs by customer and sends ONE email per customer
     * OR generates ONE WhatsApp message per customer with all invoices listed.
     */
    public function bulkRemind(Request $request)
    {
        $collector = $this->getCollector();
        if (!$collector) abort(403);

        $type       = $request->input('type', 'email');
        $invoiceIds = $request->input('invoice_ids', []);

        if (empty($invoiceIds)) {
            return response()->json(['success' => false, 'message' => 'No invoices selected.'], 422);
        }

        // Fetch all requested invoices that belong to this collector
        $invoices = DB::table('invoice as inv')
            ->join('customers as c',    'c.id',   '=', 'inv.customer_id')
            ->join('collectors as col', 'col.id', '=', 'c.collector_id')
            ->leftJoin('ar_records as r', 'r.invoice_id', '=', 'inv.id')
            ->whereIn('inv.id', $invoiceIds)
            ->where('col.id', $collector->id)
            ->select([
                'inv.id as invoice_id', 'inv.due_date',
                'c.customer_name', 'c.email', 'c.whatsapp_number', 'c.pic_name',
                'c.customer_id as customer_code',
                DB::raw('COALESCE(r.total_ar, 0) as total_ar'),
                DB::raw('COALESCE(r.ar_actual, 0) as ar_actual'),
            ])
            ->get();

        $sent    = 0;
        $skipped = 0;
        $waLinks = [];

        if ($type === 'email') {
            // Group by customer and send ONE combined email per customer
            $byCustomer = $invoices->groupBy('customer_code');

            foreach ($byCustomer as $customerCode => $custInvoices) {
                $first = $custInvoices->first();

                if (empty($first->email)) {
                    $skipped += $custInvoices->count();
                    continue;
                }

                try {
                    Mail::to($first->email)->send(
                        new ArReminderMail($custInvoices->values()->all(), $collector->name)
                    );
                    $sent += $custInvoices->count();
                } catch (\Throwable) {
                    $skipped += $custInvoices->count();
                }
            }
        } else {
            // WhatsApp: ONE message per CUSTOMER containing ALL their selected invoices
            $byCustomer = $invoices->groupBy('customer_code');

            foreach ($byCustomer as $customerCode => $custInvoices) {
                $first = $custInvoices->first();

                if (empty($first->whatsapp_number)) {
                    $skipped += $custInvoices->count();
                    continue;
                }

                $url = $this->buildMultiInvoiceWaUrl($custInvoices->values()->all(), $collector->name);

                $waLinks[] = [
                    'customer' => $first->customer_name,
                    'url'      => $url,
                ];
                $sent += $custInvoices->count();
            }
        }

        return response()->json([
            'success'  => true,
            'sent'     => $sent,
            'skipped'  => $skipped,
            'type'     => $type,
            'wa_links' => $waLinks,
        ]);
    }

    // ── Private helpers ──────────────────────────────────────────────────────

    /**
     * Build a WA URL for a SINGLE invoice.
     */
    private function buildSingleInvoiceWaUrl(object $invoice, string $collectorName): string
    {
        $dueDate = \Carbon\Carbon::parse($invoice->due_date)->format('d F Y');
        $amount  = 'Rp ' . number_format($invoice->total_ar, 0, ',', '.');
        $pic     = $invoice->pic_name ? "Dear {$invoice->pic_name}" : "Dear Finance Team";
        $company = config('app.name', 'PT. Dunia Kimia Jaya');

        $message = <<<MSG
{$pic},

Greetings from {$company}.

We would like to remind you that the invoice for *{$invoice->customer_name}* (Invoice #{$invoice->invoice_id}) amounting to *{$amount}* is due on *{$dueDate}*.

Please process payment before the due date to avoid any delays.

If payment has already been made, kindly confirm with us.

Thank you for your cooperation.

Best regards,
{$collectorName}
{$company}
MSG;

        $phone = $this->normalisePhone($invoice->whatsapp_number);
        return 'https://wa.me/' . $phone . '?text=' . rawurlencode(trim($message));
    }

    /**
     * Build a WA URL for MULTIPLE invoices belonging to the SAME customer —
     * all consolidated into one message with a numbered list.
     */
    private function buildMultiInvoiceWaUrl(array $invoices, string $collectorName): string
    {
        $first   = $invoices[0];
        $pic     = $first->pic_name ? "Dear {$first->pic_name}" : "Dear Finance Team";
        $company = config('app.name', 'PT. Dunia Kimia Jaya');

        // Build the invoice list
        $invoiceLines = '';
        $grandTotal   = 0;
        foreach ($invoices as $i => $inv) {
            $num      = $i + 1;
            $dueDate  = \Carbon\Carbon::parse($inv->due_date)->format('d M Y');
            $amount   = 'Rp ' . number_format($inv->total_ar, 0, ',', '.');
            $paid     = $inv->ar_actual > 0 ? 'Rp ' . number_format($inv->ar_actual, 0, ',', '.') : '-';
            $sisa     = max(0, $inv->total_ar - $inv->ar_actual);
            $sisaFmt  = $sisa > 0 ? 'Rp ' . number_format($sisa, 0, ',', '.') : 'Settled ✓';
            $grandTotal += $sisa;

            $invoiceLines .= "{$num}. *Invoice #{$inv->invoice_id}*\n";
            $invoiceLines .= "   Due Date : {$dueDate}\n";
            $invoiceLines .= "   Amount   : {$amount}\n";
            $invoiceLines .= "   Paid     : {$paid}\n";
            $invoiceLines .= "   Balance  : {$sisaFmt}\n";
            if ($i < count($invoices) - 1) {
                $invoiceLines .= "\n";
            }
        }

        $totalOutstanding = $grandTotal > 0
            ? 'Rp ' . number_format($grandTotal, 0, ',', '.')
            : 'All settled ✓';

        $invoiceCount = count($invoices);
        $invoiceWord  = $invoiceCount === 1 ? 'invoice' : 'invoices';

        $message = <<<MSG
{$pic},

Greetings from {$company}.

We would like to remind you regarding *{$invoiceCount} outstanding {$invoiceWord}* for *{$first->customer_name}*:

{$invoiceLines}
*Total Outstanding: {$totalOutstanding}*

Please process payment for each invoice before its respective due date to avoid any delays.

If payment has already been made, kindly confirm with us.

Thank you for your cooperation.

Best regards,
{$collectorName}
{$company}
MSG;

        $phone = $this->normalisePhone($first->whatsapp_number);
        return 'https://wa.me/' . $phone . '?text=' . rawurlencode(trim($message));
    }

    /**
     * Build a WhatsApp bulk response for the modal's multi-select WA button.
     */
    private function buildWhatsAppBulkResponse(array $invoiceIds, Collector $collector): \Illuminate\Http\JsonResponse
    {
        $invoices = DB::table('invoice as inv')
            ->join('customers as c',    'c.id',   '=', 'inv.customer_id')
            ->join('collectors as col', 'col.id', '=', 'c.collector_id')
            ->leftJoin('ar_records as r', 'r.invoice_id', '=', 'inv.id')
            ->whereIn('inv.id', $invoiceIds)
            ->where('col.id', $collector->id)
            ->select([
                'inv.id as invoice_id', 'inv.due_date',
                'c.customer_name', 'c.whatsapp_number', 'c.pic_name',
                'c.customer_id as customer_code',
                DB::raw('COALESCE(r.total_ar, 0) as total_ar'),
                DB::raw('COALESCE(r.ar_actual, 0) as ar_actual'),
            ])
            ->get();

        if ($invoices->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'No matching invoices found.'], 404);
        }

        // All belong to same customer (modal context), group just in case
        $byCustomer = $invoices->groupBy('customer_code');
        $waLinks    = [];

        foreach ($byCustomer as $custInvoices) {
            $first = $custInvoices->first();
            if (empty($first->whatsapp_number)) continue;

            $url = count($custInvoices) === 1
                ? $this->buildSingleInvoiceWaUrl($first, $collector->name)
                : $this->buildMultiInvoiceWaUrl($custInvoices->values()->all(), $collector->name);

            $waLinks[] = [
                'customer' => $first->customer_name,
                'url'      => $url,
            ];
        }

        return response()->json([
            'success'  => true,
            'sent'     => $invoices->count(),
            'skipped'  => 0,
            'type'     => 'whatsapp',
            'wa_links' => $waLinks,
            // For single-WA modal: provide url and preview directly
            'url'      => $waLinks[0]['url'] ?? null,
        ]);
    }

    /**
     * Normalise a phone number to international format starting with country code.
     */
    private function normalisePhone(string $raw): string
    {
        $phone = preg_replace('/\D/', '', $raw);
        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        }
        return $phone;
    }

    private function fetchInvoiceForCollector(int $invoiceId, int $collectorId): ?object
    {
        return DB::table('invoice as inv')
            ->join('customers as c',    'c.id',   '=', 'inv.customer_id')
            ->join('collectors as col', 'col.id', '=', 'c.collector_id')
            ->leftJoin('ar_records as r', 'r.invoice_id', '=', 'inv.id')
            ->where('inv.id', $invoiceId)
            ->where('col.id', $collectorId)
            ->select([
                'inv.id as invoice_id', 'inv.due_date',
                'c.customer_name', 'c.email', 'c.whatsapp_number', 'c.pic_name',
                DB::raw('COALESCE(r.total_ar, 0) as total_ar'),
                DB::raw('COALESCE(r.ar_actual, 0) as ar_actual'),
            ])
            ->first();
    }
}