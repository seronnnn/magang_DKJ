<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>DKJ — AR Dashboard · @yield('title', 'January 2026')</title>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<link href="https://fonts.googleapis.com/css2?family=Libre+Baskerville:ital,wght@0,400;0,700;1,400&family=Inter:wght@400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<style>
  :root{--bg:#f0f4f8;--surface:#ffffff;--border:#d1dce8;--text:#0f1f36;--muted:#4a6080;
    --navy:#1B3A6B;--navy-dark:#0d1f3c;
    --shadow:0 1px 3px rgba(27,58,107,.08),0 1px 2px rgba(27,58,107,.06);
    --shadow-md:0 4px 6px rgba(27,58,107,.07),0 2px 4px rgba(27,58,107,.06);}
  *{box-sizing:border-box;margin:0;padding:0}
  body{background:var(--bg);color:var(--text);font-family:'Inter',sans-serif;min-height:100vh}
  .mono{font-family:'DM Mono',monospace}
  ::-webkit-scrollbar{width:6px;height:6px}
  ::-webkit-scrollbar-thumb{background:#b0c4d8;border-radius:3px}
  #sidebar{position:fixed;top:0;left:0;width:220px;height:100vh;background:var(--navy-dark);
    display:flex;flex-direction:column;z-index:100;box-shadow:2px 0 12px rgba(13,31,60,.15)}
  .nav-item{display:flex;align-items:center;gap:10px;padding:10px 18px;color:#94afc8;
    font-size:13px;font-weight:500;border-left:3px solid transparent;transition:all .2s;text-decoration:none}
  .nav-item:hover,.nav-item.active{color:#fff;background:rgba(255,255,255,.08);border-left-color:#F5A623}
  #main{margin-left:220px;min-height:100vh}
  .topbar{height:56px;background:var(--surface);border-bottom:1px solid var(--border);
    display:flex;align-items:center;justify-content:space-between;padding:0 24px;
    position:sticky;top:0;z-index:50;box-shadow:var(--shadow)}
  .kpi-card{background:var(--surface);border:1px solid var(--border);border-radius:12px;
    padding:18px 20px;position:relative;overflow:hidden;transition:all .2s;box-shadow:var(--shadow)}
  .kpi-card:hover{border-color:var(--navy);transform:translateY(-2px);box-shadow:var(--shadow-md)}
  .kpi-card::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:var(--accent-color,var(--navy))}
  .kpi-value{font-size:22px;font-weight:800;letter-spacing:-.5px}
  .kpi-label{font-size:11px;color:var(--muted);font-weight:1000;text-transform:uppercase;letter-spacing:.08em;margin-bottom:6px}
  .kpi-sub{font-size:14px;color:var(--muted);margin-top:4px}
  .data-table{width:100%;border-collapse:collapse;font-size:12.5px}
  .data-table thead th{background:#f8fafc;color:var(--muted);font-size:10px;font-weight:700;
    text-transform:uppercase;letter-spacing:.08em;padding:10px 12px;text-align:left;
    border-bottom:2px solid var(--border);white-space:nowrap}
  .data-table tbody tr{border-bottom:1px solid var(--border);transition:background .15s}
  .data-table tbody tr:hover{background:#f0f6ff}
  .data-table td{padding:9px 12px;vertical-align:middle}
  .data-table .num{text-align:right;font-family:'DM Mono',monospace;font-size:12px}
  .badge{display:inline-block;padding:2px 8px;border-radius:99px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.05em}
  .badge-green{background:#dcfce7;color:#166534}.badge-yellow{background:#fef9c3;color:#92400e}
  .badge-red{background:#fee2e2;color:#991b1b}.badge-blue{background:#dbeafe;color:#1e40af}
  .badge-gray{background:#f1f5f9;color:#475569}.badge-orange{background:#ffedd5;color:#9a3412}
  .progress-bg{background:#e2e8f0;border-radius:99px;height:6px;overflow:hidden}
  .progress-fill{height:100%;border-radius:99px;transition:width .5s}
  .filter-input{background:var(--surface);border:1px solid var(--border);color:var(--text);
    padding:7px 12px;border-radius:8px;font-size:12px;font-family:'Inter',sans-serif;outline:none;transition:all .2s}
  .filter-input:focus{border-color:var(--navy);box-shadow:0 0 0 3px rgba(27,58,107,.08)}
  .btn{padding:8px 16px;border-radius:8px;font-size:12px;font-weight:700;cursor:pointer;transition:all .2s;
    border:none;font-family:'Inter',sans-serif;letter-spacing:.03em;display:inline-block;text-decoration:none}
  .btn-primary{background:var(--navy);color:white}.btn-primary:hover{background:#0d1f3c}
  .btn-ghost{background:transparent;color:var(--muted);border:1px solid var(--border)}
  .btn-ghost:hover{color:var(--navy);border-color:var(--navy);background:#eef2ff}
  .card-accent-blue{--accent-color:var(--navy)}.card-accent-green{--accent-color:#16a34a}
  .card-accent-yellow{--accent-color:#d97706}.card-accent-red{--accent-color:#dc2626}
  .card-accent-purple{--accent-color:#7c3aed}
  .chart-wrap{position:relative;height:220px}
</style>
</head>
<body>
<div id="sidebar">
  <div style="padding:20px 18px 16px;border-bottom:1px solid rgba(255,255,255,.08)">
    <div style="font-family:'Libre Baskerville',serif;font-size:18px;font-weight:700;color:#fff;letter-spacing:-.3px">DKJ</div>
    <div style="font-size:10px;color:#94afc8;font-weight:600;text-transform:uppercase;letter-spacing:.1em;margin-top:2px">AR Dashboard</div>
    <div style="font-size:10px;color:#64748b;margin-top:4px">January 2026</div>
  </div>
  <nav style="flex:1;padding:12px 0;overflow-y:auto">
    <a href="{{ route('dashboard.index') }}"      class="nav-item {{ request()->routeIs('dashboard.index')      ? 'active':'' }}">📊 Overview</a>
    <a href="{{ route('dashboard.aging') }}"      class="nav-item {{ request()->routeIs('dashboard.aging')      ? 'active':'' }}">⏳ AR Aging</a>
    <a href="{{ route('dashboard.collection') }}" class="nav-item {{ request()->routeIs('dashboard.collection') ? 'active':'' }}">💰 Collection</a>
    <a href="{{ route('dashboard.overlimit') }}"  class="nav-item {{ request()->routeIs('dashboard.overlimit')  ? 'active':'' }}">⚠️ SO Overlimit</a>
    <a href="{{ route('dashboard.customers') }}"  class="nav-item {{ request()->routeIs('dashboard.customers')  ? 'active':'' }}">👥 Customers</a>
  </nav>
  <div style="padding:16px 18px;border-top:1px solid rgba(255,255,255,.08)">
    <a href="{{ route('dashboard.export') }}?{{ request()->getQueryString() }}" class="btn btn-ghost" style="width:100%;text-align:center;font-size:11px">⬇ Export CSV</a>
  </div>
</div>
<div id="main">
  <div class="topbar">
    <div style="font-size:14px;font-weight:700">@yield('page-title','Dashboard Overview')</div>
    <div style="display:flex;align-items:center;gap:12px">
      @yield('topbar-actions')
      <div style="font-size:11px;color:var(--muted)">Period: <strong>Jan 2026</strong></div>
    </div>
  </div>
  @if(session('success'))
  <div style="background:#dcfce7;border:1px solid #bbf7d0;color:#166534;padding:12px 24px;font-size:13px">✅ {{ session('success') }}</div>
  @endif
  <div style="padding:24px">@yield('content')</div>
</div>
@stack('scripts')
</body>
</html>
