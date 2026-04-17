@extends('layouts.app')
@section('title','Collection')
@section('page-title','Collection')

@php
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
          <button class="btn btn-warning btn-sm edit-btn"
  data-edit="{{ htmlspecialchars(json_encode([
  'id'=>$r->id,'customer_id'=>$r->customer_id,'customer_name'=>$r->customer_name,
  'collection_by'=>$r->collection_by,'plant'=>$r->plant,
  'current'=>$r->current,'days_1_30'=>$r->days_1_30,'days_30_60'=>$r->days_30_60,
  'days_60_90'=>$r->days_60_90,'days_over_90'=>$r->days_over_90,
  'total'=>$r->total,'ar_target'=>$r->ar_target,'ar_actual'=>$r->ar_actual,
  'so_without_od'=>$r->so_without_od,'so_with_od'=>$r->so_with_od,'total_so'=>$r->total_so
], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_APOS), ENT_QUOTES) }}">✏️</button>
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
        <div style="font-size:16px;font-weight:700">✏️ Edit Collection</div>
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
          <div style="font-size:10px;font-weight:600;color:var(--muted,#6b7280);text-transform:uppercase;margin-bottom:3px">Collection Rate</div>
          <div id="disp-rate" style="font-size:13px;font-weight:700"></div>
        </div>
      </div>

      {{-- Collection Target & Actual --}}
      <div style="font-size:11px;font-weight:700;color:var(--muted,#6b7280);text-transform:uppercase;letter-spacing:.5px;margin-bottom:12px">Collection Target &amp; Actual</div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:20px">
        <div>
          <label style="font-size:11px;font-weight:600;display:block;margin-bottom:4px">AR Target (Rp)</label>
          <input type="number" name="ar_target" id="f-ar_target" min="0" class="filter-input" style="width:100%">
        </div>
        <div>
          <label style="font-size:11px;font-weight:600;display:block;margin-bottom:4px">AR Actual (Rp)</label>
          <input type="number" name="ar_actual" id="f-ar_actual" min="0" class="filter-input" style="width:100%">
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

      {{-- Sales Orders --}}
      <div style="font-size:11px;font-weight:700;color:var(--muted,#6b7280);text-transform:uppercase;letter-spacing:.5px;margin-bottom:12px">Sales Orders</div>
      <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin-bottom:24px">
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
  document.getElementById('modal-subtitle').textContent = d.customer_name + ' — ' + d.plant;
  document.getElementById('disp-customer').textContent  = d.customer_name;
  document.getElementById('disp-plant').textContent     = d.plant;
  document.getElementById('disp-collector').textContent = d.collection_by;

  // Calculate and display rate
  const rate = d.ar_target > 0 ? Math.round(d.ar_actual / d.ar_target * 100) + '%' : 'N/A';
  document.getElementById('disp-rate').textContent = rate;

  document.getElementById('f-id').value            = d.id;
  document.getElementById('f-ar_target').value     = d.ar_target    ?? 0;
  document.getElementById('f-ar_actual').value     = d.ar_actual    ?? 0;
  document.getElementById('f-current').value       = d.current      ?? 0;
  document.getElementById('f-days_1_30').value     = d.days_1_30    ?? 0;
  document.getElementById('f-days_30_60').value    = d.days_30_60   ?? 0;
  document.getElementById('f-days_60_90').value    = d.days_60_90   ?? 0;
  document.getElementById('f-days_over_90').value  = d.days_over_90 ?? 0;
  document.getElementById('f-so_without_od').value = d.so_without_od?? 0;
  document.getElementById('f-so_with_od').value    = d.so_with_od   ?? 0;
  document.getElementById('f-total_so').value      = d.total_so     ?? 0;

  document.getElementById('editForm').action = '/dashboard/collection/' + d.id;

  const modal = document.getElementById('editModal');
  modal.style.display = 'flex';
  document.body.style.overflow = 'hidden';
}

function closeEditModal() {
  document.getElementById('editModal').style.display = 'none';
  document.body.style.overflow = '';
}

document.querySelectorAll('.edit-btn').forEach(function(btn) {
  btn.addEventListener('click', function() {
    try {
      const d = JSON.parse(this.dataset.edit);
      openEditModal(d);
    } catch(e) {
      console.error('JSON parse error:', e);
      alert('Error opening modal: ' + e.message);
    }
  });
});
</script>
@endif

@endsection