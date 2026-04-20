{{-- resources/views/partials/table-manager-styles.blade.php --}}
<style>
.sortable { cursor:pointer; user-select:none; }
.sortable:hover { background:#f0f6ff !important; }
.sort-icon { font-size:10px; color:#94a3b8; margin-left:4px; }
.th-sort-asc  .sort-icon::after { content:'↑'; color:var(--navy); font-size:11px; }
.th-sort-desc .sort-icon::after { content:'↓'; color:var(--navy); font-size:11px; }
.th-sort-asc  .sort-icon,
.th-sort-desc .sort-icon { visibility:hidden; }
.th-sort-asc  .sort-icon::after,
.th-sort-desc .sort-icon::after { visibility:visible; }
.page-btn-num {
  padding:4px 10px;
  border:1px solid var(--border);
  border-radius:7px;
  background:var(--surface);
  cursor:pointer;
  font-size:12px;
  font-weight:600;
  transition:all .15s;
}
.page-btn-num:hover { border-color:var(--navy); color:var(--navy); }
.page-btn-num.active { background:var(--navy); color:#fff; border-color:var(--navy); }
button:disabled { opacity:0.38; cursor:not-allowed; }
</style>

<script>
/**
 * makeTableManager
 *
 * @param {string} tbodyId       - id of <tbody>
 * @param {string} tableId       - id of <table> (for header clicks)
 * @param {string} countId       - id of count <span>
 * @param {string} pageInfoId    - id of page-info <span>
 * @param {string} pageBtnsId    - id of page-numbers container
 * @param {string} prevId        - id of prev button
 * @param {string} nextId        - id of next button
 * @param {number} perPage       - rows per page (10)
 * @param {object} rawAttrMap    - map colIndex → dataset key for numeric sort
 *                                 null means use textContent (text sort)
 */
function makeTableManager(tbodyId, tableId, countId, pageInfoId, pageBtnsId, prevId, nextId, perPage, rawAttrMap) {
  rawAttrMap = rawAttrMap || {};

  const tbody   = document.getElementById(tbodyId);
  const table   = document.getElementById(tableId);
  let allRows   = Array.from(tbody.querySelectorAll('tr'));
  let filtered  = allRows.slice();
  let currentPage = 1;
  let sortCol = -1, sortDir = 1;

  // ── Render current page ───────────────────────────────────────────────────
  function render() {
    const totalPages = Math.max(1, Math.ceil(filtered.length / perPage));
    if (currentPage > totalPages) currentPage = totalPages;
    if (currentPage < 1) currentPage = 1;

    const start = (currentPage - 1) * perPage;
    const end   = start + perPage;

    allRows.forEach(r => (r.style.display = 'none'));
    filtered.forEach((r, i) => {
      r.style.display = (i >= start && i < end) ? '' : 'none';
    });

    // Count badge
    const countEl = document.getElementById(countId);
    if (countEl) countEl.textContent = filtered.length;

    // Page info text
    const infoEl = document.getElementById(pageInfoId);
    if (infoEl) {
      infoEl.textContent = filtered.length === 0
        ? 'No results found'
        : `Showing ${start + 1}–${Math.min(end, filtered.length)} of ${filtered.length}`;
    }

    // Prev / Next buttons
    const prevBtn = document.getElementById(prevId);
    const nextBtn = document.getElementById(nextId);
    if (prevBtn) prevBtn.disabled = (currentPage === 1);
    if (nextBtn) nextBtn.disabled = (currentPage === totalPages);

    // Numbered page buttons (window of 5)
    const btnsEl = document.getElementById(pageBtnsId);
    if (btnsEl) {
      btnsEl.innerHTML = '';
      const lo = Math.max(1, currentPage - 2);
      const hi = Math.min(totalPages, currentPage + 2);
      for (let p = lo; p <= hi; p++) {
        const b = document.createElement('button');
        b.className = 'page-btn-num' + (p === currentPage ? ' active' : '');
        b.textContent = p;
        const pg = p;
        b.addEventListener('click', () => { currentPage = pg; render(); });
        btnsEl.appendChild(b);
      }
    }
  }

  // ── Numeric value from dataset (raw PHP value, always accurate) ───────────
  function getRawValue(row, colIndex) {
    const key = rawAttrMap[colIndex];
    if (!key) return null;
    const v = row.dataset[key];
    return v !== undefined ? parseFloat(v) || 0 : null;
  }

  // ── Text value from cell for text-sort ───────────────────────────────────
  function getCellText(row, colIndex) {
    const cells = row.querySelectorAll('td');
    return cells[colIndex] ? cells[colIndex].textContent.trim() : '';
  }

  // ── Sort ──────────────────────────────────────────────────────────────────
  function doSort(colIndex, toggleDir) {
    if (toggleDir) {
      if (sortCol === colIndex) {
        sortDir = -sortDir;
      } else {
        sortCol = colIndex;
        sortDir = 1;
      }
    }

    // Update header classes
    if (table) {
      table.querySelectorAll('th.sortable').forEach((th, i) => {
        th.classList.remove('th-sort-asc', 'th-sort-desc');
        if (i === sortCol) {
          th.classList.add(sortDir === 1 ? 'th-sort-asc' : 'th-sort-desc');
        }
      });
    }

    filtered.sort((a, b) => {
      const ra = getRawValue(a, sortCol);
      const rb = getRawValue(b, sortCol);

      // Both have raw numeric data → pure numeric sort
      if (ra !== null && rb !== null) {
        return (ra - rb) * sortDir;
      }

      // Text sort
      const ta = getCellText(a, sortCol);
      const tb = getCellText(b, sortCol);
      return ta.localeCompare(tb, undefined, { numeric: true, sensitivity: 'base' }) * sortDir;
    });

    currentPage = 1;
    render();
  }

  // ── Search ────────────────────────────────────────────────────────────────
  function search(q) {
    const lq = q.toLowerCase().trim();
    filtered = lq
      ? allRows.filter(r => (r.dataset.search || '').includes(lq))
      : allRows.slice();
    currentPage = 1;
    if (sortCol >= 0) doSort(sortCol, false);
    else render();
  }

  // ── Bind header clicks ────────────────────────────────────────────────────
  if (table) {
    table.querySelectorAll('th.sortable').forEach((th, i) => {
      th.addEventListener('click', () => doSort(i, true));
    });
  }

  // ── Initial render ────────────────────────────────────────────────────────
  render();

  return {
    search,
    prevPage() { currentPage--; render(); },
    nextPage() { currentPage++; render(); },
  };
}
</script>