@extends('layouts.app')
@section('title','Overview')
@section('page-title','Dashboard Overview')



@section('topbar-actions')
  @include('partials.filters')
@endsection

@section('content')

{{-- ══════════ UNIVERSAL MODAL ══════════ --}}
<div id="chart-modal-overlay"
     onclick="if(event.target===this) closeChartModal()"
     style="display:none;position:fixed;inset:0;background:rgba(15,31,54,.45);z-index:200;
            align-items:center;justify-content:center;backdrop-filter:blur(3px)">
  <div id="chart-modal-box"
       style="background:#fff;border:1px solid var(--border);border-radius:16px;padding:28px;
              width:740px;max-width:95vw;max-height:88vh;overflow-y:auto;
              box-shadow:0 20px 60px rgba(15,31,54,.22)">

    {{-- Modal header --}}
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
      <div style="display:flex;align-items:center;gap:10px">
        <span id="modal-dot" style="display:inline-block;width:14px;height:14px;border-radius:4px;flex-shrink:0"></span>
        <div>
          <div id="modal-title" style="font-size:16px;font-weight:800;color:var(--navy);letter-spacing:-.3px"></div>
          <div id="modal-subtitle" style="font-size:11px;color:var(--muted);margin-top:1px"></div>
        </div>
      </div>
      <button onclick="closeChartModal()"
              style="background:none;border:1px solid var(--border);border-radius:8px;
                     width:32px;height:32px;cursor:pointer;font-size:18px;color:var(--muted);
                     display:flex;align-items:center;justify-content:center"
              onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='none'">×</button>
    </div>

    {{-- Summary row --}}
    <div id="modal-summary" style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin-bottom:20px"></div>

    {{-- Loading --}}
    <div id="modal-loading"
         style="text-align:center;padding:40px;color:var(--muted);font-size:13px;display:none">
      <div style="font-size:24px;margin-bottom:8px">⏳</div>Loading data…
    </div>

    {{-- Table --}}
    <div id="modal-table-wrap"
         style="border:1px solid var(--border);border-radius:10px;overflow:hidden;display:none">
      <div style="overflow-y:auto;overflow-x:auto;max-height:380px">
        <table class="data-table">
          <thead id="modal-thead"></thead>
          <tbody id="modal-tbody"></tbody>
        </table>
      </div>
    </div>

    <div style="display:flex;justify-content:flex-end;margin-top:18px">
      <button onclick="closeChartModal()" class="btn btn-ghost">Close</button>
    </div>
  </div>
</div>
{{-- ══════════ END MODAL ══════════ --}}

{{-- KPI Row --}}
<div class="grid-kpi-5" style="margin-bottom:20px">
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
<div class="grid-charts-3" style="margin-bottom:20px">
  {{-- Aging Breakdown --}}
  <div style="background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:18px;box-shadow:var(--shadow)">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px">
      <div style="font-size:12px;font-weight:700;color:var(--text)">Aging Breakdown</div>
      <div style="font-size:10px;color:var(--muted);display:flex;align-items:center;gap:4px">
        <span style="font-size:12px">👆</span> Click a slice
      </div>
    </div>
    <div class="chart-wrap" style="cursor:pointer"><canvas id="agingChart"></canvas></div>
  </div>

  {{-- Collection by Collector --}}
  <div style="background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:18px;box-shadow:var(--shadow)">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px">
      <div style="font-size:12px;font-weight:700;color:var(--text)">Collection by Collector</div>
      <div style="font-size:10px;color:var(--muted);display:flex;align-items:center;gap:4px">
        <span style="font-size:12px">👆</span> Click a bar
      </div>
    </div>
    <div class="chart-wrap" style="cursor:pointer"><canvas id="collectorChart"></canvas></div>
  </div>

  {{-- AR by Plant --}}
  <div style="background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:18px;box-shadow:var(--shadow)">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px">
      <div style="font-size:12px;font-weight:700;color:var(--text)">AR by Plant</div>
      <div style="font-size:10px;color:var(--muted);display:flex;align-items:center;gap:4px">
        <span style="font-size:12px">👆</span> Click a bar
      </div>
    </div>
    <div class="chart-wrap" style="cursor:pointer"><canvas id="plantChart"></canvas></div>
  </div>
