@extends('layouts.app')
@section('title', 'Reminder AR')
@section('page-title', 'Reminder AR')
{{-- Override period selector — not needed on this page --}}
@php
    $periods = collect();

    // Group invoices by customer code
    $grouped = $invoices->groupBy('customer_code')->map(function($invs) {
        $first = $invs->first();
        // Determine worst urgency among all invoices for this customer
        $urgencyOrder = ['urgency-overdue' => 0, 'urgency-critical' => 1, 'urgency-urgent' => 2, 'urgency-soon' => 3, 'urgency-upcoming' => 4];
        $worstInv = $invs->sortBy(fn($i) => $urgencyOrder[$i->urgency_class] ?? 99)->first();
        return (object)[
            'customer_code'    => $first->customer_code,
            'customer_name'    => $first->customer_name,
            'pic_name'         => $first->pic_name,
            'email'            => $first->email,
            'whatsapp_number'  => $first->whatsapp_number,
            'plant'            => $first->plant,
            'urgency_class'    => $worstInv->urgency_class,
            'urgency_label'    => $worstInv->urgency_label,
            'is_overdue'       => $invs->contains('is_overdue', true),
            'is_urgent'        => $invs->contains('is_urgent', true),
            'total_ar'         => $invs->sum('total_ar'),
            'ar_actual'        => $invs->sum('ar_actual'),
            'invoice_count'    => $invs->count(),
            'invoices'         => $invs->values(),
            // For email/WA sending: collect all invoice IDs
            'invoice_ids'      => $invs->pluck('invoice_id')->toArray(),
            'has_email'        => !empty($first->email),
            'has_wa'           => !empty($first->whatsapp_number),
        ];
    })->values();
@endphp
@section('topbar-actions')
<div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
  {{-- Status filter --}}
  <div style="display:flex;background:#f1f5f9;border:1px solid var(--border);border-radius:9px;padding:3px;gap:2px">
    @foreach(['all' => 'All', 'unpaid' => 'Unpaid', 'overdue' => 'Upcoming'] as $val => $lbl)
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
  {{-- Legend --}}
  @foreach([['#dc2626','Overdue'],['#16a34a','Upcoming']] as [$color, $lbl])
  <div style="display:flex;align-items:center;gap:4px">
    <div style="width:8px;height:8px;border-radius:2px;background:{{ $color }}"></div>
    <span style="font-size:10px;color:var(--muted)">{{ $lbl }}</span>
  </div>
  @endforeach
  {{-- Bulk Email button --}}
  <button onclick="openBulkModal('email')"
    style="display:flex;align-items:center;gap:6px;padding:7px 14px;background:var(--navy);color:#fff;
           border:none;border-radius:9px;font-size:12px;font-weight:700;cursor:pointer;transition:all .15s"
    onmouseover="this.style.background='#0d1f3c'" onmouseout="this.style.background='var(--navy)'">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
    Send Selected Email
  </button>
  {{-- Bulk WhatsApp button --}}
  <button onclick="openBulkModal('whatsapp')"
    style="display:flex;align-items:center;gap:6px;padding:7px 14px;background:#16a34a;color:#fff;
           border:none;border-radius:9px;font-size:12px;font-weight:700;cursor:pointer;transition:all .15s"
    onmouseover="this.style.background='#15803d'" onmouseout="this.style.background='#16a34a'">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M11.999 0C5.373 0 0 5.373 0 12c0 2.126.558 4.117 1.535 5.845L0 24l6.294-1.508A11.954 11.954 0 0 0 12 24c6.627 0 12-5.373 12-12S18.627 0 11.999 0zm0 21.818a9.818 9.818 0 0 1-5.007-1.374l-.36-.213-3.737.894.937-3.636-.235-.374A9.822 9.822 0 0 1 2.182 12c0-5.414 4.403-9.818 9.818-9.818 5.414 0 9.818 4.404 9.818 9.818 0 5.415-4.404 9.818-9.819 9.818z"/></svg>
    Send Selected WA
  </button>
</div>
@endsection

@section('content')

{{-- ════════ TOAST ════════ --}}
<div id="toast" style="display:none;position:fixed;top:20px;right:20px;z-index:600;
     min-width:300px;max-width:420px;padding:14px 18px;border-radius:12px;
     box-shadow:0 8px 30px rgba(0,0,0,.18);font-size:13px;font-weight:600;
     align-items:center;gap:10px;animation:slideIn .3s ease">
</div>

{{-- ════════ CUSTOMER DETAIL MODAL ════════ --}}
<div id="cust-reminder-overlay"
     onclick="if(event.target===this) closeCustReminderModal()"
     style="display:none;position:fixed;inset:0;background:rgba(15,31,54,.5);z-index:400;
            align-items:center;justify-content:center;backdrop-filter:blur(3px)">
  <div id="cust-reminder-box"
       style="background:#fff;border:1px solid var(--border);border-radius:16px;padding:28px;
              width:920px;max-width:96vw;max-height:90vh;overflow-y:auto;
              box-shadow:0 20px 60px rgba(15,31,54,.25)">

    {{-- Header --}}
    <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:20px;gap:12px">
      <div style="display:flex;align-items:center;gap:12px">
        <div style="width:44px;height:44px;border-radius:10px;background:#dbeafe;
                    display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:20px">👥</div>
        <div>
          <div id="crd-name" style="font-size:18px;font-weight:800;color:var(--navy);letter-spacing:-.3px"></div>
          <div id="crd-id"   style="font-size:12px;color:var(--muted);margin-top:2px"></div>
        </div>
      </div>
      <button onclick="closeCustReminderModal()"
              style="background:none;border:1px solid var(--border);border-radius:8px;
                     width:32px;height:32px;cursor:pointer;font-size:18px;color:var(--muted);
                     display:flex;align-items:center;justify-content:center;flex-shrink:0">×</button>
    </div>

    {{-- Info grid --}}
    <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:12px;margin-bottom:22px"
         id="crd-info-grid"></div>

    {{-- Plants row + bulk action buttons --}}
    <div style="margin-bottom:22px">
      <div style="font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;
                  letter-spacing:.08em;margin-bottom:8px">Plants</div>
      <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
        <div id="crd-plants" style="display:flex;gap:6px;flex-wrap:wrap"></div>
        {{-- Bulk send buttons for this customer --}}
        <button id="crd-email-btn" onclick="sendCustomerBulkEmail()"
          style="display:inline-flex;align-items:center;gap:5px;padding:6px 14px;
                 background:var(--navy);color:#fff;border:none;border-radius:8px;
                 font-size:11px;font-weight:700;cursor:pointer;transition:all .15s;white-space:nowrap"
          onmouseover="this.style.background='#0d1f3c'" onmouseout="this.style.background='var(--navy)'">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
          Send Selected Email
        </button>
        <button id="crd-wa-btn" onclick="sendCustomerBulkWA()"
          style="display:inline-flex;align-items:center;gap:5px;padding:6px 14px;
                 background:#16a34a;color:#fff;border:none;border-radius:8px;
                 font-size:11px;font-weight:700;cursor:pointer;transition:all .15s;white-space:nowrap"
          onmouseover="this.style.background='#15803d'" onmouseout="this.style.background='#16a34a'">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413z"/><path d="M11.999 0C5.373 0 0 5.373 0 12c0 2.126.558 4.117 1.535 5.845L0 24l6.294-1.508A11.954 11.954 0 0 0 12 24c6.627 0 12-5.373 12-12S18.627 0 11.999 0zm0 21.818a9.818 9.818 0 0 1-5.007-1.374l-.36-.213-3.737.894.937-3.636-.235-.374A9.822 9.822 0 0 1 2.182 12c0-5.414 4.403-9.818 9.818-9.818 5.414 0 9.818 4.404 9.818 9.818 0 5.415-4.404 9.818-9.819 9.818z"/></svg>
          Send Selected WA
        </button>
      </div>
    </div>

    {{-- Invoice table --}}
    <div style="border:1px solid var(--border);border-radius:10px;overflow:hidden">
      <div style="padding:14px 16px;border-bottom:1px solid var(--border);background:#f8fafc;
                  display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap">
        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
          <div style="font-size:12px;font-weight:700">Invoice / AR Records
            <span id="crd-inv-count" style="margin-left:6px;background:#dbeafe;color:#1e40af;
                  font-size:10px;padding:1px 7px;border-radius:99px;font-weight:700"></span>
          </div>
          {{-- Search bar --}}
          <div style="position:relative">
            <svg style="position:absolute;left:8px;top:50%;transform:translateY(-50%);pointer-events:none;color:#94a3b8"
                 width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
              <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
            </svg>
            <input type="text" id="crd-inv-search" placeholder="Search Invoice ID…"
                   oninput="crdFilterInvoices(this.value)"
                   style="padding:5px 10px 5px 26px;border:1px solid var(--border);border-radius:7px;
                          font-size:11px;font-family:inherit;outline:none;width:160px;
                          transition:border-color .15s;background:#fff"
                   onfocus="this.style.borderColor='var(--navy)'"
                   onblur="this.style.borderColor='var(--border)'">
          </div>
        </div>
        <label style="display:flex;align-items:center;gap:6px;font-size:11px;font-weight:600;
                      color:var(--muted);cursor:pointer">
          <input type="checkbox" id="crd-select-all" onchange="crdToggleAll(this)"
                 style="width:13px;height:13px;accent-color:var(--navy)">
          Select All
        </label>
      </div>
      <div style="overflow-x:auto;max-height:380px;overflow-y:auto">
        <table class="data-table">
          <thead>
            <tr>
              <th style="width:36px;padding:10px 12px"></th>
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
              <th style="text-align:center;min-width:130px">Actions</th>
            </tr>
          </thead>
          <tbody id="crd-inv-tbody"></tbody>
        </table>
      </div>
    </div>

    <div style="display:flex;justify-content:flex-end;margin-top:18px">
      <button onclick="closeCustReminderModal()" class="btn btn-ghost">Close</button>
    </div>
  </div>
