@extends('layouts.app')
@section('title', 'Reminder AR')
@section('page-title', 'Reminder AR — Desember 2025')

{{-- Override period selector — not needed on this page --}}
@php $periods = collect(); @endphp

@section('topbar-actions')
<div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">

  {{-- Status filter --}}
  <div style="display:flex;background:#f1f5f9;border:1px solid var(--border);border-radius:9px;padding:3px;gap:2px">
    @foreach(['all' => 'Semua', 'unpaid' => 'Belum Lunas', 'overdue' => 'Lewat Jatuh Tempo'] as $val => $lbl)
    <a href="{{ route('reminder.index') }}?status={{ $val }}"
       style="padding:5px 12px;border-radius:7px;font-size:11px;font-weight:700;text-decoration:none;
              transition:all .15s;
              {{ $filterStatus === $val
                  ? 'background:#fff;color:var(--navy);box-shadow:0 1px 3px rgba(0,0,0,.1)'
                  : 'color:var(--muted)' }}">
      {{ $lbl }}
    </a>
    @endforeach
  </div>

  {{-- Bulk Email button --}}
  <button onclick="openBulkModal('email')"
    style="display:flex;align-items:center;gap:6px;padding:7px 14px;background:var(--navy);color:#fff;
           border:none;border-radius:9px;font-size:12px;font-weight:700;cursor:pointer;transition:all .15s"
    onmouseover="this.style.background='#0d1f3c'" onmouseout="this.style.background='var(--navy)'">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
    Kirim Email Massal
  </button>

  {{-- Bulk WhatsApp button --}}
  <button onclick="openBulkModal('whatsapp')"
    style="display:flex;align-items:center;gap:6px;padding:7px 14px;background:#16a34a;color:#fff;
           border:none;border-radius:9px;font-size:12px;font-weight:700;cursor:pointer;transition:all .15s"
    onmouseover="this.style.background='#15803d'" onmouseout="this.style.background='#16a34a'">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M11.999 0C5.373 0 0 5.373 0 12c0 2.126.558 4.117 1.535 5.845L0 24l6.294-1.508A11.954 11.954 0 0 0 12 24c6.627 0 12-5.373 12-12S18.627 0 11.999 0zm0 21.818a9.818 9.818 0 0 1-5.007-1.374l-.36-.213-3.737.894.937-3.636-.235-.374A9.822 9.822 0 0 1 2.182 12c0-5.414 4.403-9.818 9.818-9.818 5.414 0 9.818 4.404 9.818 9.818 0 5.415-4.404 9.818-9.819 9.818z"/></svg>
    WhatsApp Massal
  </button>

</div>
@endsection

@section('content')

{{-- ════════ TOAST NOTIFICATION ════════ --}}
<div id="toast" style="display:none;position:fixed;top:20px;right:20px;z-index:500;
     min-width:300px;max-width:420px;padding:14px 18px;border-radius:12px;
     box-shadow:0 8px 30px rgba(0,0,0,.18);font-size:13px;font-weight:600;
     display:flex;align-items:center;gap:10px;animation:slideIn .3s ease">
</div>

