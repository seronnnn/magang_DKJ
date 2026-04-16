@extends('layouts.app')
@section('title','Overview')
@section('page-title','Dashboard Overview')

@php
function fmtIDR($v){ if($v>=1e12) return 'Rp '.number_format($v/1e12,2).'T'; if($v>=1e9) return 'Rp '.number_format($v/1e9,2).'B'; if($v>=1e6) return 'Rp '.number_format($v/1e6,1).'M'; return 'Rp '.number_format($v); }
@endphp

@section('topbar-actions')
  @include('partials.filters')
@endsection

@section('content')

{{-- ══════════ AGING BUCKET MODAL ══════════ --}}
<div id="aging-modal-overlay"
     onclick="if(event.target===this) closeAgingModal()"
     style="display:none;position:fixed;inset:0;background:rgba(15,31,54,.45);z-index:200;
            align-items:center;justify-content:center;backdrop-filter:blur(3px)">
  <div id="aging-modal-box"
       style="background:#fff;border:1px solid var(--border);border-radius:16px;padding:28px;
              width:740px;max-width:95vw;max-height:86vh;overflow-y:auto;
              box-shadow:0 20px 60px rgba(15,31,54,.22)">

    {{-- Modal header --}}
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
      <div style="display:flex;align-items:center;gap:10px">
        <span id="modal-bucket-dot"
              style="display:inline-block;width:14px;height:14px;border-radius:4px;flex-shrink:0"></span>
        <div>
          <div id="modal-bucket-title"
               style="font-size:16px;font-weight:800;color:var(--navy);letter-spacing:-.3px"></div>
          <div style="font-size:11px;color:var(--muted);margin-top:1px">Aging AR — Customer Breakdown</div>
        </div>
      </div>
      <button onclick="closeAgingModal()"
              style="background:none;border:1px solid var(--border);border-radius:8px;
                     width:32px;height:32px;cursor:pointer;font-size:18px;color:var(--muted);
                     display:flex;align-items:center;justify-content:center;transition:all .15s"
              onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='none'">×</button>
    </div>

    {{-- Summary row --}}
    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin-bottom:20px">
      <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:14px 16px">
        <div style="font-size:10px;color:var(--muted);font-weight:700;text-transform:uppercase;letter-spacing:.08em;margin-bottom:5px">Total Amount</div>
        <div id="modal-total-amount" class="mono" style="font-size:20px;font-weight:800"></div>
      </div>
      <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:14px 16px">
        <div style="font-size:10px;color:var(--muted);font-weight:700;text-transform:uppercase;letter-spacing:.08em;margin-bottom:5px">Customers</div>
        <div id="modal-cust-count" class="mono" style="font-size:20px;font-weight:800;color:var(--navy)"></div>
      </div>
      <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:14px 16px">
        <div style="font-size:10px;color:var(--muted);font-weight:700;text-transform:uppercase;letter-spacing:.08em;margin-bottom:5px">% of Total AR</div>
        <div id="modal-pct-total" class="mono" style="font-size:20px;font-weight:800;color:var(--muted)"></div>
      </div>
    </div>

    {{-- Loading / table --}}
    <div id="modal-loading"
         style="text-align:center;padding:40px;color:var(--muted);font-size:13px;display:none">
      <div style="font-size:24px;margin-bottom:8px">⏳</div>
      Loading customer data…
    </div>

    <div id="modal-table-wrap"
         style="border:1px solid var(--border);border-radius:10px;overflow:hidden;display:none">
      <div style="overflow-y:auto;max-height:360px">
        <table class="data-table">
          <thead>
            <tr>
              <th style="position:sticky;top:0;background:#f8fafc;z-index:1">#</th>
              <th style="position:sticky;top:0;background:#f8fafc;z-index:1">Customer</th>
              <th style="position:sticky;top:0;background:#f8fafc;z-index:1">Collector</th>
              <th style="position:sticky;top:0;background:#f8fafc;z-index:1">Plant</th>
              <th class="num" id="modal-bucket-col-header" style="position:sticky;top:0;background:#f8fafc;z-index:1">Bucket AR</th>
              <th class="num" style="position:sticky;top:0;background:#f8fafc;z-index:1">Total AR</th>
              <th class="num" style="position:sticky;top:0;background:#f8fafc;z-index:1">% of Bucket</th>
            </tr>
          </thead>
          <tbody id="modal-tbody"></tbody>
        </table>
      </div>
    </div>

    <div style="display:flex;justify-content:flex-end;margin-top:18px">
      <button onclick="closeAgingModal()" class="btn btn-ghost">Close</button>
    </div>
  </div>
</div>
{{-- ══════════ END MODAL ══════════ --}}

