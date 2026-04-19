@extends('layouts.app')
@section('title','Customers')
@section('page-title','Customers')

@php $isAdmin = Auth::user()->isAdmin(); @endphp

@section('topbar-actions')
  <form method="GET" action="{{ route('dashboard.customers') }}" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
    <input type="text" name="search" value="{{ request('search') }}" class="filter-input" placeholder="Search customer…" style="width:160px">
    @if(isset($period) && $period)<input type="hidden" name="period_id" value="{{ $period->id }}">@endif
    @if(request('plant'))<input type="hidden" name="plant" value="{{ request('plant') }}">@endif
    @if(request('collector'))<input type="hidden" name="collector" value="{{ request('collector') }}">@endif
    <button type="submit" class="btn btn-primary" style="padding:7px 14px">Search</button>
    @if(request('search'))<a href="{{ route('dashboard.customers') }}{{ isset($period) && $period ? '?period_id='.$period->id : '' }}" class="btn btn-ghost" style="padding:7px 14px">✕</a>@endif
  </form>
  @include('partials.filters')
@endsection

@section('content')

<div style="background:var(--surface);border:1px solid var(--border);border-radius:12px;overflow:hidden;box-shadow:var(--shadow)">
  <div style="padding:16px 20px;border-bottom:1px solid var(--border)">
    <div style="font-size:12px;font-weight:700">All Customers ({{ $rows->count() }} records)</div>
  </div>
  <div class="table-scroll">
    <table class="data-table">
      <thead><tr>
        <th>Customer ID</th><th>Customer Name</th><th>Plant</th><th>Collector</th>
        <th class="num">Current</th><th class="num">1-30d</th><th class="num">>60d</th>
        <th class="num">Total AR</th><th class="num">Target</th><th class="num">Actual</th>
        <th>Collection</th><th class="num">SO OD</th>
        @if($isAdmin)<th style="width:60px;text-align:center">Edit</th>@endif
      </tr></thead>
      <tbody>
      @foreach($rows as $r)
      @php
        $rate       = $r->collection_rate;
        $status     = $r->collection_status;
        $badgeCls   = match($status){ 'achieved'=>'badge-green','partial'=>'badge-yellow','none'=>'badge-red', default=>'badge-gray' };
        $statusLabel= match($status){ 'achieved'=>'Achieved','partial'=>'Partial','none'=>'None', default=>'No Target' };
        $hasOverdue = ($r->days_60_90 + $r->days_over_90) > 0;
      @endphp
      <tr style="{{ $hasOverdue ? 'background:#fff9f9' : '' }}">
        <td class="mono" style="font-size:11px;color:var(--muted)">{{ $r->customer_id }}</td>
        <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-weight:600" title="{{ $r->customer_name }}">{{ $r->customer_name }}</td>
        <td><span class="badge badge-blue">{{ $r->plant }}</span></td>
        <td style="font-size:11px">{{ $r->collection_by }}</td>
        <td class="num">{{ $r->current > 0 ? fmtIDR($r->current) : '—' }}</td>
        <td class="num">{{ $r->days_1_30 > 0 ? fmtIDR($r->days_1_30) : '—' }}</td>
        <td class="num" style="{{ $hasOverdue ? 'color:#dc2626;font-weight:700' : '' }}">
          {{ ($r->days_60_90 + $r->days_over_90) > 0 ? fmtIDR($r->days_60_90 + $r->days_over_90) : '—' }}
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
</div>

@endsection