</div>

{{-- Collector Table + Top Customers --}}
<div class="grid-2">
  <div style="background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:18px;box-shadow:var(--shadow)">
    <div style="font-size:12px;font-weight:700;margin-bottom:14px">Collector Performance</div>
    <div class="table-scroll">
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
  </div>

  <div style="background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:18px;box-shadow:var(--shadow)">
    <div style="font-size:12px;font-weight:700;margin-bottom:14px">Top 5 Customers by AR</div>
    <div class="table-scroll">
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
</div>

@endsection

@push('scripts')
<script>
/* ─── PHP → JS data ─── */
const aging       = @json($aging);
const byCollector = @json($byCollector);
const byPlant     = @json($byPlant);
const totalAR     = {{ $totalAR }};
const allRows     = @json($rows->values());

/* ─── Bucket metadata ─── */
const BUCKET_KEYS   = ['current','days_1_30','days_30_60','days_60_90','over_90'];
const BUCKET_LABELS = ['Current','1–30 Days','30–60 Days','60–90 Days','> 90 Days'];
const BUCKET_COLORS = ['#1B3A6B','#1e88e5','#d97706','#ea580c','#dc2626'];
const COLLECTOR_COLORS = { Mega:'#1B3A6B', Miya:'#7c3aed', Viona:'#16a34a', Risa:'#d97706' };
const PLANT_COLOR = '#1B3A6B';

/* ─── Helpers ─── */
function fmtIDR(v){
  if(!v||v===0) return 'Rp 0';
  if(v>=1e12) return 'Rp '+(v/1e12).toFixed(2)+'T';
  if(v>=1e9)  return 'Rp '+(v/1e9).toFixed(2)+'B';
  if(v>=1e6)  return 'Rp '+(v/1e6).toFixed(1)+'M';
  return 'Rp '+v.toLocaleString();
}
function summaryCard(label, value, sub, color){
  return `<div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:14px 16px">
    <div style="font-size:10px;color:var(--muted);font-weight:700;text-transform:uppercase;letter-spacing:.08em;margin-bottom:5px">${label}</div>
    <div style="font-size:18px;font-weight:800;font-family:'DM Mono',monospace;color:${color||'#0f1f36'}">${value}</div>
    ${sub?`<div style="font-size:11px;color:var(--muted);margin-top:3px">${sub}</div>`:''}
  </div>`;
}
function collectorColor(name){ return COLLECTOR_COLORS[name]||'#64748b'; }

/* ══════════════════════════════════════════════
   MODAL HELPERS
══════════════════════════════════════════════ */
function openModal(){
  const overlay = document.getElementById('chart-modal-overlay');
  const box     = document.getElementById('chart-modal-box');
  overlay.style.display = 'flex';
  box.style.opacity  = '0';
  box.style.transform = 'translateY(-16px)';
  requestAnimationFrame(()=>{ box.style.opacity='1'; box.style.transform='translateY(0)'; });
}
function closeChartModal(){
  document.getElementById('chart-modal-overlay').style.display = 'none';
}
function setModalHeader(title, subtitle, dotColor){
  document.getElementById('modal-dot').style.background   = dotColor;
  document.getElementById('modal-title').textContent      = title;
  document.getElementById('modal-subtitle').textContent   = subtitle;
}
function setModalSummary(html){ document.getElementById('modal-summary').innerHTML = html; }
function showModalLoading(){ document.getElementById('modal-loading').style.display='block'; document.getElementById('modal-table-wrap').style.display='none'; }
function showModalTable(){ document.getElementById('modal-loading').style.display='none'; document.getElementById('modal-table-wrap').style.display='block'; }
function setModalHead(html){ document.getElementById('modal-thead').innerHTML=html; }
function setModalBody(html){ document.getElementById('modal-tbody').innerHTML=html; }