{{-- KPI Row --}}
<div style="display:grid;grid-template-columns:repeat(5,1fr);gap:14px;margin-bottom:20px">
  <div class="kpi-card card-accent-blue">
    <div class="kpi-label">Total AR Outstanding</div>
    <div class="kpi-value mono">{{ fmtIDR($totalAR) }}</div>
    <div class="kpi-sub">{{ $rows->count() }} customers</div>
  </div>
  <div class="kpi-card card-accent-green">
    <div class="kpi-label">Collected</div>
    <div class="kpi-value mono" style="color:#16a34a">{{ fmtIDR($totalCollected) }}</div>
    <div class="kpi-sub">vs target {{ fmtIDR($totalTarget) }}</div>
  </div>
  <div class="kpi-card card-accent-yellow">
    <div class="kpi-label">Collection Rate</div>
    <div class="kpi-value" style="color:{{ $collectionRate >= 100 ? '#16a34a' : ($collectionRate >= 70 ? '#d97706' : '#dc2626') }}">
      {{ $collectionRate !== null ? $collectionRate.'%' : 'N/A' }}
    </div>
    <div class="kpi-sub">{{ $totalTarget > 0 ? 'of target' : 'no target set' }}</div>
  </div>
  <div class="kpi-card card-accent-red">
    <div class="kpi-label">Overdue Customers</div>
    <div class="kpi-value" style="color:#dc2626">{{ $overdueCustomers }}</div>
    <div class="kpi-sub">60+ days past due</div>
  </div>
  <div class="kpi-card card-accent-purple">
    <div class="kpi-label">SO With Overdue</div>
    <div class="kpi-value" style="color:#7c3aed">{{ $soWithOD }}</div>
    <div class="kpi-sub">of {{ $totalSO }} total SOs</div>
  </div>
</div>

{{-- Charts Row --}}
<div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px;margin-bottom:20px">
  {{-- Aging Breakdown — cursor:pointer hint --}}
  <div style="background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:18px;box-shadow:var(--shadow)">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px">
      <div style="font-size:12px;font-weight:700;color:var(--text)">Aging Breakdown</div>
      <div style="font-size:10px;color:var(--muted);display:flex;align-items:center;gap:4px">
        <span style="font-size:12px">👆</span> Click a slice to drill down
      </div>
    </div>
    <div class="chart-wrap" style="cursor:pointer"><canvas id="agingChart"></canvas></div>
  </div>

  {{-- Collection by Collector --}}
  <div style="background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:18px;box-shadow:var(--shadow)">
    <div style="font-size:12px;font-weight:700;color:var(--text);margin-bottom:12px">Collection by Collector</div>
    <div class="chart-wrap"><canvas id="collectorChart"></canvas></div>
  </div>

  {{-- AR by Plant --}}
  <div style="background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:18px;box-shadow:var(--shadow)">
    <div style="font-size:12px;font-weight:700;color:var(--text);margin-bottom:12px">AR by Plant</div>
    <div class="chart-wrap"><canvas id="plantChart"></canvas></div>
  </div>
</div>

{{-- Collector Table + Top Customers --}}
<div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
  <div style="background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:18px;box-shadow:var(--shadow)">
    <div style="font-size:12px;font-weight:700;margin-bottom:14px">Collector Performance</div>
    <table class="data-table">
      <thead><tr>
        <th>Collector</th><th>Customers</th><th class="num">Target</th><th class="num">Actual</th><th>Rate</th>
      </tr></thead>
      <tbody>
      @foreach($byCollector as $name => $c)
      <tr>
        <td><strong>{{ $name }}</strong></td>
        <td>{{ $c['customers'] }}</td>
        <td class="num">{{ fmtIDR($c['target']) }}</td>
        <td class="num">{{ fmtIDR($c['actual']) }}</td>
        <td>
          @if($c['rate'] === null)
            <span class="badge badge-gray">No Target</span>
          @elseif($c['rate'] >= 100)
            <span class="badge badge-green">{{ $c['rate'] }}%</span>
          @elseif($c['rate'] >= 70)
            <span class="badge badge-yellow">{{ $c['rate'] }}%</span>
          @else
            <span class="badge badge-red">{{ $c['rate'] }}%</span>
          @endif
        </td>
      </tr>
      @endforeach
      </tbody>
    </table>
  </div>

  <div style="background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:18px;box-shadow:var(--shadow)">
    <div style="font-size:12px;font-weight:700;margin-bottom:14px">Top 5 Customers by AR</div>
    <table class="data-table">
      <thead><tr><th>Customer</th><th>Plant</th><th class="num">Total AR</th></tr></thead>
      <tbody>
      @foreach($topCustomers as $r)
      <tr>
        <td style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="{{ $r->customer_name }}">
          <a href="{{ route('dashboard.customers') }}?search={{ urlencode($r->customer_id) }}" style="color:var(--navy);text-decoration:none;font-weight:600">{{ $r->customer_name }}</a>
        </td>
        <td><span class="badge badge-blue">{{ $r->plant }}</span></td>
        <td class="num">{{ fmtIDR($r->total) }}</td>
      </tr>
      @endforeach
      </tbody>
    </table>
  </div>
