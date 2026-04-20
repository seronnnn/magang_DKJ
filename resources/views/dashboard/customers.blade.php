@extends('layouts.app')
@section('title','Customers')
@section('page-title','Customers')

@php $isAdmin = Auth::user()->isAdmin(); @endphp

@section('topbar-actions')
  @include('partials.filters')
@endsection

@section('content')

<div style="background:var(--surface);border:1px solid var(--border);border-radius:12px;overflow:hidden;box-shadow:var(--shadow)">
  <div style="padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
    <div style="font-size:12px;font-weight:700">All Customers (<span id="cust-count">{{ $rows->count() }}</span> records)</div>
    <input type="text" id="cust-search" placeholder="Search customer…" oninput="custTable.search(this.value)"
      style="padding:6px 12px;border:1px solid var(--border);border-radius:8px;font-size:12px;outline:none;width:200px">
  </div>
  <div class="table-scroll">
    <table class="data-table" id="cust-table">
      <thead><tr>
        <th class="sortable" style="white-space:nowrap">Invoice ID <span class="sort-icon">↕</span></th>
        <th class="sortable" style="white-space:nowrap">Customer ID <span class="sort-icon">↕</span></th>
        <th class="sortable" style="white-space:nowrap">Customer Name <span class="sort-icon">↕</span></th>
        <th class="sortable" style="white-space:nowrap">Plant <span class="sort-icon">↕</span></th>
        <th class="sortable" style="white-space:nowrap">Collector <span class="sort-icon">↕</span></th>
        <th class="num sortable" style="white-space:nowrap">Current <span class="sort-icon">↕</span></th>
        <th class="num sortable" style="white-space:nowrap">1-30d <span class="sort-icon">↕</span></th>
        <th class="num sortable" style="white-space:nowrap">&gt;60d <span class="sort-icon">↕</span></th>
        <th class="num sortable" style="white-space:nowrap">Total AR <span class="sort-icon">↕</span></th>
        <th class="num sortable" style="white-space:nowrap">Target <span class="sort-icon">↕</span></th>
        <th class="num sortable" style="white-space:nowrap">Actual <span class="sort-icon">↕</span></th>
        <th class="sortable" style="white-space:nowrap">Collection <span class="sort-icon">↕</span></th>
        <th class="num sortable" style="white-space:nowrap">SO OD <span class="sort-icon">↕</span></th>
        @if($isAdmin)<th style="width:60px;text-align:center">Edit</th>@endif
      </tr></thead>
      <tbody id="cust-tbody">
      @foreach($rows as $r)
      @php
        $rate       = $r->collection_rate;
        $status     = $r->collection_status;
        $badgeCls   = match($status){ 'achieved'=>'badge-green','partial'=>'badge-yellow','none'=>'badge-red', default=>'badge-gray' };
        $statusLabel= match($status){ 'achieved'=>'Achieved','partial'=>'Partial','none'=>'None', default=>'No Target' };
        $hasOverdue = ($r->days_60_90 + $r->days_over_90) > 0;
        $over60     = $r->days_60_90 + $r->days_over_90;
      @endphp
      <tr style="{{ $hasOverdue ? 'background:#fff9f9' : '' }}"
          data-search="{{ strtolower($r->customer_name . ' ' . $r->customer_id . ' ' . $r->collection_by) }}"
          data-rawcurrent="{{ intval($r->current) }}"
          data-raw130="{{ intval($r->days_1_30) }}"
          data-rawover60="{{ intval($over60) }}"
          data-rawtotal="{{ intval($r->total) }}"
          data-rawtarget="{{ intval($r->ar_target) }}"
          data-rawactual="{{ intval($r->ar_actual) }}"
          data-rawrate="{{ $rate !== null ? floatval($rate) : -1 }}"
          data-rawsood="{{ intval($r->so_with_od) }}">
        <td style="font-weight:700;white-space:nowrap">{{ $r->invoice_id ?? $r->id }}</td>
        <td class="mono" style="font-size:11px;color:var(--muted)">{{ $r->customer_id }}</td>
        <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-weight:600" title="{{ $r->customer_name }}">{{ $r->customer_name }}</td>
        <td><span class="badge badge-blue">{{ $r->plant }}</span></td>
        <td style="font-size:11px">{{ $r->collection_by }}</td>
        <td class="num">{{ $r->current > 0 ? fmtIDR($r->current) : '—' }}</td>
        <td class="num">{{ $r->days_1_30 > 0 ? fmtIDR($r->days_1_30) : '—' }}</td>
        <td class="num" style="{{ $hasOverdue ? 'color:#dc2626;font-weight:700' : '' }}">
          {{ $over60 > 0 ? fmtIDR($over60) : '—' }}
        </td>
        <td class="num" style="font-weight:700">{{ fmtIDR($r->total) }}</td>
        <td class="num">{{ $r->ar_target > 0 ? fmtIDR($r->ar_target) : '—' }}</td>
        <td class="num">{{ $r->ar_actual > 0 ? fmtIDR($r->ar_actual) : '—' }}</td>
        <td>
          <span class="badge {{ $badgeCls }}">{{ $statusLabel }}</span>
          @if($rate !== null)<div style="font-size:10px;color:var(--muted);margin-top:2px">{{ $rate }}%</div>@endif
        </td>
        <td class="num" style="{{ $r->so_with_od > 0 ? 'color:#dc2626;font-weight:700' : '' }}">
          {{ $r->so_with_od > 0 ? $r->so_with_od : '—' }}
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
    <span style="font-size:12px;color:var(--muted)" id="cust-page-info"></span>
    <div style="display:flex;gap:6px;align-items:center">
      <button id="cust-prev" onclick="custTable.prevPage()"
        style="padding:5px 12px;border:1px solid var(--border);border-radius:7px;background:var(--surface);cursor:pointer;font-size:12px;font-weight:600">‹ Prev</button>
      <div id="cust-page-btns" style="display:flex;gap:4px"></div>
      <button id="cust-next" onclick="custTable.nextPage()"
        style="padding:5px 12px;border:1px solid var(--border);border-radius:7px;background:var(--surface);cursor:pointer;font-size:12px;font-weight:600">Next ›</button>
    </div>
  </div>
</div>

@include('partials.table-manager-styles')
<script>
const custTable = makeTableManager(
  'cust-tbody', 'cust-table',
  'cust-count', 'cust-page-info', 'cust-page-btns', 'cust-prev', 'cust-next',
  10,
  {
    0:  null,          // Invoice ID
    1:  null,          // Customer ID
    2:  null,          // Customer Name
    3:  null,          // Plant
    4:  null,          // Collector
    5:  'rawcurrent',  // Current
    6:  'raw130',      // 1-30d
    7:  'rawover60',   // >60d
    8:  'rawtotal',    // Total AR
    9:  'rawtarget',   // Target
    10: 'rawactual',   // Actual
    11: 'rawrate',     // Rate/Collection (sort by rate number)
    12: 'rawsood',     // SO OD
  }
);

// Pre-fill search from URL ?search= param (backward compat)
const urlSearch = new URLSearchParams(window.location.search).get('search');
if (urlSearch) {
  const el = document.getElementById('cust-search');
  if (el) { el.value = urlSearch; custTable.search(urlSearch); }
}
</script>
@endsection