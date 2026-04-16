@extends('layouts.app')
@section('title','Collection')
@section('page-title','Collection')

@php
function fmtIDR($v){ if($v>=1e12) return 'Rp '.number_format($v/1e12,2).'T'; if($v>=1e9) return 'Rp '.number_format($v/1e9,2).'B'; if($v>=1e6) return 'Rp '.number_format($v/1e6,1).'M'; return 'Rp '.number_format($v); }
@endphp

@section('topbar-actions')
  @include('partials.filters')
@endsection

@section('content')

{{-- Summary KPIs --}}
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:20px">
  <div class="kpi-card card-accent-blue">
    <div class="kpi-label">Collection Target</div>
    <div class="kpi-value mono">{{ fmtIDR($summary['target']) }}</div>
  </div>
  <div class="kpi-card card-accent-green">
    <div class="kpi-label">Collected</div>
    <div class="kpi-value mono" style="color:#16a34a">{{ fmtIDR($summary['actual']) }}</div>
    <div class="kpi-sub">{{ $summary['rate'] !== null ? $summary['rate'].'% rate' : 'N/A' }}</div>
  </div>
  <div class="kpi-card card-accent-green">
    <div class="kpi-label">Achieved ≥100%</div>
    <div class="kpi-value" style="color:#16a34a">{{ $summary['achieved'] }}</div>
    <div class="kpi-sub">customers</div>
  </div>
  <div class="kpi-card card-accent-red">
    <div class="kpi-label">Not Collected</div>
    <div class="kpi-value" style="color:#dc2626">{{ $summary['none'] }}</div>
    <div class="kpi-sub">+ {{ $summary['no_target'] }} with no target</div>
  </div>
</div>

{{-- Record Payment Form --}}
<div style="background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:18px;margin-bottom:20px;box-shadow:var(--shadow)">
  <div style="font-size:12px;font-weight:700;margin-bottom:12px">📝 Record Collection Payment</div>
  <form method="POST" action="{{ route('dashboard.collect') }}" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap">
    @csrf
    <div>
      <label style="font-size:11px;font-weight:600;color:var(--muted);display:block;margin-bottom:4px">Customer</label>
      <select name="customer_id" class="filter-input" required style="min-width:260px">
        <option value="">— Select Customer —</option>
        @foreach($rows->sortBy('customer_name') as $r)
        <option value="{{ $r->customer_id }}">{{ $r->customer_name }} ({{ $r->plant }})</option>
        @endforeach
      </select>
    </div>
    <div>
      <label style="font-size:11px;font-weight:600;color:var(--muted);display:block;margin-bottom:4px">Amount (IDR)</label>
      <input type="number" name="amount" min="1" class="filter-input" placeholder="e.g. 500000000" required style="width:200px">
    </div>
    <button type="submit" class="btn btn-primary">+ Record</button>
  </form>
</div>

{{-- Collection Table --}}
<div style="background:var(--surface);border:1px solid var(--border);border-radius:12px;overflow:hidden;box-shadow:var(--shadow)">
  <div style="padding:16px 20px;border-bottom:1px solid var(--border)">
    <div style="font-size:12px;font-weight:700">Collection Detail — {{ $rows->count() }} customers</div>
  </div>
  <div style="overflow-x:auto">
    <table class="data-table">
      <thead><tr>
        <th>Customer</th><th>Plant</th><th>Collector</th>
        <th class="num">Target</th><th class="num">Actual</th><th class="num">Rate</th><th>Status</th>
        <th style="width:120px">Progress</th>
      </tr></thead>
      <tbody>
      @foreach($rows as $r)
      @php
        $rate = $r->collection_rate;
        $status = $r->collection_status;
        $badgeCls = match($status){ 'achieved'=>'badge-green','partial'=>'badge-yellow','none'=>'badge-red', default=>'badge-gray' };
        $statusLabel = match($status){ 'achieved'=>'Achieved','partial'=>'Partial','none'=>'None', default=>'No Target' };
        $barColor = match($status){ 'achieved'=>'#16a34a','partial'=>'#d97706','none'=>'#dc2626', default=>'#94a3b8' };
        $pct = min($rate ?? 0, 100);
      @endphp
      <tr>
        <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-weight:600" title="{{ $r->customer_name }}">{{ $r->customer_name }}</td>
        <td><span class="badge badge-blue">{{ $r->plant }}</span></td>
        <td style="font-size:11px">{{ $r->collection_by }}</td>
        <td class="num">{{ $r->ar_target > 0 ? fmtIDR($r->ar_target) : '—' }}</td>
        <td class="num">{{ $r->ar_actual > 0 ? fmtIDR($r->ar_actual) : '—' }}</td>
        <td class="num" style="font-weight:700">{{ $rate !== null ? $rate.'%' : '—' }}</td>
        <td><span class="badge {{ $badgeCls }}">{{ $statusLabel }}</span></td>
        <td>
          <div class="progress-bg">
            <div class="progress-fill" style="width:{{ $pct }}%;background:{{ $barColor }}"></div>
          </div>
        </td>
      </tr>
      @endforeach
      </tbody>
    </table>
  </div>
</div>
@endsection
