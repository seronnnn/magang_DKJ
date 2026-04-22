{{-- resources/views/partials/csv-export.blade.php --}}
{{-- Shared CSV export utility — include after table-manager-styles on any page --}}
<script>
/**
 * exportTableCSV(tableId, filename)
 *
 * Exports ALL rows (not just the current page) from the given <table> element.
 * Skips the "Aging Bar", "Progress", and "Edit" columns (no useful text data).
 * Numbers in cells are exported raw (stripped of Rp / formatting).
 */
function exportTableCSV(tableId, filename) {
  const table = document.getElementById(tableId);
  if (!table) return;

  // Columns to SKIP by header text
  const SKIP_HEADERS = ['Aging Bar', 'Progress', 'Edit', ''];

  const thead = table.querySelector('thead');
  const tbody = table.querySelector('tbody');
  if (!thead || !tbody) return;

  // Build header row — find which column indices to keep
  const headerCells = Array.from(thead.querySelectorAll('th'));
  const keepCols    = [];
  const csvHeaders  = [];

  headerCells.forEach((th, i) => {
    // Get clean header text (strip sort icon characters)
    const txt = th.textContent.replace(/[↕↑↓]/g, '').trim();
    if (!SKIP_HEADERS.includes(txt)) {
      keepCols.push(i);
      csvHeaders.push(csvEscape(txt));
    }
  });

  const rows = [csvHeaders.join(',')];

  // Export ALL tr rows (including those hidden by pagination)
  Array.from(tbody.querySelectorAll('tr')).forEach(tr => {
    const cells = Array.from(tr.querySelectorAll('td'));
    const rowData = keepCols.map(i => {
      const cell = cells[i];
      if (!cell) return '';
      // Get visible text, strip excess whitespace
      let text = cell.textContent.replace(/\s+/g, ' ').trim();
      // Clean up "—" to empty
      if (text === '—') text = '';
      return csvEscape(text);
    });
    rows.push(rowData.join(','));
  });

  const csvContent = '\uFEFF' + rows.join('\r\n'); // BOM for Excel UTF-8
  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
  const url  = URL.createObjectURL(blob);
  const link = document.createElement('a');
  link.setAttribute('href', url);
  link.setAttribute('download', filename || 'export.csv');
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  URL.revokeObjectURL(url);
}

function csvEscape(value) {
  const str = String(value ?? '');
  // Wrap in quotes if contains comma, newline, or double-quote
  if (str.includes(',') || str.includes('\n') || str.includes('"')) {
    return '"' + str.replace(/"/g, '""') + '"';
  }
  return str;
}
</script>