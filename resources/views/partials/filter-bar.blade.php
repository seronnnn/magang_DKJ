{{-- resources/views/partials/filter-bar.blade.php --}}
<form method="GET" action="{{ $action }}" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
  @if(isset($showSearch) && $showSearch)
  <input type="text" name="search" value="{{ $search ?? '' }}"
    placeholder="Search customer…" class="filter-input" style="width:200px">
  @endif

  <select name="plant" class="filter-input">
    <option value="">All Plants</option>
    @foreach($plants as $p)
      <option value="{{ $p }}" {{ ($plant ?? '') == $p ? 'selected' : '' }}>Plant {{ $p }}</option>
    @endforeach
  </select>

  <select name="collector" class="filter-input">
    <option value="">All Collectors</option>
    @foreach($collectors as $c)
      <option value="{{ $c }}" {{ ($collector ?? '') == $c ? 'selected' : '' }}>{{ $c }}</option>
    @endforeach
  </select>

  <button type="submit" class="btn btn-primary">Filter</button>

  @if(($plant ?? '') || ($collector ?? '') || ($search ?? ''))
    <a href="{{ $action }}" class="btn btn-ghost">Clear</a>
  @endif
</form>