{{-- ════════ BULK MODAL ════════ --}}
<div id="bulk-modal-overlay" onclick="if(event.target===this) closeBulkModal()"
     style="display:none;position:fixed;inset:0;background:rgba(15,31,54,.5);z-index:300;
            align-items:center;justify-content:center;backdrop-filter:blur(4px)">
  <div id="bulk-modal-box"
       style="background:#fff;border:1px solid var(--border);border-radius:16px;padding:28px;
              width:560px;max-width:96vw;max-height:88vh;overflow-y:auto;
              box-shadow:0 20px 60px rgba(15,31,54,.25)">

    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
      <div style="display:flex;align-items:center;gap:10px">
        <div id="bulk-modal-icon" style="width:38px;height:38px;border-radius:10px;
             display:flex;align-items:center;justify-content:center;font-size:18px"></div>
        <div>
          <div id="bulk-modal-title" style="font-size:16px;font-weight:800;color:var(--navy)"></div>
          <div id="bulk-modal-sub"   style="font-size:11px;color:var(--muted);margin-top:1px"></div>
        </div>
      </div>
      <button onclick="closeBulkModal()"
              style="background:none;border:1px solid var(--border);border-radius:8px;
                     width:32px;height:32px;cursor:pointer;font-size:18px;color:var(--muted);
                     display:flex;align-items:center;justify-content:center">×</button>
    </div>

    {{-- Selection controls --}}
    <div style="background:#f8fafc;border:1px solid var(--border);border-radius:10px;
                padding:12px 16px;margin-bottom:16px;display:flex;align-items:center;
                justify-content:space-between;flex-wrap:wrap;gap:8px">
      <div style="display:flex;align-items:center;gap:10px">
        <label style="display:flex;align-items:center;gap:6px;font-size:12px;font-weight:600;cursor:pointer">
          <input type="checkbox" id="bulk-select-all" onchange="toggleSelectAll(this)"
                 style="width:14px;height:14px;accent-color:var(--navy)">
          Pilih Semua
        </label>
        <span id="bulk-selected-count" style="font-size:11px;color:var(--muted)">0 dipilih</span>
      </div>
      <div style="display:flex;gap:6px">
        <button onclick="selectByUrgency('urgent')"
          style="padding:4px 10px;border:1px solid #f97316;border-radius:6px;
                 background:#fff7ed;color:#9a3412;font-size:10px;font-weight:700;cursor:pointer">
          Urgent Only
        </button>
        <button onclick="selectByUrgency('overdue')"
          style="padding:4px 10px;border:1px solid #dc2626;border-radius:6px;
                 background:#fee2e2;color:#991b1b;font-size:10px;font-weight:700;cursor:pointer">
          Overdue Only
        </button>
      </div>
    </div>

    {{-- Invoice checklist --}}
    <div id="bulk-invoice-list" style="max-height:320px;overflow-y:auto;border:1px solid var(--border);
         border-radius:10px;overflow:hidden">
    </div>

    <div id="bulk-wa-results" style="display:none;margin-top:16px;border:1px solid #bbf7d0;
         background:#f0fdf4;border-radius:10px;padding:14px;max-height:200px;overflow-y:auto">
      <div style="font-size:11px;font-weight:700;color:#15803d;margin-bottom:10px;
                  text-transform:uppercase;letter-spacing:.06em">WhatsApp Links — klik untuk buka</div>
      <div id="bulk-wa-links"></div>
    </div>

    <div id="bulk-modal-feedback" style="display:none;margin-top:14px;padding:12px 16px;
         border-radius:9px;font-size:13px;font-weight:600"></div>

    <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:20px">
      <button onclick="closeBulkModal()" class="btn btn-ghost">Batal</button>
      <button id="bulk-send-btn" onclick="executeBulkSend()"
        style="padding:9px 20px;border-radius:9px;font-size:12px;font-weight:700;
               cursor:pointer;border:none;color:#fff;transition:all .15s">
        <span id="bulk-send-label">Kirim Sekarang</span>
      </button>
    </div>
  </div>
</div>

{{-- ════════ WA PREVIEW MODAL ════════ --}}
<div id="wa-modal-overlay" onclick="if(event.target===this) closeWaModal()"
     style="display:none;position:fixed;inset:0;background:rgba(15,31,54,.5);z-index:400;
            align-items:center;justify-content:center;backdrop-filter:blur(4px)">
  <div style="background:#fff;border-radius:16px;padding:28px;width:440px;max-width:96vw;
              box-shadow:0 20px 60px rgba(15,31,54,.25)">
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:18px">
      <div style="width:38px;height:38px;background:#dcfce7;border-radius:10px;
                  display:flex;align-items:center;justify-content:center;font-size:20px">💬</div>
      <div>
        <div style="font-size:15px;font-weight:800;color:var(--navy)">Kirim WhatsApp</div>
        <div id="wa-modal-customer" style="font-size:11px;color:var(--muted);margin-top:2px"></div>
      </div>
      <button onclick="closeWaModal()"
              style="margin-left:auto;background:none;border:1px solid var(--border);
                     border-radius:8px;width:30px;height:30px;cursor:pointer;
                     font-size:16px;color:var(--muted);display:flex;align-items:center;justify-content:center">×</button>
    </div>
    <div style="background:#075e54;border-radius:12px;padding:18px;margin-bottom:16px">
      <div style="font-size:11px;color:rgba(255,255,255,.6);margin-bottom:10px;font-weight:600">Preview Pesan</div>
      <div id="wa-modal-preview" style="font-size:12px;color:#fff;line-height:1.7;white-space:pre-wrap"></div>
    </div>
    <div style="display:flex;gap:10px;justify-content:flex-end">
      <button onclick="closeWaModal()" class="btn btn-ghost" style="font-size:12px">Batal</button>
      <a id="wa-open-link" href="#" target="_blank" onclick="closeWaModal()"
         style="padding:9px 18px;background:#16a34a;color:#fff;border-radius:9px;
                font-size:12px;font-weight:700;text-decoration:none;
                display:inline-flex;align-items:center;gap:6px">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor">
          <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
          <path d="M11.999 0C5.373 0 0 5.373 0 12c0 2.126.558 4.117 1.535 5.845L0 24l6.294-1.508A11.954 11.954 0 0 0 12 24c6.627 0 12-5.373 12-12S18.627 0 11.999 0zm0 21.818a9.818 9.818 0 0 1-5.007-1.374l-.36-.213-3.737.894.937-3.636-.235-.374A9.822 9.822 0 0 1 2.182 12c0-5.414 4.403-9.818 9.818-9.818 5.414 0 9.818 4.404 9.818 9.818 0 5.415-4.404 9.818-9.819 9.818z"/>
        </svg>
        Buka WhatsApp
      </a>
    </div>
  </div>
