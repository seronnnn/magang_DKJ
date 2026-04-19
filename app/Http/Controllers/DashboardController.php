<?php

namespace App\Http\Controllers;

use App\Models\ArPeriod;
use App\Models\ArRecord;
use App\Models\Customer;
use App\Models\Collector;
use App\Models\Invoice;
use App\Models\Plant;
use App\Models\SoOverlimit;
use App\Models\CollectionLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /* ────────────────────────────────────────────────────────────
     * Helpers
     * ──────────────────────────────────────────────────────────── */

    /** Return the active period or the latest one. */
    private function resolvePeriod(Request $request): ?ArPeriod
    {
        if ($request->filled('period_id')) {
            return ArPeriod::find($request->period_id);
        }
        return ArPeriod::orderByDesc('period_month')->first();
    }

    /**
     * Build a base query over ar_records joined with invoice/customer/collector/plant
     * filtered by period, plant code, and collector name.
     *
     * Returns a query builder that selects a "flat" result set similar to the old ar_data.
     */
    private function baseQuery(Request $request, ?ArPeriod $period = null)
    {
        $period = $period ?? $this->resolvePeriod($request);

        $q = DB::table('ar_records as r')
            ->join('invoice as inv',      'inv.id',          '=', 'r.invoice_id')
            ->join('customers as c',      'c.id',            '=', 'inv.customer_id')
            ->join('collectors as col',   'col.id',          '=', 'c.collector_id')
            ->join('plants as p',         'p.customer_id',   '=', 'c.id')
            ->leftJoin('so_overlimit as so', function ($j) use ($period) {
                $j->on('so.invoice_id', '=', 'r.invoice_id');
                if ($period) {
                    $j->where('so.period_id', '=', $period->id);
                }
            })
            ->select([
                'r.id',
                'r.invoice_id',
                'c.customer_id',
                'c.customer_name',
                'col.name as collection_by',
                'p.code as plant',
                'r.amount_current as current',
                'r.amount_1_30_days as days_1_30',
                'r.amount_30_60_days as days_30_60',
                'r.amount_60_90_days as days_60_90',
                'r.amount_over_90_days as days_over_90',
                'r.total_ar as total',
                'r.ar_target',
                'r.ar_actual',
                DB::raw('COALESCE(so.so_without_od, 0) as so_without_od'),
                DB::raw('COALESCE(so.so_with_od, 0) as so_with_od'),
                DB::raw('COALESCE(so.total_so, 0) as total_so'),
                'r.period_id',
            ]);

        if ($period) {
            $q->where('r.period_id', $period->id);
        }

        if ($request->filled('plant')) {
            $q->where('p.code', $request->plant);
        }

        if ($request->filled('collector')) {
            $q->where('col.name', $request->collector);
        }

        return $q;
    }

    /** Collectors list for filter dropdowns */
    private function collectors(): \Illuminate\Support\Collection
    {
        return Collector::orderBy('name')->pluck('name');
    }

    /** Plant codes list for filter dropdowns */
    private function plants(): \Illuminate\Support\Collection
    {
        return Plant::select('code')->distinct()->orderBy('code')->pluck('code');
    }

    /** Periods list for period selector */
    private function periods(): \Illuminate\Database\Eloquent\Collection
    {
        return ArPeriod::orderByDesc('period_month')->get();
    }

    /* ────────────────────────────────────────────────────────────
     * Pages
     * ──────────────────────────────────────────────────────────── */

    public function index(Request $request)
    {
        $period     = $this->resolvePeriod($request);
        $rows       = collect($this->baseQuery($request, $period)->get());

        $totalAR        = $rows->sum('total');
        $totalTarget    = $rows->sum('ar_target');
        $totalCollected = $rows->sum('ar_actual');
        $collectionRate = $totalTarget > 0 ? round($totalCollected / $totalTarget * 100, 1) : null;

        $aging = [
            'current'    => $rows->sum('current'),
            'days_1_30'  => $rows->sum('days_1_30'),
            'days_30_60' => $rows->sum('days_30_60'),
            'days_60_90' => $rows->sum('days_60_90'),
            'over_90'    => $rows->sum('days_over_90'),
        ];

        $totalSO          = $rows->sum('total_so');
        $soWithOD         = $rows->sum('so_with_od');
        $overdueCustomers = $rows->filter(fn($r) => ($r->days_60_90 + $r->days_over_90) > 0)->count();

        $byCollector = $rows->groupBy('collection_by')->map(fn($g) => [
            'target'    => $g->sum('ar_target'),
            'actual'    => $g->sum('ar_actual'),
            'customers' => $g->count(),
            'rate'      => $g->sum('ar_target') > 0
                ? round($g->sum('ar_actual') / $g->sum('ar_target') * 100, 1) : null,
        ]);

        $byPlant = $rows->groupBy('plant')->map(fn($g) => [
            'total'     => $g->sum('total'),
            'customers' => $g->count(),
        ]);

        $topCustomers = $rows->sortByDesc('total')->take(5);

        return view('dashboard.index', compact(
            'period', 'totalAR', 'totalTarget', 'totalCollected', 'collectionRate',
            'aging', 'totalSO', 'soWithOD', 'overdueCustomers',
            'byCollector', 'byPlant', 'topCustomers', 'rows'
        ) + [
            'plants'     => $this->plants(),
            'collectors' => $this->collectors(),
            'periods'    => $this->periods(),
        ]);
    }

    public function aging(Request $request)
    {
        $period  = $this->resolvePeriod($request);
        $rows    = collect($this->baseQuery($request, $period)->orderByDesc('total')->get());
        $buckets = [
            'current'    => $rows->sum('current'),
            'days_1_30'  => $rows->sum('days_1_30'),
            'days_30_60' => $rows->sum('days_30_60'),
            'days_60_90' => $rows->sum('days_60_90'),
            'over_90'    => $rows->sum('days_over_90'),
        ];

        return view('dashboard.aging', [
            'period'     => $period,
            'rows'       => $rows,
            'buckets'    => $buckets,
            'plants'     => $this->plants(),
            'collectors' => $this->collectors(),
            'periods'    => $this->periods(),
        ]);
    }

    public function collection(Request $request)
    {
        $period = $this->resolvePeriod($request);
        $rows   = collect($this->baseQuery($request, $period)->orderByDesc('ar_target')->get());

        // Attach computed fields (mirrors old Eloquent accessors)
        $rows = $rows->map(function ($r) {
            $r->collection_rate   = $r->ar_target > 0
                ? round($r->ar_actual / $r->ar_target * 100, 1) : null;
            $r->collection_status = match (true) {
                $r->collection_rate === null  => 'no-target',
                $r->collection_rate >= 100    => 'achieved',
                $r->collection_rate >= 70     => 'partial',
                default                       => 'none',
            };
            return $r;
        });

        $summary = [
            'target'    => $rows->sum('ar_target'),
            'actual'    => $rows->sum('ar_actual'),
            'rate'      => $rows->sum('ar_target') > 0
                ? round($rows->sum('ar_actual') / $rows->sum('ar_target') * 100, 1) : null,
            'achieved'  => $rows->filter(fn($r) => $r->collection_status === 'achieved')->count(),
            'partial'   => $rows->filter(fn($r) => $r->collection_status === 'partial')->count(),
            'none'      => $rows->filter(fn($r) => $r->collection_status === 'none')->count(),
            'no_target' => $rows->filter(fn($r) => $r->collection_status === 'no-target')->count(),
        ];

        return view('dashboard.collection', [
            'period'     => $period,
            'rows'       => $rows,
            'summary'    => $summary,
            'plants'     => $this->plants(),
            'collectors' => $this->collectors(),
            'periods'    => $this->periods(),
        ]);
    }

    public function overlimit(Request $request)
    {
        $period = $this->resolvePeriod($request);
        $rows   = collect(
            $this->baseQuery($request, $period)
                ->having('so_with_od', '>', 0)
                ->orderByDesc('so_with_od')
                ->get()
        );

        return view('dashboard.overlimit', [
            'period'         => $period,
            'rows'           => $rows,
            'totalOverlimit' => $rows->sum('so_with_od'),
            'totalExposed'   => $rows->sum('total'),
            'plants'         => $this->plants(),
            'collectors'     => $this->collectors(),
            'periods'        => $this->periods(),
        ]);
    }

    public function customers(Request $request)
    {
        $period = $this->resolvePeriod($request);
        $q      = $this->baseQuery($request, $period);

        if ($s = $request->search) {
            $q->where(fn($sub) =>
                $sub->where('c.customer_name', 'like', "%$s%")
                    ->orWhere('c.customer_id', 'like', "%$s%")
            );
        }

        $rows = collect($q->orderBy('c.customer_name')->get())->map(function ($r) {
            $r->collection_rate   = $r->ar_target > 0
                ? round($r->ar_actual / $r->ar_target * 100, 1) : null;
            $r->collection_status = match (true) {
                $r->collection_rate === null  => 'no-target',
                $r->collection_rate >= 100    => 'achieved',
                $r->collection_rate >= 70     => 'partial',
                default                       => 'none',
            };
            return $r;
        });

        return view('dashboard.customers', [
            'period'     => $period,
            'rows'       => $rows,
            'plants'     => $this->plants(),
            'collectors' => $this->collectors(),
            'periods'    => $this->periods(),
        ]);
    }

    /* ────────────────────────────────────────────────────────────
     * AJAX / Actions
     * ──────────────────────────────────────────────────────────── */

    public function recordPayment(Request $request)
    {
        $period = $this->resolvePeriod($request);

        $v = $request->validate([
            'customer_id' => 'required|exists:customers,customer_id',
            'amount'      => 'required|numeric|min:1',
        ]);

        // Find the invoice for this customer in the current period
        $record = ArRecord::join('invoice as inv', 'inv.id', '=', 'ar_records.invoice_id')
            ->join('customers as c', 'c.id', '=', 'inv.customer_id')
            ->where('c.customer_id', $v['customer_id'])
            ->where('ar_records.period_id', $period?->id)
            ->select('ar_records.*')
            ->first();

        if ($record) {
            $record->increment('ar_actual', (int) $v['amount']);

            // Log the payment
            CollectionLog::create([
                'ar_record_id'    => $record->id,
                'user_id'         => Auth::id(),
                'amount_collected' => (int) $v['amount'],
                'notes'           => 'Recorded via dashboard',
                'collected_at'    => now(),
            ]);
        }

        return back()->with('success', 'Payment recorded successfully.');
    }

    public function export(Request $request)
    {
        $period = $this->resolvePeriod($request);
        $rows   = collect($this->baseQuery($request, $period)->get());

        $cols    = ['customer_id', 'customer_name', 'collection_by', 'plant',
                    'current', 'days_1_30', 'days_30_60', 'days_60_90', 'days_over_90',
                    'total', 'ar_target', 'ar_actual', 'so_without_od', 'so_with_od', 'total_so'];
        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="ar_dashboard.csv"',
        ];
        $cb = function () use ($rows, $cols) {
            $f = fopen('php://output', 'w');
            fputcsv($f, $cols);
            foreach ($rows as $r) {
                fputcsv($f, array_map(fn($c) => $r->$c ?? '', $cols));
            }
            fclose($f);
        };

        return response()->stream($cb, 200, $headers);
    }

    public function agingBucket(Request $request)
    {
        $columnMap = [
            'current'    => 'amount_current',
            'days_1_30'  => 'amount_1_30_days',
            'days_30_60' => 'amount_30_60_days',
            'days_60_90' => 'amount_60_90_days',
            'over_90'    => 'amount_over_90_days',
        ];

        $bucketKey = $request->input('bucket', 'current');
        $column    = $columnMap[$bucketKey] ?? 'amount_current';
        $period    = $this->resolvePeriod($request);

        $rows = collect(
            $this->baseQuery($request, $period)
                ->where("r.$column", '>', 0)
                ->orderByDesc("r.$column")
                ->get(['c.customer_name', 'col.name as collection_by', 'p.code as plant',
                       "r.$column as bucket_amount", 'r.total_ar as total'])
        );

        return response()->json([
            'bucket'     => $bucketKey,
            'column'     => $column,
            'bucket_sum' => $rows->sum('bucket_amount'),
            'rows'       => $rows->map(fn($r) => [
                'customer_name' => $r->customer_name,
                'collection_by' => $r->collection_by,
                'plant'         => $r->plant,
                'bucket_amount' => $r->bucket_amount,
                'total'         => $r->total,
            ]),
        ]);
    }

    /**
     * Admin-only: update an ar_record row via AJAX modal.
     */
    public function updateArData(Request $request, int $id)
    {
        if (!Auth::user()?->isAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $data = $request->validate([
            'amount_current'      => 'sometimes|numeric|min:0',
            'amount_1_30_days'    => 'sometimes|numeric|min:0',
            'amount_30_60_days'   => 'sometimes|numeric|min:0',
            'amount_60_90_days'   => 'sometimes|numeric|min:0',
            'amount_over_90_days' => 'sometimes|numeric|min:0',
            'total_ar'            => 'sometimes|numeric|min:0',
            'ar_target'           => 'sometimes|numeric|min:0',
            'ar_actual'           => 'sometimes|numeric|min:0',
            // so_overlimit fields — update separately
            'so_without_od'       => 'sometimes|integer|min:0',
            'so_with_od'          => 'sometimes|integer|min:0',
            'total_so'            => 'sometimes|integer|min:0',
        ]);

        $record = ArRecord::findOrFail($id);

        // Split SO fields to so_overlimit table
        $soFields = array_intersect_key($data, array_flip(['so_without_od', 'so_with_od', 'total_so']));
        $arFields = array_diff_key($data, $soFields);

        if ($arFields) {
            $record->update($arFields);
        }

        if ($soFields) {
            SoOverlimit::where('invoice_id', $record->invoice_id)
                ->where('period_id', $record->period_id)
                ->update($soFields);
        }

        return response()->json(['success' => true, 'row' => $record->fresh()]);
    }
}