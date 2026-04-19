@extends('layouts.app')
@section('title','AR Aging')
@section('page-title','AR Aging')

@php
    $totalBucket = array_sum($buckets);
    $isAdmin = Auth::user()->isAdmin();
@endphp

@section('topbar-actions')
  @include('partials.filters')
@endsection

@section('content')

{{-- Bucket KPI Cards --}}
<div class="grid-kpi-5" style="margin-bottom:20px">
  @php
  $bucketDefs = [
    ['label'=>'Current',   'key'=>'current',    'color'=>'card-accent-blue',  'tc'=>'#1B3A6B'],
    ['label'=>'1–30 Days', 'key'=>'days_1_30',  'color'=>'card-accent-green', 'tc'=>'#16a34a'],
    ['label'=>'30–60 Days','key'=>'days_30_60', 'color'=>'card-accent-yellow','tc'=>'#d97706'],
    ['label'=>'60–90 Days','key'=>'days_60_90', 'color'=>'card-accent-red',   'tc'=>'#ea580c'],
    ['label'=>'> 90 Days', 'key'=>'over_90',    'color'=>'card-accent-red',   'tc'=>'#dc2626'],
  ];
  @endphp
  @foreach($bucketDefs as $b)
  <div class="kpi-card {{ $b['color'] }}">
    <div class="kpi-label">{{ $b['label'] }}</div>
    <div class="kpi-value mono" style="color:{{ $b['tc'] }}">{{ fmtIDR($buckets[$b['key']]) }}</div>
    <div class="kpi-sub">{{ pctOf($buckets[$b['key']],$totalBucket) }}% of total</div>
  </div>
  @endforeach
</div>

{{-- Aging Bar Chart --}}
<div style="background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:20px;margin-bottom:20px;box-shadow:var(--shadow)">
  <div style="font-size:12px;font-weight:700;margin-bottom:14px">Aging Distribution</div>
  @if($totalBucket > 0)
  <div style="display:flex;gap:2px;height:12px;border-radius:6px;overflow:hidden;margin-bottom:12px">
    @foreach([['current','#1B3A6B'],['days_1_30','#1e88e5'],['days_30_60','#d97706'],['days_60_90','#ea580c'],['over_90','#dc2626']] as [$k,$c])
      @if($buckets[$k] > 0)
      <div style="flex:{{ $buckets[$k] }};background:{{ $c }}" title="{{ $k }}: {{ fmtIDR($buckets[$k]) }}"></div>
      @endif
    @endforeach
  </div>
  <div style="display:flex;gap:16px;flex-wrap:wrap">
    @foreach([['Current','current','#1B3A6B'],['1-30d','days_1_30','#1e88e5'],['30-60d','days_30_60','#d97706'],['60-90d','days_60_90','#ea580c'],['>90d','over_90','#dc2626']] as [$lbl,$k,$c])
    <div style="display:flex;align-items:center;gap:5px">
      <div style="width:10px;height:10px;border-radius:2px;background:{{ $c }}"></div>
      <span style="font-size:11px;color:var(--muted)">{{ $lbl }}</span>
    </div>
    @endforeach
  </div>
  @endif
</div>

