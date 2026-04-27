{{-- resources/views/partials/xlsx-export.blade.php --}}
{{-- Shared XLSX export utility — include after table-manager-styles on any page --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script>
/**
 * exportTableXLSX(tableId, filename, options)
 *
 * options = {
 *   pageTitle  : string   — e.g. "AR Aging", "Collection", "SO Overlimit"
 *   period     : string   — e.g. "January 2024"  (pass from Blade via data attribute or inline)
 *   collector  : string   — logged-in collector name
 * }
 *
 * Row layout:
 *   A1 : Page title | period | exported by
 *   A2 : (blank spacer)
 *   A3 : Table headers
 *   A4+ : Data rows
 */
function exportTableXLSX(tableId, filename, options) {
    options = options || {};

    const table = document.getElementById(tableId);
    if (!table) return;

    // ── Resolve meta from options or data attributes on <table> ──────────
    const pageTitle  = options.pageTitle  || table.dataset.pageTitle  || 'Export';
    const period     = options.period     || table.dataset.period     || '';
    const collector  = options.collector  || table.dataset.collector  || '';

    // Build the subtitle line
    const subtitleParts = [];
    if (period)    subtitleParts.push('Period: ' + period);
    if (collector) subtitleParts.push('Collector: ' + collector);
    const subtitle = subtitleParts.join('   |   ');

    // ── Columns to SKIP by header text ───────────────────────────────────
    const SKIP_HEADERS = ['Aging Bar', 'Progress', 'Edit', ''];

    const thead = table.querySelector('thead');
    const tbody = table.querySelector('tbody');
    if (!thead || !tbody) return;

    // Build header row — track which column indices to keep
    const headerCells = Array.from(thead.querySelectorAll('th'));
    const keepCols    = [];
    const csvHeaders  = [];

    headerCells.forEach((th, i) => {
        const txt = th.textContent.replace(/[↕↑↓]/g, '').trim();
        if (!SKIP_HEADERS.includes(txt)) {
            keepCols.push(i);
            csvHeaders.push(txt);
        }
    });

    const colCount = csvHeaders.length;

    // Build data rows (ALL rows, including pagination-hidden)
    const dataRows = [];
    Array.from(tbody.querySelectorAll('tr')).forEach(tr => {
        const cells   = Array.from(tr.querySelectorAll('td'));
        const rowData = keepCols.map(i => {
            const cell = cells[i];
            if (!cell) return '';
            let text = cell.textContent.replace(/\s+/g, ' ').trim();
            if (text === '—') text = '';
            return text;
        });
        dataRows.push(rowData);
    });

    // ── Build 2D array ────────────────────────────────────────────────────
    // Row 1: Title cell (will be merged across all columns)
    const titleRow = [pageTitle];
    for (let i = 1; i < colCount; i++) titleRow.push('');

    // Row 2: Subtitle / meta (period + collector, merged)
    const metaRow = [subtitle];
    for (let i = 1; i < colCount; i++) metaRow.push('');

    // Row 3: blank spacer
    const blankRow = Array(colCount).fill('');

    // Row 4: column headers
    // Row 5+: data
    const sheetData = [titleRow, metaRow, blankRow, csvHeaders, ...dataRows];

    // ── Create workbook / worksheet ───────────────────────────────────────
    const wb = XLSX.utils.book_new();
    const ws = XLSX.utils.aoa_to_sheet(sheetData);

    // ── Merge cells for title and meta rows across all columns ────────────
    if (!ws['!merges']) ws['!merges'] = [];
    // Title row (row index 0) A1:lastCol1
    ws['!merges'].push({ s: { r: 0, c: 0 }, e: { r: 0, c: colCount - 1 } });
    // Meta row (row index 1) A2:lastCol2
    ws['!merges'].push({ s: { r: 1, c: 0 }, e: { r: 1, c: colCount - 1 } });
    // Blank spacer row (row index 2)
    ws['!merges'].push({ s: { r: 2, c: 0 }, e: { r: 2, c: colCount - 1 } });

    // ── Column widths ─────────────────────────────────────────────────────
    const colWidths = csvHeaders.map((header, colIdx) => {
        let maxLen = header.length;
        dataRows.forEach(row => {
            const cellVal = row[colIdx] ? String(row[colIdx]).length : 0;
            if (cellVal > maxLen) maxLen = cellVal;
        });
        return { wch: Math.min(maxLen + 4, 42) };
    });
    ws['!cols'] = colWidths;

    // ── Row heights ───────────────────────────────────────────────────────
    ws['!rows'] = [
        { hpt: 28 },  // title row
        { hpt: 18 },  // meta row
        { hpt: 8  },  // blank spacer
        { hpt: 22 },  // header row
    ];

    // ── Freeze header (row 4, i.e. after 4 rows) ─────────────────────────
    ws['!freeze'] = { xSplit: 0, ySplit: 4, topLeftCell: 'A5', activePane: 'bottomLeft' };

    // ── Styles ────────────────────────────────────────────────────────────
    // Title style
    const titleStyle = {
        font      : { bold: true, sz: 16, name: 'Arial', color: { rgb: 'FFFFFF' } },
        fill      : { patternType: 'solid', fgColor: { rgb: '0F2942' } },
        alignment : { horizontal: 'left', vertical: 'center' },
    };

    // Meta / subtitle style
    const metaStyle = {
        font      : { sz: 11, name: 'Arial', color: { rgb: 'FFFFFF' }, italic: true },
        fill      : { patternType: 'solid', fgColor: { rgb: '1B3A6B' } },
        alignment : { horizontal: 'left', vertical: 'center' },
    };

    // Blank spacer style (same dark bg so it blends)
    const spacerStyle = {
        fill : { patternType: 'solid', fgColor: { rgb: '1B3A6B' } },
    };

    // Header row style
    const headerStyle = {
        font      : { bold: true, color: { rgb: 'FFFFFF' }, name: 'Arial', sz: 11 },
        fill      : { patternType: 'solid', fgColor: { rgb: '1F2937' } },
        alignment : { horizontal: 'center', vertical: 'center' },
        border    : {
            bottom : { style: 'thin', color: { rgb: '374151' } },
            right  : { style: 'thin', color: { rgb: '374151' } },
        },
    };

    // Data row styles (alternating)
    const dataStyleEven = {
        font      : { name: 'Arial', sz: 10, color: { rgb: '111827' } },
        fill      : { patternType: 'solid', fgColor: { rgb: 'F9FAFB' } },
        alignment : { vertical: 'center' },
    };
    const dataStyleOdd = {
        font      : { name: 'Arial', sz: 10, color: { rgb: '111827' } },
        fill      : { patternType: 'solid', fgColor: { rgb: 'FFFFFF' } },
        alignment : { vertical: 'center' },
    };

    // Apply styles cell by cell
    sheetData.forEach((row, rowIdx) => {
        row.forEach((_, colIdx) => {
            const addr = XLSX.utils.encode_cell({ r: rowIdx, c: colIdx });
            if (!ws[addr]) {
                // ensure merged blank cells exist so styles apply
                ws[addr] = { v: '', t: 's' };
            }

            if (rowIdx === 0) {
                ws[addr].s = titleStyle;
            } else if (rowIdx === 1) {
                ws[addr].s = metaStyle;
            } else if (rowIdx === 2) {
                ws[addr].s = spacerStyle;
            } else if (rowIdx === 3) {
                ws[addr].s = headerStyle;
            } else {
                // data rows start at index 4 (rowIdx 4 = first data row → even)
                ws[addr].s = (rowIdx % 2 === 0) ? dataStyleEven : dataStyleOdd;
            }
        });
    });

    // ── Write file ────────────────────────────────────────────────────────
    XLSX.utils.book_append_sheet(wb, ws, 'Export');
    XLSX.writeFile(wb, (filename || 'export').replace(/\.csv$/i, '').replace(/\.xlsx$/i, '') + '.xlsx');
}
</script>