@extends('layouts.app')
@section('title','History')
@section('page-title','Collection History')

@php $isCollectorRole = Auth::user()->isCollector(); @endphp

@section('topbar-actions')
<form method="GET" action="{{ route('dashboard.history') }}" id="history-form"
      style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">

  {{-- Year selector --}}
  <select name="year" class="filter-input" style="font-size:11px;padding:5px 10px">
    @foreach($availableYears as $yr)
      <option value="{{ $yr }}" {{ $selectedYear == $yr ? 'selected' : '' }}>{{ $yr }}</option>
    @endforeach
  </select>

  <select name="collector" class="filter-input" style="font-size:11px;padding:5px 10px">
    <option value="">All Collectors (Individual Lines)</option>
    <option value="__total__" {{ $selectedCollector == '__total__' ? 'selected' : '' }}>📊 Total All Collectors</option>
    @foreach($collectors as $c)
      <option value="{{ $c }}" {{ $selectedCollector == $c ? 'selected' : '' }}>{{ $c }}</option>
    @endforeach
  </select>
  

  {{-- Compare checkbox — hide for collectors (keep it simple) --}}
  @if(!$isCollectorRole)
  <label style="display:flex;align-items:center;gap:6px;font-size:12px;font-weight:600;
                color:var(--navy);cursor:pointer;padding:6px 12px;border:1px solid var(--border);
                border-radius:8px;background:{{ $compareMode ? '#dbeafe' : 'var(--surface)' }};
                transition:all .15s"
         id="compare-label">
    <input type="checkbox" name="compare" value="1" id="compare-checkbox"
           {{ $compareMode ? 'checked' : '' }}
           onchange="toggleCompareYear(this.checked)"
           style="width:14px;height:14px;accent-color:var(--navy)">
    Compare
  </label>

  {{-- Compare year (shown only when compare checked) --}}
  <div id="compare-year-wrap" style="display:{{ $compareMode ? 'flex' : 'none' }};align-items:center;gap:6px">
    <span style="font-size:11px;color:var(--muted);font-weight:600">vs</span>
    <select name="compare_year" class="filter-input" style="font-size:11px;padding:5px 10px">
      @foreach($availableYears as $yr)
        @if($yr != $selectedYear)
          <option value="{{ $yr }}" {{ $compareYear == $yr ? 'selected' : '' }}>{{ $yr }}</option>
        @endif
      @endforeach
    </select>
  </div>
  @endif

  <button type="submit" class="btn btn-primary" style="font-size:11px;padding:6px 14px">Search</button>

  @if(!$isCollectorRole && ($selectedCollector !== '' || $selectedYear != date('Y') || $compareMode))
    <a href="{{ route('dashboard.history') }}" class="btn btn-ghost" style="font-size:11px">Clear</a>
  @endif
</form>
@endsection

{{-- Override $periods to empty so the global topbar period dropdown does NOT render on this page --}}
@php $periods = collect(); @endphp

@section('content')

@php
  $totalTarget = $summary->sum('total_target');
  $totalActual = $summary->sum('total_actual');
  $totalAR     = $summary->sum('total_ar');
  $overallRate = $totalTarget > 0 ? round($totalActual / $totalTarget * 100, 1) : null;

  // Compare year totals
  $cmpTarget = $compareSummary->sum('total_target');
  $cmpActual = $compareSummary->sum('total_actual');
  $cmpAR     = $compareSummary->sum('total_ar');
  $cmpRate   = $cmpTarget > 0 ? round($cmpActual / $cmpTarget * 100, 1) : null;

  $isTotalMode = ($selectedCollector === '__total__');
@endphp

