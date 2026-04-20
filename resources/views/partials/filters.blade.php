{{-- resources/views/partials/filters.blade.php --}}
@php
  $isCollectorRole = Auth::user()->isCollector();
  $myCollectorName = $lockedCollector ?? null;
@endphp
<form method="GET" action="{{ request()->url() }}" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
  {{-- Always preserve period --}}
  @if(request('period_id'))
    <input type="hidden" name="period_id" value="{{ request('period_id') }}">
  @elseif(isset($period) && $period)
    <input type="hidden" name="period_id" value="{{ $period->id }}">
  @endif

  {{-- Plant filter — hidden for collectors --}}
  @if(!$isCollectorRole)
  <select name="plant" class="filter-input" onchange="this.form.submit()">
    <option value="">All Plants</option>
    @foreach($plants as $p)
      <option value="{{ $p }}" {{ request('plant') == $p ? 'selected' : '' }}>Plant {{ $p }}</option>
    @endforeach
  </select>
  @endif

  {{-- Collector filter — locked badge for collectors, dropdown for admin/manager --}}
  @if($isCollectorRole)
    {{-- Show a locked indicator --}}
    <span style="display:inline-flex;align-items:center;gap:6px;padding:6px 12px;
                 background:#dbeafe;border:1px solid #93c5fd;border-radius:8px;
                 font-size:12px;font-weight:600;color:#1e40af">
      <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
      {{ $myCollectorName }}
    </span>
  @else
  <select name="collector" class="filter-input" onchange="this.form.submit()">
    <option value="">All Collectors</option>
    @foreach($collectors as $c)
      <option value="{{ $c }}" {{ request('collector') == $c ? 'selected' : '' }}>{{ $c }}</option>
    @endforeach
  </select>
  @endif

  {{-- Clear filters — only for admin/manager --}}
  @if(!$isCollectorRole && (request('plant') || request('collector')))
    <a href="{{ request()->url() }}{{ (isset($period) && $period) ? '?period_id='.$period->id : '' }}"
       class="btn btn-ghost" style="font-size:11px">✕ Clear</a>
  @endif
</form>