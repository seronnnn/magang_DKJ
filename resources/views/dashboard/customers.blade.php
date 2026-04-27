@extends('layouts.app')
@section('title','Customers')
@section('page-title','Customers')

@php $isAdmin = Auth::user()->isAdmin(); @endphp

@section('topbar-actions')
  @include('partials.filters')
@endsection

@section('content')

{{-- ══════════ CUSTOMER DETAIL MODAL ══════════ --}}
<div id="cust-detail-overlay"
     onclick="if(event.target===this) closeCustDetail()"
     style="display:none;position:fixed;inset:0;background:rgba(15,31,54,.5);z-index:300;
            align-items:center;justify-content:center;backdrop-filter:blur(3px)">
  <div id="cust-detail-box"
       style="background:#fff;border:1px solid var(--border);border-radius:16px;padding:28px;
              width:860px;max-width:96vw;max-height:90vh;overflow-y:auto;
              box-shadow:0 20px 60px rgba(15,31,54,.25)">

    {{-- Header --}}
    <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:20px;gap:12px">
      <div style="display:flex;align-items:center;gap:12px">
        <div style="width:44px;height:44px;border-radius:10px;background:#dbeafe;
                    display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:20px">👥</div>
        <div>
          <div id="cd-name" style="font-size:18px;font-weight:800;color:var(--navy);letter-spacing:-.3px"></div>
          <div id="cd-id"   style="font-size:12px;color:var(--muted);margin-top:2px"></div>
        </div>
      </div>
      <button onclick="closeCustDetail()"
              style="background:none;border:1px solid var(--border);border-radius:8px;
                     width:32px;height:32px;cursor:pointer;font-size:18px;color:var(--muted);
                     display:flex;align-items:center;justify-content:center;flex-shrink:0"
              onmouseover="this.parentElement.style.background=''" onmouseout="">×</button>
    </div>

    {{-- Loading --}}
    <div id="cd-loading" style="text-align:center;padding:48px 0;color:var(--muted)">
      <div style="font-size:28px;margin-bottom:10px">⏳</div>
      <div style="font-size:13px">Loading customer data…</div>
    </div>

    {{-- Content (hidden until loaded) --}}
    <div id="cd-content" style="display:none">

      {{-- Info grid --}}
      <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:12px;margin-bottom:22px"
           id="cd-info-grid"></div>

      {{-- Plants --}}
      <div style="margin-bottom:22px">
        <div style="font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;
                    letter-spacing:.08em;margin-bottom:8px">Plants</div>
        <div id="cd-plants" style="display:flex;gap:6px;flex-wrap:wrap"></div>
      </div>

      {{-- Invoices table --}}
      <div style="border:1px solid var(--border);border-radius:10px;overflow:hidden">
        <div style="padding:14px 16px;border-bottom:1px solid var(--border);background:#f8fafc;
                    display:flex;align-items:center;justify-content:space-between">
          <div style="font-size:12px;font-weight:700">Invoice / AR Records
            <span id="cd-inv-count" style="margin-left:6px;background:#dbeafe;color:#1e40af;
                  font-size:10px;padding:1px 7px;border-radius:99px;font-weight:700"></span>
          </div>
        </div>
        <div style="overflow-x:auto;max-height:340px;overflow-y:auto">
          <table class="data-table">
            <thead>
              <tr>
                <th>Period</th>
                <th>Invoice ID</th>
                <th>Collector</th>
                <th>Due Date</th>
                <th class="num">Current</th>
                <th class="num">1–30d</th>
                <th class="num">30–60d</th>
                <th class="num">60–90d</th>
                <th class="num">&gt;90d</th>
                <th class="num">Total AR</th>
                <th class="num">Target</th>
                <th class="num">Actual</th>
                <th class="num">Rate</th>
              </tr>
            </thead>
            <tbody id="cd-inv-tbody"></tbody>
          </table>
        </div>
      </div>

    </div>
  </div>
</div>
{{-- ══════════ END CUSTOMER DETAIL MODAL ══════════ --}}

