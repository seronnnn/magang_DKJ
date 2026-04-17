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
          <button class="btn btn-warning btn-sm"
            data-edit="{{ htmlspecialchars(json_encode([
              'id'=>$r->id,'customer_id'=>$r->customer_id,'customer_name'=>$r->customer_name,
              'collection_by'=>$r->collection_by,'plant'=>$r->plant,
              'current'=>$r->current,'days_1_30'=>$r->days_1_30,'days_30_60'=>$r->days_30_60,
              'days_60_90'=>$r->days_60_90,'days_over_90'=>$r->days_over_90,
              'total'=>$r->total,'ar_target'=>$r->ar_target,'ar_actual'=>$r->ar_actual,
              'so_without_od'=>$r->so_without_od,'so_with_od'=>$r->so_with_od,'total_so'=>$r->total_so
            ]), ENT_QUOTES) }}"
            onclick="openEditModal(JSON.parse(this.dataset.edit))">✏️</button>
        </td>
        @endif
      </tr>
      @endforeach
      </tbody>
    </table>
  </div>
</div>

@if($isAdmin)
{{-- ========== EDIT MODAL ========== --}}
<div id="editModal" style="display:none;position:fixed;inset:0;z-index:9999;align-items:center;justify-content:center;">
  <div onclick="closeEditModal()" style="position:absolute;inset:0;background:rgba(0,0,0,0.5);backdrop-filter:blur(2px)"></div>
  <div style="position:relative;background:var(--surface,#fff);border-radius:16px;box-shadow:0 20px 60px rgba(0,0,0,0.3);width:100%;max-width:520px;max-height:90vh;overflow-y:auto;margin:16px">
    {{-- Header --}}
    <div style="display:flex;align-items:center;justify-content:space-between;padding:20px 24px;border-bottom:1px solid var(--border,#e5e7eb)">
      <div>
        <div style="font-size:16px;font-weight:700">✏️ Edit SO Overlimit</div>
        <div id="modal-subtitle" style="font-size:12px;color:var(--muted,#6b7280);margin-top:2px"></div>
      </div>
      <button onclick="closeEditModal()" style="background:none;border:none;font-size:20px;cursor:pointer;color:var(--muted,#6b7280);line-height:1;padding:4px">×</button>
    </div>
    {{-- Form --}}
    <form id="editForm" method="POST" style="padding:24px">
      @csrf
      @method('PUT')
      <input type="hidden" name="id" id="f-id">

      {{-- Customer Info --}}
      <div style="background:var(--bg,#f8fafc);border-radius:10px;padding:14px;margin-bottom:20px;display:grid;grid-template-columns:1fr 1fr;gap:10px">
        <div>
          <div style="font-size:10px;font-weight:600;color:var(--muted,#6b7280);text-transform:uppercase;margin-bottom:3px">Customer</div>
          <div id="disp-customer" style="font-size:13px;font-weight:600"></div>
        </div>
        <div>
          <div style="font-size:10px;font-weight:600;color:var(--muted,#6b7280);text-transform:uppercase;margin-bottom:3px">Plant</div>
          <div id="disp-plant" style="font-size:13px"></div>
        </div>
        <div>
          <div style="font-size:10px;font-weight:600;color:var(--muted,#6b7280);text-transform:uppercase;margin-bottom:3px">Collector</div>
          <div id="disp-collector" style="font-size:13px"></div>
        </div>
        <div>
          <div style="font-size:10px;font-weight:600;color:var(--muted,#6b7280);text-transform:uppercase;margin-bottom:3px">Total AR</div>
          <div id="disp-total" style="font-size:13px;font-weight:700;color:#dc2626"></div>
        </div>
      </div>

      {{-- Sales Orders --}}
      <div style="font-size:11px;font-weight:700;color:var(--muted,#6b7280);text-transform:uppercase;letter-spacing:.5px;margin-bottom:12px">Sales Orders</div>
      <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin-bottom:20px">
        <div>
          <label style="font-size:11px;font-weight:600;display:block;margin-bottom:4px">SO Without OD</label>
          <input type="number" name="so_without_od" id="f-so_without_od" min="0" class="filter-input" style="width:100%">
        </div>
        <div>
          <label style="font-size:11px;font-weight:600;display:block;margin-bottom:4px">SO With OD</label>
          <input type="number" name="so_with_od" id="f-so_with_od" min="0" class="filter-input" style="width:100%">
        </div>
        <div>
          <label style="font-size:11px;font-weight:600;display:block;margin-bottom:4px">Total SO</label>
          <input type="number" name="total_so" id="f-total_so" min="0" class="filter-input" style="width:100%">
        </div>
      </div>

      {{-- AR Aging Buckets --}}
      <div style="font-size:11px;font-weight:700;color:var(--muted,#6b7280);text-transform:uppercase;letter-spacing:.5px;margin-bottom:12px">AR Aging Buckets</div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:20px">
        <div>
          <label style="font-size:11px;font-weight:600;display:block;margin-bottom:4px">Current (Rp)</label>
          <input type="number" name="current" id="f-current" min="0" class="filter-input" style="width:100%">
        </div>
        <div>
          <label style="font-size:11px;font-weight:600;display:block;margin-bottom:4px">1–30 Days (Rp)</label>
          <input type="number" name="days_1_30" id="f-days_1_30" min="0" class="filter-input" style="width:100%">
        </div>
        <div>
          <label style="font-size:11px;font-weight:600;display:block;margin-bottom:4px">30–60 Days (Rp)</label>
          <input type="number" name="days_30_60" id="f-days_30_60" min="0" class="filter-input" style="width:100%">
        </div>
        <div>
          <label style="font-size:11px;font-weight:600;display:block;margin-bottom:4px">60–90 Days (Rp)</label>
          <input type="number" name="days_60_90" id="f-days_60_90" min="0" class="filter-input" style="width:100%">
        </div>
        <div>
          <label style="font-size:11px;font-weight:600;display:block;margin-bottom:4px">&gt;90 Days (Rp)</label>
          <input type="number" name="days_over_90" id="f-days_over_90" min="0" class="filter-input" style="width:100%">
        </div>
      </div>

      {{-- Collection --}}
      <div style="font-size:11px;font-weight:700;color:var(--muted,#6b7280);text-transform:uppercase;letter-spacing:.5px;margin-bottom:12px">Collection</div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:24px">
        <div>
          <label style="font-size:11px;font-weight:600;display:block;margin-bottom:4px">AR Target (Rp)</label>
          <input type="number" name="ar_target" id="f-ar_target" min="0" class="filter-input" style="width:100%">
        </div>
        <div>
          <label style="font-size:11px;font-weight:600;display:block;margin-bottom:4px">AR Actual (Rp)</label>
          <input type="number" name="ar_actual" id="f-ar_actual" min="0" class="filter-input" style="width:100%">
        </div>
      </div>

      {{-- Actions --}}
      <div style="display:flex;gap:10px;justify-content:flex-end;padding-top:16px;border-top:1px solid var(--border,#e5e7eb)">
        <button type="button" onclick="closeEditModal()" class="btn btn-ghost">Cancel</button>
        <button type="submit" class="btn btn-primary">💾 Save Changes</button>
      </div>
    </form>
  </div>
