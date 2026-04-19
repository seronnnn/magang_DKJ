@extends('layouts.app')
@section('title','Customers')
@section('page-title','Customers')

@php
$isAdmin = Auth::user()->isAdmin();
@endphp

@section('topbar-actions')
  <form method="GET" action="{{ route('dashboard.customers') }}" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
    <input type="text" name="search" value="{{ request('search') }}" class="filter-input" placeholder="Search customer…" style="width:160px">
    @if(request('plant'))<input type="hidden" name="plant" value="{{ request('plant') }}">@endif
    @if(request('collector'))<input type="hidden" name="collector" value="{{ request('collector') }}">@endif
    <button type="submit" class="btn btn-primary" style="padding:7px 14px">Search</button>
    @if(request('search'))<a href="{{ route('dashboard.customers') }}" class="btn btn-ghost" style="padding:7px 14px">✕</a>@endif
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
          {{ ($r->days_60_90+$r->days_over_90) > 0 ? fmtIDR($r->days_60_90+$r->days_over_90) : '—' }}
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
          <button class="btn btn-warning btn-sm" data-id="{{ $r->id }}">✏️</button>
        </td>
        @endif
      </tr>
      @endforeach
      </tbody>
    </table>
  </div>
</div>

@if($isAdmin)
<script>
const allCustomerRows = @json($rows->values());
const customerRowMap = {};
allCustomerRows.forEach(r => { customerRowMap[r.id] = r; });

document.querySelectorAll('button[data-id]').forEach(function(btn) {
  btn.addEventListener('click', function() {
    const d = customerRowMap[parseInt(this.dataset.id)];
    if (!d) return;
    openCustomerEditModal(d);
  });
});

function openCustomerEditModal(d) {
  document.getElementById('cust-modal-subtitle').textContent   = d.customer_id + ' — Plant ' + d.plant;
  document.getElementById('cust-disp-customer').textContent    = d.customer_name;
  document.getElementById('cust-disp-customerid').textContent  = 'ID: ' + d.customer_id;
  document.getElementById('cust-disp-plant').textContent       = d.plant;
  document.getElementById('cust-disp-collector').textContent   = d.collection_by;

  document.getElementById('custf-id').value            = d.id;
  document.getElementById('custf-current').value       = d.current       || 0;
  document.getElementById('custf-days_1_30').value     = d.days_1_30     || 0;
  document.getElementById('custf-days_30_60').value    = d.days_30_60    || 0;
  document.getElementById('custf-days_60_90').value    = d.days_60_90    || 0;
  document.getElementById('custf-days_over_90').value  = d.days_over_90  || 0;
  document.getElementById('custf-ar_target').value     = d.ar_target     || 0;
  document.getElementById('custf-ar_actual').value     = d.ar_actual     || 0;
  document.getElementById('custf-so_without_od').value = d.so_without_od || 0;
  document.getElementById('custf-so_with_od').value    = d.so_with_od    || 0;
  document.getElementById('custf-total_so').value      = d.total_so      || 0;

  document.getElementById('custf-form').action = '/dashboard/ar-data/' + d.id;

  const modal = document.getElementById('cust-edit-modal');
  modal.style.display = 'flex';
  document.body.style.overflow = 'hidden';
}

function closeCustomerEditModal() {
  document.getElementById('cust-edit-modal').style.display = 'none';
  document.body.style.overflow = '';
}

document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') closeCustomerEditModal();
});
</script>