</div>

{{-- ════════ KPI CARDS ════════ --}}
<div class="grid-kpi-4" style="margin-bottom:20px">

  <div class="kpi-card card-accent-blue">
    <div class="kpi-label">Total Invoice Desember</div>
    <div class="kpi-value mono">{{ $invoices->count() }}</div>
    <div class="kpi-sub">{{ $totalCustomers }} pelanggan unik</div>
  </div>

  <div class="kpi-card card-accent-yellow">
    <div class="kpi-label">Total Tagihan</div>
    <div class="kpi-value mono" style="font-size:18px">
      Rp {{ number_format($totalDue / 1e9, 2) }}B
    </div>
    <div class="kpi-sub">seluruh invoice aktif</div>
  </div>

  <div class="kpi-card" style="--accent-color:#f97316">
    <div class="kpi-label">Urgent / Overdue</div>
    <div class="kpi-value" style="color:#f97316">{{ $urgentCount }}</div>
    <div class="kpi-sub">perlu tindakan segera</div>
  </div>

  <div class="kpi-card card-accent-green">
    <div class="kpi-label">Collector</div>
    <div class="kpi-value" style="font-size:16px;font-weight:800">{{ $collectorName }}</div>
    <div class="kpi-sub">AR Desember 2025</div>
  </div>

</div>

{{-- ════════ INVOICE TABLE ════════ --}}
@if($invoices->isEmpty())
<div style="background:var(--surface);border:1px solid var(--border);border-radius:12px;
            padding:64px 24px;text-align:center;box-shadow:var(--shadow)">
  <div style="font-size:48px;margin-bottom:16px">📋</div>
  <div style="font-size:16px;font-weight:700;color:var(--navy);margin-bottom:6px">Tidak ada invoice ditemukan</div>
  <div style="font-size:13px;color:var(--muted)">
    Tidak ada invoice yang jatuh tempo di bulan Desember 2025 untuk collector ini.
  </div>
</div>
@else