</div>

@endsection

@push('scripts')
<script>
/* ─── PHP data passed to JS ─── */
const aging       = @json($aging);
const byCollector = @json($byCollector);
const byPlant     = @json($byPlant);
const totalAR     = {{ $totalAR }};

/* ─── Bucket metadata ─── */
const BUCKET_KEYS   = ['current','days_1_30','days_30_60','days_60_90','over_90'];
const BUCKET_LABELS = ['Current','1–30 Days','30–60 Days','60–90 Days','> 90 Days'];
const BUCKET_COLORS = ['#1B3A6B','#1e88e5','#d97706','#ea580c','#dc2626'];

/* ─── Formatting helpers ─── */
function fmtIDR(v) {
  if (!v || v === 0) return 'Rp 0';
  if (v >= 1e12) return 'Rp ' + (v/1e12).toFixed(2) + 'T';
  if (v >= 1e9)  return 'Rp ' + (v/1e9).toFixed(2)  + 'B';
  if (v >= 1e6)  return 'Rp ' + (v/1e6).toFixed(1)  + 'M';
  return 'Rp ' + v.toLocaleString();
}

/* ══════════════════════════════════════════════
   AGING DOUGHNUT — clickable slices
══════════════════════════════════════════════ */
new Chart(document.getElementById('agingChart'), {
  type: 'doughnut',
  data: {
    labels: BUCKET_LABELS,
    datasets: [{
      data: [aging.current, aging.days_1_30, aging.days_30_60, aging.days_60_90, aging.over_90],
      backgroundColor: BUCKET_COLORS,
      borderWidth: 2,
      borderColor: '#ffffff',
      hoverOffset: 8
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    cutout: '62%',
    plugins: {
      legend: {
        position: 'bottom',
        labels: { font:{size:10}, boxWidth:10, padding:10,
          color: '#4a6080',
          generateLabels: chart => {
            const ds = chart.data.datasets[0];
            return chart.data.labels.map((lbl, i) => ({
              text: lbl,
              fillStyle: ds.backgroundColor[i],
              strokeStyle: '#fff',
              lineWidth: 1,
              index: i
            }));
          }
        },
        // clicking a legend item also opens modal
        onClick: (e, legendItem, legend) => {
          showAgingBucketModal(legendItem.index);
        }
      },
      tooltip: {
        callbacks: {
          label: ctx => ' ' + fmtIDR(ctx.raw)
        }
      }
    },
    onClick: (evt, elements) => {
      if (!elements.length) return;
      showAgingBucketModal(elements[0].index);
    }
  }
});

/* ══════════════════════════════════════════════
   showAgingBucketModal(idx)
   Fetches /dashboard/aging-bucket?bucket=KEY
   then builds the modal table client-side.
══════════════════════════════════════════════ */
function showAgingBucketModal(idx) {
  const bucketKey   = BUCKET_KEYS[idx];
  const bucketLabel = BUCKET_LABELS[idx];
  const bucketColor = BUCKET_COLORS[idx];

  // Update header
  document.getElementById('modal-bucket-dot').style.background   = bucketColor;
  document.getElementById('modal-bucket-title').textContent        = bucketLabel;
  document.getElementById('modal-bucket-col-header').textContent   = bucketLabel;
  document.getElementById('modal-total-amount').style.color        = bucketColor;

  // Reset content, show loading
  document.getElementById('modal-loading').style.display     = 'block';
  document.getElementById('modal-table-wrap').style.display  = 'none';
  document.getElementById('modal-total-amount').textContent  = '…';
  document.getElementById('modal-cust-count').textContent    = '…';
  document.getElementById('modal-pct-total').textContent     = '…';

  // Open overlay
  const overlay = document.getElementById('aging-modal-overlay');
  overlay.style.display = 'flex';
  // Trigger CSS transition
  requestAnimationFrame(() => {
    document.getElementById('aging-modal-box').style.opacity    = '1';
    document.getElementById('aging-modal-box').style.transform  = 'translateY(0)';
  });

  // Build query string (preserve current plant/collector filter)
  const params = new URLSearchParams(window.location.search);
  params.set('bucket', bucketKey);

  fetch(`{{ route('dashboard.agingBucket') }}?${params.toString()}`, {
    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
  })
  .then(r => r.json())
  .then(data => {
    const rows       = data.rows;        // [{customer_name, collection_by, plant, bucket_amount, total}, …]
    const bucketSum  = data.bucket_sum;
    const pct        = totalAR > 0 ? (bucketSum / totalAR * 100).toFixed(1) : '0.0';

    // Summary
    document.getElementById('modal-total-amount').textContent = fmtIDR(bucketSum);
    document.getElementById('modal-cust-count').textContent   = rows.length;
    document.getElementById('modal-pct-total').textContent    = pct + '%';

    // Table rows
    const tbody = document.getElementById('modal-tbody');

    if (rows.length === 0) {
      tbody.innerHTML = `<tr><td colspan="7" style="text-align:center;padding:32px;color:var(--muted);font-size:13px">
        No customers with AR in this bucket.</td></tr>`;
    } else {
      tbody.innerHTML = rows.map((r, i) => {
        const share = bucketSum > 0 ? (r.bucket_amount / bucketSum * 100).toFixed(1) : '0.0';
        const barW  = Math.min(share, 100);
        return `<tr>
          <td style="font-size:11px;color:var(--muted);font-family:'DM Mono',monospace">${i + 1}</td>
          <td style="max-width:210px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;font-weight:600;font-size:12px"
              title="${r.customer_name}">${r.customer_name}</td>
          <td>
            <span class="badge" style="background:${collectorColor(r.collection_by)};color:#fff;font-size:10px">
              ${r.collection_by}
            </span>
          </td>
          <td><span class="badge badge-blue" style="font-size:10px">${r.plant}</span></td>
          <td class="num" style="font-weight:700;color:${bucketColor};font-size:12px;font-family:'DM Mono',monospace">
            ${fmtIDR(r.bucket_amount)}
          </td>
          <td class="num" style="font-size:11px;color:var(--muted);font-family:'DM Mono',monospace">
            ${fmtIDR(r.total)}
          </td>
          <td class="num" style="min-width:90px">
            <div style="display:flex;align-items:center;gap:6px;justify-content:flex-end">
              <div style="flex:1;background:#e2e8f0;border-radius:99px;height:5px;min-width:50px">
                <div style="width:${barW}%;background:${bucketColor};height:100%;border-radius:99px"></div>
              </div>
              <span style="font-size:10px;color:var(--muted);font-family:'DM Mono',monospace;min-width:32px;text-align:right">${share}%</span>
            </div>
          </td>
        </tr>`;
      }).join('');
    }

    // Show table, hide loader
    document.getElementById('modal-loading').style.display    = 'none';
    document.getElementById('modal-table-wrap').style.display = 'block';
  })
  .catch(() => {
    document.getElementById('modal-loading').innerHTML =
      '<div style="color:#dc2626;padding:20px">⚠️ Failed to load data. Please try again.</div>';
  });
}

function closeAgingModal() {
  document.getElementById('aging-modal-overlay').style.display = 'none';
}

// Collector color helper
function collectorColor(name) {
  const map = { Mega:'#1B3A6B', Miya:'#7c3aed', Viona:'#16a34a', Risa:'#d97706' };
  return map[name] || '#64748b';
}

/* ─── Collector bar chart ─── */
const cNames = Object.keys(byCollector);
new Chart(document.getElementById('collectorChart'), {
  type: 'bar',
  data: {
    labels: cNames,
    datasets: [
      { label:'Target', data: cNames.map(n=>byCollector[n].target), backgroundColor:'#e2e8f0', borderRadius:4 },
      { label:'Actual', data: cNames.map(n=>byCollector[n].actual), backgroundColor:'#16a34a', borderRadius:4 }
    ]
  },
  options: {
    responsive:true, maintainAspectRatio:false,
    plugins:{legend:{labels:{font:{size:10},boxWidth:10}}},
    scales:{ x:{ticks:{font:{size:10}}}, y:{ticks:{font:{size:9},callback:v=>v>=1e9?'Rp '+(v/1e9).toFixed(1)+'B':'Rp '+(v/1e6).toFixed(0)+'M'}} }
  }
});

/* ─── Plant bar chart ─── */
const pNames = Object.keys(byPlant);
new Chart(document.getElementById('plantChart'), {
  type: 'bar',
  data: {
    labels: pNames.map(p=>'Plant '+p),
    datasets: [{ label:'AR Total', data: pNames.map(p=>byPlant[p].total), backgroundColor:'#1B3A6B', borderRadius:4 }]
  },
  options: {
    responsive:true, maintainAspectRatio:false,
    plugins:{legend:{display:false}},
    scales:{ x:{ticks:{font:{size:10}}}, y:{ticks:{font:{size:9},callback:v=>v>=1e9?'Rp '+(v/1e9).toFixed(1)+'B':'Rp '+(v/1e6).toFixed(0)+'M'}} }
  }
});

/* ─── Modal box entrance animation ─── */
const box = document.getElementById('aging-modal-box');
box.style.opacity   = '0';
box.style.transform = 'translateY(-16px)';
box.style.transition = 'opacity .2s ease, transform .2s ease';
</script>
@endpush