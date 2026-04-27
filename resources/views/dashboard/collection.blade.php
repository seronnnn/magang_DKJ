@extends('layouts.app')
@section('title','Collection')
@section('page-title','AR Collection')

@php 
  $isAdmin = Auth::user()->isAdmin(); 
  $exportPeriod    = isset($period) && $period ?  $period->period_label : 'All Periods';
  $exportCollector = request('collector') ?: 'All Collectors';
@endphp

@section('topbar-actions')
  @include('partials.filters')
@endsection

@section('content')

{{-- Summary KPI Cards --}}
<div class="grid-kpi-4" style="margin-bottom:20px">
  <div class="kpi-card card-accent-blue">
    <div class="kpi-label">Total Target</div>
    <div class="kpi-value mono">{{ fmtIDR($summary['target']) }}</div>
  </div>
  <div class="kpi-card card-accent-green">
    <div class="kpi-label">Total Collected</div>
    <div class="kpi-value mono" style="color:#16a34a">{{ fmtIDR($summary['actual']) }}</div>
    <div class="kpi-sub">Rate: {{ $summary['rate'] !== null ? $summary['rate'].'%' : 'N/A' }}</div>
  </div>
  <div class="kpi-card card-accent-yellow">
    <div class="kpi-label">Achieved / Partial</div>
    <div class="kpi-value">{{ $summary['achieved'] }} / {{ $summary['partial'] }}</div>
    <div class="kpi-sub">customers</div>
  </div>
  <div class="kpi-card card-accent-red">
    <div class="kpi-label">Not Collected</div>
    <div class="kpi-value" style="color:#dc2626">{{ $summary['none'] }}</div>
    <div class="kpi-sub">{{ $summary['no_target'] }} no target</div>
  </div>
</div>