<div style="background:var(--surface);border:1px solid var(--border);border-radius:12px;
            overflow:hidden;box-shadow:var(--shadow)">

  {{-- Table header bar --}}
  <div style="padding:16px 20px;border-bottom:1px solid var(--border);
              display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px">
    <div>
      <div style="font-size:13px;font-weight:700;color:var(--navy)">
        Invoice Jatuh Tempo Desember 2025
      </div>
      <div style="font-size:11px;color:var(--muted);margin-top:2px">
        {{ $invoices->count() }} invoice · Klik tombol untuk kirim pengingat
      </div>
    </div>
    <div style="display:flex;align-items:center;gap:8px">
      {{-- Legend --}}
      @foreach([
        ['urgency-overdue','Lewat Jatuh Tempo','#dc2626'],
        ['urgency-critical','Critical ≤3h','#e11d48'],
        ['urgency-urgent','Urgent ≤7h','#f97316'],
        ['urgency-soon','Soon ≤14h','#d97706'],
        ['urgency-upcoming','Upcoming','#16a34a'],
      ] as [$cls, $lbl, $color])
      <div style="display:flex;align-items:center;gap:4px">
        <div style="width:8px;height:8px;border-radius:2px;background:{{ $color }}"></div>
        <span style="font-size:10px;color:var(--muted)">{{ $lbl }}</span>
      </div>
      @endforeach
    </div>
  </div>

  <div class="table-scroll">
    <table class="data-table" id="reminder-table">
      <thead>
        <tr>
          <th style="width:40px;padding:10px 12px">
            <input type="checkbox" id="table-select-all" onchange="tableSelectAll(this)"
                   style="width:14px;height:14px;accent-color:var(--navy)">
          </th>
          <th>Invoice #</th>
          <th>Pelanggan</th>
          <th>Kontak</th>
          <th>Jatuh Tempo</th>
          <th>Status</th>
          <th class="num">Total AR</th>
          <th class="num">Terbayar</th>
          <th class="num">Sisa</th>
          <th style="text-align:center;min-width:160px">Aksi Reminder</th>
        </tr>
      </thead>
      <tbody>
      @foreach($invoices as $inv)
      @php
        $urgencyColors = [
          'urgency-overdue'  => ['bg' => '#fff5f5', 'dot' => '#dc2626', 'badge_bg' => '#fee2e2', 'badge_color' => '#991b1b'],
          'urgency-critical' => ['bg' => '#fff1f2', 'dot' => '#e11d48', 'badge_bg' => '#ffe4e6', 'badge_color' => '#881337'],
          'urgency-urgent'   => ['bg' => '#fff7ed', 'dot' => '#f97316', 'badge_bg' => '#ffedd5', 'badge_color' => '#9a3412'],
          'urgency-soon'     => ['bg' => '#fffbeb', 'dot' => '#d97706', 'badge_bg' => '#fef3c7', 'badge_color' => '#92400e'],
          'urgency-upcoming' => ['bg' => '#f0fdf4', 'dot' => '#16a34a', 'badge_bg' => '#dcfce7', 'badge_color' => '#166534'],
        ];
        $uc    = $urgencyColors[$inv->urgency_class] ?? $urgencyColors['urgency-upcoming'];
        $sisa  = max(0, $inv->total_ar - $inv->ar_actual);
        $hasEmail = !empty($inv->email);
        $hasWA    = !empty($inv->whatsapp_number);
      @endphp
      <tr style="background:{{ $uc['bg'] }}"
          data-invoice-id="{{ $inv->invoice_id }}"
          data-urgency="{{ $inv->urgency_class }}"
          data-customer="{{ $inv->customer_name }}"
          data-has-email="{{ $hasEmail ? '1' : '0' }}"
          data-has-wa="{{ $hasWA ? '1' : '0' }}">

        {{-- Checkbox --}}
        <td style="padding:10px 12px">
          <input type="checkbox" class="row-check"
                 value="{{ $inv->invoice_id }}"
                 style="width:14px;height:14px;accent-color:var(--navy)">
        </td>

        {{-- Invoice ID --}}
        <td>
          <div style="display:flex;align-items:center;gap:8px">
            <div style="width:8px;height:8px;border-radius:2px;background:{{ $uc['dot'] }};flex-shrink:0"></div>
            <span style="font-weight:700;font-family:'DM Mono',monospace;font-size:12px">#{{ $inv->invoice_id }}</span>
          </div>
        </td>

        {{-- Customer --}}
        <td>
          <div style="font-weight:700;font-size:12px;max-width:180px;white-space:nowrap;
                      overflow:hidden;text-overflow:ellipsis;color:var(--navy)" title="{{ $inv->customer_name }}">
            {{ $inv->customer_name }}
          </div>
          @if($inv->pic_name)
          <div style="font-size:10px;color:var(--muted);margin-top:1px">{{ $inv->pic_name }}</div>
          @endif
          <span class="badge badge-blue" style="font-size:9px;margin-top:3px">Plant {{ $inv->plant }}</span>
        </td>

        {{-- Contact info --}}
        <td>
          <div style="display:flex;flex-direction:column;gap:3px">
            @if($hasEmail)
            <div style="display:flex;align-items:center;gap:4px;font-size:11px;color:#1e40af">
              <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
              <span style="max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $inv->email }}</span>
            </div>
            @else
            <div style="font-size:10px;color:#94a3b8;font-style:italic">Tidak ada email</div>
            @endif

            @if($hasWA)
            <div style="display:flex;align-items:center;gap:4px;font-size:11px;color:#16a34a">
              <svg width="10" height="10" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413z"/><path d="M11.999 0C5.373 0 0 5.373 0 12c0 2.126.558 4.117 1.535 5.845L0 24l6.294-1.508A11.954 11.954 0 0 0 12 24c6.627 0 12-5.373 12-12S18.627 0 11.999 0zm0 21.818a9.818 9.818 0 0 1-5.007-1.374l-.36-.213-3.737.894.937-3.636-.235-.374A9.822 9.822 0 0 1 2.182 12c0-5.414 4.403-9.818 9.818-9.818 5.414 0 9.818 4.404 9.818 9.818 0 5.415-4.404 9.818-9.819 9.818z"/></svg>
              +{{ $inv->whatsapp_number }}
            </div>
            @else
            <div style="font-size:10px;color:#94a3b8;font-style:italic">Tidak ada WA</div>
            @endif
          </div>
        </td>

        {{-- Due date --}}
        <td>
          <div style="font-weight:700;font-size:12px;color:{{ $uc['dot'] }};white-space:nowrap">
            {{ \Carbon\Carbon::parse($inv->due_date)->format('d M Y') }}
          </div>
          <div style="font-size:10px;margin-top:3px">
            @if($inv->is_overdue)
              <span style="color:#dc2626;font-weight:700">{{ abs($inv->days_left) }} hari lalu</span>
            @else
              <span style="color:{{ $uc['dot'] }}">{{ $inv->days_left }} hari lagi</span>
            @endif
          </div>
        </td>

        {{-- Urgency badge --}}
        <td>
          <span style="display:inline-block;padding:3px 9px;border-radius:99px;font-size:10px;font-weight:700;
                       background:{{ $uc['badge_bg'] }};color:{{ $uc['badge_color'] }}">
            {{ $inv->urgency_label }}
          </span>
          @if($inv->overdue_90 > 0 || $inv->overdue_60 > 0)
          <div style="font-size:9px;color:#dc2626;margin-top:3px;font-weight:600">⚠ Ada aging &gt;60h</div>
          @endif
        </td>

        {{-- Total AR --}}
        <td class="num" style="font-weight:700;font-size:12px;white-space:nowrap">
          {{ $inv->total_ar > 0 ? 'Rp '.number_format($inv->total_ar, 0, ',', '.') : '—' }}
        </td>

        {{-- Paid --}}
        <td class="num" style="color:#16a34a;font-size:12px;white-space:nowrap">
          {{ $inv->ar_actual > 0 ? 'Rp '.number_format($inv->ar_actual, 0, ',', '.') : '—' }}
        </td>

        {{-- Remaining --}}
        <td class="num" style="font-weight:700;font-size:12px;white-space:nowrap;
             color:{{ $sisa > 0 ? '#dc2626' : '#16a34a' }}">
          {{ $sisa > 0 ? 'Rp '.number_format($sisa, 0, ',', '.') : '✓ Lunas' }}
        </td>

        {{-- Action buttons --}}
        <td style="text-align:center;white-space:nowrap">
          <div style="display:flex;align-items:center;justify-content:center;gap:6px">

            {{-- Email button --}}
            <button
              onclick="sendSingleEmail({{ $inv->invoice_id }}, '{{ addslashes($inv->customer_name) }}')"
              @if(!$hasEmail) disabled title="Tidak ada email" @endif
              style="display:inline-flex;align-items:center;gap:4px;padding:5px 10px;
                     border-radius:7px;border:1px solid {{ $hasEmail ? '#1e40af' : '#e2e8f0' }};
                     background:{{ $hasEmail ? '#dbeafe' : '#f8fafc' }};
                     color:{{ $hasEmail ? '#1e40af' : '#94a3b8' }};
                     font-size:11px;font-weight:700;cursor:{{ $hasEmail ? 'pointer' : 'not-allowed' }};
                     transition:all .15s"
              id="email-btn-{{ $inv->invoice_id }}"
              onmouseover="{{ $hasEmail ? "this.style.background='#bfdbfe'" : '' }}"
              onmouseout="{{ $hasEmail ? "this.style.background='#dbeafe'" : '' }}">
              <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
              Email
            </button>

            {{-- WhatsApp button --}}
            <button
              onclick="sendSingleWA({{ $inv->invoice_id }}, '{{ addslashes($inv->customer_name) }}')"
              @if(!$hasWA) disabled title="Tidak ada nomor WA" @endif
              style="display:inline-flex;align-items:center;gap:4px;padding:5px 10px;
                     border-radius:7px;border:1px solid {{ $hasWA ? '#16a34a' : '#e2e8f0' }};
                     background:{{ $hasWA ? '#dcfce7' : '#f8fafc' }};
                     color:{{ $hasWA ? '#15803d' : '#94a3b8' }};
                     font-size:11px;font-weight:700;cursor:{{ $hasWA ? 'pointer' : 'not-allowed' }};
                     transition:all .15s"
              id="wa-btn-{{ $inv->invoice_id }}"
              onmouseover="{{ $hasWA ? "this.style.background='#bbf7d0'" : '' }}"
              onmouseout="{{ $hasWA ? "this.style.background='#dcfce7'" : '' }}">
              <svg width="11" height="11" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413z"/><path d="M11.999 0C5.373 0 0 5.373 0 12c0 2.126.558 4.117 1.535 5.845L0 24l6.294-1.508A11.954 11.954 0 0 0 12 24c6.627 0 12-5.373 12-12S18.627 0 11.999 0zm0 21.818a9.818 9.818 0 0 1-5.007-1.374l-.36-.213-3.737.894.937-3.636-.235-.374A9.822 9.822 0 0 1 2.182 12c0-5.414 4.403-9.818 9.818-9.818 5.414 0 9.818 4.404 9.818 9.818 0 5.415-4.404 9.818-9.819 9.818z"/></svg>
              WA
            </button>

          </div>
        </td>

      </tr>
      @endforeach
      </tbody>
    </table>
  </div>

  {{-- Table footer --}}
  <div style="padding:12px 20px;border-top:1px solid var(--border);background:#f8fafc;
              display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px">
    <div style="font-size:12px;color:var(--muted)">
      <span id="selected-footer-count">0</span> invoice dipilih
    </div>
    <div style="display:flex;gap:8px">
      <button onclick="openBulkModal('email')"
        style="padding:6px 14px;background:var(--navy);color:#fff;border:none;border-radius:8px;
               font-size:11px;font-weight:700;cursor:pointer">
        📧 Kirim Email Terpilih
      </button>
      <button onclick="openBulkModal('whatsapp')"
        style="padding:6px 14px;background:#16a34a;color:#fff;border:none;border-radius:8px;
               font-size:11px;font-weight:700;cursor:pointer">
        💬 WA Terpilih
      </button>
    </div>
  </div>