</div>
{{-- ════════ END CUSTOMER DETAIL MODAL ════════ --}}

{{-- ════════ BULK MODAL ════════ --}}
<div id="bulk-modal-overlay" onclick="if(event.target===this) closeBulkModal()"
     style="display:none;position:fixed;inset:0;background:rgba(15,31,54,.5);z-index:300;
            align-items:center;justify-content:center;backdrop-filter:blur(4px)">
  <div id="bulk-modal-box"
       style="background:#fff;border:1px solid var(--border);border-radius:16px;padding:28px;
              width:580px;max-width:96vw;max-height:90vh;overflow-y:auto;
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

    {{-- Toolbar: select-all + search + urgency filters --}}
    <div style="background:#f8fafc;border:1px solid var(--border);border-radius:10px;
                padding:12px 16px;margin-bottom:12px">
      <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;margin-bottom:10px">
        <div style="display:flex;align-items:center;gap:10px">
          <label style="display:flex;align-items:center;gap:6px;font-size:12px;font-weight:600;cursor:pointer">
            <input type="checkbox" id="bulk-select-all" onchange="toggleSelectAll(this)"
                   style="width:14px;height:14px;accent-color:var(--navy)">
            Select All
          </label>
          <span id="bulk-selected-count" style="font-size:11px;color:var(--muted)">0 selected</span>
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
      {{-- Search bar inside bulk modal --}}
      <div style="position:relative">
        <svg style="position:absolute;left:10px;top:50%;transform:translateY(-50%);pointer-events:none;color:#94a3b8"
             width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
        </svg>
        <input type="text" id="bulk-customer-search"
               placeholder="Search customer name…"
               oninput="filterBulkCustomers(this.value)"
               style="width:100%;padding:7px 12px 7px 32px;border:1px solid var(--border);
                      border-radius:8px;font-size:12px;font-family:inherit;outline:none;
                      background:#fff;transition:border-color .15s"
               onfocus="this.style.borderColor='var(--navy)'"
               onblur="this.style.borderColor='var(--border)'">
      </div>
    </div>

    <div id="bulk-invoice-list"
         style="max-height:300px;overflow-y:auto;overflow-x:hidden;
                border:1px solid var(--border);border-radius:10px;margin-bottom:12px;"></div>

    {{-- No results message --}}
    <div id="bulk-no-results"
         style="display:none;padding:20px;text-align:center;color:var(--muted);font-size:13px;
                border:1px solid var(--border);border-radius:10px;margin-bottom:12px">
      No customers match your search.
    </div>

    <div id="bulk-modal-feedback" style="display:none;margin-top:12px;padding:12px 16px;
         border-radius:9px;font-size:13px;font-weight:600"></div>
    <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:16px">
      <button onclick="closeBulkModal()" class="btn btn-ghost">Cancel</button>
      <button id="bulk-send-btn" onclick="executeBulkSend()"
        style="padding:9px 20px;border-radius:9px;font-size:12px;font-weight:700;
               cursor:pointer;border:none;color:#fff;transition:all .15s">
        <span id="bulk-send-label">Send Now</span>
      </button>
    </div>
  </div>
</div>

{{-- ════════ WA PREVIEW MODAL ════════ --}}
<div id="wa-modal-overlay" onclick="if(event.target===this) closeWaModal()"
     style="display:none;position:fixed;inset:0;background:rgba(15,31,54,.5);z-index:500;
            align-items:center;justify-content:center;backdrop-filter:blur(4px)">
  <div style="background:#fff;border-radius:16px;padding:28px;width:480px;max-width:96vw;max-height:90vh;
              display:flex;flex-direction:column;
              box-shadow:0 20px 60px rgba(15,31,54,.25)">

    {{-- Header --}}
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:18px;flex-shrink:0">
      <div style="width:38px;height:38px;background:#dcfce7;border-radius:10px;
                  display:flex;align-items:center;justify-content:center;font-size:20px">💬</div>
      <div style="flex:1;min-width:0">
        <div style="font-size:15px;font-weight:800;color:var(--navy)">Send WhatsApp</div>
        <div id="wa-modal-customer" style="font-size:11px;color:var(--muted);margin-top:2px;
             white-space:nowrap;overflow:hidden;text-overflow:ellipsis"></div>
      </div>
      <button onclick="closeWaModal()"
        style="flex-shrink:0;background:none;border:1px solid var(--border);
               border-radius:8px;width:30px;height:30px;cursor:pointer;
               font-size:16px;color:var(--muted);display:flex;align-items:center;justify-content:center">×</button>
    </div>

    {{-- Invoice summary badges (shown for multi-invoice) --}}
    <div id="wa-modal-summary" style="display:none;margin-bottom:14px;flex-shrink:0">
      <div style="font-size:10px;font-weight:700;color:var(--muted);text-transform:uppercase;
                  letter-spacing:.08em;margin-bottom:8px">Selected Invoices</div>
      <div id="wa-modal-invoice-badges" style="display:flex;gap:6px;flex-wrap:wrap"></div>
      <div id="wa-modal-total-bar" style="margin-top:10px;padding:10px 14px;background:#f0fdf4;
           border:1px solid #bbf7d0;border-radius:8px;display:flex;align-items:center;
           justify-content:space-between;gap:10px">
        <span style="font-size:11px;font-weight:700;color:#15803d">Total Outstanding</span>
        <span id="wa-modal-grand-total" style="font-size:14px;font-weight:800;color:#15803d;
              font-family:'DM Mono',monospace"></span>
      </div>
    </div>

    {{-- Message preview --}}
    <div style="background:#075e54;border-radius:12px;padding:18px;margin-bottom:16px;flex:1;overflow-y:auto;min-height:0">
      <div style="font-size:11px;color:rgba(255,255,255,.6);margin-bottom:10px;font-weight:600">
        Message Preview
      </div>
      <div id="wa-modal-preview"
           style="font-size:12px;color:#fff;line-height:1.7;white-space:pre-wrap;word-break:break-word"></div>
    </div>

    {{-- Actions --}}
    <div style="display:flex;gap:10px;justify-content:flex-end;flex-shrink:0">
      <button onclick="closeWaModal()" class="btn btn-ghost" style="font-size:12px">Cancel</button>
      <a id="wa-open-link" href="#" target="_blank" onclick="closeWaModal()"
        style="padding:9px 18px;background:#16a34a;color:#fff;border-radius:9px;
               font-size:12px;font-weight:700;text-decoration:none;
               display:inline-flex;align-items:center;gap:6px">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor">
          <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413z"/>
          <path d="M11.999 0C5.373 0 0 5.373 0 12c0 2.126.558 4.117 1.535 5.845L0 24l6.294-1.508A11.954 11.954 0 0 0 12 24c6.627 0 12-5.373 12-12S18.627 0 11.999 0zm0 21.818a9.818 9.818 0 0 1-5.007-1.374l-.36-.213-3.737.894.937-3.636-.235-.374A9.822 9.822 0 0 1 2.182 12c0-5.414 4.403-9.818 9.818-9.818 5.414 0 9.818 4.404 9.818 9.818 0 5.415-4.404 9.818-9.819 9.818z"/>
        </svg>
        Open WhatsApp
      </a>
    </div>
  </div>
