<?php

namespace App\Http\Controllers;

use App\Models\ArData;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    private function baseQuery(Request $request)
    {
        return ArData::byPeriod('2026-01-31')
            ->byPlant($request->plant)
            ->byCollector($request->collector);
    }

    public function index(Request $request)
    {
        $rows           = $this->baseQuery($request)->get();
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

        $totalSO         = $rows->sum('total_so');
        $soWithOD        = $rows->sum('so_with_od');
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
        $plants       = ArData::select('plant')->distinct()->orderBy('plant')->pluck('plant');
        $collectors   = ArData::select('collection_by')->distinct()->orderBy('collection_by')->pluck('collection_by');

        return view('dashboard.index', compact(
            'totalAR','totalTarget','totalCollected','collectionRate',
            'aging','totalSO','soWithOD','overdueCustomers',
            'byCollector','byPlant','topCustomers','plants','collectors','rows'
        ));
    }

    public function aging(Request $request)
    {
        $rows = $this->baseQuery($request)->orderByDesc('total')->get();
        $plants     = ArData::select('plant')->distinct()->orderBy('plant')->pluck('plant');
        $collectors = ArData::select('collection_by')->distinct()->orderBy('collection_by')->pluck('collection_by');
        $buckets = [
            'current'    => $rows->sum('current'),
            'days_1_30'  => $rows->sum('days_1_30'),
            'days_30_60' => $rows->sum('days_30_60'),
            'days_60_90' => $rows->sum('days_60_90'),
            'over_90'    => $rows->sum('days_over_90'),
        ];
        return view('dashboard.aging', compact('rows','buckets','plants','collectors'));
    }

    public function collection(Request $request)
    {
        $rows       = $this->baseQuery($request)->orderByDesc('ar_target')->get();
        $plants     = ArData::select('plant')->distinct()->orderBy('plant')->pluck('plant');
        $collectors = ArData::select('collection_by')->distinct()->orderBy('collection_by')->pluck('collection_by');
        $summary = [
            'target'    => $rows->sum('ar_target'),
            'actual'    => $rows->sum('ar_actual'),
            'rate'      => $rows->sum('ar_target') > 0 ? round($rows->sum('ar_actual') / $rows->sum('ar_target') * 100, 1) : null,
            'achieved'  => $rows->filter(fn($r) => $r->collection_rate !== null && $r->collection_rate >= 100)->count(),
            'partial'   => $rows->filter(fn($r) => $r->collection_rate !== null && $r->collection_rate >= 70 && $r->collection_rate < 100)->count(),
            'none'      => $rows->filter(fn($r) => $r->collection_rate !== null && $r->collection_rate < 70)->count(),
            'no_target' => $rows->filter(fn($r) => $r->collection_rate === null)->count(),
        ];
        return view('dashboard.collection', compact('rows','summary','plants','collectors'));
    }

    public function overlimit(Request $request)
    {
        $rows = $this->baseQuery($request)->where('so_with_od','>',0)->orderByDesc('so_with_od')->get();
        $plants     = ArData::select('plant')->distinct()->orderBy('plant')->pluck('plant');
        $collectors = ArData::select('collection_by')->distinct()->orderBy('collection_by')->pluck('collection_by');
        $totalOverlimit = $rows->sum('so_with_od');
        $totalExposed   = $rows->sum('total');
        return view('dashboard.overlimit', compact('rows','totalOverlimit','totalExposed','plants','collectors'));
    }

    public function customers(Request $request)
    {
        $q = $this->baseQuery($request);
        if ($s = $request->search) {
            $q->where(fn($sub) => $sub->where('customer_name','like',"%$s%")->orWhere('customer_id','like',"%$s%"));
        }
        $rows       = $q->orderBy('customer_name')->get();
        $plants     = ArData::select('plant')->distinct()->orderBy('plant')->pluck('plant');
        $collectors = ArData::select('collection_by')->distinct()->orderBy('collection_by')->pluck('collection_by');
        return view('dashboard.customers', compact('rows','plants','collectors'));
    }

    public function recordPayment(Request $request)
    {
        $v = $request->validate(['customer_id'=>'required|exists:ar_data,customer_id','amount'=>'required|numeric|min:1']);
        ArData::where('customer_id',$v['customer_id'])->where('period','2026-01-31')->increment('ar_actual',(int)$v['amount']);
        return back()->with('success','Payment recorded successfully.');
    }

    public function export(Request $request)
    {
        $rows = $this->baseQuery($request)->get();
        $cols = ['customer_id','customer_name','collection_by','plant','current','days_1_30','days_30_60','days_60_90','days_over_90','total','ar_target','ar_actual','so_without_od','so_with_od','total_so'];
        $headers = ['Content-Type'=>'text/csv','Content-Disposition'=>'attachment; filename="ar_dashboard.csv"'];
        $cb = function() use($rows,$cols){ $f=fopen('php://output','w'); fputcsv($f,$cols); foreach($rows as $r){ fputcsv($f,array_map(fn($c)=>$r->$c,$cols)); } fclose($f); };
        return response()->stream($cb,200,$headers);
    }

    public function agingBucket(Request $request)
    {
        // Map JS bucket key → model column
        $columnMap = [
            'current'    => 'current',
            'days_1_30'  => 'days_1_30',
            'days_30_60' => 'days_30_60',
            'days_60_90' => 'days_60_90',
            'over_90'    => 'days_over_90',
        ];

        $bucketKey = $request->input('bucket', 'current');
        $column    = $columnMap[$bucketKey] ?? 'current';

        $rows = $this->baseQuery($request)
            ->where($column, '>', 0)
            ->orderByDesc($column)
            ->get(['customer_name', 'collection_by', 'plant', $column, 'total']);

        $formatted = $rows->map(fn($r) => [
            'customer_name'  => $r->customer_name,
            'collection_by'  => $r->collection_by,
            'plant'          => $r->plant,
            'bucket_amount'  => $r->$column,
            'total'          => $r->total,
        ]);

        return response()->json([
            'bucket'     => $bucketKey,
            'column'     => $column,
            'bucket_sum' => $rows->sum($column),
            'rows'       => $formatted,
        ]);
    }
}