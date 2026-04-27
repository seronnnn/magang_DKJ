{{-- resources/views/partials/xlsx-export.blade.php --}}
{{-- Shared XLSX export utility — include after table-manager-styles on any page --}}
{{-- Requires SheetJS (xlsx) library --}}
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script>
/**
 * exportTableXLSX(tableId, filename)
 *
 * Exports ALL rows (not just the current page) from the given <table> element
 * as a formatted .xlsx file using SheetJS.
 * Skips the "Aging Bar", "Progress", and "Edit" columns (no useful text data).
 * Applies header styling: bold, dark background, white text, auto column widths.
 */
function exportTableXLSX(tableId, filename) {
    const table = document.getElementById(tableId);
    if (!table) return;

    // Columns to SKIP by header text
    const SKIP_HEADERS = ['Aging Bar', 'Progress', 'Edit', ''];

    const thead = table.querySelector('thead');
    const tbody = table.querySelector('tbody');
    if (!thead || !tbody) return;

    // Build header row — find which column indices to keep
    const headerCells = Array.from(thead.querySelectorAll('th'));
    const keepCols   = [];
    const csvHeaders = [];

    headerCells.forEach((th, i) => {
        const txt = th.textContent.replace(/[↕↑↓]/g, '').trim();
        if (!SKIP_HEADERS.includes(txt)) {
            keepCols.push(i);
            csvHeaders.push(txt);
        }
    });

    // Build data rows array (ALL rows, including pagination-hidden ones)
    const dataRows = [];
    Array.from(tbody.querySelectorAll('tr')).forEach(tr => {
        const cells = Array.from(tr.querySelectorAll('td'));
        const rowData = keepCols.map(i => {
            const cell = cells[i];
            if (!cell) return '';
            let text = cell.textContent.replace(/\s+/g, ' ').trim();
            if (text === '—') text = '';
            return text;
        });
        dataRows.push(rowData);
    });

    // Combine headers + data into a 2D array
    const sheetData = [csvHeaders, ...dataRows];

    // Create workbook and worksheet
    const wb = XLSX.utils.book_new();
    const ws = XLSX.utils.aoa_to_sheet(sheetData);

    // --- Styling ---

    // Calculate column widths based on longest content
    const colWidths = csvHeaders.map((header, colIdx) => {
        let maxLen = header.length;
        dataRows.forEach(row => {
            const cellVal = row[colIdx] ? String(row[colIdx]).length : 0;
            if (cellVal > maxLen) maxLen = cellVal;
        });
        return { wch: Math.min(maxLen + 4, 40) }; // +4 padding, cap at 40
    });
    ws['!cols'] = colWidths;

    // Style header row: bold, dark background (#1F2937), white text, centered
    const headerStyle = {
        font:      { bold: true, color: { rgb: 'FFFFFF' }, name: 'Arial', sz: 11 },
        fill:      { patternType: 'solid', fgColor: { rgb: '1F2937' } },
        alignment: { horizontal: 'center', vertical: 'center' },
        border: {
            bottom: { style: 'thin', color: { rgb: '374151' } },
            right:  { style: 'thin', color: { rgb: '374151' } },
        }
    };

    // Style data rows: alternating light grey fill, Arial font
    const dataStyleEven = {
        font:      { name: 'Arial', sz: 10, color: { rgb: '111827' } },
        fill:      { patternType: 'solid', fgColor: { rgb: 'F9FAFB' } },
        alignment: { vertical: 'center' },
    };
    const dataStyleOdd = {
        font:      { name: 'Arial', sz: 10, color: { rgb: '111827' } },
        fill:      { patternType: 'solid', fgColor: { rgb: 'FFFFFF' } },
        alignment: { vertical: 'center' },
    };

    // Apply styles cell by cell
    sheetData.forEach((row, rowIdx) => {
        row.forEach((_, colIdx) => {
            const cellAddress = XLSX.utils.encode_cell({ r: rowIdx, c: colIdx });
            if (!ws[cellAddress]) return;

            if (rowIdx === 0) {
                ws[cellAddress].s = headerStyle;
            } else {
                ws[cellAddress].s = rowIdx % 2 === 0 ? dataStyleEven : dataStyleOdd;
            }
        });
    });

    // Set row height for header
    ws['!rows'] = [{ hpt: 24 }]; // header row height in points

    // Freeze the header row
    ws['!freeze'] = { xSplit: 0, ySplit: 1, topLeftCell: 'A2', activePane: 'bottomLeft' };

    // Append sheet and trigger download
    XLSX.utils.book_append_sheet(wb, ws, 'Export');
    XLSX.writeFile(wb, (filename || 'export').replace(/\.csv$/i, '') + '.xlsx');
}
</script>