</div>
@endif

<style>
@keyframes slideIn {
  from { opacity:0; transform:translateX(20px); }
  to   { opacity:1; transform:translateX(0); }
}
@keyframes fadeIn {
  from { opacity:0; transform:translateY(-8px); }
  to   { opacity:1; transform:translateY(0); }
}
#toast { animation: slideIn .3s ease; }
</style>

@endsection

@push('scripts')
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;

/* ───────────────────────────────────────────────
   Toast helper
─────────────────────────────────────────────── */
function showToast(msg, type = 'success') {
  const t = document.getElementById('toast');
  const colors = {
    success : { bg:'#dcfce7', border:'#86efac', color:'#166534', icon:'✅' },
    error   : { bg:'#fee2e2', border:'#fca5a5', color:'#991b1b', icon:'⚠️' },
    info    : { bg:'#dbeafe', border:'#93c5fd', color:'#1e40af', icon:'ℹ️' },
    warning : { bg:'#fff7ed', border:'#fed7aa', color:'#9a3412', icon:'🔔' },
  };
  const c = colors[type] || colors.info;
  t.style.cssText = `
    display:flex; position:fixed; top:20px; right:20px; z-index:500;
    min-width:280px; max-width:420px; padding:14px 18px; border-radius:12px;
    box-shadow:0 8px 30px rgba(0,0,0,.18); font-size:13px; font-weight:600;
    align-items:center; gap:10px; animation:slideIn .3s ease;
    background:${c.bg}; border:1px solid ${c.border}; color:${c.color};
  `;
  t.innerHTML = `<span style="font-size:16px">${c.icon}</span><span>${msg}</span>
    <button onclick="this.parentElement.style.display='none'"
      style="margin-left:auto;background:none;border:none;cursor:pointer;
             font-size:18px;color:${c.color};opacity:.6">×</button>`;
  clearTimeout(window._toastTimer);
  window._toastTimer = setTimeout(() => t.style.display = 'none', 5000);
}

