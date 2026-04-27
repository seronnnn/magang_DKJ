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
     * Send a reminder email for a SINGLE invoice (used by the single-row action button).
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

    public function whatsappLink(Request $request, int $invoiceId)
    {
        $collector = $this->getCollector();
        if (!$collector) abort(403);

        $invoice = $this->fetchInvoiceForCollector($invoiceId, $collector->id);

        if (!$invoice) {
            return response()->json(['success' => false, 'message' => 'Invoice not found.'], 404);
        }

        if (empty($invoice->whatsapp_number)) {
            return response()->json(['success' => false, 'message' => 'No WhatsApp number on record for this customer.'], 422);
        }

        $dueDate        = \Carbon\Carbon::parse($invoice->due_date)->format('d F Y');
        $amount         = 'Rp ' . number_format($invoice->total_ar, 0, ',', '.');
        $pic            = $invoice->pic_name ? "Dear {$invoice->pic_name}" : "Dear Finance Team";
        $company        = config('app.name', 'PT. Dunia Kimia Jaya');
        $collector_name = $collector->name;

        $message = <<<MSG
        {$pic},

        Greetings from {$company}.

        We would like to remind you that the invoice for *{$invoice->customer_name}* (Invoice #{$invoice->invoice_id}) amounting to *{$amount}* is due on *{$dueDate}*.

        Please process payment before the due date to avoid any delays.

        If payment has already been made, kindly confirm with us.

        Thank you for your cooperation.

        Best regards,
        {$collector_name}
        {$company}
        MSG;

        $phone = preg_replace('/\D/', '', $invoice->whatsapp_number);
        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        }

        $url = 'https://wa.me/' . $phone . '?text=' . rawurlencode(trim($message));

        return response()->json(['success' => true, 'url' => $url, 'phone' => $phone]);
    }

    /**
     * Bulk send — groups invoice IDs by customer and sends ONE email per customer
     * containing all selected invoices for that customer.
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
                    // Pass ALL invoices for this customer as an array
                    Mail::to($first->email)->send(
                        new ArReminderMail($custInvoices->values()->all(), $collector->name)
                    );
                    $sent += $custInvoices->count();
                } catch (\Throwable) {
                    $skipped += $custInvoices->count();
                }
            }
        } else {
            // WhatsApp: one link per invoice (WA is inherently per-conversation)
            foreach ($invoices as $invoice) {
                if (!empty($invoice->whatsapp_number)) {
                    $dueDate  = \Carbon\Carbon::parse($invoice->due_date)->format('d F Y');
                    $amount   = 'Rp ' . number_format($invoice->total_ar, 0, ',', '.');
                    $pic      = $invoice->pic_name ? "Dear {$invoice->pic_name}" : "Dear Finance Team";
                    $company  = config('app.name', 'PT. Dunia Kimia Jaya');
                    $message  = "{$pic},\n\nReminder: Invoice *#{$invoice->invoice_id}* for *{$invoice->customer_name}* amounting to *{$amount}* is due on *{$dueDate}*.\n\nPlease process payment promptly.\n\nBest regards,\n{$collector->name} – {$company}";
                    $phone    = preg_replace('/\D/', '', $invoice->whatsapp_number);
                    if (str_starts_with($phone, '0')) $phone = '62' . substr($phone, 1);
                    $waLinks[] = [
                        'customer' => $invoice->customer_name,
                        'url'      => 'https://wa.me/' . $phone . '?text=' . rawurlencode($message),
                    ];
                    $sent++;
                } else {
                    $skipped++;
                }
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

    // ── Helpers ─────────────────────────────────────────────────────────────

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