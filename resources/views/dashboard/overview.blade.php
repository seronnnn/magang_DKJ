@extends('layouts.app')
@section('page-title', 'Dashboard Overview')

@php
  function idr($n) { return 'Rp '.number_format($n,0,',','.'); }
  function pct($a,$b) { return $b > 0 ? round($a/$b*100,1) : 0; }
@endphp

@section('content')

{{-- Filter Bar --}}
<div class="section-card mb-24" style="padding:14px 20px">
  @include('partials.filter-bar', ['action' => route('dashboard.overview'), 'showSearch' => false])
</div>

{{-- KPI Row --}}
<div class="grid-4 mb-24">
  <div class="kpi-card card-accent-blue">
    <div class="kpi-label">Total AR Outstanding</div>
    <div class="kpi-value mono">{{ idr($totalAR) }}</div>
    <div class="kpi-sub">{{ $byCollector->count() }} collectors · {{ $byPlant->count() }} plants</div>
  </div>
  <div class="kpi-card card-accent-green">
    <div class="kpi-label">AR Collected</div>
    <div class="kpi-value mono">{{ idr($totalActual) }}</div>
    <div class="kpi-sub">
      @if($collectionRate !== null)
        <span style="color:{{ $collectionRate >= 100 ? 'var(--green)' : ($collectionRate >= 70 ? 'var(--yellow)' : 'var(--red)') }}">
          {{ $collectionRate }}% of target
        </span>
      @else
        No target set
      @endif
    </div>
  </div>
  <div class="kpi-card card-accent-yellow">
    <div class="kpi-label">Overdue (>60 days)</div>
    <div class="kpi-value mono">{{ idr($totalOverdue) }}</div>
    <div class="kpi-sub">{{ $totalAR > 0 ? round($totalOverdue/$totalAR*100,1) : 0 }}% of total AR</div>
  </div>
  <div class="kpi-card card-accent-red">
    <div class="kpi-label">SO with Overdue</div>
    <div class="kpi-value mono">{{ number_format($totalSOWithOD) }}</div>
    <div class="kpi-sub">out of {{ number_format($totalSO) }} total SOs</div>
  </div>
</div>

{{-- Charts Row --}}
<div class="grid-2 mb-24">

  {{-- Aging Breakdown Chart --}}
  <div class="section-card">
    <div class="section-header">
      <span class="section-title">AR Aging Breakdown</span>
    </div>
    <div class="section-body">
      <div class="chart-wrap"><canvas id="agingChart"></canvas></div>
      <div style="display:flex;gap:12px;margin-top:12px;flex-wrap:wrap">
        @php
          $agingLabels = ['Current','1–30d','30–60d','60–90d','>90d'];
          $agingColors = ['#1B3A6B','#1e88e5','#d97706','#ea580c','#dc2626'];
          $agingVals   = [$aging['current'],$aging['days_1_30'],$aging['days_30_60'],$aging['days_60_90'],$aging['over_90']];
          $agingTotal  = array_sum($agingVals) ?: 1;
        @endphp
        @foreach($agingLabels as $i => $lbl)
          <div style="display:flex;align-items:center;gap:5px;font-size:11px">
            <span style="width:10px;height:10px;border-radius:2px;background:{{ $agingColors[$i] }};display:inline-block"></span>
            <span style="color:var(--muted)">{{ $lbl }}</span>
            <span class="mono" style="font-size:10px">{{ round($agingVals[$i]/$agingTotal*100,1) }}%</span>
          </div>
        @endforeach
      </div>
    </div>
  </div>

  {{-- Collection by Collector --}}
  <div class="section-card">
    <div class="section-header">
      <span class="section-title">Collection by Collector</span>
    </div>
    <div class="section-body">
      <div class="chart-wrap"><canvas id="collectorChart"></canvas></div>
    </div>
  </div>

</div>

