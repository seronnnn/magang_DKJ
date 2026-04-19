{{-- resources/views/partials/filters.blade.php --}}
<form method="GET" action="{{ request()->url() }}" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
  {{-- Always preserve period --}}
  @if(request('period_id'))
    <input type="hidden" name="period_id" value="{{ request('period_id') }}">
  @elseif(isset($period) && $period)
    <input type="hidden" name="period_id" value="{{ $period->id }}">
  @endif

  <select name="plant" class="filter-input" onchange="this.form.submit()">
    <option value="">All Plants</option>
    @foreach($plants as $p)
      <option value="{{ $p }}" {{ request('plant') == $p ? 'selected' : '' }}>Plant {{ $p }}</option>
    @endforeach
  </select>

  <select name="collector" class="filter-input" onchange="this.form.submit()">
    <option value="">All Collectors</option>
    @foreach($collectors as $c)
      <option value="{{ $c }}" {{ request('collector') == $c ? 'selected' : '' }}>{{ $c }}</option>
    @endforeach
  </select>

  @if(request('plant') || request('collector'))
    <a href="{{ request()->url() }}{{ (isset($period) && $period) ? '?period_id='.$period->id : '' }}"
       class="btn btn-ghost" style="font-size:11px">✕ Clear</a>
  @endif
</form>