/* ───────────────────────────────────────────────
   Row checkbox sync
─────────────────────────────────────────────── */
function updateSelectedCount() {
  const count = document.querySelectorAll('.row-check:checked').length;
  const el    = document.getElementById('selected-footer-count');
  if (el) el.textContent = count;
}

document.addEventListener('change', e => {
  if (e.target.classList.contains('row-check')) updateSelectedCount();
});

function tableSelectAll(master) {
  document.querySelectorAll('.row-check').forEach(cb => cb.checked = master.checked);
  updateSelectedCount();
}

/* ───────────────────────────────────────────────
   Single Email
─────────────────────────────────────────────── */
async function sendSingleEmail(invoiceId, customerName) {
  const btn = document.getElementById('email-btn-' + invoiceId);
  if (btn) { btn.disabled = true; btn.textContent = '…'; }

  try {
    const res  = await fetch(`/reminder/email/${invoiceId}`, {
      method : 'POST',
      headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
    });
    const data = await res.json();
    if (data.success) {
      showToast(`Email berhasil dikirim ke ${customerName}!`, 'success');
      if (btn) { btn.textContent = '✓ Terkirim'; btn.style.background = '#dcfce7'; btn.style.borderColor = '#16a34a'; btn.style.color = '#15803d'; }
    } else {
      showToast(data.message || 'Gagal mengirim email.', 'error');
      if (btn) { btn.disabled = false; btn.innerHTML = '<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg> Email'; }
    }
  } catch (err) {
    showToast('Terjadi kesalahan jaringan.', 'error');
    if (btn) { btn.disabled = false; btn.textContent = 'Email'; }
  }
}

/* ───────────────────────────────────────────────
   Single WhatsApp
─────────────────────────────────────────────── */
async function sendSingleWA(invoiceId, customerName) {
  const btn = document.getElementById('wa-btn-' + invoiceId);
  if (btn) { btn.disabled = true; btn.textContent = '…'; }

  try {
    const res  = await fetch(`/reminder/whatsapp/${invoiceId}`, {
      method : 'POST',
      headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
    });
    const data = await res.json();
    if (data.success) {
      // Show preview modal
      document.getElementById('wa-modal-customer').textContent = customerName;
      // Decode URL to show preview
      const msg = decodeURIComponent(data.url.split('?text=')[1] || '');
      document.getElementById('wa-modal-preview').textContent = msg;
      document.getElementById('wa-open-link').href = data.url;
      document.getElementById('wa-modal-overlay').style.display = 'flex';
      if (btn) { btn.disabled = false; btn.innerHTML = '<svg width="11" height="11" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413z"/><path d="M11.999 0C5.373 0 0 5.373 0 12c0 2.126.558 4.117 1.535 5.845L0 24l6.294-1.508A11.954 11.954 0 0 0 12 24c6.627 0 12-5.373 12-12S18.627 0 11.999 0zm0 21.818a9.818 9.818 0 0 1-5.007-1.374l-.36-.213-3.737.894.937-3.636-.235-.374A9.822 9.822 0 0 1 2.182 12c0-5.414 4.403-9.818 9.818-9.818 5.414 0 9.818 4.404 9.818 9.818 0 5.415-4.404 9.818-9.819 9.818z"/></svg> WA'; }
    } else {
      showToast(data.message || 'Gagal mengambil link WA.', 'error');
      if (btn) { btn.disabled = false; btn.textContent = 'WA'; }
    }
  } catch (err) {
    showToast('Terjadi kesalahan jaringan.', 'error');
    if (btn) { btn.disabled = false; btn.textContent = 'WA'; }
  }
}

