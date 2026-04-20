@extends('layouts.app')
@section('title','SO Overlimit')
@section('page-title','SO Overlimit')

@php $isAdmin = Auth::user()->isAdmin(); @endphp

@section('topbar-actions')
  @include('partials.filters')
@endsection

@section('content')

<div class="grid-kpi-3" style="margin-bottom:20px">
  <div class="kpi-card card-accent-red">
    <div class="kpi-label">Overlimit Customers</div>
    <div class="kpi-value" style="color:#dc2626">{{ $rows->count() }}</div>
    <div class="kpi-sub">with SO overdue</div>
  </div>
  <div class="kpi-card card-accent-yellow">
    <div class="kpi-label">Total SO with Overdue</div>
    <div class="kpi-value" style="color:#d97706">{{ number_format($totalOverlimit) }}</div>
    <div class="kpi-sub">sales orders</div>
  </div>
  <div class="kpi-card card-accent-red">
    <div class="kpi-label">Exposed AR</div>
    <div class="kpi-value mono" style="color:#dc2626">{{ fmtIDR($totalExposed) }}</div>
    <div class="kpi-sub">total outstanding</div>
  </div>
</div>

<div style="background:var(--surface);border:1px solid var(--border);border-radius:12px;overflow:hidden;box-shadow:var(--shadow)">
  <div style="padding:16px 20px;border-bottom:1px solid var(--border);background:#fff5f5;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
    <div style="font-size:12px;font-weight:700;color:#dc2626">⚠️ Customers with Overdue Sales Orders (<span id="ol-count">{{ $rows->count() }}</span>)</div>
    <input type="text" id="ol-search" placeholder="Search customer…" oninput="olTable.search(this.value)"
      style="padding:6px 12px;border:1px solid var(--border);border-radius:8px;font-size:12px;outline:none;width:200px">
  </div>
  <div class="table-scroll">
    <table class="data-table" id="ol-table">
      <thead><tr>
        <th class="sortable" style="white-space:nowrap">Invoice ID <span class="sort-icon">↕</span></th>
        <th class="sortable" style="white-space:nowrap">Customer <span class="sort-icon">↕</span></th>
        <th class="sortable" style="white-space:nowrap">Plant <span class="sort-icon">↕</span></th>
        <th class="sortable" style="white-space:nowrap">Collector <span class="sort-icon">↕</span></th>
        <th class="num sortable" style="white-space:nowrap">SO Without OD <span class="sort-icon">↕</span></th>
        <th class="num sortable" style="white-space:nowrap">SO With OD <span class="sort-icon">↕</span></th>
        <th class="num sortable" style="white-space:nowrap">Total SO <span class="sort-icon">↕</span></th>
        <th class="num sortable" style="white-space:nowrap">Total AR <span class="sort-icon">↕</span></th>
        <th class="sortable" style="white-space:nowrap">Risk <span class="sort-icon">↕</span></th>
        @if($isAdmin)<th style="width:60px;text-align:center">Edit</th>@endif
      </tr></thead>
      <tbody id="ol-tbody">
      @foreach($rows as $r)
      @php
        $risk = $r->so_with_od >= 20 ? ['High','badge-red'] : ($r->so_with_od >= 10 ? ['Medium','badge-orange'] : ['Low','badge-yellow']);
      @endphp
      <tr data-search="{{ strtolower($r->customer_name . ' ' . $r->customer_id . ' ' . $r->collection_by) }}"
          data-rawsowithout="{{ intval($r->so_without_od) }}"
          data-rawsowith="{{ intval($r->so_with_od) }}"
          data-rawtotalso="{{ intval($r->total_so) }}"
          data-rawtotal="{{ intval($r->total) }}">
        <td style="font-weight:700;white-space:nowrap">{{ $r->invoice_id ?? $r->id }}</td>
        <td style="max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-weight:600" title="{{ $r->customer_name }}">{{ $r->customer_name }}</td>
        <td><span class="badge badge-blue">{{ $r->plant }}</span></td>
        <td style="font-size:11px">{{ $r->collection_by }}</td>
        <td class="num">{{ $r->so_without_od }}</td>
        <td class="num" style="color:#dc2626;font-weight:700">{{ $r->so_with_od }}</td>
        <td class="num">{{ $r->total_so }}</td>
        <td class="num">{{ fmtIDR($r->total) }}</td>
        <td><span class="badge {{ $risk[1] }}">{{ $risk[0] }}</span></td>
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
    <span style="font-size:12px;color:var(--muted)" id="ol-page-info"></span>
    <div style="display:flex;gap:6px;align-items:center">
      <button id="ol-prev" onclick="olTable.prevPage()"
        style="padding:5px 12px;border:1px solid var(--border);border-radius:7px;background:var(--surface);cursor:pointer;font-size:12px;font-weight:600">‹ Prev</button>
      <div id="ol-page-btns" style="display:flex;gap:4px"></div>
      <button id="ol-next" onclick="olTable.nextPage()"
        style="padding:5px 12px;border:1px solid var(--border);border-radius:7px;background:var(--surface);cursor:pointer;font-size:12px;font-weight:600">Next ›</button>
    </div>
  </div>
</div>

@include('partials.table-manager-styles')
<script>
const olTable = makeTableManager(
  'ol-tbody', 'ol-table',
  'ol-count', 'ol-page-info', 'ol-page-btns', 'ol-prev', 'ol-next',
  10,
  {
    0: null,              // Invoice ID
    1: null,              // Customer
    2: null,              // Plant
    3: null,              // Collector
    4: 'rawsowithout',    // SO Without OD
    5: 'rawsowith',       // SO With OD
    6: 'rawtotalso',      // Total SO
    7: 'rawtotal',        // Total AR
    8: null,              // Risk — text
  }
);
</script>
@endsection