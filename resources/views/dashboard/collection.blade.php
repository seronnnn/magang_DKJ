@extends('layouts.app')
@section('title','Collection')
@section('page-title','Collection')

@php
function fmtIDR($v){ if($v>=1e12) return 'Rp '.number_format($v/1e12,2).'T'; if($v>=1e9) return 'Rp '.number_format($v/1e9,2).'B'; if($v>=1e6) return 'Rp '.number_format($v/1e6,1).'M'; return 'Rp '.number_format($v); }
$isAdmin = Auth::user()->isAdmin();
@endphp

@section('topbar-actions')
  @include('partials.filters')
@endsection

@section('content')

{{-- Summary KPIs --}}
<div class="grid-kpi-4" style="margin-bottom:20px">
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
    <div style="flex:1;min-width:200px">
      <label style="font-size:11px;font-weight:600;color:var(--muted);display:block;margin-bottom:4px">Customer</label>
      <select name="customer_id" class="filter-input" required style="width:100%">
        <option value="">— Select Customer —</option>
        @foreach($rows->sortBy('customer_name') as $r)
        <option value="{{ $r->customer_id }}">{{ $r->customer_name }} ({{ $r->plant }})</option>
        @endforeach
      </select>
    </div>
    <div style="flex:1;min-width:160px">
      <label style="font-size:11px;font-weight:600;color:var(--muted);display:block;margin-bottom:4px">Amount (IDR)</label>
      <input type="number" name="amount" min="1" class="filter-input" placeholder="e.g. 500000000" required style="width:100%">
    </div>
    <button type="submit" class="btn btn-primary">+ Record</button>
  </form>
</div>

{{-- Collection Table --}}
<div style="background:var(--surface);border:1px solid var(--border);border-radius:12px;overflow:hidden;box-shadow:var(--shadow)">
  <div style="padding:16px 20px;border-bottom:1px solid var(--border)">
    <div style="font-size:12px;font-weight:700">Collection Detail — {{ $rows->count() }} customers</div>
  </div>
  <div class="table-scroll">
    <table class="data-table">
      <thead><tr>
        <th>Customer</th><th>Plant</th><th>Collector</th>
        <th class="num">Target</th><th class="num">Actual</th><th class="num">Rate</th>
        <th>Status</th><th style="min-width:100px">Progress</th>
        @if($isAdmin)<th style="width:60px;text-align:center">Edit</th>@endif
      </tr></thead>
      <tbody>
      @foreach($rows as $r)
      @php
        $rate       = $r->collection_rate;
        $status     = $r->collection_status;
        $badgeCls   = match($status){ 'achieved'=>'badge-green','partial'=>'badge-yellow','none'=>'badge-red', default=>'badge-gray' };
        $statusLabel= match($status){ 'achieved'=>'Achieved','partial'=>'Partial','none'=>'None', default=>'No Target' };
        $barColor   = match($status){ 'achieved'=>'#16a34a','partial'=>'#d97706','none'=>'#dc2626', default=>'#94a3b8' };
        $pct        = min($rate ?? 0, 100);
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
        @if($isAdmin)
        <td style="text-align:center">
          <button class="btn btn-warning btn-sm"
            onclick='openEditModal(@json([
              "id"=>$r->id,"customer_id"=>$r->customer_id,"customer_name"=>$r->customer_name,
              "collection_by"=>$r->collection_by,"plant"=>$r->plant,
              "current"=>$r->current,"days_1_30"=>$r->days_1_30,"days_30_60"=>$r->days_30_60,
              "days_60_90"=>$r->days_60_90,"days_over_90"=>$r->days_over_90,
              "total"=>$r->total,"ar_target"=>$r->ar_target,"ar_actual"=>$r->ar_actual,
              "so_without_od"=>$r->so_without_od,"so_with_od"=>$r->so_with_od,"total_so"=>$r->total_so
            ]))'>✏️</button>
        </td>
        @endif
      </tr>
      @endforeach
      </tbody>
    </table>
  </div>
</div>
@endsection