/* ══════════════════════════════════════════════
   1. AGING DOUGHNUT — clickable slices (fetch API)
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
        labels: { font:{size:10}, boxWidth:10, padding:10, color:'#4a6080' },
        onClick: (e, legendItem) => showAgingBucketModal(legendItem.index)
      },
      tooltip: { callbacks:{ label: ctx=>' '+fmtIDR(ctx.raw) } }
    },
    onClick: (evt, elements) => {
      if(!elements.length) return;
      showAgingBucketModal(elements[0].index);
    }
  }
});

function showAgingBucketModal(idx){
  const key   = BUCKET_KEYS[idx];
  const label = BUCKET_LABELS[idx];
  const color = BUCKET_COLORS[idx];

  setModalHeader(label, 'Aging AR — Customer Breakdown', color);
  openModal();
  showModalLoading();

  const params = new URLSearchParams(window.location.search);
  params.set('bucket', key);

  fetch(`{{ route('dashboard.agingBucket') }}?${params}`, {
    headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'}
  })
  .then(r=>r.json())
  .then(data=>{
    const rows = data.rows;
    const sum  = data.bucket_sum;
    const pct  = totalAR > 0 ? (sum/totalAR*100).toFixed(1) : '0.0';

    setModalSummary(
      summaryCard('Total Amount', fmtIDR(sum), null, color) +
      summaryCard('Customers', rows.length, null, 'var(--navy)') +
      summaryCard('% of Total AR', pct+'%', null, 'var(--muted)')
    );

    setModalHead(`<tr>
      <th>#</th><th>Customer</th><th>Collector</th><th>Plant</th>
      <th class="num">${label}</th><th class="num">Total AR</th><th class="num">% of Bucket</th>
    </tr>`);

    if(!rows.length){
      setModalBody(`<tr><td colspan="7" style="text-align:center;padding:32px;color:var(--muted)">No customers in this bucket.</td></tr>`);
    } else {
      setModalBody(rows.map((r,i)=>{
        const share = sum>0?(r.bucket_amount/sum*100).toFixed(1):'0.0';
        return `<tr>
          <td style="font-size:11px;color:var(--muted)">${i+1}</td>
          <td style="font-weight:600;font-size:12px;max-width:200px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis" title="${r.customer_name}">${r.customer_name}</td>
          <td><span class="badge" style="background:${collectorColor(r.collection_by)}22;color:${collectorColor(r.collection_by)}">${r.collection_by}</span></td>
          <td><span class="badge badge-blue">${r.plant}</span></td>
          <td class="num" style="font-weight:700;color:${color}">${fmtIDR(r.bucket_amount)}</td>
          <td class="num" style="color:var(--muted)">${fmtIDR(r.total)}</td>
          <td class="num">
            <div style="display:flex;align-items:center;gap:6px;justify-content:flex-end">
              <div style="flex:1;background:#e2e8f0;border-radius:99px;height:5px;min-width:40px">
                <div style="width:${Math.min(share,100)}%;background:${color};height:100%;border-radius:99px"></div>
              </div>
              <span style="font-size:10px;color:var(--muted);min-width:30px;text-align:right">${share}%</span>
            </div>
          </td>
        </tr>`;
      }).join(''));
    }
    showModalTable();
  })
  .catch(()=>{
    setModalBody(`<tr><td colspan="7" style="text-align:center;padding:24px;color:#dc2626">⚠️ Failed to load. Please try again.</td></tr>`);
    showModalTable();
  });
}

/* ══════════════════════════════════════════════
   2. COLLECTION BY COLLECTOR — clickable bars (client-side)
══════════════════════════════════════════════ */
const cNames = Object.keys(byCollector);
const collectorChart = new Chart(document.getElementById('collectorChart'), {
  type: 'bar',
  data: {
    labels: cNames,
    datasets: [
      { label:'Target', data: cNames.map(n=>byCollector[n].target), backgroundColor:'#e2e8f0', borderRadius:4 },
      { label:'Actual', data: cNames.map(n=>byCollector[n].actual), backgroundColor: cNames.map(n=>collectorColor(n)), borderRadius:4 }
    ]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: { labels:{ font:{size:10}, boxWidth:10 } },
      tooltip: { callbacks:{ label: ctx=>' '+fmtIDR(ctx.raw) } }
    },
    scales: {
      x: { ticks:{font:{size:10}} },
      y: { ticks:{ font:{size:9}, callback: v=>v>=1e9?'Rp '+(v/1e9).toFixed(1)+'B':'Rp '+(v/1e6).toFixed(0)+'M' } }
    },
    onClick: (evt, elements) => {
      if(!elements.length) return;
      const name = cNames[elements[0].index];
      showCollectorModal(name);
    }
  }
});