{{-- KPIs --}}
<div class="{{ $compareMode ? 'grid-kpi-4' : 'grid-kpi-4' }}" style="margin-bottom:20px">
  <div class="kpi-card card-accent-blue">
    <div class="kpi-label">Total AR ({{ $selectedYear }})</div>
    <div class="kpi-value mono">{{ fmtIDR($totalAR) }}</div>
    <div class="kpi-sub">Full year outstanding</div>
  </div>
  <div class="kpi-card card-accent-blue">
    <div class="kpi-label">Total Target</div>
    <div class="kpi-value mono">{{ fmtIDR($totalTarget) }}</div>
    @if($compareMode && $cmpTarget > 0)
      <div class="kpi-sub" style="color:var(--muted)">{{ $compareYear }}: {{ fmtIDR($cmpTarget) }}</div>
    @endif
  </div>
  <div class="kpi-card card-accent-green">
    <div class="kpi-label">Total Collected</div>
    <div class="kpi-value mono" style="color:#16a34a">{{ fmtIDR($totalActual) }}</div>
    @if($compareMode && $cmpActual > 0)
      <div class="kpi-sub" style="color:var(--muted)">{{ $compareYear }}: {{ fmtIDR($cmpActual) }}</div>
    @endif
  </div>
  <div class="kpi-card card-accent-yellow">
    <div class="kpi-label">Overall Rate</div>
    <div class="kpi-value" style="color:{{ $overallRate === null ? '#94a3b8' : ($overallRate >= 100 ? '#16a34a' : ($overallRate >= 70 ? '#d97706' : '#dc2626')) }}">
      {{ $overallRate !== null ? $overallRate.'%' : 'N/A' }}
    </div>
    @if($compareMode && $cmpRate !== null)
      <div class="kpi-sub" style="color:var(--muted)">{{ $compareYear }}: {{ $cmpRate }}%</div>
    @endif
  </div>
</div>

{{-- Line Chart --}}
<div style="background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:24px;margin-bottom:20px;box-shadow:var(--shadow)">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:8px">
    <div>
      <div style="font-size:14px;font-weight:700">
        Monthly Collection Progress —
        @if($compareMode)
          {{ $selectedYear }} vs {{ $compareYear }}
        @else
          {{ $selectedYear }}
        @endif
      </div>
      <div style="font-size:12px;color:var(--muted);margin-top:2px">
        @if($isTotalMode)
          📊 Total All Collectors Combined
        @elseif($selectedCollector !== '')
          {{ $selectedCollector }}
        @else
          All Collectors (Individual Lines)
        @endif
        · Solid = Actual, Dashed = Target
      </div>
    </div>
    @if($compareMode)
    <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
      <span style="display:flex;align-items:center;gap:5px;font-size:11px">
        <span style="width:20px;height:3px;background:#1B3A6B;display:inline-block;border-radius:2px"></span>
        {{ $selectedYear }}
      </span>
      <span style="display:flex;align-items:center;gap:5px;font-size:11px">
        <span style="width:20px;height:3px;background:#60a5fa;display:inline-block;border-radius:2px"></span>
        {{ $compareYear }}
      </span>
    </div>
    @endif
  </div>

  {{-- Legend for individual lines mode --}}
  @if(!$isTotalMode && $selectedCollector === '')
  <div style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:14px" id="collector-legend">
    @foreach($datasets as $ds)
      @if(!str_contains($ds['label'],'Target'))
      <div style="display:flex;align-items:center;gap:5px">
        <span style="width:16px;height:3px;background:{{ $ds['borderColor'] }};display:inline-block;border-radius:2px"></span>
        <span style="font-size:11px;color:var(--muted)">{{ preg_replace('/\s*\(Actual\)\s*\(\d+\)/', '', $ds['label']) }}</span>
      </div>
      @endif
    @endforeach
  </div>
  @endif

  <div style="position:relative;height:360px">
    <canvas id="historyChart"></canvas>
  </div>
</div>