{{-- Collection Table --}}
<div style="background:var(--surface);border:1px solid var(--border);border-radius:12px;overflow:hidden;box-shadow:var(--shadow)">
  <div style="padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
    <div style="font-size:12px;font-weight:700">Collection Detail (<span id="col-count">{{ $rows->count() }}</span> records)</div>
    <div style="display:flex;align-items:center;gap:8px">
      <input type="text" id="col-search" placeholder="Search customer…" oninput="colTable.search(this.value)"
        style="padding:6px 12px;border:1px solid var(--border);border-radius:8px;font-size:12px;outline:none;width:200px">
      <button onclick="exportTableXLSX('col-table', 'ar_collection', {
          pageTitle: 'AR Collection',
          period: '{{ $exportPeriod }}',
          collector: '{{ $exportCollector }}'
        })"
        style="display:inline-flex;align-items:center;gap:5px;padding:6px 14px;
               background:#16a34a;color:#fff;border:none;border-radius:8px;
               font-size:11px;font-weight:700;cursor:pointer;white-space:nowrap;transition:all .15s"
        onmouseover="this.style.background='#15803d'" onmouseout="this.style.background='#16a34a'">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        Export XLSX
      </button>
    </div>
  </div>
  <div class="table-scroll">
    <table class="data-table" id="col-table">
      <thead><tr>
        <th class="sortable" style="white-space:nowrap">Invoice ID <span class="sort-icon">↕</span></th>
        <th class="sortable" style="white-space:nowrap">Customer <span class="sort-icon">↕</span></th>
        <th class="sortable" style="white-space:nowrap">Plant <span class="sort-icon">↕</span></th>
        <th class="sortable" style="white-space:nowrap">Collector <span class="sort-icon">↕</span></th>
        <th class="num sortable" style="white-space:nowrap">Total AR <span class="sort-icon">↕</span></th>
        <th class="num sortable" style="white-space:nowrap">Target <span class="sort-icon">↕</span></th>
        <th class="num sortable" style="white-space:nowrap">Actual <span class="sort-icon">↕</span></th>
        <th class="num sortable" style="white-space:nowrap">Rate <span class="sort-icon">↕</span></th>
        <th class="sortable" style="white-space:nowrap">Status <span class="sort-icon">↕</span></th>
        <th>Progress</th>
        <th style="width:60px;text-align:center">Edit</th>
      </tr></thead>
      <tbody id="col-tbody">
      @foreach($rows as $r)
      @php
        $rate      = $r->collection_rate;
        $status    = $r->collection_status;
        $badgeCls  = match($status){ 'achieved'=>'badge-green','partial'=>'badge-yellow','none'=>'badge-red', default=>'badge-gray' };
        $statusLbl = match($status){ 'achieved'=>'Achieved','partial'=>'Partial','none'=>'None', default=>'No Target' };
        $barColor  = $rate === null ? '#94a3b8' : ($rate >= 100 ? '#16a34a' : ($rate >= 70 ? '#d97706' : '#dc2626'));
        $pct       = min($rate ?? 0, 100);
      @endphp
      <tr data-search="{{ strtolower($r->customer_name . ' ' . $r->customer_id . ' ' . $r->collection_by) }}"
          data-rawtotal="{{ intval($r->total) }}"
          data-rawtarget="{{ intval($r->ar_target) }}"
          data-rawactual="{{ intval($r->ar_actual) }}"
          data-rawrate="{{ $rate !== null ? floatval($rate) : -1 }}">
        <td style="font-weight:700;white-space:nowrap">{{ $r->invoice_id ?? $r->id }}</td>
        <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-weight:600" title="{{ $r->customer_name }}">{{ $r->customer_name }}</td>
        <td><span class="badge badge-blue">{{ $r->plant }}</span></td>
        <td style="font-size:11px">{{ $r->collection_by }}</td>
        <td class="num" style="font-weight:700">{{ fmtIDR($r->total) }}</td>
        <td class="num">{{ $r->ar_target > 0 ? fmtIDR($r->ar_target) : '—' }}</td>
        <td class="num" style="color:#16a34a">{{ $r->ar_actual > 0 ? fmtIDR($r->ar_actual) : '—' }}</td>
        <td class="num">{{ $rate !== null ? $rate.'%' : '—' }}</td>
        <td><span class="badge {{ $badgeCls }}">{{ $statusLbl }}</span></td>
        <td style="min-width:120px">
          <div style="display:flex;align-items:center;gap:6px">
            <div class="progress-bg" style="flex:1">
              <div class="progress-fill" style="width:{{ $pct }}%;background:{{ $barColor }}"></div>
            </div>
            <span style="font-size:10px;color:{{ $barColor }};min-width:30px;font-weight:700">{{ $rate !== null ? $rate.'%' : '—' }}</span>
          </div>
        </td>
        <td style="text-align:center">
          <button class="btn btn-warning btn-sm" onclick='openEditModal(@json((array)$r))'>✏️</button>
        </td>
      </tr>
      @endforeach
      </tbody>
    </table>
  </div>
  <div style="padding:12px 20px;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px">
    <span style="font-size:12px;color:var(--muted)" id="col-page-info"></span>
    <div style="display:flex;gap:6px;align-items:center">
      <button id="col-prev" onclick="colTable.prevPage()"
        style="padding:5px 12px;border:1px solid var(--border);border-radius:7px;background:var(--surface);cursor:pointer;font-size:12px;font-weight:600">‹ Prev</button>
      <div id="col-page-btns" style="display:flex;gap:4px"></div>
      <button id="col-next" onclick="colTable.nextPage()"
        style="padding:5px 12px;border:1px solid var(--border);border-radius:7px;background:var(--surface);cursor:pointer;font-size:12px;font-weight:600">Next ›</button>
    </div>
  </div>
</div>

@include('partials.table-manager-styles')
@include('partials.csv-export')
<script>
const colTable = makeTableManager(
  'col-tbody', 'col-table',
  'col-count', 'col-page-info', 'col-page-btns', 'col-prev', 'col-next',
  10,
  {
    0: null,
    1: null,
    2: null,
    3: null,
    4: 'rawtotal',
    5: 'rawtarget',
    6: 'rawactual',
    7: 'rawrate',
    8: null,
  }
);
</script>
@endsection