function showCollectorModal(name){
  const color  = collectorColor(name);
  const cData  = byCollector[name];
  const rate   = cData.rate;
  const rateColor = rate===null?'#64748b':rate>=100?'#16a34a':rate>=70?'#d97706':'#dc2626';

  setModalHeader(name, 'Collector — Customer Detail', color);
  openModal();

  // Filter rows for this collector (client-side)
  const rows = allRows.filter(r => r.collection_by === name);

  setModalSummary(
    summaryCard('Target', fmtIDR(cData.target), null, 'var(--navy)') +
    summaryCard('Collected', fmtIDR(cData.actual), rate!==null?rate+'% rate':null, '#16a34a') +
    summaryCard('Customers', rows.length, null, color)
  );

  setModalHead(`<tr>
    <th>#</th><th>Customer</th><th>Plant</th>
    <th class="num">Total AR</th><th class="num">Target</th><th class="num">Actual</th>
    <th class="num">Rate</th>
  </tr>`);

  if(!rows.length){
    setModalBody(`<tr><td colspan="7" style="text-align:center;padding:32px;color:var(--muted)">No customers found.</td></tr>`);
  } else {
    const sorted = [...rows].sort((a,b)=>b.total-a.total);
    setModalBody(sorted.map((r,i)=>{
      const cr = r.ar_target>0 ? (r.ar_actual/r.ar_target*100).toFixed(1) : null;
      const crColor = cr===null?'#94a3b8':cr>=100?'#16a34a':cr>=70?'#d97706':'#dc2626';
      return `<tr>
        <td style="font-size:11px;color:var(--muted)">${i+1}</td>
        <td style="font-weight:600;font-size:12px;max-width:200px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis" title="${r.customer_name}">${r.customer_name}</td>
        <td><span class="badge badge-blue">${r.plant}</span></td>
        <td class="num" style="font-weight:700">${fmtIDR(r.total)}</td>
        <td class="num" style="color:var(--muted)">${r.ar_target>0?fmtIDR(r.ar_target):'—'}</td>
        <td class="num" style="color:#16a34a">${r.ar_actual>0?fmtIDR(r.ar_actual):'—'}</td>
        <td class="num">
          ${cr!==null
            ? `<div style="display:flex;align-items:center;gap:5px;justify-content:flex-end">
                <div style="flex:1;background:#e2e8f0;border-radius:99px;height:5px;min-width:40px">
                  <div style="width:${Math.min(cr,100)}%;background:${crColor};height:100%;border-radius:99px"></div>
                </div>
                <span style="font-size:10px;color:${crColor};min-width:32px;text-align:right;font-weight:700">${cr}%</span>
              </div>`
            : '<span style="color:#94a3b8;font-size:11px">—</span>'}
        </td>
      </tr>`;
    }).join(''));
  }
  showModalTable();
}

