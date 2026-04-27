{{-- resources/views/partials/filters.blade.php --}}
@php
  $currentPeriodId = isset($period) && $period ? $period->id : request('period_id');
@endphp
<form method="GET" action="{{ request()->url() }}" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
  {{-- Always preserve period --}}
  @if($currentPeriodId)
    <input type="hidden" name="period_id" value="{{ $currentPeriodId }}">
  @endif

  {{-- Plant filter — visible for all roles --}}
  <select name="plant" class="filter-input" onchange="this.form.submit()">
    <option value="">All Plants</option>
    @foreach($plants as $p)
      <option value="{{ $p }}" {{ request('plant') == $p ? 'selected' : '' }}>Plant {{ $p }}</option>
    @endforeach
  </select>

  {{-- Collector filter — visible for all roles --}}
  <select name="collector" class="filter-input" onchange="this.form.submit()">
    <option value="">All Collectors</option>
    @foreach($collectors as $c)
      <option value="{{ $c }}" {{ request('collector') == $c ? 'selected' : '' }}>{{ $c }}</option>
    @endforeach
  </select>

  {{-- Clear filters --}}
  @if(request('plant') || request('collector'))
    <a href="{{ request()->url() }}{{ $currentPeriodId ? '?period_id='.$currentPeriodId : '' }}"
       class="btn btn-ghost" style="font-size:11px">✕ Clear</a>
  @endif
</form>