</div>

{{-- ════════ KPI CARDS ════════ --}}
<div class="grid-kpi-4" style="margin-bottom:20px">
  <div class="kpi-card card-accent-blue">
    <div class="kpi-label">Total Customers</div>
    <div class="kpi-value mono">{{ $grouped->count() }}</div>
    <div class="kpi-sub">{{ $invoices->count() }} active invoices</div>
  </div>
  <div class="kpi-card card-accent-yellow">
    <div class="kpi-label">Total Outstanding</div>
    <div class="kpi-value mono" style="font-size:18px">
      Rp {{ number_format($totalDue / 1e9, 2) }}B
    </div>
    <div class="kpi-sub">across all active invoices</div>
  </div>
  <div class="kpi-card" style="--accent-color:#f97316">
    <div class="kpi-label">Urgent / Overdue</div>
    <div class="kpi-value" style="color:#f97316">{{ $urgentCount }}</div>
    <div class="kpi-sub">require immediate action</div>
  </div>
  <div class="kpi-card card-accent-green">
    <div class="kpi-label">Collector</div>
    <div class="kpi-value" style="font-size:16px;font-weight:800">{{ $collectorName }}</div>
    <div class="kpi-sub">Simulated: 1 Dec 2024</div>
  </div>
</div>

{{-- ════════ CUSTOMER TABLE ════════ --}}
@if($grouped->isEmpty())
<div style="background:var(--surface);border:1px solid var(--border);border-radius:12px;
            padding:64px 24px;text-align:center;box-shadow:var(--shadow)">
  <div style="font-size:48px;margin-bottom:16px">📋</div>
  <div style="font-size:16px;font-weight:700;color:var(--navy);margin-bottom:6px">No customers found</div>
  <div style="font-size:13px;color:var(--muted)">No invoices available for this collector.</div>
</div>
@else
<div style="background:var(--surface);border:1px solid var(--border);border-radius:12px;
            overflow:hidden;box-shadow:var(--shadow)">
  <div style="padding:16px 20px;border-bottom:1px solid var(--border);
              display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px">
    <div>
      <div style="font-size:13px;font-weight:700;color:var(--navy)">Customer List</div>
      <div style="font-size:11px;color:var(--muted);margin-top:2px">
        {{ $grouped->count() }} customers · Click name for invoice detail · Simulated today: 1 December 2024
      </div>
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
          <th>Customer</th>
          <th>Contact</th>
          <th>Plant</th>
          <th>Invoices</th>
          <th>Worst Status</th>
          <th class="num">Total AR</th>
          <th class="num">Paid</th>
          <th class="num">Remaining</th>
          <th style="text-align:center;min-width:130px">Actions</th>
        </tr>
      </thead>
      <tbody>
        @foreach($grouped as $cust)
        @php
          $urgencyColors = [
            'urgency-overdue'  => ['bg' => '#fff5f5', 'dot' => '#dc2626', 'badge_bg' => '#fee2e2', 'badge_color' => '#991b1b'],
            'urgency-critical' => ['bg' => '#fff1f2', 'dot' => '#e11d48', 'badge_bg' => '#ffe4e6', 'badge_color' => '#881337'],
            'urgency-urgent'   => ['bg' => '#fff7ed', 'dot' => '#f97316', 'badge_bg' => '#ffedd5', 'badge_color' => '#9a3412'],
            'urgency-soon'     => ['bg' => '#fefce8', 'dot' => '#ca8a04', 'badge_bg' => '#fef9c3', 'badge_color' => '#92400e'],
            'urgency-upcoming' => ['bg' => '#f0fdf4', 'dot' => '#16a34a', 'badge_bg' => '#dcfce7', 'badge_color' => '#166534'],
          ];
          $uc   = $urgencyColors[$cust->urgency_class] ?? $urgencyColors['urgency-upcoming'];
          $sisa = max(0, $cust->total_ar - $cust->ar_actual);
          $hasEmail = $cust->has_email;
          $hasWA    = $cust->has_wa;
        @endphp
        <tr style="background:{{ $uc['bg'] }}"
            data-customer-code="{{ $cust->customer_code }}"
            data-urgency="{{ $cust->urgency_class }}"
            data-has-email="{{ $hasEmail ? '1' : '0' }}"
            data-has-wa="{{ $hasWA ? '1' : '0' }}"
            data-invoice-ids="{{ implode(',', $cust->invoice_ids) }}">

          {{-- Checkbox --}}
          <td style="padding:10px 12px">
            <input type="checkbox" class="row-check"
                   value="{{ $cust->customer_code }}"
                   style="width:14px;height:14px;accent-color:var(--navy)">
          </td>

          {{-- Customer name (clickable) --}}
          <td>
            <div style="display:flex;align-items:center;gap:8px">
              <div style="width:8px;height:8px;border-radius:2px;background:{{ $uc['dot'] }};flex-shrink:0"></div>
              <div>
                <button onclick="openCustReminderModal('{{ addslashes($cust->customer_code) }}')"
                  style="background:none;border:none;cursor:pointer;font-weight:700;color:var(--navy);
                         font-size:13px;font-family:inherit;text-align:left;padding:0;
                         text-decoration:underline;text-underline-offset:3px;
                         text-decoration-color:rgba(27,58,107,.35);max-width:200px;
                         white-space:nowrap;overflow:hidden;text-overflow:ellipsis;display:block"
                  onmouseover="this.style.color='#1e88e5'" onmouseout="this.style.color='var(--navy)'"
                  title="{{ $cust->customer_name }}">
                  {{ $cust->customer_name }}
                </button>
                @if($cust->pic_name)
                <div style="font-size:10px;color:var(--muted);margin-top:1px">{{ $cust->pic_name }}</div>
                @endif
              </div>
            </div>
          </td>

          {{-- Contact --}}
          <td>
            <div style="display:flex;flex-direction:column;gap:3px">
              @if($hasEmail)
              <div style="display:flex;align-items:center;gap:4px;font-size:11px;color:#1e40af">
                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                <span style="max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $cust->email }}</span>
              </div>
              @else
              <div style="font-size:10px;color:#94a3b8;font-style:italic">No email</div>
              @endif
              @if($hasWA)
              <div style="display:flex;align-items:center;gap:4px;font-size:11px;color:#16a34a">
                <svg width="10" height="10" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413z"/><path d="M11.999 0C5.373 0 0 5.373 0 12c0 2.126.558 4.117 1.535 5.845L0 24l6.294-1.508A11.954 11.954 0 0 0 12 24c6.627 0 12-5.373 12-12S18.627 0 11.999 0zm0 21.818a9.818 9.818 0 0 1-5.007-1.374l-.36-.213-3.737.894.937-3.636-.235-.374A9.822 9.822 0 0 1 2.182 12c0-5.414 4.403-9.818 9.818-9.818 5.414 0 9.818 4.404 9.818 9.818 0 5.415-4.404 9.818-9.819 9.818z"/></svg>
                +{{ $cust->whatsapp_number }}
              </div>
              @else
              <div style="font-size:10px;color:#94a3b8;font-style:italic">No WhatsApp</div>
              @endif
            </div>
          </td>

          {{-- Plant --}}
          <td><span class="badge badge-blue">Plant {{ $cust->plant }}</span></td>

          {{-- Invoice count --}}
          <td style="text-align:center">
            <span style="background:#dbeafe;color:#1e40af;font-size:11px;font-weight:700;
                         padding:2px 9px;border-radius:99px">{{ $cust->invoice_count }}</span>
          </td>

          {{-- Worst urgency --}}
          <td>
            <span style="display:inline-block;padding:3px 9px;border-radius:99px;font-size:10px;font-weight:700;
                         background:{{ $uc['badge_bg'] }};color:{{ $uc['badge_color'] }}">
              {{ $cust->urgency_label }}
            </span>
          </td>

          {{-- Total AR --}}
          <td class="num" style="font-weight:700;font-size:12px;white-space:nowrap">
            {{ $cust->total_ar > 0 ? 'Rp '.number_format($cust->total_ar, 0, ',', '.') : '—' }}
          </td>

          {{-- Paid --}}
          <td class="num" style="color:#16a34a;font-size:12px;white-space:nowrap">
            {{ $cust->ar_actual > 0 ? 'Rp '.number_format($cust->ar_actual, 0, ',', '.') : '—' }}
          </td>

          {{-- Remaining --}}
          <td class="num" style="font-weight:700;font-size:12px;white-space:nowrap;
               color:{{ $sisa > 0 ? '#dc2626' : '#16a34a' }}">
            {{ $sisa > 0 ? 'Rp '.number_format($sisa, 0, ',', '.') : '✓ Settled' }}
          </td>

          {{-- Action buttons --}}
          <td style="text-align:center;white-space:nowrap">
            <div style="display:flex;align-items:center;justify-content:center;gap:6px">
              <button
                onclick="sendCustomerEmail('{{ addslashes($cust->customer_code) }}')"
                @if(!$hasEmail) disabled title="No email on record" @endif
                style="display:inline-flex;align-items:center;gap:4px;padding:5px 10px;
                       border-radius:7px;border:1px solid {{ $hasEmail ? '#1e40af' : '#e2e8f0' }};
                       background:{{ $hasEmail ? '#dbeafe' : '#f8fafc' }};
                       color:{{ $hasEmail ? '#1e40af' : '#94a3b8' }};
                       font-size:11px;font-weight:700;cursor:{{ $hasEmail ? 'pointer' : 'not-allowed' }};
                       transition:all .15s"
                id="email-btn-{{ $cust->customer_code }}"
                onmouseover="{{ $hasEmail ? "this.style.background='#bfdbfe'" : '' }}"
                onmouseout="{{ $hasEmail ? "this.style.background='#dbeafe'" : '' }}">
                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                Email
              </button>
              <button
                onclick="sendCustomerWA('{{ addslashes($cust->customer_code) }}')"
                @if(!$hasWA) disabled title="No WhatsApp number on record" @endif
                style="display:inline-flex;align-items:center;gap:4px;padding:5px 10px;
                       border-radius:7px;border:1px solid {{ $hasWA ? '#16a34a' : '#e2e8f0' }};
                       background:{{ $hasWA ? '#dcfce7' : '#f8fafc' }};
                       color:{{ $hasWA ? '#15803d' : '#94a3b8' }};
                       font-size:11px;font-weight:700;cursor:{{ $hasWA ? 'pointer' : 'not-allowed' }};
                       transition:all .15s"
                id="wa-btn-{{ $cust->customer_code }}"
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
  <div style="padding:12px 20px;border-top:1px solid var(--border);background:#f8fafc;
              display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px">
    <div style="font-size:12px;color:var(--muted)">
      <span id="selected-footer-count">0</span> customers selected
    </div>
  </div>