{{-- Customer Table --}}
<div style="background:var(--surface);border:1px solid var(--border);border-radius:12px;overflow:hidden;box-shadow:var(--shadow)">
  <div style="padding:16px 20px;border-bottom:1px solid var(--border)">
    <div style="font-size:12px;font-weight:700">Customer Aging Detail ({{ $rows->count() }} records)</div>
  </div>
  <div class="table-scroll">
    <table class="data-table">
      <thead><tr>
        <th>Customer</th><th>Plant</th><th>Collector</th>
        <th class="num">Current</th><th class="num">1-30d</th>
        <th class="num">30-60d</th><th class="num">60-90d</th>
        <th class="num">>90d</th><th class="num">Total</th><th>Aging</th>
        @if($isAdmin)<th style="width:60px;text-align:center">Edit</th>@endif
      </tr></thead>
      <tbody>
      @foreach($rows as $r)
      @php $hasOverdue = ($r->days_60_90 + $r->days_over_90) > 0; @endphp
      <tr style="{{ $hasOverdue ? 'background:#fff5f5' : '' }}">
        <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-weight:600" title="{{ $r->customer_name }}">{{ $r->customer_name }}</td>
        <td><span class="badge badge-blue">{{ $r->plant }}</span></td>
        <td style="font-size:11px">{{ $r->collection_by }}</td>
        <td class="num">{{ $r->current > 0 ? fmtIDR($r->current) : '—' }}</td>
        <td class="num">{{ $r->days_1_30 > 0 ? fmtIDR($r->days_1_30) : '—' }}</td>
        <td class="num" style="{{ $r->days_30_60 > 0 ? 'color:#d97706;font-weight:600' : '' }}">{{ $r->days_30_60 > 0 ? fmtIDR($r->days_30_60) : '—' }}</td>
        <td class="num" style="{{ $r->days_60_90 > 0 ? 'color:#ea580c;font-weight:600' : '' }}">{{ $r->days_60_90 > 0 ? fmtIDR($r->days_60_90) : '—' }}</td>
        <td class="num" style="{{ $r->days_over_90 > 0 ? 'color:#dc2626;font-weight:700' : '' }}">{{ $r->days_over_90 > 0 ? fmtIDR($r->days_over_90) : '—' }}</td>
        <td class="num" style="font-weight:700">{{ fmtIDR($r->total) }}</td>
        <td style="min-width:80px">
          <div style="display:flex;gap:2px;height:6px;border-radius:4px;overflow:hidden">
            <div style="flex:{{ $r->current }};background:#1B3A6B"></div>
            <div style="flex:{{ $r->days_1_30 }};background:#1e88e5"></div>
            <div style="flex:{{ $r->days_30_60 }};background:#d97706"></div>
            <div style="flex:{{ $r->days_60_90 }};background:#ea580c"></div>
            <div style="flex:{{ $r->days_over_90 }};background:#dc2626"></div>
          </div>
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
{{-- All rows as JS data (same pattern as overview) --}}
<script>
const allAgingRows = @json($rows->values());

// Build lookup by id
const agingRowMap = {};
allAgingRows.forEach(r => { agingRowMap[r.id] = r; });

// Attach click handlers
document.querySelectorAll('button[data-id]').forEach(function(btn) {
  btn.addEventListener('click', function() {
    const d = agingRowMap[parseInt(this.dataset.id)];
    if (!d) return;
    openAgingEditModal(d);
  });
});

function openAgingEditModal(d) {
  document.getElementById('aging-modal-subtitle').textContent  = d.customer_name + ' — Plant ' + d.plant;
  document.getElementById('aging-disp-customer').textContent   = d.customer_name;
  document.getElementById('aging-disp-plant').textContent      = d.plant;
  document.getElementById('aging-disp-collector').textContent  = d.collection_by;
  document.getElementById('aging-disp-total').textContent      = 'Rp ' + Number(d.total).toLocaleString('id-ID');

  document.getElementById('af-id').value           = d.id;
  document.getElementById('af-current').value      = d.current      || 0;
  document.getElementById('af-days_1_30').value    = d.days_1_30    || 0;
  document.getElementById('af-days_30_60').value   = d.days_30_60   || 0;
  document.getElementById('af-days_60_90').value   = d.days_60_90   || 0;
  document.getElementById('af-days_over_90').value = d.days_over_90 || 0;
  document.getElementById('af-ar_target').value    = d.ar_target    || 0;
  document.getElementById('af-ar_actual').value    = d.ar_actual    || 0;
  document.getElementById('af-so_without_od').value= d.so_without_od|| 0;
  document.getElementById('af-so_with_od').value   = d.so_with_od   || 0;
  document.getElementById('af-total_so').value     = d.total_so     || 0;

  document.getElementById('af-form').action = '/dashboard/ar-data/' + d.id;

  const modal = document.getElementById('aging-edit-modal');
  modal.style.display = 'flex';
  document.body.style.overflow = 'hidden';
}

function closeAgingEditModal() {
  document.getElementById('aging-edit-modal').style.display = 'none';
  document.body.style.overflow = '';
}

document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') closeAgingEditModal();
});
</script>

