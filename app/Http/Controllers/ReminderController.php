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
    /**
     * Only collectors can access this page.
     */
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!Auth::check() || !Auth::user()->isCollector()) {
                abort(403, 'Access denied. Collector role required.');
            }
            return $next($request);
        });
    }

    /**
     * Simulated "today" for demo purposes.
     * Pretend it is 1 December 2024.
     */
    private function simulatedNow(): \Carbon\Carbon
    {
        return \Carbon\Carbon::create(2024, 12, 1, 0, 0, 0);
    }

    /**
     * Resolve the collector record for the current authenticated user.
     */
    private function getCollector(): ?Collector
    {
        return Collector::where('user_id', Auth::id())->first();
    }

    /**
     * Main reminder page.
     *
     * Filter tabs:
     *   all     → all invoices
     *   unpaid  → "Belum Lunas" = invoices past their due date (overdue)
     *   overdue → "Ongoing"     = invoices not yet past their due date (upcoming)
     */
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

        // Base query: ALL invoices for this collector
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
            // "Belum Lunas" tab = invoices that have already passed their due date (overdue)
            $query->where('inv.due_date', '<', $now->toDateString());
        } elseif ($filterStatus === 'overdue') {
            // "Ongoing" tab = invoices that are not yet past their due date (upcoming)
            $query->where('inv.due_date', '>=', $now->toDateString());
        }

        $invoices = collect($query->get())->map(function ($row) use ($now) {
            $dueDate  = \Carbon\Carbon::parse($row->due_date);
            $daysLeft = (int) $now->diffInDays($dueDate, false);

            $row->days_left  = $daysLeft;
            $row->is_overdue = $daysLeft < 0;
            $row->is_urgent  = $daysLeft >= 0 && $daysLeft <= 7;

            // Keep detailed urgency_class for internal use (bulk modal filtering etc.)
            $row->urgency_label = match(true) {
                $daysLeft < 0    => 'Lewat Jatuh Tempo',
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
     * Send an email reminder to a specific customer's invoice.
     */
    public function sendEmail(Request $request, int $invoiceId)
    {
        $collector = $this->getCollector();
        if (!$collector) abort(403);

        $invoice = DB::table('invoice as inv')
            ->join('customers as c',    'c.id',   '=', 'inv.customer_id')
            ->join('collectors as col', 'col.id', '=', 'c.collector_id')
            ->leftJoin('ar_records as r', 'r.invoice_id', '=', 'inv.id')
            ->where('inv.id', $invoiceId)
            ->where('col.id', $collector->id)
            ->select([
                'inv.id as invoice_id', 'inv.due_date',
                'c.customer_name', 'c.email', 'c.pic_name',
                DB::raw('COALESCE(r.total_ar, 0) as total_ar'),
                DB::raw('COALESCE(r.ar_actual, 0) as ar_actual'),
            ])
            ->first();

        if (!$invoice) {
            return response()->json(['success' => false, 'message' => 'Invoice not found or access denied.'], 404);
        }

        if (empty($invoice->email)) {
            return response()->json(['success' => false, 'message' => 'No email address on record for this customer.'], 422);
        }

        try {
            Mail::to($invoice->email)->send(new ArReminderMail($invoice, $collector->name));
            return response()->json(['success' => true, 'message' => 'Email reminder sent to ' . $invoice->email]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Failed to send email: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Build a WhatsApp deep-link URL for a reminder message.
     */
    public function whatsappLink(Request $request, int $invoiceId)
    {
        $collector = $this->getCollector();
        if (!$collector) abort(403);

        $invoice = DB::table('invoice as inv')
            ->join('customers as c',    'c.id',   '=', 'inv.customer_id')
            ->join('collectors as col', 'col.id', '=', 'c.collector_id')
            ->leftJoin('ar_records as r', 'r.invoice_id', '=', 'inv.id')
            ->where('inv.id', $invoiceId)
            ->where('col.id', $collector->id)
            ->select([
                'inv.id as invoice_id', 'inv.due_date',
                'c.customer_name', 'c.whatsapp_number', 'c.pic_name',
                DB::raw('COALESCE(r.total_ar, 0) as total_ar'),
                DB::raw('COALESCE(r.ar_actual, 0) as ar_actual'),
            ])
            ->first();

        if (!$invoice) {
            return response()->json(['success' => false, 'message' => 'Invoice not found.'], 404);
        }

        if (empty($invoice->whatsapp_number)) {
            return response()->json(['success' => false, 'message' => 'No WhatsApp number on record for this customer.'], 422);
        }

        $dueDate        = \Carbon\Carbon::parse($invoice->due_date)->format('d F Y');
        $amount         = 'Rp ' . number_format($invoice->total_ar, 0, ',', '.');
        $pic            = $invoice->pic_name ? "Yth. {$invoice->pic_name}" : "Yth. Tim Finance";
        $company        = config('app.name', 'PT. Dunia Kimia Jaya');
        $collector_name = $collector->name;

        $message = <<<MSG
        {$pic},

        Salam hormat dari {$company}.

        Kami ingin mengingatkan bahwa invoice Anda atas nama *{$invoice->customer_name}* (Inv. #{$invoice->invoice_id}) senilai *{$amount}* akan jatuh tempo pada *{$dueDate}*.

        Mohon segera melakukan pembayaran sebelum tanggal jatuh tempo untuk menghindari keterlambatan.

        Apabila pembayaran sudah dilakukan, mohon konfirmasi kepada kami.

        Terima kasih atas kerjasamanya.

        Hormat kami,
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
     * Bulk send reminders (email + WhatsApp link) for selected invoices.
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

        $invoices = DB::table('invoice as inv')
            ->join('customers as c',    'c.id',   '=', 'inv.customer_id')
            ->join('collectors as col', 'col.id', '=', 'c.collector_id')
            ->leftJoin('ar_records as r', 'r.invoice_id', '=', 'inv.id')
            ->whereIn('inv.id', $invoiceIds)
            ->where('col.id', $collector->id)
            ->select([
                'inv.id as invoice_id', 'inv.due_date',
                'c.customer_name', 'c.email', 'c.whatsapp_number', 'c.pic_name',
                DB::raw('COALESCE(r.total_ar, 0) as total_ar'),
                DB::raw('COALESCE(r.ar_actual, 0) as ar_actual'),
            ])
            ->get();

        $sent    = 0;
        $skipped = 0;
        $waLinks = [];

        foreach ($invoices as $invoice) {
            if ($type === 'email') {
                if (!empty($invoice->email)) {
                    try {
                        Mail::to($invoice->email)->send(new ArReminderMail($invoice, $collector->name));
                        $sent++;
                    } catch (\Throwable) {
                        $skipped++;
                    }
                } else {
                    $skipped++;
                }
            } else {
                if (!empty($invoice->whatsapp_number)) {
                    $dueDate  = \Carbon\Carbon::parse($invoice->due_date)->format('d F Y');
                    $amount   = 'Rp ' . number_format($invoice->total_ar, 0, ',', '.');
                    $pic      = $invoice->pic_name ? "Yth. {$invoice->pic_name}" : "Yth. Tim Finance";
                    $company  = config('app.name', 'PT. Dunia Kimia Jaya');
                    $message  = "{$pic},\n\nKami mengingatkan invoice *#{$invoice->invoice_id}* atas nama *{$invoice->customer_name}* senilai *{$amount}* jatuh tempo pada *{$dueDate}*.\n\nMohon segera lakukan pembayaran.\n\nTerima kasih,\n{$collector->name} – {$company}";
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
            'success' => true,
            'sent'    => $sent,
            'skipped' => $skipped,
            'type'    => $type,
            'wa_links'=> $waLinks,
        ]);
    }
}