</div>
@endif

<style>
@keyframes slideIn {
  from { opacity:0; transform:translateX(20px); }
  to   { opacity:1; transform:translateX(0); }
}
</style>

{{-- Pass PHP grouped data to JS --}}
@php
$groupedForJs = $grouped->keyBy('customer_code')->map(function($c) use ($collectorName) {
    return [
        'customer_code'   => $c->customer_code,
        'customer_name'   => $c->customer_name,
        'pic_name'        => $c->pic_name,
        'email'           => $c->email,
        'whatsapp_number' => $c->whatsapp_number,
        'plant'           => $c->plant,
        'has_email'       => $c->has_email,
        'has_wa'          => $c->has_wa,
        'invoice_ids'     => $c->invoice_ids,
        'collector_name'  => $collectorName,
        'invoices'        => $c->invoices->map(function($inv) use ($collectorName) {
            return [
                'invoice_id'     => $inv->invoice_id,
                'period_label'   => '',
                'due_date'       => $inv->due_date,
                'total_ar'       => $inv->total_ar,
                'ar_actual'      => $inv->ar_actual,
                'ar_target'      => $inv->ar_target,
                'current'        => 0,
                'days_1_30'      => 0,
                'days_30_60'     => 0,
                'days_60_90'     => $inv->overdue_60,
                'days_over_90'   => $inv->overdue_90,
                'urgency_class'  => $inv->urgency_class,
                'urgency_label'  => $inv->urgency_label,
                'days_left'      => $inv->days_left,
                'is_overdue'     => $inv->is_overdue,
                'collector_name' => $collectorName,
            ];
        })->values(),
    ];
});
@endphp
<script>
const GROUPED_CUSTOMERS = @json($groupedForJs);
</script>
@endsection

@push('scripts')
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;

/* ─── Toast ─────────────────────────────────────────────── */
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
    display:flex; position:fixed; top:20px; right:20px; z-index:600;
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

function fmtIDR(v) {
  if (!v || v == 0) return '—';
  if (v >= 1e12) return 'Rp '+(v/1e12).toFixed(2)+'T';
  if (v >= 1e9)  return 'Rp '+(v/1e9).toFixed(2)+'B';
  if (v >= 1e6)  return 'Rp '+(v/1e6).toFixed(1)+'M';
  return 'Rp '+Number(v).toLocaleString('id-ID');
}

/* ─── WA modal helpers ───────────────────────────────────── */
/**
 * Show the WA preview modal.
 *
 * @param {string} customerLabel  - name shown in the header subtitle
 * @param {string} previewText    - full pre-built message text
 * @param {string} waUrl          - the wa.me URL
 * @param {Array}  invoices       - array of invoice objects (for badge list)
 */
function showWaModal(customerLabel, previewText, waUrl, invoices) {
  document.getElementById('wa-modal-customer').textContent = customerLabel;
  document.getElementById('wa-modal-preview').textContent  = previewText;
  document.getElementById('wa-open-link').href             = waUrl;

  // Invoice badges + grand total (only shown when multiple invoices)
  const summaryEl  = document.getElementById('wa-modal-summary');
  const badgesEl   = document.getElementById('wa-modal-invoice-badges');
  const grandTotEl = document.getElementById('wa-modal-grand-total');

  if (invoices && invoices.length > 1) {
    let grandTotal = 0;
    badgesEl.innerHTML = invoices.map(inv => {
      const balance = Math.max(0, inv.total_ar - inv.ar_actual);
      grandTotal   += balance;
      const dueDate = inv.due_date
        ? new Date(inv.due_date).toLocaleDateString('en-GB', { day:'2-digit', month:'short' })
        : '?';
      const isOverdue = inv.is_overdue;
      return `<span style="display:inline-flex;align-items:center;gap:5px;padding:4px 10px;
                border-radius:99px;font-size:11px;font-weight:700;
                background:${isOverdue ? '#fee2e2' : '#dbeafe'};
                color:${isOverdue ? '#991b1b' : '#1e40af'}">
        #${inv.invoice_id}
        <span style="font-size:9px;opacity:.75">${dueDate}</span>
      </span>`;
    }).join('');
    grandTotEl.textContent = grandTotal > 0
      ? 'Rp ' + Number(grandTotal).toLocaleString('id-ID')
      : 'All Settled ✓';
    summaryEl.style.display = 'block';
  } else {
    summaryEl.style.display = 'none';
  }

  document.getElementById('wa-modal-overlay').style.display = 'flex';
}