{{-- Full-Year Summary Table --}}
<div style="background:var(--surface);border:1px solid var(--border);border-radius:12px;overflow:hidden;box-shadow:var(--shadow);margin-bottom:20px">
  <div style="padding:16px 20px;border-bottom:1px solid var(--border)">
    <div style="font-size:12px;font-weight:700">
      Full-Year Summary — {{ $selectedYear }}
      @if($isTotalMode)
        · 📊 Total All Collectors
      @elseif($selectedCollector !== '')
        · {{ $selectedCollector }}
      @else
        · All Collectors
      @endif
    </div>
  </div>
  <div class="table-scroll">
    <table class="data-table">
      <thead><tr>
        <th>Collector</th>
        <th class="num">Total AR</th>
        <th class="num">Target</th>
        <th class="num">Collected</th>
        <th class="num">Rate</th>
        <th style="min-width:160px">Progress</th>
      </tr></thead>
      <tbody>
      @forelse($summary as $row)
        @php
          $rate     = $row->rate;
          $barColor = $rate === null ? '#94a3b8' : ($rate >= 100 ? '#16a34a' : ($rate >= 70 ? '#d97706' : '#dc2626'));
          $badgeCls = $rate === null ? 'badge-gray' : ($rate >= 100 ? 'badge-green' : ($rate >= 70 ? 'badge-yellow' : 'badge-red'));
          $pct      = min($rate ?? 0, 100);
        @endphp
        <tr>
          <td style="font-weight:700">{{ $row->collector }}</td>
          <td class="num">{{ fmtIDR($row->total_ar) }}</td>
          <td class="num">{{ $row->total_target > 0 ? fmtIDR($row->total_target) : '—' }}</td>
          <td class="num" style="color:#16a34a;font-weight:600">{{ $row->total_actual > 0 ? fmtIDR($row->total_actual) : '—' }}</td>
          <td class="num"><span class="badge {{ $badgeCls }}">{{ $rate !== null ? $rate.'%' : 'N/A' }}</span></td>
          <td>
            <div style="display:flex;align-items:center;gap:8px">
              <div class="progress-bg" style="flex:1">
                <div class="progress-fill" style="width:{{ $pct }}%;background:{{ $barColor }}"></div>
              </div>
              <span style="font-size:10px;color:{{ $barColor }};min-width:34px;font-weight:700">
                {{ $rate !== null ? $rate.'%' : '—' }}
              </span>
            </div>
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="6" style="text-align:center;padding:32px;color:var(--muted)">No data found for {{ $selectedYear }}.</td>
        </tr>
      @endforelse
      </tbody>
    </table>
  </div>
</div>

{{-- Compare Summary Table (only shown in compare mode, admin/manager only) --}}
@if(!$isCollectorRole && $compareMode && $compareSummary->count() > 0)
<div style="background:var(--surface);border:1px solid #bfdbfe;border-radius:12px;overflow:hidden;box-shadow:var(--shadow);margin-bottom:20px">
  <div style="padding:16px 20px;border-bottom:1px solid #bfdbfe;background:#eff6ff">
    <div style="font-size:12px;font-weight:700;color:#1e40af">
      Full-Year Summary — {{ $compareYear }} (Compare)
      @if($isTotalMode)
        · 📊 Total All Collectors
      @elseif($selectedCollector !== '')
        · {{ $selectedCollector }}
      @else
        · All Collectors
      @endif
    </div>
  </div>
  <div class="table-scroll">
    <table class="data-table">
      <thead><tr>
        <th>Collector</th>
        <th class="num">Total AR</th>
        <th class="num">Target</th>
        <th class="num">Collected</th>
        <th class="num">Rate</th>
        <th style="min-width:160px">Progress</th>
      </tr></thead>
      <tbody>
      @foreach($compareSummary as $row)
        @php
          $rate     = $row->rate;
          $barColor = $rate === null ? '#94a3b8' : ($rate >= 100 ? '#16a34a' : ($rate >= 70 ? '#d97706' : '#dc2626'));
          $badgeCls = $rate === null ? 'badge-gray' : ($rate >= 100 ? 'badge-green' : ($rate >= 70 ? 'badge-yellow' : 'badge-red'));
          $pct      = min($rate ?? 0, 100);
        @endphp
        <tr>
          <td style="font-weight:700">{{ $row->collector }}</td>
          <td class="num">{{ fmtIDR($row->total_ar) }}</td>
          <td class="num">{{ $row->total_target > 0 ? fmtIDR($row->total_target) : '—' }}</td>
          <td class="num" style="color:#60a5fa;font-weight:600">{{ $row->total_actual > 0 ? fmtIDR($row->total_actual) : '—' }}</td>
          <td class="num"><span class="badge {{ $badgeCls }}">{{ $rate !== null ? $rate.'%' : 'N/A' }}</span></td>
          <td>
            <div style="display:flex;align-items:center;gap:8px">
              <div class="progress-bg" style="flex:1">
                <div class="progress-fill" style="width:{{ $pct }}%;background:{{ $barColor }}"></div>
              </div>
              <span style="font-size:10px;color:{{ $barColor }};min-width:34px;font-weight:700">
                {{ $rate !== null ? $rate.'%' : '—' }}
              </span>
            </div>
          </td>
        </tr>
      @endforeach
      </tbody>
    </table>
  </div>