<div style="background:var(--surface);border:1px solid var(--border);border-radius:12px;overflow:hidden;box-shadow:var(--shadow)">
  <div style="padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
    <div style="font-size:12px;font-weight:700">All Customers (<span id="cust-count">{{ $rows->count() }}</span> records)</div>
    <div style="display:flex;align-items:center;gap:8px">
      <input type="text" id="cust-search" placeholder="Search customer…" oninput="custTable.search(this.value)"
        style="padding:6px 12px;border:1px solid var(--border);border-radius:8px;font-size:12px;outline:none;width:200px">
      <button onclick="exportTableXLSX('cust-table', 'customers.csv')"
        style="display:inline-flex;align-items:center;gap:5px;padding:6px 14px;
               background:#16a34a;color:#fff;border:none;border-radius:8px;
               font-size:11px;font-weight:700;cursor:pointer;white-space:nowrap;transition:all .15s"
        onmouseover="this.style.background='#15803d'" onmouseout="this.style.background='#16a34a'">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        Export XLSX
      </button>
    </div>
  </div>
  <div class="table-scroll">
    <table class="data-table" id="cust-table">
      <thead><tr>
        <th class="sortable" style="white-space:nowrap">Customer ID <span class="sort-icon">↕</span></th>
        <th class="sortable" style="white-space:nowrap">Customer Name <span class="sort-icon">↕</span></th>
        <th class="sortable" style="white-space:nowrap">Plant <span class="sort-icon">↕</span></th>
        <th class="sortable" style="white-space:nowrap">Collector <span class="sort-icon">↕</span></th>
        <th class="num sortable" style="white-space:nowrap">Current <span class="sort-icon">↕</span></th>
        <th class="num sortable" style="white-space:nowrap">1-30d <span class="sort-icon">↕</span></th>
        <th class="num sortable" style="white-space:nowrap">&gt;60d <span class="sort-icon">↕</span></th>
        <th class="num sortable" style="white-space:nowrap">Total AR <span class="sort-icon">↕</span></th>
        <th class="num sortable" style="white-space:nowrap">Target <span class="sort-icon">↕</span></th>
        <th class="num sortable" style="white-space:nowrap">Actual <span class="sort-icon">↕</span></th>
        <th class="sortable" style="white-space:nowrap">Collection <span class="sort-icon">↕</span></th>
        <th class="num sortable" style="white-space:nowrap">SO OD <span class="sort-icon">↕</span></th>
        <th style="width:60px;text-align:center">Edit</th>
      </tr></thead>
      <tbody id="cust-tbody">
      @foreach($rows as $r)
      @php
        $rate       = $r->collection_rate;
        $status     = $r->collection_status;
        $badgeCls   = match($status){ 'achieved'=>'badge-green','partial'=>'badge-yellow','none'=>'badge-red', default=>'badge-gray' };
        $statusLabel= match($status){ 'achieved'=>'Achieved','partial'=>'Partial','none'=>'None', default=>'No Target' };
        $hasOverdue = ($r->days_60_90 + $r->days_over_90) > 0;
        $over60     = $r->days_60_90 + $r->days_over_90;
      @endphp
      <tr style="{{ $hasOverdue ? 'background:#fff9f9' : '' }}"
          data-search="{{ strtolower($r->customer_name . ' ' . $r->customer_id . ' ' . $r->collection_by) }}"
          data-rawcurrent="{{ intval($r->current) }}"
          data-raw130="{{ intval($r->days_1_30) }}"
          data-rawover60="{{ intval($over60) }}"
          data-rawtotal="{{ intval($r->total) }}"
          data-rawtarget="{{ intval($r->ar_target) }}"
          data-rawactual="{{ intval($r->ar_actual) }}"
          data-rawrate="{{ $rate !== null ? floatval($rate) : -1 }}"
          data-rawsood="{{ intval($r->so_with_od) }}">
        <td class="mono" style="font-size:11px;color:var(--muted)">{{ $r->customer_id }}</td>
        <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="{{ $r->customer_name }}">
          <button onclick="openCustDetail('{{ addslashes($r->customer_id) }}')"
            style="background:none;border:none;cursor:pointer;font-weight:600;color:var(--navy);
                   font-size:inherit;font-family:inherit;text-align:left;padding:0;
                   text-decoration:underline;text-underline-offset:3px;text-decoration-color:rgba(27,58,107,.35)"
            onmouseover="this.style.color='#1e88e5'" onmouseout="this.style.color='var(--navy)'">
            {{ $r->customer_name }}
          </button>
        </td>
        <td><span class="badge badge-blue">{{ $r->plant }}</span></td>
        <td style="font-size:11px">{{ $r->collection_by }}</td>
        <td class="num">{{ $r->current > 0 ? fmtIDR($r->current) : '—' }}</td>
        <td class="num">{{ $r->days_1_30 > 0 ? fmtIDR($r->days_1_30) : '—' }}</td>
        <td class="num" style="{{ $hasOverdue ? 'color:#dc2626;font-weight:700' : '' }}">
          {{ $over60 > 0 ? fmtIDR($over60) : '—' }}
        </td>
        <td class="num" style="font-weight:700">{{ fmtIDR($r->total) }}</td>
        <td class="num">{{ $r->ar_target > 0 ? fmtIDR($r->ar_target) : '—' }}</td>
        <td class="num">{{ $r->ar_actual > 0 ? fmtIDR($r->ar_actual) : '—' }}</td>
        <td>
          <span class="badge {{ $badgeCls }}">{{ $statusLabel }}</span>
          @if($rate !== null)<div style="font-size:10px;color:var(--muted);margin-top:2px">{{ $rate }}%</div>@endif
        </td>
        <td class="num" style="{{ $r->so_with_od > 0 ? 'color:#dc2626;font-weight:700' : '' }}">
          {{ $r->so_with_od > 0 ? $r->so_with_od : '—' }}
        </td>
        <td style="text-align:center">
          <button class="btn btn-warning btn-sm" onclick='openEditModal(@json((array)$r))'>✏️</button>
        </td>
      </tr>
      @endforeach
      </tbody>
    </table>
  </div>
  <div style="padding:12px 20px;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px">
    <span style="font-size:12px;color:var(--muted)" id="cust-page-info"></span>
    <div style="display:flex;gap:6px;align-items:center">
      <button id="cust-prev" onclick="custTable.prevPage()"
        style="padding:5px 12px;border:1px solid var(--border);border-radius:7px;background:var(--surface);cursor:pointer;font-size:12px;font-weight:600">‹ Prev</button>
      <div id="cust-page-btns" style="display:flex;gap:4px"></div>
      <button id="cust-next" onclick="custTable.nextPage()"
        style="padding:5px 12px;border:1px solid var(--border);border-radius:7px;background:var(--surface);cursor:pointer;font-size:12px;font-weight:600">Next ›</button>
    </div>
  </div>