function closeWaModal() {
  document.getElementById('wa-modal-overlay').style.display = 'none';
}

/* ─── Build WA preview text locally (mirrors server logic) ── */
function buildWaPreviewText(cust, selectedInvoices, collectorName) {
  const company = 'PT. Dunia Kimia Jaya';
  const pic     = cust.pic_name ? `Dear ${cust.pic_name}` : 'Dear Finance Team';
  const lines   = [];

  lines.push(`${pic},`);
  lines.push('');
  lines.push(`Greetings from ${company}.`);
  lines.push('');

  if (selectedInvoices.length === 1) {
    const inv     = selectedInvoices[0];
    const dueDate = inv.due_date
      ? new Date(inv.due_date).toLocaleDateString('en-GB', { day:'2-digit', month:'long', year:'numeric' })
      : '—';
    const amount  = 'Rp ' + Number(inv.total_ar).toLocaleString('id-ID');
    lines.push(`We would like to remind you that the invoice for *${cust.customer_name}* (Invoice #${inv.invoice_id}) amounting to *${amount}* is due on *${dueDate}*.`);
    lines.push('');
    lines.push('Please process payment before the due date to avoid any delays.');
  } else {
    lines.push(`We would like to remind you regarding *${selectedInvoices.length} outstanding invoices* for *${cust.customer_name}*:`);
    lines.push('');

    let grandTotal = 0;
    selectedInvoices.forEach((inv, i) => {
      const dueDate = inv.due_date
        ? new Date(inv.due_date).toLocaleDateString('en-GB', { day:'2-digit', month:'short', year:'numeric' })
        : '—';
      const amount  = 'Rp ' + Number(inv.total_ar).toLocaleString('id-ID');
      const paid    = inv.ar_actual > 0 ? 'Rp ' + Number(inv.ar_actual).toLocaleString('id-ID') : '-';
      const balance = Math.max(0, inv.total_ar - inv.ar_actual);
      const balFmt  = balance > 0 ? 'Rp ' + Number(balance).toLocaleString('id-ID') : 'Settled ✓';
      grandTotal   += balance;

      lines.push(`${i + 1}. *Invoice #${inv.invoice_id}*`);
      lines.push(`   Due Date : ${dueDate}`);
      lines.push(`   Amount   : ${amount}`);
      lines.push(`   Paid     : ${paid}`);
      lines.push(`   Balance  : ${balFmt}`);
      if (i < selectedInvoices.length - 1) lines.push('');
    });

    lines.push('');
    const totalOutstanding = grandTotal > 0
      ? 'Rp ' + Number(grandTotal).toLocaleString('id-ID')
      : 'All settled ✓';
    lines.push(`*Total Outstanding: ${totalOutstanding}*`);
    lines.push('');
    lines.push('Please process payment for each invoice before its respective due date to avoid any delays.');
  }

  lines.push('');
  lines.push('If payment has already been made, kindly confirm with us.');
  lines.push('');
  lines.push('Thank you for your cooperation.');
  lines.push('');
  lines.push(`Best regards,\n${collectorName}\n${company}`);

  return lines.join('\n');
}

/* ─── Selected count footer ──────────────────────────────── */
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

/* ─── Customer Detail Modal ──────────────────────────────── */
let currentModalCustomer = null;

function infoCard(label, value, icon) {
  return `<div style="background:#f8fafc;border:1px solid var(--border);border-radius:10px;padding:14px 16px">
    <div style="font-size:10px;color:var(--muted);font-weight:700;text-transform:uppercase;
                letter-spacing:.08em;margin-bottom:5px">${icon ? icon + ' ' : ''}${label}</div>
    <div style="font-size:13px;font-weight:600;color:var(--text)">${value || '<span style="color:var(--muted);font-weight:400">—</span>'}</div>
  </div>`;
}

async function openCustReminderModal(customerCode) {
  const cust = GROUPED_CUSTOMERS[customerCode];
  if (!cust) return;
  currentModalCustomer = cust;

  document.getElementById('crd-name').textContent = cust.customer_name;
  document.getElementById('crd-id').textContent   = 'Customer ID: ' + cust.customer_code;

  document.getElementById('crd-info-grid').innerHTML =
    infoCard('PIC Name',      cust.pic_name,    '👤') +
    infoCard('Email',         cust.email ? `<a href="mailto:${cust.email}" style="color:var(--navy)">${cust.email}</a>` : null, '📧') +
    infoCard('WhatsApp',      cust.whatsapp_number ? `<a href="https://wa.me/${cust.whatsapp_number}" target="_blank" style="color:#16a34a">+${cust.whatsapp_number}</a>` : null, '💬') +
    infoCard('Customer Code', cust.customer_code, '🏷️');

  const plantsEl = document.getElementById('crd-plants');
  plantsEl.innerHTML = `<span class="badge badge-blue" style="font-size:11px;padding:3px 10px">Plant ${cust.plant}</span>`;

  document.getElementById('crd-inv-count').textContent = cust.invoices.length + ' invoice' + (cust.invoices.length !== 1 ? 's' : '');

  const saEl = document.getElementById('crd-select-all');
  if (saEl) saEl.checked = false;
  const searchEl = document.getElementById('crd-inv-search');
  if (searchEl) searchEl.value = '';

  const tbody = document.getElementById('crd-inv-tbody');
  if (!cust.invoices.length) {
    tbody.innerHTML = `<tr><td colspan="15" style="text-align:center;padding:32px;color:var(--muted)">No invoices found.</td></tr>`;
  } else {
    tbody.innerHTML = cust.invoices.map(inv => {
      const rate       = inv.ar_target > 0 ? (inv.ar_actual / inv.ar_target * 100).toFixed(1) : null;
      const rateColor  = rate === null ? '#94a3b8' : (rate >= 100 ? '#16a34a' : rate >= 70 ? '#d97706' : '#dc2626');
      const hasOD      = (inv.days_60_90 > 0 || inv.days_over_90 > 0);
      const urgColors  = {
        'urgency-overdue'  : ['#fee2e2','#991b1b'],
        'urgency-critical' : ['#ffe4e6','#881337'],
        'urgency-urgent'   : ['#ffedd5','#9a3412'],
        'urgency-soon'     : ['#fef9c3','#92400e'],
        'urgency-upcoming' : ['#dcfce7','#166534'],
      };
      const [ubg, ucol] = urgColors[inv.urgency_class] || ['#f1f5f9','#475569'];
      const periodLabel = inv.due_date
        ? new Date(inv.due_date).toLocaleDateString('en-US', {month:'short', year:'numeric'})
        : '—';
      return `<tr style="${hasOD ? 'background:#fff9f9' : ''}">
        <td style="padding:10px 12px">
          <input type="checkbox" class="crd-inv-check" value="${inv.invoice_id}"
                 onchange="crdUpdateCount()"
                 style="width:13px;height:13px;accent-color:var(--navy)">
        </td>
        <td style="font-weight:600;white-space:nowrap;font-size:12px">${periodLabel}</td>
        <td style="font-weight:700;white-space:nowrap">#${inv.invoice_id}</td>
        <td style="font-size:11px;color:var(--muted);white-space:nowrap">${cust.collector_name || '—'}</td>
        <td style="font-size:11px;color:var(--muted);white-space:nowrap">
          ${inv.due_date ? new Date(inv.due_date).toLocaleDateString('en-US',{day:'2-digit',month:'short',year:'numeric'}) : '—'}
          ${inv.is_overdue
            ? `<div style="font-size:9px;color:#dc2626;font-weight:700">${Math.abs(inv.days_left)}d overdue</div>`
            : `<div style="font-size:9px;color:#16a34a">${inv.days_left}d remaining</div>`}
        </td>
        <td class="num">${fmtIDR(inv.current)}</td>
        <td class="num">${fmtIDR(inv.days_1_30)}</td>
        <td class="num" style="${inv.days_30_60 > 0 ? 'color:#d97706;font-weight:600' : ''}">${fmtIDR(inv.days_30_60)}</td>
        <td class="num" style="${inv.days_60_90 > 0 ? 'color:#ea580c;font-weight:600' : ''}">${fmtIDR(inv.days_60_90)}</td>
        <td class="num" style="${inv.days_over_90 > 0 ? 'color:#dc2626;font-weight:700' : ''}">${fmtIDR(inv.days_over_90)}</td>
        <td class="num" style="font-weight:700">${fmtIDR(inv.total_ar)}</td>
        <td class="num" style="color:var(--muted)">${fmtIDR(inv.ar_target)}</td>
        <td class="num" style="color:#16a34a">${fmtIDR(inv.ar_actual)}</td>
        <td class="num" style="color:${rateColor};font-weight:700">${rate !== null ? rate+'%' : '—'}</td>
        <td style="text-align:center;white-space:nowrap">
          <div style="display:flex;align-items:center;justify-content:center;gap:5px">
            <button onclick="sendSingleEmailById(${inv.invoice_id}, '${cust.customer_name.replace(/'/g,"\\'")}', this)"
              ${!cust.has_email ? 'disabled title="No email on record"' : ''}
              style="display:inline-flex;align-items:center;gap:3px;padding:4px 9px;
                     border-radius:7px;border:1px solid ${cust.has_email ? '#1e40af' : '#e2e8f0'};
                     background:${cust.has_email ? '#dbeafe' : '#f8fafc'};
                     color:${cust.has_email ? '#1e40af' : '#94a3b8'};
                     font-size:10px;font-weight:700;cursor:${cust.has_email ? 'pointer' : 'not-allowed'};
                     transition:all .15s"
              onmouseover="${cust.has_email ? "this.style.background='#bfdbfe'" : ''}"
              onmouseout="${cust.has_email ? "this.style.background='#dbeafe'" : ''}">
              <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
              Email
            </button>
            <button onclick="sendSingleWAById(${inv.invoice_id}, '${cust.customer_name.replace(/'/g,"\\'")}', this)"
              ${!cust.has_wa ? 'disabled title="No WhatsApp number"' : ''}
              style="display:inline-flex;align-items:center;gap:3px;padding:4px 9px;
                     border-radius:7px;border:1px solid ${cust.has_wa ? '#16a34a' : '#e2e8f0'};
                     background:${cust.has_wa ? '#dcfce7' : '#f8fafc'};
                     color:${cust.has_wa ? '#15803d' : '#94a3b8'};
                     font-size:10px;font-weight:700;cursor:${cust.has_wa ? 'pointer' : 'not-allowed'};
                     transition:all .15s"
              onmouseover="${cust.has_wa ? "this.style.background='#bbf7d0'" : ''}"
              onmouseout="${cust.has_wa ? "this.style.background='#dcfce7'" : ''}">
              <svg width="10" height="10" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413z"/><path d="M11.999 0C5.373 0 0 5.373 0 12c0 2.126.558 4.117 1.535 5.845L0 24l6.294-1.508A11.954 11.954 0 0 0 12 24c6.627 0 12-5.373 12-12S18.627 0 11.999 0zm0 21.818a9.818 9.818 0 0 1-5.007-1.374l-.36-.213-3.737.894.937-3.636-.235-.374A9.822 9.822 0 0 1 2.182 12c0-5.414 4.403-9.818 9.818-9.818 5.414 0 9.818 4.404 9.818 9.818 0 5.415-4.404 9.818-9.819 9.818z"/></svg>
              WA
            </button>
          </div>
        </td>
      </tr>`;
    }).join('');
  }

  const overlay = document.getElementById('cust-reminder-overlay');
  const box     = document.getElementById('cust-reminder-box');
  overlay.style.display = 'flex';
  box.style.opacity    = '0';
  box.style.transform  = 'translateY(-16px)';
  box.style.transition = 'opacity .2s ease, transform .2s ease';
  requestAnimationFrame(() => { box.style.opacity = '1'; box.style.transform = 'translateY(0)'; });
}

