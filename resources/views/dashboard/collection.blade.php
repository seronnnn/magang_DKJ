@extends('layouts.app')
@section('title','Collection')
@section('page-title','AR Collection')

@php
  $isAdmin = true; 
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
    <div class="kpi-sub">{{ $rows->count() }} customers</div>
  </div>
  <div class="kpi-card card-accent-green">
    <div class="kpi-label">Total Collected</div>
    <div class="kpi-value mono" style="color:#16a34a">{{ fmtIDR($summary['actual']) }}</div>
    <div class="kpi-sub">actual payments received</div>
  </div>
  <div class="kpi-card card-accent-yellow">
    <div class="kpi-label">Collection Rate</div>
    <div class="kpi-value" style="color:{{ $summary['rate'] === null ? '#94a3b8' : ($summary['rate'] >= 100 ? '#16a34a' : ($summary['rate'] >= 70 ? '#d97706' : '#dc2626')) }}">
      {{ $summary['rate'] !== null ? $summary['rate'].'%' : 'N/A' }}
    </div>
    <div class="kpi-sub">of target</div>
  </div>
  <div class="kpi-card card-accent-red">
    <div class="kpi-label">Status Breakdown</div>
    <div style="display:flex;gap:6px;flex-wrap:wrap;margin-top:6px">
      <span class="badge badge-green">✓ {{ $summary['achieved'] }}</span>
      <span class="badge badge-yellow">~ {{ $summary['partial'] }}</span>
      <span class="badge badge-red">✗ {{ $summary['none'] }}</span>
      <span class="badge badge-gray">— {{ $summary['no_target'] }}</span>
    </div>
  </div>
</div>

{{-- Collection Table --}}
<div style="background:var(--surface);border:1px solid var(--border);border-radius:12px;overflow:hidden;box-shadow:var(--shadow)">
  <div style="padding:16px 20px;border-bottom:1px solid var(--border)">
    <div style="font-size:12px;font-weight:700">Collection Detail ({{ $rows->count() }} records)</div>
  </div>
  <div class="table-scroll">
    <table class="data-table">
      <thead><tr>
        <th>Customer</th><th>Plant</th><th>Collector</th>
        <th class="num">Total AR</th>
        <th class="num">Target</th>
        <th class="num">Collected</th>
        <th>Rate</th>
        <th>Status</th>
        <th style="width:60px;text-align:center">Edit</th>
      </tr></thead>
      <tbody>
      @foreach($rows as $r)
      @php
        $rate   = $r->collection_rate;
        $status = $r->collection_status;
        $badgeCls = match($status){
          'achieved' => 'badge-green',
          'partial'  => 'badge-yellow',
          'none'     => 'badge-red',
          default    => 'badge-gray'
        };
        $statusLabel = match($status){
          'achieved' => '✓ Achieved',
          'partial'  => '~ Partial',
          'none'     => '✗ None',
          default    => '— No Target'
        };
        $barColor = $rate === null ? '#94a3b8' : ($rate >= 100 ? '#16a34a' : ($rate >= 70 ? '#d97706' : '#dc2626'));
        $pct = min($rate ?? 0, 100);
      @endphp
      <tr>
        <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-weight:600" title="{{ $r->customer_name }}">{{ $r->customer_name }}</td>
        <td><span class="badge badge-blue">{{ $r->plant }}</span></td>
        <td style="font-size:11px">{{ $r->collection_by }}</td>
        <td class="num">{{ fmtIDR($r->total) }}</td>
        <td class="num">{{ $r->ar_target > 0 ? fmtIDR($r->ar_target) : '—' }}</td>
        <td class="num" style="color:#16a34a;font-weight:600">{{ $r->ar_actual > 0 ? fmtIDR($r->ar_actual) : '—' }}</td>
        <td style="min-width:120px">
          <div style="display:flex;align-items:center;gap:8px">
            <div class="progress-bg" style="flex:1">
              <div class="progress-fill" style="width:{{ $pct }}%;background:{{ $barColor }}"></div>
            </div>
            <span style="font-size:10px;color:{{ $barColor }};min-width:34px;font-weight:700">
              {{ $rate !== null ? $rate.'%' : '—' }}
            </span>
          </div>
        </td>
        <td><span class="badge {{ $badgeCls }}">{{ $statusLabel }}</span></td>
        <td style="text-align:center">
          <button class="btn btn-warning btn-sm" onclick='openEditModal(@json((array)$r))'>✏️</button>
        </td>
      </tr>
      @endforeach
      </tbody>
    </table>
  </div>
</div>

@endsection