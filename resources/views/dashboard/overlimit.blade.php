{{-- overlimit.blade.php --}}
@extends('layouts.app')
@section('title','SO Overlimit')
@section('page-title','SO Overlimit')

@php
function fmtIDR($v){ if($v>=1e12) return 'Rp '.number_format($v/1e12,2).'T'; if($v>=1e9) return 'Rp '.number_format($v/1e9,2).'B'; if($v>=1e6) return 'Rp '.number_format($v/1e6,1).'M'; return 'Rp '.number_format($v); }
@endphp

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
    <div class="kpi-value" style="color:#d97706">{{ $totalOverlimit }}</div>
    <div class="kpi-sub">sales orders</div>
  </div>
  <div class="kpi-card card-accent-red">
    <div class="kpi-label">Exposed AR</div>
    <div class="kpi-value mono" style="color:#dc2626">{{ fmtIDR($totalExposed) }}</div>
    <div class="kpi-sub">total outstanding</div>
  </div>
</div>

<div style="background:var(--surface);border:1px solid var(--border);border-radius:12px;overflow:hidden;box-shadow:var(--shadow)">
  <div style="padding:16px 20px;border-bottom:1px solid var(--border);background:#fff5f5">
    <div style="font-size:12px;font-weight:700;color:#dc2626">⚠️ Customers with Overdue Sales Orders ({{ $rows->count() }})</div>
  </div>
  <div class="table-scroll">
    <table class="data-table">
      <thead><tr>
        <th>Customer</th><th>Plant</th><th>Collector</th>
        <th class="num">SO Without OD</th><th class="num">SO With OD</th>
        <th class="num">Total SO</th><th class="num">Total AR</th><th>Risk</th>
      </tr></thead>
      <tbody>
      @foreach($rows as $r)
      @php
        $risk = $r->so_with_od >= 20 ? ['High','badge-red'] : ($r->so_with_od >= 10 ? ['Medium','badge-orange'] : ['Low','badge-yellow']);
      @endphp
      <tr>
        <td style="max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-weight:600" title="{{ $r->customer_name }}">{{ $r->customer_name }}</td>
        <td><span class="badge badge-blue">{{ $r->plant }}</span></td>
        <td style="font-size:11px">{{ $r->collection_by }}</td>
        <td class="num">{{ $r->so_without_od }}</td>
        <td class="num" style="color:#dc2626;font-weight:700">{{ $r->so_with_od }}</td>
        <td class="num">{{ $r->total_so }}</td>
        <td class="num">{{ fmtIDR($r->total) }}</td>
        <td><span class="badge {{ $risk[1] }}">{{ $risk[0] }}</span></td>
      </tr>
      @endforeach
      </tbody>
    </table>
  </div>
</div>
@endsection