function closeCustReminderModal() {
  document.getElementById('cust-reminder-overlay').style.display = 'none';
  currentModalCustomer = null;
}

function crdToggleAll(master) {
  document.querySelectorAll('.crd-inv-check').forEach(cb => cb.checked = master.checked);
  crdUpdateCount();
}

function crdUpdateCount() {
  // placeholder — could show count in UI
}

function crdFilterInvoices(query) {
  const q = query.trim().toLowerCase();
  document.querySelectorAll('#crd-inv-tbody tr').forEach(row => {
    const invId = (row.cells[2]?.textContent || '').toLowerCase();
    row.style.display = (!q || invId.includes(q)) ? '' : 'none';
  });
  document.querySelectorAll('#crd-inv-tbody tr').forEach(row => {
    if (row.style.display === 'none') {
      const cb = row.querySelector('.crd-inv-check');
      if (cb) cb.checked = false;
    }
  });
  const saEl = document.getElementById('crd-select-all');
  if (saEl) saEl.checked = false;
}

function crdShakeButtons() {
  const btns = [document.getElementById('crd-email-btn'), document.getElementById('crd-wa-btn')];
  btns.forEach(btn => {
    if (!btn) return;
    btn.style.transition = 'transform .08s ease';
    const steps = ['-4px','4px','-4px','4px','0px'];
    let i = 0;
    const go = () => {
      if (i >= steps.length) { btn.style.transform = ''; return; }
      btn.style.transform = `translateX(${steps[i++]})`;
      setTimeout(go, 80);
    };
    go();
  });
}

function crdGetSelectedIds() {
  return Array.from(document.querySelectorAll('.crd-inv-check:checked')).map(cb => cb.value);
}

/* ─── Send Selected Email (customer detail modal) ──────── */
async function sendCustomerBulkEmail() {
  if (!currentModalCustomer) return;
  const ids = crdGetSelectedIds();
  if (!ids.length) {
    showToast('Please select at least one invoice first.', 'warning');
    crdShakeButtons();
    return;
  }
  const btn = document.getElementById('crd-email-btn');
  btn.disabled = true; btn.textContent = '…';
  try {
    const res  = await fetch('/reminder/bulk', {
      method:'POST',
      headers:{'X-CSRF-TOKEN':CSRF,'Accept':'application/json','Content-Type':'application/json'},
      body: JSON.stringify({ type:'email', invoice_ids: ids }),
    });
    const data = await res.json();
    if (data.success) {
      showToast(`${data.sent} email(s) sent · ${data.skipped} skipped`, 'success');
    } else {
      showToast(data.message || 'Failed to send emails.', 'error');
    }
  } catch(e) {
    showToast('A network error occurred.', 'error');
  } finally {
    btn.disabled = false;
    btn.innerHTML = `<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg> Send Selected Email`;
  }
}

/* ─── Send Selected WA (customer detail modal) ─────────── */
/*
 * Always shows the WA preview modal first, whether 1 or multiple invoices are selected.
 * The modal displays:
 *   - A badge row listing all selected invoices with their due dates (multi only)
 *   - A grand total outstanding bar (multi only)
 *   - The full message preview in a WhatsApp-green chat bubble
 *   - An "Open WhatsApp" button that fires the real wa.me link
 */