</div>

@include('partials.table-manager-styles')
@include('partials.csv-export')
<script>
const custTable = makeTableManager(
  'cust-tbody', 'cust-table',
  'cust-count', 'cust-page-info', 'cust-page-btns', 'cust-prev', 'cust-next',
  10,
  {
    0:  null,
    1:  null,
    2:  null,
    3:  null,
    4:  'rawcurrent',
    5:  'raw130',
    6:  'rawover60',
    7:  'rawtotal',
    8:  'rawtarget',
    9:  'rawactual',
    10: 'rawrate',
    11: 'rawsood',
  }
);

const urlSearch = new URLSearchParams(window.location.search).get('search');
if (urlSearch) {
  const el = document.getElementById('cust-search');
  if (el) { el.value = urlSearch; custTable.search(urlSearch); }
}

/* ── Customer Detail Modal ── */
function fmtIDR(v) {
  if (!v || v === 0) return '—';
  if (v >= 1e12) return 'Rp ' + (v / 1e12).toFixed(2) + 'T';
  if (v >= 1e9)  return 'Rp ' + (v / 1e9).toFixed(2) + 'B';
  if (v >= 1e6)  return 'Rp ' + (v / 1e6).toFixed(1) + 'M';
  return 'Rp ' + Number(v).toLocaleString('id-ID');
}

function infoCard(label, value, icon) {
  return `<div style="background:#f8fafc;border:1px solid var(--border);border-radius:10px;padding:14px 16px">
    <div style="font-size:10px;color:var(--muted);font-weight:700;text-transform:uppercase;
                letter-spacing:.08em;margin-bottom:5px">${icon ? icon + ' ' : ''}${label}</div>
    <div style="font-size:13px;font-weight:600;color:var(--text)">${value || '<span style="color:var(--muted);font-weight:400">—</span>'}</div>
  </div>`;
}