<div id="cust-edit-modal" onclick="if(event.target===this)closeCustomerEditModal()" style="display:none;position:fixed;inset:0;z-index:9999;align-items:center;justify-content:center;background:rgba(0,0,0,0.5);backdrop-filter:blur(2px)">
  <div style="position:relative;background:var(--surface,#fff);border-radius:16px;box-shadow:0 20px 60px rgba(0,0,0,0.3);width:100%;max-width:580px;max-height:90vh;overflow-y:auto;margin:16px">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:20px 24px;border-bottom:1px solid var(--border,#e5e7eb)">
      <div>
        <div style="font-size:16px;font-weight:700">✏️ Edit Customer</div>
        <div id="cust-modal-subtitle" style="font-size:12px;color:var(--muted,#6b7280);margin-top:2px"></div>
      </div>
      <button onclick="closeCustomerEditModal()" style="background:none;border:none;font-size:20px;cursor:pointer;color:var(--muted,#6b7280);line-height:1;padding:4px">×</button>
    </div>
    <form id="custf-form" method="POST" style="padding:24px">
      @csrf @method('PUT')
      <input type="hidden" name="id" id="custf-id">

      <div style="background:var(--bg,#f8fafc);border-radius:10px;padding:14px;margin-bottom:20px;display:grid;grid-template-columns:1fr 1fr;gap:10px">
        <div style="grid-column:span 2">
          <div style="font-size:10px;font-weight:600;color:var(--muted);text-transform:uppercase;margin-bottom:3px">Customer</div>
          <div id="cust-disp-customer" style="font-size:14px;font-weight:700"></div>
          <div id="cust-disp-customerid" style="font-size:11px;color:var(--muted);margin-top:1px"></div>
        </div>
        <div><div style="font-size:10px;font-weight:600;color:var(--muted);text-transform:uppercase;margin-bottom:3px">Plant</div><div id="cust-disp-plant" style="font-size:13px"></div></div>
        <div><div style="font-size:10px;font-weight:600;color:var(--muted);text-transform:uppercase;margin-bottom:3px">Collector</div><div id="cust-disp-collector" style="font-size:13px"></div></div>
      </div>

      <div style="font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:12px">AR Aging Buckets</div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:20px">
        <div><label style="font-size:11px;font-weight:600;display:block;margin-bottom:4px">Current (Rp)</label><input type="number" name="current" id="custf-current" min="0" class="filter-input" style="width:100%"></div>
        <div><label style="font-size:11px;font-weight:600;display:block;margin-bottom:4px">1–30 Days (Rp)</label><input type="number" name="days_1_30" id="custf-days_1_30" min="0" class="filter-input" style="width:100%"></div>
        <div><label style="font-size:11px;font-weight:600;display:block;margin-bottom:4px">30–60 Days (Rp)</label><input type="number" name="days_30_60" id="custf-days_30_60" min="0" class="filter-input" style="width:100%"></div>
        <div><label style="font-size:11px;font-weight:600;display:block;margin-bottom:4px">60–90 Days (Rp)</label><input type="number" name="days_60_90" id="custf-days_60_90" min="0" class="filter-input" style="width:100%"></div>
        <div><label style="font-size:11px;font-weight:600;display:block;margin-bottom:4px">&gt;90 Days (Rp)</label><input type="number" name="days_over_90" id="custf-days_over_90" min="0" class="filter-input" style="width:100%"></div>
      </div>

      <div style="font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:12px">Collection Target &amp; Actual</div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:20px">
        <div><label style="font-size:11px;font-weight:600;display:block;margin-bottom:4px">AR Target (Rp)</label><input type="number" name="ar_target" id="custf-ar_target" min="0" class="filter-input" style="width:100%"></div>
        <div><label style="font-size:11px;font-weight:600;display:block;margin-bottom:4px">AR Actual (Rp)</label><input type="number" name="ar_actual" id="custf-ar_actual" min="0" class="filter-input" style="width:100%"></div>
      </div>

      <div style="font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:12px">Sales Orders</div>
      <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin-bottom:24px">
        <div><label style="font-size:11px;font-weight:600;display:block;margin-bottom:4px">SO Without OD</label><input type="number" name="so_without_od" id="custf-so_without_od" min="0" class="filter-input" style="width:100%"></div>
        <div><label style="font-size:11px;font-weight:600;display:block;margin-bottom:4px">SO With OD</label><input type="number" name="so_with_od" id="custf-so_with_od" min="0" class="filter-input" style="width:100%"></div>
        <div><label style="font-size:11px;font-weight:600;display:block;margin-bottom:4px">Total SO</label><input type="number" name="total_so" id="custf-total_so" min="0" class="filter-input" style="width:100%"></div>
      </div>

      <div style="display:flex;gap:10px;justify-content:flex-end;padding-top:16px;border-top:1px solid var(--border,#e5e7eb)">
        <button type="button" onclick="closeCustomerEditModal()" class="btn btn-ghost">Cancel</button>
        <button type="submit" class="btn btn-primary">💾 Save Changes</button>
      </div>
    </form>
  </div>
</div>
@endif

@endsection