function closeWaModal() {
  document.getElementById('wa-modal-overlay').style.display = 'none';
}

/* ───────────────────────────────────────────────
   Bulk Modal
─────────────────────────────────────────────── */
let currentBulkType = 'email';

function openBulkModal(type) {
  currentBulkType = type;
  const isEmail = type === 'email';

  document.getElementById('bulk-modal-title').textContent = isEmail ? 'Kirim Email Massal' : 'WhatsApp Massal';
  document.getElementById('bulk-modal-sub').textContent   = isEmail
    ? 'Pilih invoice yang akan dikirim reminder email'
    : 'Pilih invoice untuk generate WhatsApp link';
  document.getElementById('bulk-modal-icon').textContent  = isEmail ? '📧' : '💬';
  document.getElementById('bulk-modal-icon').style.background = isEmail ? '#dbeafe' : '#dcfce7';

  const sendBtn = document.getElementById('bulk-send-btn');
  sendBtn.style.background = isEmail ? 'var(--navy)' : '#16a34a';
  document.getElementById('bulk-send-label').textContent = isEmail ? 'Kirim Email' : 'Generate WA Links';

  document.getElementById('bulk-wa-results').style.display = 'none';
  document.getElementById('bulk-modal-feedback').style.display = 'none';

  // Build invoice list
  const rows = document.querySelectorAll('#reminder-table tbody tr');
  let html   = '';
  rows.forEach(row => {
    const id        = row.dataset.invoiceId;
    const customer  = row.dataset.customer;
    const urgency   = row.dataset.urgency;
    const hasEmail  = row.dataset.hasEmail === '1';
    const hasWA     = row.dataset.hasWa    === '1';
    const canSend   = isEmail ? hasEmail : hasWA;
    const urgColors = {
      'urgency-overdue'  : ['#fee2e2','#991b1b'],
      'urgency-critical' : ['#ffe4e6','#881337'],
      'urgency-urgent'   : ['#ffedd5','#9a3412'],
      'urgency-soon'     : ['#fef3c7','#92400e'],
      'urgency-upcoming' : ['#dcfce7','#166534'],
    };
    const [bg, color] = urgColors[urgency] || ['#f1f5f9','#475569'];

    const preChecked = document.querySelector(`.row-check[value="${id}"]`)?.checked;

    html += `
      <div style="display:flex;align-items:center;gap:12px;padding:10px 16px;
                  border-bottom:1px solid var(--border);${!canSend?'opacity:.45':''}">
        <input type="checkbox" class="bulk-check" value="${id}"
               ${preChecked ? 'checked' : ''} ${!canSend ? 'disabled' : ''}
               onchange="updateBulkCount()"
               style="width:14px;height:14px;accent-color:var(--navy)">
        <div style="flex:1;min-width:0">
          <div style="font-size:12px;font-weight:700;color:var(--navy);
                      white-space:nowrap;overflow:hidden;text-overflow:ellipsis">#${id} · ${customer}</div>
          <div style="font-size:10px;color:var(--muted);margin-top:1px">
            ${isEmail
              ? (hasEmail ? '✉️ Email tersedia' : '✗ Tidak ada email')
              : (hasWA    ? '💬 WA tersedia'    : '✗ Tidak ada nomor WA')}
          </div>
        </div>
        <span style="font-size:9px;font-weight:700;padding:2px 8px;border-radius:99px;
                     background:${bg};color:${color}">${urgency.replace('urgency-','').toUpperCase()}</span>
      </div>`;
  });

  document.getElementById('bulk-invoice-list').innerHTML = html || '<div style="padding:24px;text-align:center;color:var(--muted);font-size:13px">Tidak ada invoice.</div>';
  updateBulkCount();

  const overlay = document.getElementById('bulk-modal-overlay');
  const box     = document.getElementById('bulk-modal-box');
  overlay.style.display = 'flex';
  box.style.opacity    = '0';
  box.style.transform  = 'translateY(-14px)';
  box.style.transition = 'opacity .2s ease, transform .2s ease';
  requestAnimationFrame(() => { box.style.opacity = '1'; box.style.transform = 'translateY(0)'; });
}

function closeBulkModal() {
  document.getElementById('bulk-modal-overlay').style.display = 'none';
}

