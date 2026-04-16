@extends('layouts.app')
@section('title','AR Aging')
@section('page-title','AR Aging')

@php
function fmtIDR($v){ if($v>=1e12) return 'Rp '.number_format($v/1e12,2).'T'; if($v>=1e9) return 'Rp '.number_format($v/1e9,2).'B'; if($v>=1e6) return 'Rp '.number_format($v/1e6,1).'M'; return 'Rp '.number_format($v); }
$totalBucket = array_sum($buckets);
function pctOf($v,$t){ return $t>0 ? round($v/$t*100,1) : 0; }
@endphp

@section('topbar-actions')
  @include('partials.filters')
@endsection

@section('content')

{{-- Bucket KPI Cards --}}
<div class="grid-kpi-5" style="margin-bottom:20px">
  @php
  $bucketDefs = [
    ['label'=>'Current','key'=>'current','color'=>'card-accent-blue','tc'=>'#1B3A6B'],
    ['label'=>'1–30 Days','key'=>'days_1_30','color'=>'card-accent-green','tc'=>'#16a34a'],
    ['label'=>'30–60 Days','key'=>'days_30_60','color'=>'card-accent-yellow','tc'=>'#d97706'],
    ['label'=>'60–90 Days','key'=>'days_60_90','color'=>'card-accent-red','tc'=>'#ea580c'],
    ['label'=>'> 90 Days','key'=>'over_90','color'=>'card-accent-red','tc'=>'#dc2626'],
  ];
  @endphp
  @foreach($bucketDefs as $b)
  <div class="kpi-card {{ $b['color'] }}">
    <div class="kpi-label">{{ $b['label'] }}</div>
    <div class="kpi-value mono" style="color:{{ $b['tc'] }}">{{ fmtIDR($buckets[$b['key']]) }}</div>
    <div class="kpi-sub">{{ pctOf($buckets[$b['key']],$totalBucket) }}% of total</div>
  </div>
  @endforeach
</div>

{{-- Aging Bar Chart --}}
<div style="background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:20px;margin-bottom:20px;box-shadow:var(--shadow)">
  <div style="font-size:12px;font-weight:700;margin-bottom:14px">Aging Distribution</div>
  @if($totalBucket > 0)
  <div style="display:flex;gap:2px;height:12px;border-radius:6px;overflow:hidden;margin-bottom:12px">
    @foreach([['current','#1B3A6B'],['days_1_30','#1e88e5'],['days_30_60','#d97706'],['days_60_90','#ea580c'],['over_90','#dc2626']] as [$k,$c])
      @if($buckets[$k] > 0)
      <div style="flex:{{ $buckets[$k] }};background:{{ $c }}" title="{{ $k }}: {{ fmtIDR($buckets[$k]) }}"></div>
      @endif
    @endforeach
  </div>
  <div style="display:flex;gap:16px;flex-wrap:wrap">
    @foreach([['Current','current','#1B3A6B'],['1-30d','days_1_30','#1e88e5'],['30-60d','days_30_60','#d97706'],['60-90d','days_60_90','#ea580c'],['>90d','over_90','#dc2626']] as [$lbl,$k,$c])
    <div style="display:flex;align-items:center;gap:5px">
      <div style="width:10px;height:10px;border-radius:2px;background:{{ $c }}"></div>
      <span style="font-size:11px;color:var(--muted)">{{ $lbl }}</span>
    </div>
    @endforeach
  </div>
  @endif
</div>

{{-- Customer Table --}}
<div style="background:var(--surface);border:1px solid var(--border);border-radius:12px;overflow:hidden;box-shadow:var(--shadow)">
  <div style="padding:16px 20px;border-bottom:1px solid var(--border)">
    <div style="font-size:12px;font-weight:700">Customer Aging Detail ({{ $rows->count() }} records)</div>
  </div>
  <div class="table-scroll">
    <table class="data-table">
      <thead><tr>
        <th>Customer</th><th>Plant</th><th>Collector</th>
        <th class="num">Current</th><th class="num">1-30d</th>
        <th class="num">30-60d</th><th class="num">60-90d</th>
        <th class="num">>90d</th><th class="num">Total</th><th>Aging</th>
      </tr></thead>
      <tbody>
      @foreach($rows as $r)
      @php
        $hasOverdue = ($r->days_60_90 + $r->days_over_90) > 0;
        $tot = $r->total ?: 1;
      @endphp
      <tr style="{{ $hasOverdue ? 'background:#fff5f5' : '' }}">
        <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-weight:600" title="{{ $r->customer_name }}">{{ $r->customer_name }}</td>
        <td><span class="badge badge-blue">{{ $r->plant }}</span></td>
        <td style="font-size:11px">{{ $r->collection_by }}</td>
        <td class="num">{{ $r->current > 0 ? fmtIDR($r->current) : '—' }}</td>
        <td class="num">{{ $r->days_1_30 > 0 ? fmtIDR($r->days_1_30) : '—' }}</td>
        <td class="num" style="{{ $r->days_30_60 > 0 ? 'color:#d97706;font-weight:600' : '' }}">{{ $r->days_30_60 > 0 ? fmtIDR($r->days_30_60) : '—' }}</td>
        <td class="num" style="{{ $r->days_60_90 > 0 ? 'color:#ea580c;font-weight:600' : '' }}">{{ $r->days_60_90 > 0 ? fmtIDR($r->days_60_90) : '—' }}</td>
        <td class="num" style="{{ $r->days_over_90 > 0 ? 'color:#dc2626;font-weight:700' : '' }}">{{ $r->days_over_90 > 0 ? fmtIDR($r->days_over_90) : '—' }}</td>
        <td class="num" style="font-weight:700">{{ fmtIDR($r->total) }}</td>
        <td style="min-width:80px">
          <div style="display:flex;gap:2px;height:6px;border-radius:4px;overflow:hidden">
            <div style="flex:{{ $r->current }};background:#1B3A6B"></div>
            <div style="flex:{{ $r->days_1_30 }};background:#1e88e5"></div>
            <div style="flex:{{ $r->days_30_60 }};background:#d97706"></div>
            <div style="flex:{{ $r->days_60_90 }};background:#ea580c"></div>
            <div style="flex:{{ $r->days_over_90 }};background:#dc2626"></div>
          </div>
        </td>
      </tr>
      @endforeach
      </tbody>
    </table>
  </div>
</div>
@endsection