<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Invoice Due Date Reminder</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { background: #f0f4f8; font-family: 'Segoe UI', Arial, sans-serif; color: #1e293b; }
  .wrapper { max-width: 640px; margin: 32px auto; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 24px rgba(15,41,107,.12); }

  /* ── Header ── */
  .header { background: #0f2942; padding: 24px 32px; }
  .header-inner { display: table; width: 100%; }
  .header-logo-wrap { display: table-cell; vertical-align: middle; width: 56px; }
  .header-logo-img  { width: 48px; height: 48px; object-fit: contain; display: block; }
  .header-logo-placeholder {
    width: 48px; height: 48px; border-radius: 10px;
    background: rgba(255,255,255,.15);
    display: inline-block;
    text-align: center; line-height: 48px;
    font-size: 16px; font-weight: 800; color: #fff; letter-spacing: -1px;
  }
  .header-text-wrap { display: table-cell; vertical-align: middle; padding-left: 14px; }
  .header-brand { font-size: 22px; font-weight: 800; color: #fff; letter-spacing: -.5px; line-height: 1.1; }
  .header-brand span { color: #38bdf8; }
  .header-subtitle { font-size: 11px; color: #94afc8; margin-top: 3px; text-transform: uppercase; letter-spacing: .1em; }
  .sender-badge {
    background: rgba(56,189,248,.15); border: 1px solid rgba(56,189,248,.3);
    border-radius: 6px; padding: 5px 12px; margin-top: 12px; display: inline-block;
    font-size: 11px; color: #7dd3fc; font-weight: 600; letter-spacing: .04em;
  }

  /* ── Body ── */
  .body { padding: 32px 36px; }
  .greeting { font-size: 14px; color: #334155; margin-bottom: 22px; line-height: 1.7; }

  /* ── Summary banner (multi-invoice) ── */
  .summary-banner {
    background: #f0f6ff; border: 1px solid #bfdbfe; border-radius: 10px;
    padding: 16px 20px; margin-bottom: 22px;
    display: table; width: 100%;
  }
  .summary-cell { display: table-cell; text-align: center; vertical-align: middle; }
  .summary-cell + .summary-cell { border-left: 1px solid #bfdbfe; }
  .summary-label { font-size: 10px; color: #64748b; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; margin-bottom: 4px; }
  .summary-value { font-size: 18px; font-weight: 800; color: #0f2942; font-family: 'Courier New', monospace; }
  .summary-value.green  { color: #16a34a; }
  .summary-value.red    { color: #dc2626; }
  .summary-value.count  { font-size: 28px; color: #1e40af; }

  /* ── Section title ── */
  .section-title {
    font-size: 10px; font-weight: 700; color: #64748b;
    text-transform: uppercase; letter-spacing: .1em;
    margin: 24px 0 10px;
  }

  /* ── Invoice Table (multi) ── */
  .invoices-table { width: 100%; border-collapse: collapse; border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden; font-size: 12px; }
  .invoices-table thead th {
    background: #f8fafc; color: #64748b; font-size: 10px; font-weight: 700;
    text-transform: uppercase; letter-spacing: .07em;
    padding: 9px 12px; text-align: left; border-bottom: 2px solid #e2e8f0;
  }
  .invoices-table thead th.num { text-align: right; }
  .invoices-table tbody tr { border-bottom: 1px solid #f1f5f9; }
  .invoices-table tbody tr:last-child { border-bottom: none; }
  .invoices-table tbody td { padding: 10px 12px; vertical-align: middle; }
  .invoices-table tbody td.num { text-align: right; font-family: 'Courier New', monospace; }
  .invoices-table tfoot td {
    padding: 10px 12px; font-weight: 800; font-size: 12px;
    background: #f8fafc; border-top: 2px solid #e2e8f0;
  }
  .invoices-table tfoot td.num { text-align: right; font-family: 'Courier New', monospace; }

  /* ── Single invoice card (fallback / single invoice) ── */
  .invoice-card { border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden; margin: 22px 0; }
  .invoice-card-title {
    background: #f8fafc; padding: 10px 16px;
    font-size: 10px; font-weight: 700; color: #94a3b8;
    text-transform: uppercase; letter-spacing: .1em;
    border-bottom: 1px solid #e2e8f0;
  }
  .invoice-table { width: 100%; border-collapse: collapse; }
  .invoice-table td { padding: 11px 16px; font-size: 13px; vertical-align: middle; border-bottom: 1px solid #f1f5f9; }
  .invoice-table tr:last-child td { border-bottom: none; }
  .invoice-table .td-label { width: 50%; color: #64748b; font-weight: 500; background: #fafbfc; }
  .invoice-table .td-value { width: 50%; color: #0f172a; font-weight: 600; text-align: right; }
  .due-badge { display: inline-block; background: #fee2e2; color: #991b1b; border-radius: 99px; padding: 3px 12px; font-size: 12px; font-weight: 700; }
  .due-badge.ok { background: #dbeafe; color: #1e40af; }
  .amount-value { font-size: 20px; font-weight: 800; color: #0f2942; font-family: 'Courier New', monospace; }
  .paid-value   { color: #16a34a; font-weight: 700; }
  .remain-value { color: #dc2626; font-weight: 800; font-size: 16px; }

  /* ── Alert ── */
  .alert-box { border-left: 4px solid #f97316; border-radius: 8px; padding: 14px 18px; margin: 20px 0; font-size: 13px; line-height: 1.6; background: #fff7ed; color: #7c2d12; }

  /* ── Signature ── */
  .collector-sig { margin-top: 24px; padding-top: 18px; border-top: 1px solid #e2e8f0; }
  .collector-name { font-size: 14px; font-weight: 700; color: #0f2942; }
  .collector-role { font-size: 11px; color: #94a3b8; margin-top: 2px; }
  .collector-email { font-size: 11px; color: #38bdf8; margin-top: 3px; }

  /* ── Footer ── */
  .footer { background: #f8fafc; border-top: 1px solid #e2e8f0; padding: 18px 36px; }
  .footer-text { font-size: 11px; color: #94a3b8; line-height: 1.7; }
</style>
</head>
<body>
<div class="wrapper">

  {{-- ── Header ── --}}
  <div class="header">
    <div class="header-inner">
      <div class="header-logo-wrap">
        @if(file_exists(public_path('images/logo_dkj.png')))
          <img src="{{ asset('images/logo_dkj.jpg') }}" alt="DKJ Logo" class="header-logo-img">
        @elseif(file_exists(public_path('logo.png')))
          <img src="{{ asset('logo.png') }}" alt="DKJ Logo" class="header-logo-img">
        @else
          <div class="header-logo-placeholder">DKJ</div>
        @endif
      </div>
      <div class="header-text-wrap">
        <div class="header-brand">DKJ <span>Finance</span></div>
        <div class="header-subtitle">PT. Dunia Kimia Jaya · AR Collection</div>
        <div class="sender-badge">✉ Sent by: {{ $collectorName }}</div>
      </div>
    </div>
  </div>

  {{-- ── Body ── --}}
  <div class="body">

    {{-- Greeting --}}
    <div class="greeting">
      @if($invoice->pic_name)
        Dear <strong>{{ $invoice->pic_name }}</strong>,<br>
      @else
        Dear Finance / Accounting Team,<br>
      @endif
      <br>
      We from <strong>PT. Dunia Kimia Jaya</strong> would like to remind you of the following
      {{ count($invoices) > 1 ? count($invoices).' invoices that are' : 'invoice that is' }}
      due soon:
    </div>

    @if(count($invoices) > 1)
      {{-- ── MULTI-INVOICE: Summary banner ── --}}
      <div class="summary-banner">
        <div class="summary-cell">
          <div class="summary-label">Invoices</div>
          <div class="summary-value count">{{ count($invoices) }}</div>
        </div>
        <div class="summary-cell">
          <div class="summary-label">Total Billed</div>
          <div class="summary-value">Rp {{ number_format($totalAR, 0, ',', '.') }}</div>
        </div>
        <div class="summary-cell">
          <div class="summary-label">Already Paid</div>
          <div class="summary-value green">Rp {{ number_format($totalPaid, 0, ',', '.') }}</div>
        </div>
        <div class="summary-cell">
          <div class="summary-label">Outstanding</div>
          <div class="summary-value red">Rp {{ number_format($totalRemaining, 0, ',', '.') }}</div>
        </div>
      </div>

      <div class="section-title">Invoice Details</div>
      <table class="invoices-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Invoice ID</th>
            <th>Due Date</th>
            <th class="num">Total Billed (IDR)</th>
            <th class="num">Paid (IDR)</th>
            <th class="num">Outstanding (IDR)</th>
          </tr>
        </thead>
        <tbody>
          @foreach($invoices as $i => $inv)
          @php
            $daysLeft = now()->diffInDays(\Carbon\Carbon::parse($inv->due_date), false);
            $remaining = max(0, $inv->total_ar - $inv->ar_actual);
            $isOverdue = $daysLeft < 0;
          @endphp
          <tr style="{{ $isOverdue ? 'background:#fff5f5' : '' }}">
            <td style="color:#94a3b8;font-weight:600">{{ $i + 1 }}</td>
            <td style="font-weight:700">#{{ $inv->invoice_id }}</td>
            <td>
              <span style="font-size:11px;font-weight:700;
                background:{{ $isOverdue ? '#fee2e2' : '#dbeafe' }};
                color:{{ $isOverdue ? '#991b1b' : '#1e40af' }};
                padding:2px 8px;border-radius:99px;display:inline-block">
                {{ \Carbon\Carbon::parse($inv->due_date)->format('d M Y') }}
                @if($isOverdue)
                  <span style="font-size:9px">({{ abs((int)$daysLeft) }}d overdue)</span>
                @else
                  <span style="font-size:9px">({{ (int)$daysLeft }}d left)</span>
                @endif
              </span>
            </td>
            <td class="num">{{ number_format($inv->total_ar, 0, ',', '.') }}</td>
            <td class="num" style="color:#16a34a">
              {{ $inv->ar_actual > 0 ? number_format($inv->ar_actual, 0, ',', '.') : '—' }}
            </td>
            <td class="num" style="color:{{ $remaining > 0 ? '#dc2626' : '#16a34a' }};font-weight:700">
              {{ $remaining > 0 ? number_format($remaining, 0, ',', '.') : '✓ Settled' }}
            </td>
          </tr>
          @endforeach
        </tbody>
        <tfoot>
          <tr>
            <td colspan="3" style="font-weight:800;color:#0f2942">Total</td>
            <td class="num">Rp {{ number_format($totalAR, 0, ',', '.') }}</td>
            <td class="num" style="color:#16a34a">Rp {{ number_format($totalPaid, 0, ',', '.') }}</td>
            <td class="num" style="color:{{ $totalRemaining > 0 ? '#dc2626' : '#16a34a' }}">
              {{ $totalRemaining > 0 ? 'Rp '.number_format($totalRemaining, 0, ',', '.') : '✓ All Settled' }}
            </td>
          </tr>
        </tfoot>
      </table>

      {{-- Multi-invoice alert --}}
      @php $overdueCount = collect($invoices)->filter(fn($i) => now()->gt(\Carbon\Carbon::parse($i->due_date)))->count(); @endphp
      @if($overdueCount > 0)
      <div class="alert-box" style="background:#fee2e2;border-left-color:#dc2626;color:#7f1d1d;margin-top:20px">
        ⚠️ <strong>{{ $overdueCount }} of {{ count($invoices) }} invoices have already passed their due date.</strong>
        Please process payment immediately to avoid late penalties.
      </div>
      @else
      <div class="alert-box" style="background:#f0fdf4;border-left-color:#16a34a;color:#14532d;margin-top:20px">
        📅 Please ensure all outstanding amounts are settled before each invoice's due date.
      </div>
      @endif

    @else
      {{-- ── SINGLE INVOICE: original card layout ── --}}
      @php
        $daysLeft = now()->diffInDays(\Carbon\Carbon::parse($invoice->due_date), false);
        $sisa     = max(0, $invoice->total_ar - $invoice->ar_actual);
      @endphp
      <div class="invoice-card">
        <div class="invoice-card-title">Invoice Details</div>
        <table class="invoice-table">
          <tr>
            <td class="td-label">Company Name</td>
            <td class="td-value">{{ $invoice->customer_name }}</td>
          </tr>
          <tr>
            <td class="td-label">Invoice Number</td>
            <td class="td-value">#{{ $invoice->invoice_id }}</td>
          </tr>
          <tr>
            <td class="td-label">Due Date</td>
            <td class="td-value">
              <span class="due-badge {{ $daysLeft >= 0 ? 'ok' : '' }}">
                {{ \Carbon\Carbon::parse($invoice->due_date)->format('d F Y') }}
              </span>
            </td>
          </tr>
          <tr>
            <td class="td-label">Total Billed</td>
            <td class="td-value">
              <span class="amount-value">Rp {{ number_format($invoice->total_ar, 0, ',', '.') }}</span>
            </td>
          </tr>
          <tr>
            <td class="td-label">Already Paid</td>
            <td class="td-value">
              @if($invoice->ar_actual > 0)
                <span class="paid-value">Rp {{ number_format($invoice->ar_actual, 0, ',', '.') }}</span>
              @else
                <span style="color:#94a3b8">—</span>
              @endif
            </td>
          </tr>
          <tr>
            <td class="td-label">Outstanding Balance</td>
            <td class="td-value">
              @if($sisa > 0)
                <span class="remain-value">Rp {{ number_format($sisa, 0, ',', '.') }}</span>
              @else
                <span style="color:#16a34a;font-weight:700">✓ Settled</span>
              @endif
            </td>
          </tr>
        </table>
      </div>

      @if($daysLeft < 0)
      <div class="alert-box" style="background:#fee2e2;border-left-color:#dc2626;color:#7f1d1d">
        ⚠️ <strong>This invoice has passed its due date</strong> ({{ abs((int)$daysLeft) }} days ago).
        Please make payment immediately to avoid late penalties.
      </div>
      @elseif($daysLeft <= 3)
      <div class="alert-box" style="background:#fff1f2;border-left-color:#e11d48;color:#881337">
        🔴 <strong>Urgent!</strong> This invoice is due in <strong>{{ (int)$daysLeft }} day(s)</strong>.
        Please process payment right away.
      </div>
      @elseif($daysLeft <= 7)
      <div class="alert-box">
        🟠 This invoice is due in <strong>{{ (int)$daysLeft }} days</strong>.
        Please arrange payment soon.
      </div>
      @else
      <div class="alert-box" style="background:#f0fdf4;border-left-color:#16a34a;color:#14532d">
        📅 This invoice is due on <strong>{{ \Carbon\Carbon::parse($invoice->due_date)->format('d F Y') }}</strong>.
        Please ensure payment is made on time.
      </div>
      @endif
    @endif

    <p style="font-size:13px;color:#475569;line-height:1.7;margin-top:16px">
      If payment has already been made, please disregard this email and confirm with our team.
      For further information, please contact your collector directly.
    </p>

    {{-- ── Collector Signature ── --}}
    <div class="collector-sig">
      <div class="collector-name">{{ $collectorName }}</div>
      <div class="collector-role">AR Collector · PT. Dunia Kimia Jaya</div>
      @php
        $collectorEmailMap = [
            'miya'  => 'testing_miya@gmail.com',
            'mega'  => 'testing_mega@gmail.com',
            'risa'  => 'testing_risa@gmail.com',
            'viona' => 'testing_viona@gmail.com',
        ];
        $collectorKey  = strtolower(trim($collectorName));
        $displayEmail  = $collectorEmailMap[$collectorKey] ?? null;
        if (!$displayEmail) {
            foreach ($collectorEmailMap as $name => $email) {
                if (str_contains($collectorKey, $name)) { $displayEmail = $email; break; }
            }
        }
      @endphp
      @if($displayEmail)
        <div class="collector-email">{{ $displayEmail }}</div>
      @endif
    </div>
  </div>

  <div class="footer">
    <p class="footer-text">
      This email was sent automatically from the AR Dashboard system of PT. Dunia Kimia Jaya.<br>
      If you have any questions, please reply to this email or contact our team directly.<br>
      © {{ date('Y') }} PT. Dunia Kimia Jaya · Accounts Receivable Management
    </p>
  </div>

</div>
</body>
</html>