/* ══════════════════════════════════════════════
   3. AR BY PLANT — clickable bars (client-side)
══════════════════════════════════════════════ */
const pNames = Object.keys(byPlant);
const plantColors = ['#1B3A6B','#1e88e5','#7c3aed','#16a34a'];
const plantChart = new Chart(document.getElementById('plantChart'), {
  type: 'bar',
  data: {
    labels: pNames.map(p=>'Plant '+p),
    datasets: [{
      label:'AR Total',
      data: pNames.map(p=>byPlant[p].total),
      backgroundColor: pNames.map((_,i)=>plantColors[i%plantColors.length]),
      borderRadius:4
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: { display:false },
      tooltip: { callbacks:{ label: ctx=>' '+fmtIDR(ctx.raw) } }
    },
    scales: {
      x: { ticks:{font:{size:10}} },
      y: { ticks:{ font:{size:9}, callback: v=>v>=1e9?'Rp '+(v/1e9).toFixed(1)+'B':'Rp '+(v/1e6).toFixed(0)+'M' } }
    },
    onClick: (evt, elements) => {
      if(!elements.length) return;
      const plant = pNames[elements[0].index];
      showPlantModal(plant, plantColors[elements[0].index%plantColors.length]);
    }
  }
});

function showPlantModal(plant, color){
  const pData = byPlant[plant];

  setModalHeader('Plant '+plant, 'AR Outstanding — Customer Detail', color||PLANT_COLOR);
  openModal();

  const rows = allRows.filter(r => r.plant === plant);
  const totalPlantAR = rows.reduce((s,r)=>s+r.total,0);

  setModalSummary(
    summaryCard('Total AR', fmtIDR(pData.total), null, color||PLANT_COLOR) +
    summaryCard('Customers', rows.length, null, 'var(--navy)') +
    summaryCard('% of Total AR', totalAR>0?(pData.total/totalAR*100).toFixed(1)+'%':'—', null, 'var(--muted)')
  );

  setModalHead(`<tr>
    <th>#</th><th>Customer</th><th>Collector</th>
    <th class="num">Current</th><th class="num">&gt;60d Overdue</th>
    <th class="num">Total AR</th><th class="num">% of Plant</th>
  </tr>`);

  if(!rows.length){
    setModalBody(`<tr><td colspan="7" style="text-align:center;padding:32px;color:var(--muted)">No customers found.</td></tr>`);
  } else {
    const sorted = [...rows].sort((a,b)=>b.total-a.total);
    setModalBody(sorted.map((r,i)=>{
      const overdue  = (r.days_60_90||0) + (r.days_over_90||0);
      const share    = totalPlantAR>0?(r.total/totalPlantAR*100).toFixed(1):'0.0';
      const barColor = color||PLANT_COLOR;
      return `<tr style="${overdue>0?'background:#fff9f9':''}">
        <td style="font-size:11px;color:var(--muted)">${i+1}</td>
        <td style="font-weight:600;font-size:12px;max-width:200px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis" title="${r.customer_name}">${r.customer_name}</td>
        <td>
          <span class="badge" style="background:${collectorColor(r.collection_by)}22;color:${collectorColor(r.collection_by)}">${r.collection_by}</span>
        </td>
        <td class="num">${r.current>0?fmtIDR(r.current):'—'}</td>
        <td class="num" style="${overdue>0?'color:#dc2626;font-weight:700':''}">${overdue>0?fmtIDR(overdue):'—'}</td>
        <td class="num" style="font-weight:700">${fmtIDR(r.total)}</td>
        <td class="num">
          <div style="display:flex;align-items:center;gap:6px;justify-content:flex-end">
            <div style="flex:1;background:#e2e8f0;border-radius:99px;height:5px;min-width:40px">
              <div style="width:${Math.min(share,100)}%;background:${barColor};height:100%;border-radius:99px"></div>
            </div>
            <span style="font-size:10px;color:var(--muted);min-width:30px;text-align:right">${share}%</span>
          </div>
        </td>
      </tr>`;
    }).join(''));
  }
  showModalTable();
}

/* ─── Modal entrance animation ─── */
const box = document.getElementById('chart-modal-box');
box.style.transition = 'opacity .2s ease, transform .2s ease';
</script>
@endpush