@extends('layouts.app')
@section('title','SO Overlimit')
@section('page-title','SO Overlimit')

@php
$isAdmin = Auth::user()->isAdmin();
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
        @if($isAdmin)<th style="width:60px;text-align:center">Edit</th>@endif
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
const allOverlimitRows = @json($rows->values());
const overlimitRowMap = {};
allOverlimitRows.forEach(r => { overlimitRowMap[r.id] = r; });

document.querySelectorAll('button[data-id]').forEach(function(btn) {
  btn.addEventListener('click', function() {
    const d = overlimitRowMap[parseInt(this.dataset.id)];
    if (!d) return;
    openOverlimitEditModal(d);
  });
});

function openOverlimitEditModal(d) {
  document.getElementById('ol-modal-subtitle').textContent  = d.customer_name + ' — Plant ' + d.plant;
  document.getElementById('ol-disp-customer').textContent   = d.customer_name;
  document.getElementById('ol-disp-plant').textContent      = d.plant;
  document.getElementById('ol-disp-collector').textContent  = d.collection_by;
  document.getElementById('ol-disp-total').textContent      = 'Rp ' + Number(d.total).toLocaleString('id-ID');

  document.getElementById('of-id').value            = d.id;
  document.getElementById('of-so_without_od').value = d.so_without_od || 0;
  document.getElementById('of-so_with_od').value    = d.so_with_od    || 0;
  document.getElementById('of-total_so').value      = d.total_so      || 0;
  document.getElementById('of-current').value       = d.current       || 0;
  document.getElementById('of-days_1_30').value     = d.days_1_30     || 0;
  document.getElementById('of-days_30_60').value    = d.days_30_60    || 0;
  document.getElementById('of-days_60_90').value    = d.days_60_90    || 0;
  document.getElementById('of-days_over_90').value  = d.days_over_90  || 0;
  document.getElementById('of-ar_target').value     = d.ar_target     || 0;
  document.getElementById('of-ar_actual').value     = d.ar_actual     || 0;

  document.getElementById('of-form').action = '/dashboard/ar-data/' + d.id;

  const modal = document.getElementById('ol-edit-modal');
  modal.style.display = 'flex';
  document.body.style.overflow = 'hidden';
}

function closeOverlimitEditModal() {
  document.getElementById('ol-edit-modal').style.display = 'none';
  document.body.style.overflow = '';
}

document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') closeOverlimitEditModal();
});
</script>

<div id="ol-edit-modal" onclick="if(event.target===this)closeOverlimitEditModal()" style="display:none;position:fixed;inset:0;z-index:9999;align-items:center;justify-content:center;background:rgba(0,0,0,0.5);backdrop-filter:blur(2px)">
  <div style="position:relative;background:var(--surface,#fff);border-radius:16px;box-shadow:0 20px 60px rgba(0,0,0,0.3);width:100%;max-width:520px;max-height:90vh;overflow-y:auto;margin:16px">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:20px 24px;border-bottom:1px solid var(--border,#e5e7eb)">
      <div>
        <div style="font-size:16px;font-weight:700">✏️ Edit SO Overlimit</div>
        <div id="ol-modal-subtitle" style="font-size:12px;color:var(--muted,#6b7280);margin-top:2px"></div>
      </div>
      <button onclick="closeOverlimitEditModal()" style="background:none;border:none;font-size:20px;cursor:pointer;color:var(--muted,#6b7280);line-height:1;padding:4px">×</button>
    </div>
    <form id="of-form" method="POST" style="padding:24px">
      @csrf @method('PUT')
      <input type="hidden" name="id" id="of-id">

      <div style="background:var(--bg,#f8fafc);border-radius:10px;padding:14px;margin-bottom:20px;display:grid;grid-template-columns:1fr 1fr;gap:10px">
        <div><div style="font-size:10px;font-weight:600;color:var(--muted);text-transform:uppercase;margin-bottom:3px">Customer</div><div id="ol-disp-customer" style="font-size:13px;font-weight:600"></div></div>
        <div><div style="font-size:10px;font-weight:600;color:var(--muted);text-transform:uppercase;margin-bottom:3px">Plant</div><div id="ol-disp-plant" style="font-size:13px"></div></div>
        <div><div style="font-size:10px;font-weight:600;color:var(--muted);text-transform:uppercase;margin-bottom:3px">Collector</div><div id="ol-disp-collector" style="font-size:13px"></div></div>
        <div><div style="font-size:10px;font-weight:600;color:var(--muted);text-transform:uppercase;margin-bottom:3px">Total AR</div><div id="ol-disp-total" style="font-size:13px;font-weight:700;color:#dc2626"></div></div>
      </div>

      <div style="font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:12px">Sales Orders</div>
      <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin-bottom:20px">
        <div><label style="font-size:11px;font-weight:600;display:block;margin-bottom:4px">SO Without OD</label><input type="number" name="so_without_od" id="of-so_without_od" min="0" class="filter-input" style="width:100%"></div>
        <div><label style="font-size:11px;font-weight:600;display:block;margin-bottom:4px">SO With OD</label><input type="number" name="so_with_od" id="of-so_with_od" min="0" class="filter-input" style="width:100%"></div>
        <div><label style="font-size:11px;font-weight:600;display:block;margin-bottom:4px">Total SO</label><input type="number" name="total_so" id="of-total_so" min="0" class="filter-input" style="width:100%"></div>
      </div>

      <div style="font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:12px">AR Aging Buckets</div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:20px">
        <div><label style="font-size:11px;font-weight:600;display:block;margin-bottom:4px">Current (Rp)</label><input type="number" name="current" id="of-current" min="0" class="filter-input" style="width:100%"></div>
        <div><label style="font-size:11px;font-weight:600;display:block;margin-bottom:4px">1–30 Days (Rp)</label><input type="number" name="days_1_30" id="of-days_1_30" min="0" class="filter-input" style="width:100%"></div>
        <div><label style="font-size:11px;font-weight:600;display:block;margin-bottom:4px">30–60 Days (Rp)</label><input type="number" name="days_30_60" id="of-days_30_60" min="0" class="filter-input" style="width:100%"></div>
        <div><label style="font-size:11px;font-weight:600;display:block;margin-bottom:4px">60–90 Days (Rp)</label><input type="number" name="days_60_90" id="of-days_60_90" min="0" class="filter-input" style="width:100%"></div>
        <div><label style="font-size:11px;font-weight:600;display:block;margin-bottom:4px">&gt;90 Days (Rp)</label><input type="number" name="days_over_90" id="of-days_over_90" min="0" class="filter-input" style="width:100%"></div>
      </div>

      <div style="font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:12px">Collection</div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:24px">
        <div><label style="font-size:11px;font-weight:600;display:block;margin-bottom:4px">AR Target (Rp)</label><input type="number" name="ar_target" id="of-ar_target" min="0" class="filter-input" style="width:100%"></div>
        <div><label style="font-size:11px;font-weight:600;display:block;margin-bottom:4px">AR Actual (Rp)</label><input type="number" name="ar_actual" id="of-ar_actual" min="0" class="filter-input" style="width:100%"></div>
      </div>

      <div style="display:flex;gap:10px;justify-content:flex-end;padding-top:16px;border-top:1px solid var(--border,#e5e7eb)">
        <button type="button" onclick="closeOverlimitEditModal()" class="btn btn-ghost">Cancel</button>
        <button type="submit" class="btn btn-primary">💾 Save Changes</button>
      </div>
    </form>
  </div>
</div>
@endif

@endsection