async function sendCustomerBulkWA() {
  if (!currentModalCustomer) return;
  const ids = crdGetSelectedIds();
  if (!ids.length) {
    showToast('Please select at least one invoice first.', 'warning');
    crdShakeButtons();
    return;
  }

  const cust             = currentModalCustomer;
  const selectedInvoices = cust.invoices.filter(inv => ids.includes(String(inv.invoice_id)));
  const btn              = document.getElementById('crd-wa-btn');
  btn.disabled           = true;

  try {
    // Fetch the server-generated WA URL (server handles phone normalisation, encoding, etc.)
    const res  = await fetch('/reminder/bulk', {
      method:'POST',
      headers:{'X-CSRF-TOKEN':CSRF,'Accept':'application/json','Content-Type':'application/json'},
      body: JSON.stringify({ type:'whatsapp', invoice_ids: ids }),
    });
    const data = await res.json();

    if (data.success && data.wa_links && data.wa_links.length > 0) {
      const waUrl      = data.wa_links[0].url;
      const previewTxt = buildWaPreviewText(cust, selectedInvoices, cust.collector_name || '—');
      const label      = selectedInvoices.length > 1
        ? `${cust.customer_name} — ${selectedInvoices.length} invoices selected`
        : `${cust.customer_name} — Invoice #${selectedInvoices[0].invoice_id}`;

      showWaModal(label, previewTxt, waUrl, selectedInvoices);
    } else {
      showToast(data.message || 'Failed to create WhatsApp link.', 'error');
    }
  } catch(e) {
    showToast('A network error occurred.', 'error');
  } finally {
    btn.disabled = false;
    btn.innerHTML = `<svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413z"/><path d="M11.999 0C5.373 0 0 5.373 0 12c0 2.126.558 4.117 1.535 5.845L0 24l6.294-1.508A11.954 11.954 0 0 0 12 24c6.627 0 12-5.373 12-12S18.627 0 11.999 0zm0 21.818a9.818 9.818 0 0 1-5.007-1.374l-.36-.213-3.737.894.937-3.636-.235-.374A9.822 9.822 0 0 1 2.182 12c0-5.414 4.403-9.818 9.818-9.818 5.414 0 9.818 4.404 9.818 9.818 0 5.415-4.404 9.818-9.819 9.818z"/></svg> Send Selected WA`;
  }
}

/* ─── Single invoice actions (per-row buttons) ──────────── */
async function sendSingleEmailById(invoiceId, customerName, btnEl) {
  if (btnEl) { btnEl.disabled = true; btnEl.textContent = '…'; }
  try {
    const res  = await fetch(`/reminder/email/${invoiceId}`, {
      method:'POST',
      headers:{'X-CSRF-TOKEN':CSRF,'Accept':'application/json','Content-Type':'application/json'},
    });
    const data = await res.json();
    if (data.success) {
      showToast(`Email sent to ${customerName}`, 'success');
      if (btnEl) { btnEl.textContent = '✓'; btnEl.style.background='#dcfce7'; btnEl.style.color='#15803d'; }
    } else {
      showToast(data.message || 'Failed.', 'error');
      if (btnEl) { btnEl.disabled = false; btnEl.textContent = 'Email'; }
    }
  } catch(e) {
    showToast('Network error.', 'error');
    if (btnEl) { btnEl.disabled = false; btnEl.textContent = 'Email'; }
  }
}

/*
 * Per-row WA button: fetches the server URL then shows the preview modal.
 * Even for a single invoice click this goes through the same modal flow.
 */
async function sendSingleWAById(invoiceId, customerName, btnEl) {
  if (btnEl) { btnEl.disabled = true; btnEl.textContent = '…'; }

  // Find the invoice object from the currently-open customer modal
  const inv  = currentModalCustomer?.invoices.find(i => String(i.invoice_id) === String(invoiceId));
  const cust = currentModalCustomer;

  try {
    const res  = await fetch(`/reminder/whatsapp/${invoiceId}`, {
      method:'POST',
      headers:{'X-CSRF-TOKEN':CSRF,'Accept':'application/json','Content-Type':'application/json'},
    });
    const data = await res.json();
    if (data.success) {
      const previewTxt = cust && inv
        ? buildWaPreviewText(cust, [inv], cust.collector_name || '—')
        : decodeURIComponent(data.url.split('?text=')[1] || '');

      showWaModal(
        `${customerName} — Invoice #${invoiceId}`,
        previewTxt,
        data.url,
        inv ? [inv] : null
      );

      if (btnEl) { btnEl.disabled = false; btnEl.textContent = 'WA'; }
    } else {
      showToast(data.message || 'Failed.', 'error');
      if (btnEl) { btnEl.disabled = false; btnEl.textContent = 'WA'; }
    }
  } catch(e) {
    showToast('Network error.', 'error');
    if (btnEl) { btnEl.disabled = false; btnEl.textContent = 'WA'; }
  }
}

/* ─── Main table: send all invoices for a customer ──────── */
async function sendCustomerEmail(customerCode) {
  const cust = GROUPED_CUSTOMERS[customerCode];
  if (!cust || !cust.has_email) return;
  const btn = document.getElementById('email-btn-' + customerCode);
  if (btn) { btn.disabled = true; btn.textContent = '…'; }
  try {
    const res  = await fetch('/reminder/bulk', {
      method:'POST',
      headers:{'X-CSRF-TOKEN':CSRF,'Accept':'application/json','Content-Type':'application/json'},
      body: JSON.stringify({ type:'email', invoice_ids: cust.invoice_ids }),
    });
    const data = await res.json();
    if (data.success) {
      showToast(`${data.sent} email(s) sent to ${cust.customer_name}`, 'success');
      if (btn) { btn.textContent = '✓ Sent'; btn.style.background='#dcfce7'; btn.style.borderColor='#16a34a'; btn.style.color='#15803d'; }
    } else {
      showToast(data.message || 'Failed.', 'error');
      if (btn) { btn.disabled = false; btn.textContent = 'Email'; }
    }
  } catch(e) {
    showToast('Network error.', 'error');
    if (btn) { btn.disabled = false; btn.textContent = 'Email'; }
  }
}

/*
 * Main table WA button: opens the customer in its detail modal first,
 * then lets the user pick invoices — OR for a quick single send,
 * show the preview for all invoices of that customer directly.
 */
async function sendCustomerWA(customerCode) {
  const cust = GROUPED_CUSTOMERS[customerCode];
  if (!cust || !cust.has_wa) return;
  const btn = document.getElementById('wa-btn-' + customerCode);
  if (btn) { btn.disabled = true; btn.textContent = '…'; }

  try {
    const res  = await fetch('/reminder/bulk', {
      method:'POST',
      headers:{'X-CSRF-TOKEN':CSRF,'Accept':'application/json','Content-Type':'application/json'},
      body: JSON.stringify({ type:'whatsapp', invoice_ids: cust.invoice_ids }),
    });
    const data = await res.json();
    if (data.success && data.wa_links && data.wa_links.length > 0) {
      const waUrl      = data.wa_links[0].url;
      const previewTxt = buildWaPreviewText(cust, cust.invoices, cust.collector_name || '—');
      const label      = cust.invoice_ids.length > 1
        ? `${cust.customer_name} — ${cust.invoice_ids.length} invoices`
        : `${cust.customer_name}`;

      showWaModal(label, previewTxt, waUrl, cust.invoices);
      if (btn) { btn.disabled = false; btn.textContent = 'WA'; }
    } else {
      showToast(data.message || 'Failed.', 'error');
      if (btn) { btn.disabled = false; btn.textContent = 'WA'; }
    }
  } catch(e) {
    showToast('Network error.', 'error');
    if (btn) { btn.disabled = false; btn.textContent = 'WA'; }
  }
}

/* ─── Bulk modal (main table) ────────────────────────────── */
let currentBulkType = 'email';

function openBulkModal(type) {
  currentBulkType = type;
  const isEmail = type === 'email';
  document.getElementById('bulk-modal-title').textContent = isEmail ? 'Bulk Email Send' : 'Bulk WhatsApp';
  document.getElementById('bulk-modal-sub').textContent   = isEmail
    ? 'Select customers to send email reminders'
    : 'Select customers to generate WhatsApp links';
  document.getElementById('bulk-modal-icon').textContent  = isEmail ? '📧' : '💬';
  document.getElementById('bulk-modal-icon').style.background = isEmail ? '#dbeafe' : '#dcfce7';
  const sendBtn = document.getElementById('bulk-send-btn');
  sendBtn.style.background = isEmail ? 'var(--navy)' : '#16a34a';
  document.getElementById('bulk-send-label').textContent = isEmail ? 'Send Email' : 'Generate WA Links';
  document.getElementById('bulk-modal-feedback').style.display = 'none';

  const searchEl = document.getElementById('bulk-customer-search');
  if (searchEl) searchEl.value = '';

  buildBulkList(isEmail, '');
  updateBulkCount();

  const overlay = document.getElementById('bulk-modal-overlay');
  const box     = document.getElementById('bulk-modal-box');
  overlay.style.display = 'flex';
  box.style.opacity    = '0'; box.style.transform = 'translateY(-14px)';
  box.style.transition = 'opacity .2s ease, transform .2s ease';
  requestAnimationFrame(() => { box.style.opacity = '1'; box.style.transform = 'translateY(0)'; });
}