function updateBulkCount() {
  const count  = document.querySelectorAll('.bulk-check:checked').length;
  const el     = document.getElementById('bulk-selected-count');
  const allEl  = document.getElementById('bulk-select-all');
  const total  = document.querySelectorAll('.bulk-check:not(:disabled)').length;
  if (el)    el.textContent = `${count} dipilih`;
  if (allEl) allEl.checked  = count > 0 && count === total;
}

function toggleSelectAll(master) {
  document.querySelectorAll('.bulk-check:not(:disabled)').forEach(cb => cb.checked = master.checked);
  updateBulkCount();
}

function selectByUrgency(level) {
  document.querySelectorAll('.bulk-check:not(:disabled)').forEach(cb => cb.checked = false);
  const urgencyMap = { urgent: ['urgency-urgent','urgency-critical'], overdue: ['urgency-overdue'] };
  const targets    = urgencyMap[level] || [];
  document.querySelectorAll('#reminder-table tbody tr').forEach(row => {
    if (targets.includes(row.dataset.urgency)) {
      const id  = row.dataset.invoiceId;
      const cb  = document.querySelector(`.bulk-check[value="${id}"]`);
      if (cb && !cb.disabled) cb.checked = true;
    }
  });
  updateBulkCount();
}

async function executeBulkSend() {
  const ids  = Array.from(document.querySelectorAll('.bulk-check:checked')).map(cb => cb.value);
  const fb   = document.getElementById('bulk-modal-feedback');
  const btn  = document.getElementById('bulk-send-btn');
  const lbl  = document.getElementById('bulk-send-label');

  if (!ids.length) { showToast('Pilih setidaknya satu invoice.', 'warning'); return; }

  btn.disabled    = true;
  lbl.textContent = 'Memproses…';
  fb.style.display = 'none';

  try {
    const res  = await fetch('/reminder/bulk', {
      method : 'POST',
      headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
      body   : JSON.stringify({ type: currentBulkType, invoice_ids: ids }),
    });
    const data = await res.json();

    if (data.success) {
      if (currentBulkType === 'whatsapp' && data.wa_links && data.wa_links.length > 0) {
        // Show WA links
        document.getElementById('bulk-wa-results').style.display = 'block';
        document.getElementById('bulk-wa-links').innerHTML = data.wa_links.map(l =>
          `<a href="${l.url}" target="_blank"
              style="display:flex;align-items:center;gap:8px;padding:8px 12px;
                     margin-bottom:6px;background:#dcfce7;border:1px solid #86efac;
                     border-radius:8px;text-decoration:none;color:#15803d;
                     font-size:12px;font-weight:600;transition:all .15s"
              onmouseover="this.style.background='#bbf7d0'"
              onmouseout="this.style.background='#dcfce7'">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413z"/><path d="M11.999 0C5.373 0 0 5.373 0 12c0 2.126.558 4.117 1.535 5.845L0 24l6.294-1.508A11.954 11.954 0 0 0 12 24c6.627 0 12-5.373 12-12S18.627 0 11.999 0zm0 21.818a9.818 9.818 0 0 1-5.007-1.374l-.36-.213-3.737.894.937-3.636-.235-.374A9.822 9.822 0 0 1 2.182 12c0-5.414 4.403-9.818 9.818-9.818 5.414 0 9.818 4.404 9.818 9.818 0 5.415-4.404 9.818-9.819 9.818z"/></svg>
            ${l.customer} — Buka WhatsApp
          </a>`
        ).join('');
        showToast(`${data.sent} WA link berhasil dibuat.`, 'success');
      } else {
        fb.textContent    = `✅ ${data.sent} email terkirim · ${data.skipped} dilewati (tidak ada email)`;
        fb.style.cssText  = 'display:block;background:#dcfce7;border:1px solid #86efac;color:#166534;padding:12px 16px;border-radius:9px;font-size:13px;font-weight:600;margin-top:14px';
        showToast(`${data.sent} email reminder berhasil dikirim!`, 'success');
        setTimeout(() => closeBulkModal(), 3000);
      }
    } else {
      fb.textContent   = '⚠️ ' + (data.message || 'Gagal mengirim.');
      fb.style.cssText = 'display:block;background:#fee2e2;border:1px solid #fca5a5;color:#991b1b;padding:12px 16px;border-radius:9px;font-size:13px;font-weight:600;margin-top:14px';
    }
  } catch (err) {
    fb.textContent   = '⚠️ Terjadi kesalahan jaringan.';
    fb.style.cssText = 'display:block;background:#fee2e2;border:1px solid #fca5a5;color:#991b1b;padding:12px 16px;border-radius:9px;font-size:13px;font-weight:600;margin-top:14px';
  } finally {
    btn.disabled    = false;
    lbl.textContent = currentBulkType === 'email' ? 'Kirim Email' : 'Generate WA Links';
  }
}

document.addEventListener('keydown', e => { if (e.key === 'Escape') { closeBulkModal(); closeWaModal(); } });
</script>
@endpush