{{-- EDIT MODAL --}}
<div id="aging-edit-modal" onclick="if(event.target===this)closeAgingEditModal()" style="display:none;position:fixed;inset:0;z-index:9999;align-items:center;justify-content:center;background:rgba(0,0,0,0.5);backdrop-filter:blur(2px)">
  <div style="position:relative;background:var(--surface,#fff);border-radius:16px;box-shadow:0 20px 60px rgba(0,0,0,0.3);width:100%;max-width:560px;max-height:90vh;overflow-y:auto;margin:16px">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:20px 24px;border-bottom:1px solid var(--border,#e5e7eb)">
      <div>
        <div style="font-size:16px;font-weight:700">✏️ Edit AR Aging</div>
        <div id="aging-modal-subtitle" style="font-size:12px;color:var(--muted,#6b7280);margin-top:2px"></div>
      </div>
      <button onclick="closeAgingEditModal()" style="background:none;border:none;font-size:20px;cursor:pointer;color:var(--muted,#6b7280);line-height:1;padding:4px">×</button>
    </div>
    <form id="af-form" method="POST" style="padding:24px">
      @csrf @method('PUT')
      <input type="hidden" name="id" id="af-id">

      <div style="background:var(--bg,#f8fafc);border-radius:10px;padding:14px;margin-bottom:20px;display:grid;grid-template-columns:1fr 1fr;gap:10px">
        <div><div style="font-size:10px;font-weight:600;color:var(--muted);text-transform:uppercase;margin-bottom:3px">Customer</div><div id="aging-disp-customer" style="font-size:13px;font-weight:600"></div></div>
        <div><div style="font-size:10px;font-weight:600;color:var(--muted);text-transform:uppercase;margin-bottom:3px">Plant</div><div id="aging-disp-plant" style="font-size:13px"></div></div>
        <div><div style="font-size:10px;font-weight:600;color:var(--muted);text-transform:uppercase;margin-bottom:3px">Collector</div><div id="aging-disp-collector" style="font-size:13px"></div></div>
        <div><div style="font-size:10px;font-weight:600;color:var(--muted);text-transform:uppercase;margin-bottom:3px">Total AR</div><div id="aging-disp-total" style="font-size:13px;font-weight:700"></div></div>
      </div>

      <div style="font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:12px">AR Aging Buckets</div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:20px">
        <div><label style="font-size:11px;font-weight:600;display:block;margin-bottom:4px">Current (Rp)</label><input type="number" name="current" id="af-current" min="0" class="filter-input" style="width:100%"></div>
        <div><label style="font-size:11px;font-weight:600;display:block;margin-bottom:4px">1–30 Days (Rp)</label><input type="number" name="days_1_30" id="af-days_1_30" min="0" class="filter-input" style="width:100%"></div>
        <div><label style="font-size:11px;font-weight:600;display:block;margin-bottom:4px">30–60 Days (Rp)</label><input type="number" name="days_30_60" id="af-days_30_60" min="0" class="filter-input" style="width:100%"></div>
        <div><label style="font-size:11px;font-weight:600;display:block;margin-bottom:4px">60–90 Days (Rp)</label><input type="number" name="days_60_90" id="af-days_60_90" min="0" class="filter-input" style="width:100%"></div>
        <div><label style="font-size:11px;font-weight:600;display:block;margin-bottom:4px">&gt;90 Days (Rp)</label><input type="number" name="days_over_90" id="af-days_over_90" min="0" class="filter-input" style="width:100%"></div>
      </div>

      <div style="font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:12px">Collection</div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:20px">
        <div><label style="font-size:11px;font-weight:600;display:block;margin-bottom:4px">AR Target (Rp)</label><input type="number" name="ar_target" id="af-ar_target" min="0" class="filter-input" style="width:100%"></div>
        <div><label style="font-size:11px;font-weight:600;display:block;margin-bottom:4px">AR Actual (Rp)</label><input type="number" name="ar_actual" id="af-ar_actual" min="0" class="filter-input" style="width:100%"></div>
      </div>

      <div style="font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:12px">Sales Orders</div>
      <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin-bottom:24px">
        <div><label style="font-size:11px;font-weight:600;display:block;margin-bottom:4px">SO Without OD</label><input type="number" name="so_without_od" id="af-so_without_od" min="0" class="filter-input" style="width:100%"></div>
        <div><label style="font-size:11px;font-weight:600;display:block;margin-bottom:4px">SO With OD</label><input type="number" name="so_with_od" id="af-so_with_od" min="0" class="filter-input" style="width:100%"></div>
        <div><label style="font-size:11px;font-weight:600;display:block;margin-bottom:4px">Total SO</label><input type="number" name="total_so" id="af-total_so" min="0" class="filter-input" style="width:100%"></div>
      </div>

      <div style="display:flex;gap:10px;justify-content:flex-end;padding-top:16px;border-top:1px solid var(--border,#e5e7eb)">
        <button type="button" onclick="closeAgingEditModal()" class="btn btn-ghost">Cancel</button>
        <button type="submit" class="btn btn-primary">💾 Save Changes</button>
      </div>
    </form>
  </div>
</div>
@endif

@endsection