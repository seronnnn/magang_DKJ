<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>DKJ — AR Dashboard · @yield('title', 'Overview')</title>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<link href="https://fonts.googleapis.com/css2?family=Libre+Baskerville:ital,wght@0,400;0,700;1,400&family=Inter:wght@400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
:root{
  --bg:#f0f4f8;--surface:#ffffff;--border:#d1dce8;
  --text:#0f1f36;--muted:#4a6080;
  --navy:#1B3A6B;--navy-dark:#0d1f3c;
  --shadow:0 1px 3px rgba(27,58,107,.08),0 1px 2px rgba(27,58,107,.06);
  --shadow-md:0 4px 6px rgba(27,58,107,.07),0 2px 4px rgba(27,58,107,.06);
  --sidebar-w:220px;
}
*{box-sizing:border-box;margin:0;padding:0}
body{background:var(--bg);color:var(--text);font-family:'Inter',sans-serif;min-height:100vh}
.mono{font-family:'DM Mono',monospace}
::-webkit-scrollbar{width:6px;height:6px}
::-webkit-scrollbar-thumb{background:#b0c4d8;border-radius:3px}

/* ─── Sidebar ── */
#sidebar{
  position:fixed;top:0;left:0;width:var(--sidebar-w);height:100vh;
  background:var(--navy-dark);display:flex;flex-direction:column;
  z-index:100;box-shadow:2px 0 12px rgba(13,31,60,.15);
  transition:transform .25s ease;
}
.nav-item{
  display:flex;align-items:center;gap:10px;padding:10px 18px;
  color:#94afc8;font-size:13px;font-weight:500;
  border-left:3px solid transparent;transition:all .2s;text-decoration:none;
}
.nav-item:hover,.nav-item.active{color:#fff;background:rgba(255,255,255,.08);border-left-color:#F5A623}
.nav-divider{height:1px;background:rgba(255,255,255,.07);margin:6px 18px}

#menu-toggle{
  display:none;position:fixed;top:14px;left:14px;z-index:200;
  background:var(--navy-dark);border:none;border-radius:8px;
  width:38px;height:38px;cursor:pointer;align-items:center;
  justify-content:center;color:#fff;font-size:20px;
  box-shadow:0 2px 8px rgba(0,0,0,.3);
}
#sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:99}

/* ─── Main ── */
#main{margin-left:var(--sidebar-w);min-height:100vh}
.topbar{
  height:56px;background:var(--surface);border-bottom:1px solid var(--border);
  display:flex;align-items:center;justify-content:space-between;padding:0 24px;
  position:sticky;top:0;z-index:50;box-shadow:var(--shadow);gap:12px;flex-wrap:wrap;
}

/* ─── KPI Cards ── */
.kpi-card{
  background:var(--surface);border:1px solid var(--border);border-radius:12px;
  padding:18px 20px;position:relative;overflow:hidden;transition:all .2s;box-shadow:var(--shadow);
}
.kpi-card:hover{border-color:var(--navy);transform:translateY(-2px);box-shadow:var(--shadow-md)}
.kpi-card::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:var(--accent-color,var(--navy))}
.kpi-value{font-size:22px;font-weight:800;letter-spacing:-.5px}
.kpi-label{font-size:11px;color:var(--muted);font-weight:700;text-transform:uppercase;letter-spacing:.08em;margin-bottom:6px}
.kpi-sub{font-size:13px;color:var(--muted);margin-top:4px}

.grid-kpi-5{display:grid;grid-template-columns:repeat(1,1fr);gap:14px}
.grid-kpi-4{display:grid;grid-template-columns:repeat(1,1fr);gap:14px}
.grid-kpi-3{display:grid;grid-template-columns:repeat(1,1fr);gap:14px}
@media(min-width:640px){
  .grid-kpi-5{grid-template-columns:repeat(2,1fr)}
  .grid-kpi-4{grid-template-columns:repeat(2,1fr)}
  .grid-kpi-3{grid-template-columns:repeat(2,1fr)}
}
@media(min-width:1024px){
  .grid-kpi-5{grid-template-columns:repeat(5,1fr)}
  .grid-kpi-4{grid-template-columns:repeat(4,1fr)}
  .grid-kpi-3{grid-template-columns:repeat(3,1fr)}
}
.grid-charts-3{display:grid;grid-template-columns:1fr;gap:14px}
@media(min-width:768px){.grid-charts-3{grid-template-columns:repeat(2,1fr)}}
@media(min-width:1024px){.grid-charts-3{grid-template-columns:repeat(3,1fr)}}
.grid-2{display:grid;grid-template-columns:1fr;gap:14px}
@media(min-width:768px){.grid-2{grid-template-columns:repeat(2,1fr)}}