function buildBulkList(isEmail, filterQuery) {
  const rows  = document.querySelectorAll('#reminder-table tbody tr');
  const query = filterQuery.trim().toLowerCase();
  let html    = '';
  let hasResults = false;

  rows.forEach(row => {
    const code     = row.dataset.customerCode;
    const urgency  = row.dataset.urgency;
    const hasEmail = row.dataset.hasEmail === '1';
    const hasWA    = row.dataset.hasWa    === '1';
    const canSend  = isEmail ? hasEmail : hasWA;
    const cust     = GROUPED_CUSTOMERS[code];
    if (!cust) return;

    if (query && !cust.customer_name.toLowerCase().includes(query)) return;
    hasResults = true;

    const preChecked = row.querySelector('.row-check')?.checked;
    const urgColors  = {
      'urgency-overdue'  : ['#fee2e2','#991b1b'],
      'urgency-critical' : ['#ffe4e6','#881337'],
      'urgency-urgent'   : ['#ffedd5','#9a3412'],
      'urgency-soon'     : ['#fef9c3','#92400e'],
      'urgency-upcoming' : ['#dcfce7','#166534'],
    };
    const [bg, color] = urgColors[urgency] || ['#f1f5f9','#475569'];

    let displayName = cust.customer_name;
    if (query) {
      const idx = displayName.toLowerCase().indexOf(query);
      if (idx >= 0) {
        displayName = displayName.slice(0, idx)
          + `<mark style="background:#fef08a;border-radius:2px;padding:0 1px">${displayName.slice(idx, idx + query.length)}</mark>`
          + displayName.slice(idx + query.length);
      }
    }

    html += `
      <div class="bulk-row" data-customer-name="${cust.customer_name.toLowerCase()}"
           style="display:flex;align-items:center;gap:12px;padding:10px 16px;
                  border-bottom:1px solid var(--border);${!canSend?'opacity:.45':''}">
        <input type="checkbox" class="bulk-check" value="${code}"
               ${preChecked ? 'checked' : ''} ${!canSend ? 'disabled' : ''}
               data-invoice-ids="${cust.invoice_ids.join(',')}"
               onchange="updateBulkCount()"
               style="width:14px;height:14px;accent-color:var(--navy);flex-shrink:0">
        <div style="flex:1;min-width:0">
          <div style="font-size:12px;font-weight:700;color:var(--navy);
                      white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${displayName}</div>
          <div style="font-size:10px;color:var(--muted);margin-top:1px">
            ${cust.invoice_ids.length} invoice${cust.invoice_ids.length !== 1 ? 's' : ''} ·
            ${isEmail ? (hasEmail ? `✉️ ${cust.email}` : '✗ No email') : (hasWA ? `💬 +${cust.whatsapp_number}` : '✗ No WhatsApp')}
          </div>
        </div>
        <span style="font-size:9px;font-weight:700;padding:2px 8px;border-radius:99px;
                     background:${bg};color:${color};flex-shrink:0">${urgency.replace('urgency-','').toUpperCase()}</span>
      </div>`;
  });

  document.getElementById('bulk-invoice-list').innerHTML = html || '';

  const noRes = document.getElementById('bulk-no-results');
  if (!hasResults && query) {
    noRes.style.display = 'block';
    document.getElementById('bulk-invoice-list').style.display = 'none';
  } else {
    noRes.style.display = 'none';
    document.getElementById('bulk-invoice-list').style.display = 'block';
  }

  updateBulkCount();
}

function filterBulkCustomers(query) {
  buildBulkList(currentBulkType === 'email', query);
}

function closeBulkModal() {
  document.getElementById('bulk-modal-overlay').style.display = 'none';
}

function updateBulkCount() {
  const count = document.querySelectorAll('.bulk-check:checked').length;
  const total = document.querySelectorAll('.bulk-check:not(:disabled)').length;
  const el    = document.getElementById('bulk-selected-count');
  const allEl = document.getElementById('bulk-select-all');
  if (el)    el.textContent = `${count} selected`;
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
      const code = row.dataset.customerCode;
      const cb   = document.querySelector(`.bulk-check[value="${code}"]`);
      if (cb && !cb.disabled) cb.checked = true;
    }
  });
  updateBulkCount();
}

async function executeBulkSend() {
  const selected   = Array.from(document.querySelectorAll('.bulk-check:checked'));
  const invoiceIds = [];
  selected.forEach(cb => {
    const ids = cb.dataset.invoiceIds ? cb.dataset.invoiceIds.split(',') : [];
    ids.forEach(id => invoiceIds.push(id));
  });

  const fb  = document.getElementById('bulk-modal-feedback');
  const btn = document.getElementById('bulk-send-btn');
  const lbl = document.getElementById('bulk-send-label');
  if (!invoiceIds.length) { showToast('Please select at least one customer.', 'warning'); return; }

  btn.disabled = true; lbl.textContent = 'Processing…'; fb.style.display = 'none';

  try {
    const res  = await fetch('/reminder/bulk', {
      method:'POST',
      headers:{'X-CSRF-TOKEN':CSRF,'Accept':'application/json','Content-Type':'application/json'},
      body: JSON.stringify({ type: currentBulkType, invoice_ids: invoiceIds }),
    });
    const data = await res.json();
    if (data.success) {
      if (currentBulkType === 'whatsapp' && data.wa_links && data.wa_links.length > 0) {
        // For bulk WA from the main table, show links directly (multiple customers)
        fb.innerHTML  = `✅ ${data.sent} WhatsApp link(s) ready:<br><br>` +
          data.wa_links.map(l =>
            `<a href="${l.url}" target="_blank"
               style="display:flex;align-items:center;gap:8px;padding:8px 12px;margin-bottom:6px;
                      background:#dcfce7;border:1px solid #86efac;border-radius:8px;
                      text-decoration:none;color:#15803d;font-size:12px;font-weight:600">
               💬 ${l.customer} — Open WhatsApp
             </a>`
          ).join('');
        fb.style.cssText = 'display:block;background:#f0fdf4;border:1px solid #86efac;color:#166534;padding:12px 16px;border-radius:9px;font-size:13px;margin-top:14px';
        showToast(`${data.sent} WhatsApp link(s) generated.`, 'success');
      } else {
        fb.textContent   = `✅ ${data.sent} email(s) sent · ${data.skipped} skipped`;
        fb.style.cssText = 'display:block;background:#dcfce7;border:1px solid #86efac;color:#166534;padding:12px 16px;border-radius:9px;font-size:13px;font-weight:600;margin-top:14px';
        showToast(`${data.sent} email(s) sent successfully!`, 'success');
        setTimeout(() => closeBulkModal(), 3000);
      }
    } else {
      fb.textContent   = '⚠️ ' + (data.message || 'Failed.');
      fb.style.cssText = 'display:block;background:#fee2e2;border:1px solid #fca5a5;color:#991b1b;padding:12px 16px;border-radius:9px;font-size:13px;font-weight:600;margin-top:14px';
    }
  } catch(e) {
    fb.textContent   = '⚠️ Network error: ' + e.message;
    fb.style.cssText = 'display:block;background:#fee2e2;border:1px solid #fca5a5;color:#991b1b;padding:12px 16px;border-radius:9px;font-size:13px;font-weight:600;margin-top:14px';
  } finally {
    btn.disabled = false;
    lbl.textContent = currentBulkType === 'email' ? 'Send Email' : 'Generate WA Links';
  }
}

document.addEventListener('keydown', e => {
  if (e.key === 'Escape') { closeBulkModal(); closeWaModal(); closeCustReminderModal(); }
});
</script>
@endpush