</div>
@endif

{{-- Monthly Breakdown Table --}}
@if($summary->count() > 0)
<div style="background:var(--surface);border:1px solid var(--border);border-radius:12px;overflow:hidden;box-shadow:var(--shadow)">
  <div style="padding:16px 20px;border-bottom:1px solid var(--border)">
    <div style="font-size:12px;font-weight:700">Monthly Detail — {{ $selectedYear }}</div>
    <div style="font-size:11px;color:var(--muted);margin-top:2px">All amounts in IDR Billion (B)</div>
  </div>
  <div class="table-scroll">
    <table class="data-table">
      <thead>
        <tr>
          <th>Month</th>
          @foreach($datasets as $ds)
            @if(!str_contains($ds['label'],'Target'))
              <th class="num" style="font-size:10px">
                {{ $ds['label'] }}
              </th>
            @endif
          @endforeach
        </tr>
      </thead>
      <tbody>
        @foreach($periodLabels as $i => $label)
          <tr>
            <td style="font-weight:600">{{ $label }}</td>
            @foreach($datasets as $ds)
              @if(!str_contains($ds['label'],'Target'))
                @php $val = $ds['data'][$i] ?? 0; @endphp
                <td class="num" style="color:{{ $val > 0 ? '#16a34a' : 'var(--muted)' }}">
                  {{ $val > 0 ? 'Rp '.number_format($val, 2).'B' : '—' }}
                </td>
              @endif
            @endforeach
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>
@endif

@endsection

@push('scripts')
<script>
const periodLabels = @json($periodLabels);
const datasets     = @json($datasets);

const chartDatasets = datasets.map(ds => ({
  ...ds,
  borderDash: ds.borderDash && ds.borderDash.length > 0 ? ds.borderDash : undefined,
}));

new Chart(document.getElementById('historyChart'), {
  type: 'line',
  data: { labels: periodLabels, datasets: chartDatasets },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    interaction: { mode: 'index', intersect: false },
    plugins: {
      legend: { position: 'top', labels: { font: { size: 11 }, boxWidth: 12, padding: 16 } },
      tooltip: {
        callbacks: {
          label: ctx => ` ${ctx.dataset.label}: Rp ${ctx.parsed.y.toFixed(2)}B`,
        },
      },
    },
    scales: {
      x: { grid: { color: 'rgba(0,0,0,.04)' }, ticks: { font: { size: 11 }, color: '#4a6080' } },
      y: {
        grid: { color: 'rgba(0,0,0,.04)' },
        ticks: { font: { size: 10 }, color: '#4a6080', callback: v => 'Rp ' + v.toFixed(1) + 'B' },
        title: { display: true, text: 'IDR (Billion)', font: { size: 11 }, color: '#4a6080' },
      },
    },
  },
});

// Toggle compare year selector visibility
function toggleCompareYear(checked) {
  document.getElementById('compare-year-wrap').style.display = checked ? 'flex' : 'none';
  document.getElementById('compare-label').style.background  = checked ? '#dbeafe' : 'var(--surface)';
}
</script>
@endpush