{{-- Collector Summary Table --}}
<div class="section-card mb-24">
  <div class="section-header">
    <span class="section-title">Collector Summary</span>
  </div>
  <div style="overflow-x:auto">
    <table class="data-table">
      <thead>
        <tr>
          <th>Collector</th>
          <th class="num">Customers</th>
          <th class="num">AR Outstanding</th>
          <th class="num">Target</th>
          <th class="num">Collected</th>
          <th style="min-width:140px">Achievement</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        @foreach($byCollector as $c)
          @php
            $rate  = $c['collection_rate'];
            $color = $rate === null ? '#64748b' : ($rate >= 100 ? '#16a34a' : ($rate >= 70 ? '#d97706' : '#dc2626'));
            $badge = $rate === null ? '<span class="badge badge-gray">No Target</span>'
                   : ($rate >= 100 ? '<span class="badge badge-green">Achieved</span>'
                   : ($rate >   0  ? '<span class="badge badge-yellow">Partial</span>'
                                   : '<span class="badge badge-red">None</span>'));
          @endphp
          <tr>
            <td><strong>{{ $c['name'] }}</strong></td>
            <td class="num">{{ $c['customers'] }}</td>
            <td class="num mono">{{ idr($c['total_ar']) }}</td>
            <td class="num mono">{{ idr($c['ar_target']) }}</td>
            <td class="num mono" style="color:{{ $color }}">{{ idr($c['ar_actual']) }}</td>
            <td>
              <div style="display:flex;align-items:center;gap:8px">
                <div class="progress-bg" style="flex:1">
                  <div class="progress-fill" style="width:{{ min(100,$rate??0) }}%;background:{{ $color }}"></div>
                </div>
                <span class="mono" style="font-size:11px;color:{{ $color }};min-width:36px">
                  {{ $rate !== null ? $rate.'%' : '—' }}
                </span>
              </div>
            </td>
            <td>{!! $badge !!}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>

{{-- Plant AR Summary --}}
<div class="section-card">
  <div class="section-header">
    <span class="section-title">AR by Plant</span>
  </div>
  <div style="overflow-x:auto">
    <table class="data-table">
      <thead>
        <tr>
          <th>Plant</th>
          <th class="num">Customers</th>
          <th class="num">AR Outstanding</th>
          <th style="min-width:180px">Share</th>
        </tr>
      </thead>
      <tbody>
        @php $grandTotal = $byPlant->sum('total_ar') ?: 1; @endphp
        @foreach($byPlant->sortByDesc('total_ar') as $p)
          <tr>
            <td><strong>Plant {{ $p['plant'] }}</strong></td>
            <td class="num">{{ $p['customers'] }}</td>
            <td class="num mono">{{ idr($p['total_ar']) }}</td>
            <td>
              <div style="display:flex;align-items:center;gap:8px">
                <div class="progress-bg" style="flex:1">
                  <div class="progress-fill" style="width:{{ round($p['total_ar']/$grandTotal*100,1) }}%;background:var(--navy)"></div>
                </div>
                <span class="mono" style="font-size:11px;color:var(--muted);min-width:38px">
                  {{ round($p['total_ar']/$grandTotal*100,1) }}%
                </span>
              </div>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>

@endsection

@push('scripts')
<script>
// ── Aging Doughnut ──
new Chart(document.getElementById('agingChart'), {
  type: 'doughnut',
  data: {
    labels: ['Current','1–30d','30–60d','60–90d','>90d'],
    datasets: [{ data: @json(array_values($aging)), backgroundColor: ['#1B3A6B','#1e88e5','#d97706','#ea580c','#dc2626'], borderWidth: 2, borderColor:'#fff' }]
  },
  options: { responsive:true, maintainAspectRatio:false, plugins:{ legend:{ display:false } } }
});

// ── Collector Bar ──
const colData = @json($byCollector);
new Chart(document.getElementById('collectorChart'), {
  type: 'bar',
  data: {
    labels: colData.map(c => c.name),
    datasets: [
      { label:'Target',    data: colData.map(c=>c.ar_target), backgroundColor:'#e2e8f0' },
      { label:'Collected', data: colData.map(c=>c.ar_actual), backgroundColor:'#1B3A6B' },
    ]
  },
  options: {
    responsive:true, maintainAspectRatio:false,
    plugins:{ legend:{ position:'top', labels:{ font:{ size:11 } } } },
    scales:{
      x:{ ticks:{ font:{ size:11 } } },
      y:{ ticks:{ font:{ size:10 }, callback: v => 'Rp '+Intl.NumberFormat('id').format(v) } }
    }
  }
});
</script>
@endpush