async function openCustDetail(customerId) {
  const overlay = document.getElementById('cust-detail-overlay');
  const box     = document.getElementById('cust-detail-box');
  const loading = document.getElementById('cd-loading');
  const content = document.getElementById('cd-content');

  document.getElementById('cd-name').textContent = 'Loading…';
  document.getElementById('cd-id').textContent   = '';
  loading.style.display = 'block';
  content.style.display = 'none';

  overlay.style.display = 'flex';
  box.style.opacity     = '0';
  box.style.transform   = 'translateY(-16px)';
  requestAnimationFrame(() => { box.style.opacity = '1'; box.style.transform = 'translateY(0)'; box.style.transition = 'opacity .2s ease, transform .2s ease'; });

  try {
    const res  = await fetch(`{{ url('/dashboard/customers') }}/${encodeURIComponent(customerId)}/detail`, {
      headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
    });
    if (!res.ok) throw new Error('Failed to load');
    const data = await res.json();
    const c    = data.customer;

    document.getElementById('cd-name').textContent = c.customer_name;
    document.getElementById('cd-id').textContent   = 'Customer ID: ' + c.customer_id + (c.collector_name ? ' · Collector: ' + c.collector_name : '');

    document.getElementById('cd-info-grid').innerHTML =
      infoCard('PIC Name',         c.pic_name,        '👤') +
      infoCard('Email',            c.email ? `<a href="mailto:${c.email}" style="color:var(--navy)">${c.email}</a>` : null, '📧') +
      infoCard('WhatsApp',         c.whatsapp_number ? `<a href="https://wa.me/${c.whatsapp_number}" target="_blank" style="color:#16a34a">+${c.whatsapp_number}</a>` : null, '💬') +
      infoCard('Office Number',    c.office_number,   '📞') +
      infoCard('Address',          c.address,         '📍') +
      infoCard('Remark',           c.remark,          '📝');

    const plantsEl = document.getElementById('cd-plants');
    if (data.plants && data.plants.length > 0) {
      plantsEl.innerHTML = data.plants.map(p =>
        `<span class="badge badge-blue" style="font-size:11px;padding:3px 10px">Plant ${p}</span>`
      ).join('');
    } else {
      plantsEl.innerHTML = '<span style="font-size:12px;color:var(--muted)">No plants assigned</span>';
    }

    const invs = data.invoices;
    document.getElementById('cd-inv-count').textContent = invs.length + ' record' + (invs.length !== 1 ? 's' : '');

    if (invs.length === 0) {
      document.getElementById('cd-inv-tbody').innerHTML =
        `<tr><td colspan="13" style="text-align:center;padding:32px;color:var(--muted)">No invoice records found.</td></tr>`;
    } else {
      document.getElementById('cd-inv-tbody').innerHTML = invs.map(inv => {
        const rate      = inv.ar_target > 0 ? (inv.ar_actual / inv.ar_target * 100).toFixed(1) : null;
        const rateColor = rate === null ? '#94a3b8' : (rate >= 100 ? '#16a34a' : rate >= 70 ? '#d97706' : '#dc2626');
        const hasOD     = (inv.days_60_90 > 0 || inv.days_over_90 > 0);
        const collectorName = inv.collector_name || c.collector_name || '—';
        return `<tr style="${hasOD ? 'background:#fff9f9' : ''}">
          <td style="font-weight:600;white-space:nowrap;font-size:11px">${inv.period_label}</td>
          <td style="font-weight:700;white-space:nowrap">#${inv.invoice_id}</td>
          <td style="font-weight:700;font-size:12px;white-space:nowrap;color:var(--navy)">${collectorName}</td>
          <td style="font-size:11px;color:var(--muted);white-space:nowrap">${inv.due_date || '—'}</td>
          <td class="num">${fmtIDR(inv.current)}</td>
          <td class="num">${fmtIDR(inv.days_1_30)}</td>
          <td class="num" style="${inv.days_30_60 > 0 ? 'color:#d97706;font-weight:600' : ''}">${fmtIDR(inv.days_30_60)}</td>
          <td class="num" style="${inv.days_60_90 > 0 ? 'color:#ea580c;font-weight:600' : ''}">${fmtIDR(inv.days_60_90)}</td>
          <td class="num" style="${inv.days_over_90 > 0 ? 'color:#dc2626;font-weight:700' : ''}">${fmtIDR(inv.days_over_90)}</td>
          <td class="num" style="font-weight:700">${fmtIDR(inv.total)}</td>
          <td class="num" style="color:var(--muted)">${fmtIDR(inv.ar_target)}</td>
          <td class="num" style="color:#16a34a">${fmtIDR(inv.ar_actual)}</td>
          <td class="num" style="color:${rateColor};font-weight:700">${rate !== null ? rate + '%' : '—'}</td>
        </tr>`;
      }).join('');
    }

    loading.style.display = 'none';
    content.style.display = 'block';

  } catch (err) {
    loading.innerHTML = `<div style="color:#dc2626;text-align:center;padding:40px">
      <div style="font-size:24px">⚠️</div>
      <div style="margin-top:8px;font-size:13px">Failed to load customer data.</div>
    </div>`;
  }
}

function closeCustDetail() {
  document.getElementById('cust-detail-overlay').style.display = 'none';
}

document.addEventListener('keydown', e => {
  if (e.key === 'Escape') closeCustDetail();
});
</script>
@endsection