</div>

<script>
function openEditModal(d) {
  document.getElementById('modal-subtitle').textContent  = d.customer_name + ' — ' + d.plant;
  document.getElementById('disp-customer').textContent   = d.customer_name;
  document.getElementById('disp-plant').textContent      = d.plant;
  document.getElementById('disp-collector').textContent  = d.collection_by;
  document.getElementById('disp-total').textContent      = 'Rp ' + Number(d.total).toLocaleString('id-ID');

  document.getElementById('f-id').value            = d.id;
  document.getElementById('f-so_without_od').value = d.so_without_od ?? 0;
  document.getElementById('f-so_with_od').value    = d.so_with_od    ?? 0;
  document.getElementById('f-total_so').value      = d.total_so      ?? 0;
  document.getElementById('f-current').value       = d.current       ?? 0;
  document.getElementById('f-days_1_30').value     = d.days_1_30     ?? 0;
  document.getElementById('f-days_30_60').value    = d.days_30_60    ?? 0;
  document.getElementById('f-days_60_90').value    = d.days_60_90    ?? 0;
  document.getElementById('f-days_over_90').value  = d.days_over_90  ?? 0;
  document.getElementById('f-ar_target').value     = d.ar_target     ?? 0;
  document.getElementById('f-ar_actual').value     = d.ar_actual     ?? 0;

  document.getElementById('editForm').action = '/dashboard/overlimit/' + d.id;

  const modal = document.getElementById('editModal');
  modal.style.display = 'flex';
  document.body.style.overflow = 'hidden';
}

function closeEditModal() {
  document.getElementById('editModal').style.display = 'none';
  document.body.style.overflow = '';
}

document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') closeEditModal();
});
</script>
@endif

@endsection