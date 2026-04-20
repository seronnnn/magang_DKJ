@extends('layouts.app')
@section('title','AR Aging')
@section('page-title','AR Aging')

@php
  $totalBucket = array_sum($buckets);
  $isAdmin     = Auth::user()->isAdmin();
@endphp

@section('topbar-actions')
  @include('partials.filters')
@endsection

@section('content')

{{-- Bucket KPI Cards --}}
<div class="grid-kpi-5" style="margin-bottom:20px">
  @php
  $bucketDefs = [
    ['label'=>'Current',    'key'=>'current',    'color'=>'card-accent-blue',  'tc'=>'#1B3A6B'],
    ['label'=>'1–30 Days',  'key'=>'days_1_30',  'color'=>'card-accent-green', 'tc'=>'#16a34a'],
    ['label'=>'30–60 Days', 'key'=>'days_30_60', 'color'=>'card-accent-yellow','tc'=>'#d97706'],
    ['label'=>'60–90 Days', 'key'=>'days_60_90', 'color'=>'card-accent-red',   'tc'=>'#ea580c'],
    ['label'=>'> 90 Days',  'key'=>'over_90',    'color'=>'card-accent-red',   'tc'=>'#dc2626'],
  ];
  @endphp
  @foreach($bucketDefs as $b)
  <div class="kpi-card {{ $b['color'] }}">
    <div class="kpi-label">{{ $b['label'] }}</div>
    <div class="kpi-value mono" style="color:{{ $b['tc'] }}">{{ fmtIDR($buckets[$b['key']]) }}</div>
    <div class="kpi-sub">{{ pctOf($buckets[$b['key']], $totalBucket) }}% of total</div>
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

{{-- Customer Aging Table --}}
<div style="background:var(--surface);border:1px solid var(--border);border-radius:12px;overflow:hidden;box-shadow:var(--shadow)">
  <div style="padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
    <div style="font-size:12px;font-weight:700">Customer Aging Detail (<span id="aging-count">{{ $rows->count() }}</span> records)</div>
    <input type="text" id="aging-search" placeholder="Search customer…" oninput="agingTable.search(this.value)"
      style="padding:6px 12px;border:1px solid var(--border);border-radius:8px;font-size:12px;outline:none;width:200px">
  </div>
  <div class="table-scroll">
    <table class="data-table" id="aging-table">
      <thead><tr>
        <th class="sortable" style="white-space:nowrap">Invoice ID <span class="sort-icon">↕</span></th>
        <th class="sortable" style="white-space:nowrap">Customer <span class="sort-icon">↕</span></th>
        <th class="sortable" style="white-space:nowrap">Plant <span class="sort-icon">↕</span></th>
        <th class="sortable" style="white-space:nowrap">Collector <span class="sort-icon">↕</span></th>
        <th class="num sortable" style="white-space:nowrap">Current <span class="sort-icon">↕</span></th>
        <th class="num sortable" style="white-space:nowrap">1-30d <span class="sort-icon">↕</span></th>
        <th class="num sortable" style="white-space:nowrap">30-60d <span class="sort-icon">↕</span></th>
        <th class="num sortable" style="white-space:nowrap">60-90d <span class="sort-icon">↕</span></th>
        <th class="num sortable" style="white-space:nowrap">&gt;90d <span class="sort-icon">↕</span></th>
        <th class="num sortable" style="white-space:nowrap">Total <span class="sort-icon">↕</span></th>
        <th>Aging Bar</th>
        @if($isAdmin)<th style="width:60px;text-align:center">Edit</th>@endif
      </tr></thead>
      <tbody id="aging-tbody">
      @foreach($rows as $r)
      @php $hasOverdue = ($r->days_60_90 + $r->days_over_90) > 0; @endphp
      <tr style="{{ $hasOverdue ? 'background:#fff5f5' : '' }}"
          data-search="{{ strtolower($r->customer_name . ' ' . $r->customer_id . ' ' . $r->collection_by) }}"
          data-raw-current="{{ intval($r->current) }}"
          data-raw130="{{ intval($r->days_1_30) }}"
          data-raw3060="{{ intval($r->days_30_60) }}"
          data-raw6090="{{ intval($r->days_60_90) }}"
          data-rawover90="{{ intval($r->days_over_90) }}"
          data-rawtotal="{{ intval($r->total) }}">
        <td style="font-weight:700;white-space:nowrap">{{ $r->invoice_id ?? $r->id }}</td>
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
        @if($isAdmin)
        <td style="text-align:center">
          <button class="btn btn-warning btn-sm" onclick='openEditModal(@json((array)$r))'>✏️</button>
        </td>
        @endif
      </tr>
      @endforeach
      </tbody>
    </table>
  </div>
  <div style="padding:12px 20px;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px">
    <span style="font-size:12px;color:var(--muted)" id="aging-page-info"></span>
    <div style="display:flex;gap:6px;align-items:center">
      <button id="aging-prev" onclick="agingTable.prevPage()"
        style="padding:5px 12px;border:1px solid var(--border);border-radius:7px;background:var(--surface);cursor:pointer;font-size:12px;font-weight:600">‹ Prev</button>
      <div id="aging-page-btns" style="display:flex;gap:4px"></div>
      <button id="aging-next" onclick="agingTable.nextPage()"
        style="padding:5px 12px;border:1px solid var(--border);border-radius:7px;background:var(--surface);cursor:pointer;font-size:12px;font-weight:600">Next ›</button>
    </div>
  </div>
</div>

@include('partials.table-manager-styles')
<script>
// rawAttrMap: colIndex → dataset key (camelCase of data-* attribute)
// data-raw-current → dataset.rawCurrent, data-raw130 → dataset.raw130, etc.
const agingTable = makeTableManager(
  'aging-tbody', 'aging-table',
  'aging-count', 'aging-page-info', 'aging-page-btns', 'aging-prev', 'aging-next',
  10,
  {
    0: null,           // Invoice ID — text
    1: null,           // Customer  — text
    2: null,           // Plant     — text
    3: null,           // Collector — text
    4: 'rawCurrent',   // Current
    5: 'raw130',       // 1-30d
    6: 'raw3060',      // 30-60d
    7: 'raw6090',      // 60-90d
    8: 'rawover90',    // >90d
    9: 'rawtotal',     // Total
  }
);
</script>
@endsection