/* ─── Tables ── */
.data-table{width:100%;border-collapse:collapse;font-size:12.5px}
.data-table thead th{
  background:#f8fafc;color:var(--muted);font-size:10px;font-weight:700;
  text-transform:uppercase;letter-spacing:.08em;padding:10px 12px;
  text-align:left;border-bottom:2px solid var(--border);white-space:nowrap;
}
.data-table tbody tr{border-bottom:1px solid var(--border);transition:background .15s}
.data-table tbody tr:hover{background:#f0f6ff}
.data-table td{padding:9px 12px;vertical-align:middle}
.data-table .num{text-align:right;font-family:'DM Mono',monospace;font-size:12px}
.table-scroll{overflow-x:auto;-webkit-overflow-scrolling:touch}

/* ─── Badges ── */
.badge{display:inline-block;padding:2px 8px;border-radius:99px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.05em}
.badge-green{background:#dcfce7;color:#166534}
.badge-yellow{background:#fef9c3;color:#92400e}
.badge-red{background:#fee2e2;color:#991b1b}
.badge-blue{background:#dbeafe;color:#1e40af}
.badge-gray{background:#f1f5f9;color:#475569}
.badge-orange{background:#ffedd5;color:#9a3412}

.progress-bg{background:#e2e8f0;border-radius:99px;height:6px;overflow:hidden}
.progress-fill{height:100%;border-radius:99px;transition:width .5s}

.filter-input{
  background:var(--surface);border:1px solid var(--border);color:var(--text);
  padding:7px 12px;border-radius:8px;font-size:12px;font-family:'Inter',sans-serif;
  outline:none;transition:all .2s;
}
.filter-input:focus{border-color:var(--navy);box-shadow:0 0 0 3px rgba(27,58,107,.08)}

.btn{
  padding:8px 16px;border-radius:8px;font-size:12px;font-weight:700;cursor:pointer;
  transition:all .2s;border:none;font-family:'Inter',sans-serif;
  letter-spacing:.03em;display:inline-block;text-decoration:none;
}
.btn-primary{background:var(--navy);color:white}
.btn-primary:hover{background:#0d1f3c}
.btn-ghost{background:transparent;color:var(--muted);border:1px solid var(--border)}
.btn-ghost:hover{color:var(--navy);border-color:var(--navy);background:#eef2ff}
.btn-sm{padding:5px 10px;font-size:11px}
.btn-warning{background:#d97706;color:white}
.btn-warning:hover{background:#b45309}

.card-accent-blue  {--accent-color:var(--navy)}
.card-accent-green {--accent-color:#16a34a}
.card-accent-yellow{--accent-color:#d97706}
.card-accent-red   {--accent-color:#dc2626}
.card-accent-purple{--accent-color:#7c3aed}

.chart-wrap{position:relative;height:220px}

@media(max-width:767px){
  #menu-toggle{display:flex}
  #sidebar{transform:translateX(-100%)}
  #sidebar.open{transform:translateX(0)}
  #sidebar-overlay.open{display:block}
  #main{margin-left:0}
  .topbar{padding:0 16px 0 60px;height:auto;min-height:56px;padding-top:8px;padding-bottom:8px}
}
</style>
</head>
<body>

<button id="menu-toggle" onclick="toggleSidebar()" aria-label="Open menu">☰</button>
<div id="sidebar-overlay" onclick="toggleSidebar()"></div>

{{-- ── Sidebar ── --}}
<div id="sidebar">
  <div style="padding:20px 18px 16px;border-bottom:1px solid rgba(255,255,255,.08)">
    <div style="font-family:'Libre Baskerville',serif;font-size:18px;font-weight:700;color:#fff;letter-spacing:-.3px">DKJ</div>
    <div style="font-size:10px;color:#94afc8;font-weight:600;text-transform:uppercase;letter-spacing:.1em;margin-top:2px">AR Dashboard</div>
    <div style="font-size:10px;color:#64748b;margin-top:4px">
      {{ isset($period) && $period ? $period->period_label : 'All Periods' }}
    </div>
  </div>

  <nav style="flex:1;padding:12px 0;overflow-y:auto">
    <a href="{{ route('dashboard.index') }}"      class="nav-item {{ request()->routeIs('dashboard.index')      ? 'active':'' }}">📊 Overview</a>
    <a href="{{ route('dashboard.aging') }}"      class="nav-item {{ request()->routeIs('dashboard.aging')      ? 'active':'' }}">⏳ AR Aging</a>
    <a href="{{ route('dashboard.collection') }}" class="nav-item {{ request()->routeIs('dashboard.collection') ? 'active':'' }}">💰 Collection</a>
    <a href="{{ route('dashboard.overlimit') }}"  class="nav-item {{ request()->routeIs('dashboard.overlimit')  ? 'active':'' }}">⚠️ SO Overlimit</a>
    <a href="{{ route('dashboard.customers') }}"  class="nav-item {{ request()->routeIs('dashboard.customers')  ? 'active':'' }}">👥 Customers</a>

    <div class="nav-divider"></div>
    <a href="{{ route('dashboard.history') }}" class="nav-item {{ request()->routeIs('dashboard.history') ? 'active':'' }}">
      📈 History
      @if(Auth::user()->isAdmin())
      <span style="margin-left:auto;font-size:9px;background:rgba(245,166,35,.2);color:#F5A623;
                   padding:1px 6px;border-radius:99px;font-weight:700;letter-spacing:.05em">ADMIN</span>
      @endif
    </a>

    @if(Auth::user()->isCollector())
    <div class="nav-divider"></div>
    <a href="{{ route('reminder.index') }}"
       class="nav-item {{ request()->routeIs('reminder.*') ? 'active':'' }}">
      🔔 Reminder AR
      <span style="margin-left:auto;font-size:9px;background:rgba(249,115,22,.2);color:#f97316;
                   padding:1px 6px;border-radius:99px;font-weight:700;letter-spacing:.05em">DES</span>
    </a>
    @endif
  </nav>

  <div style="padding:16px 18px;border-top:1px solid rgba(255,255,255,.08)">
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px">
      <div style="width:32px;height:32px;border-radius:50%;background:rgba(255,255,255,.15);
                  display:flex;align-items:center;justify-content:center;flex-shrink:0;
                  font-size:13px;font-weight:700;color:#fff">
        {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
      </div>
      <div style="overflow:hidden">
        <div style="font-size:12px;font-weight:600;color:#e2eaf4;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:140px">
          {{ Auth::user()->name }}
        </div>
        <div style="font-size:10px;color:#64748b;text-transform:uppercase;letter-spacing:.06em">
          {{ Auth::user()->role }}
        </div>
      </div>
    </div>
    <form method="POST" action="{{ route('logout') }}">
      @csrf
      <button type="submit" class="btn btn-ghost"
              style="width:100%;font-size:11px;padding:8px 0;color:#94afc8;
                     border-color:rgba(255,255,255,.12);display:flex;align-items:center;justify-content:center;gap:6px">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>
        </svg>
        Logout
      </button>
    </form>
  </div>
</div>

{{-- ── Main ── --}}
<div id="main">
  <div class="topbar">
    <div style="font-size:14px;font-weight:700;flex-shrink:0">@yield('page-title','Dashboard Overview')</div>

    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;justify-content:flex-end;flex:1">
      @if(isset($periods) && $periods->count())
      <form method="GET" action="{{ request()->url() }}" id="period-form" style="display:flex;align-items:center;gap:6px">
        @if(request('plant'))    <input type="hidden" name="plant"     value="{{ request('plant') }}">@endif
        @if(request('collector'))<input type="hidden" name="collector" value="{{ request('collector') }}">@endif
        @if(request('search'))  <input type="hidden" name="search"    value="{{ request('search') }}">@endif
        <select name="period_id" class="filter-input" style="font-size:11px;padding:5px 10px"
                onchange="document.getElementById('period-form').submit()">
          @foreach($periods as $p)
            <option value="{{ $p->id }}"
              {{ (isset($period) && $period && $period->id == $p->id) ? 'selected' : '' }}>
              {{ $p->period_label }}
            </option>
          @endforeach
        </select>
      </form>
      @endif

      @yield('topbar-actions')
    </div>
  </div>

  @if(session('success'))
  <div style="background:#dcfce7;border:1px solid #bbf7d0;color:#166534;padding:12px 24px;font-size:13px">
    ✅ {{ session('success') }}
  </div>
  @endif

  <div style="padding:20px">@yield('content')</div>
</div>

{{-- ── Edit Modal — available to ALL roles ── --}}
<div id="edit-modal-overlay" onclick="if(event.target===this) closeEditModal()"
     style="display:none;position:fixed;inset:0;background:rgba(15,31,54,.5);z-index:300;
            align-items:center;justify-content:center;backdrop-filter:blur(3px)">
  <div id="edit-modal-box"
       style="background:#fff;border:1px solid var(--border);border-radius:16px;padding:28px;
              width:560px;max-width:95vw;max-height:90vh;overflow-y:auto;
              box-shadow:0 20px 60px rgba(15,31,54,.25);transition:opacity .2s,transform .2s">

    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
      <div>
        <div style="font-size:16px;font-weight:800;color:var(--navy)">Edit AR Record</div>
        <div id="edit-modal-subtitle" style="font-size:11px;color:var(--muted);margin-top:2px"></div>
      </div>
      <button onclick="closeEditModal()"
              style="background:none;border:1px solid var(--border);border-radius:8px;
                     width:32px;height:32px;cursor:pointer;font-size:18px;color:var(--muted);
                     display:flex;align-items:center;justify-content:center"
              onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='none'">×</button>
    </div>

    <form id="edit-modal-form" onsubmit="submitEditModal(event)">
      @csrf
      <input type="hidden" id="edit-row-id">
      <div style="background:#f8fafc;border:1px solid var(--border);border-radius:10px;padding:14px;margin-bottom:18px">
        <div style="font-size:10px;color:var(--muted);font-weight:700;text-transform:uppercase;letter-spacing:.08em;margin-bottom:6px">Customer</div>
        <div id="edit-customer-name" style="font-size:14px;font-weight:700;color:var(--navy)"></div>
        <div id="edit-customer-meta" style="font-size:11px;color:var(--muted);margin-top:2px"></div>
      </div>

      <div style="font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:10px">AR Aging Buckets</div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:18px">
        <div><label style="font-size:10px;font-weight:700;color:var(--muted);text-transform:uppercase;display:block;margin-bottom:4px">Current (IDR)</label><input type="number" name="amount_current" id="ef_amount_current" class="filter-input" style="width:100%" min="0"></div>
        <div><label style="font-size:10px;font-weight:700;color:var(--muted);text-transform:uppercase;display:block;margin-bottom:4px">1-30 Days</label><input type="number" name="amount_1_30_days" id="ef_amount_1_30_days" class="filter-input" style="width:100%" min="0"></div>
        <div><label style="font-size:10px;font-weight:700;color:var(--muted);text-transform:uppercase;display:block;margin-bottom:4px">30-60 Days</label><input type="number" name="amount_30_60_days" id="ef_amount_30_60_days" class="filter-input" style="width:100%" min="0"></div>
        <div><label style="font-size:10px;font-weight:700;color:var(--muted);text-transform:uppercase;display:block;margin-bottom:4px">60-90 Days</label><input type="number" name="amount_60_90_days" id="ef_amount_60_90_days" class="filter-input" style="width:100%" min="0"></div>
        <div><label style="font-size:10px;font-weight:700;color:var(--muted);text-transform:uppercase;display:block;margin-bottom:4px">&gt;90 Days</label><input type="number" name="amount_over_90_days" id="ef_amount_over_90_days" class="filter-input" style="width:100%" min="0"></div>
        <div><label style="font-size:10px;font-weight:700;color:var(--muted);text-transform:uppercase;display:block;margin-bottom:4px">Total AR</label><input type="number" name="total_ar" id="ef_total_ar" class="filter-input" style="width:100%" min="0"></div>
      </div>
      <div style="font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:10px">Collection</div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:18px">
        <div><label style="font-size:10px;font-weight:700;color:var(--muted);text-transform:uppercase;display:block;margin-bottom:4px">AR Target (IDR)</label><input type="number" name="ar_target" id="ef_ar_target" class="filter-input" style="width:100%" min="0"></div>
        <div><label style="font-size:10px;font-weight:700;color:var(--muted);text-transform:uppercase;display:block;margin-bottom:4px">AR Actual (IDR)</label><input type="number" name="ar_actual" id="ef_ar_actual" class="filter-input" style="width:100%" min="0"></div>
      </div>
      <div style="font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:10px">Sales Orders</div>
      <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px;margin-bottom:24px">
        <div><label style="font-size:10px;font-weight:700;color:var(--muted);text-transform:uppercase;display:block;margin-bottom:4px">SO Without OD</label><input type="number" name="so_without_od" id="ef_so_without_od" class="filter-input" style="width:100%" min="0"></div>
        <div><label style="font-size:10px;font-weight:700;color:var(--muted);text-transform:uppercase;display:block;margin-bottom:4px">SO With OD</label><input type="number" name="so_with_od" id="ef_so_with_od" class="filter-input" style="width:100%" min="0"></div>
        <div><label style="font-size:10px;font-weight:700;color:var(--muted);text-transform:uppercase;display:block;margin-bottom:4px">Total SO</label><input type="number" name="total_so" id="ef_total_so" class="filter-input" style="width:100%" min="0"></div>
      </div>

      <div id="edit-modal-error"   style="display:none;background:#fee2e2;border:1px solid #fca5a5;color:#991b1b;padding:10px 14px;border-radius:8px;font-size:12px;margin-bottom:14px"></div>
      <div id="edit-modal-success" style="display:none;background:#dcfce7;border:1px solid #86efac;color:#166534;padding:10px 14px;border-radius:8px;font-size:12px;margin-bottom:14px"></div>

      <div style="display:flex;justify-content:flex-end;gap:10px">
        <button type="button" onclick="closeEditModal()" class="btn btn-ghost">Cancel</button>
        <button type="submit" id="edit-save-btn" class="btn btn-primary"><span id="edit-save-label">Save Changes</span></button>
      </div>
    </form>
  </div>
</div>

@stack('scripts')
<script>
function toggleSidebar(){
  document.getElementById('sidebar').classList.toggle('open');
  document.getElementById('sidebar-overlay').classList.toggle('open');
}

function openEditModal(rowData){
  document.getElementById('edit-row-id').value = rowData.id;
  document.getElementById('edit-customer-name').textContent = rowData.customer_name;
  document.getElementById('edit-customer-meta').textContent =
    'ID: '+rowData.customer_id+' · Plant '+rowData.plant+' · '+rowData.collection_by;
  document.getElementById('edit-modal-subtitle').textContent = 'AR Record #'+rowData.id;

  const map = {
    ef_amount_current:      rowData.current       ?? rowData.amount_current      ?? 0,
    ef_amount_1_30_days:    rowData.days_1_30      ?? rowData.amount_1_30_days    ?? 0,
    ef_amount_30_60_days:   rowData.days_30_60     ?? rowData.amount_30_60_days   ?? 0,
    ef_amount_60_90_days:   rowData.days_60_90     ?? rowData.amount_60_90_days   ?? 0,
    ef_amount_over_90_days: rowData.days_over_90   ?? rowData.amount_over_90_days ?? 0,
    ef_total_ar:            rowData.total          ?? rowData.total_ar            ?? 0,
    ef_ar_target:           rowData.ar_target      ?? 0,
    ef_ar_actual:           rowData.ar_actual      ?? 0,
    ef_so_without_od:       rowData.so_without_od  ?? 0,
    ef_so_with_od:          rowData.so_with_od     ?? 0,
    ef_total_so:            rowData.total_so       ?? 0,
  };
  Object.entries(map).forEach(([id,val]) => { const el=document.getElementById(id); if(el) el.value=val; });

  document.getElementById('edit-modal-error').style.display   = 'none';
  document.getElementById('edit-modal-success').style.display = 'none';

  const overlay = document.getElementById('edit-modal-overlay');
  const box     = document.getElementById('edit-modal-box');
  overlay.style.display = 'flex';
  box.style.opacity     = '0';
  box.style.transform   = 'translateY(-14px)';
  requestAnimationFrame(()=>{ box.style.opacity='1'; box.style.transform='translateY(0)'; });
}

function closeEditModal(){
  document.getElementById('edit-modal-overlay').style.display = 'none';
}

async function submitEditModal(e){
  e.preventDefault();
  const id    = document.getElementById('edit-row-id').value;
  const btn   = document.getElementById('edit-save-btn');
  const lbl   = document.getElementById('edit-save-label');
  const errEl = document.getElementById('edit-modal-error');
  const okEl  = document.getElementById('edit-modal-success');
  errEl.style.display='none'; okEl.style.display='none';
  btn.disabled=true; lbl.textContent='Saving…';

  const fd = new FormData(document.getElementById('edit-modal-form'));
  fd.append('_method','PUT');
  try {
    const res  = await fetch(`/dashboard/ar-data/${id}`,{
      method:'POST',
      headers:{'X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content,'Accept':'application/json'},
      body:fd
    });
    const data = await res.json();
    if(!res.ok) throw new Error(data.message||'Failed to save');
    okEl.textContent='✅ Changes saved!'; okEl.style.display='block';
    setTimeout(()=>{ closeEditModal(); window.location.reload(); }, 900);
  } catch(err){
    errEl.textContent='⚠️ '+err.message; errEl.style.display='block';
  } finally { btn.disabled=false; lbl.textContent='Save Changes'; }
}

document.addEventListener('keydown', e=>{ if(e.key==='Escape') closeEditModal(); });
</script>
</body>
</html>