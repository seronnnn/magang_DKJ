{{-- resources/views/partials/pagination.blade.php --}}
@if($paginator->hasPages())
<div class="pagination-wrap">
  {{-- Prev --}}
  @if($paginator->onFirstPage())
    <span class="page-btn disabled">‹</span>
  @else
    <a href="{{ $paginator->previousPageUrl() }}" class="page-btn">‹</a>
  @endif

  {{-- Pages --}}
  @foreach($paginator->getUrlRange(max(1,$paginator->currentPage()-2), min($paginator->lastPage(),$paginator->currentPage()+2)) as $page => $url)
    @if($page == $paginator->currentPage())
      <span class="page-btn active">{{ $page }}</span>
    @else
      <a href="{{ $url }}" class="page-btn">{{ $page }}</a>
    @endif
  @endforeach

  {{-- Next --}}
  @if($paginator->hasMorePages())
    <a href="{{ $paginator->nextPageUrl() }}" class="page-btn">›</a>
  @else
    <span class="page-btn disabled">›</span>
  @endif

  <span style="font-size:11px;color:var(--muted);margin-left:8px">
    {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} of {{ $paginator->total() }}
  </span>
</div>
@endif
