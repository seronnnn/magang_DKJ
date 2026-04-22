<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Pengingat Jatuh Tempo Invoice</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { background: #f0f4f8; font-family: 'Segoe UI', Arial, sans-serif; color: #1e293b; }
  .wrapper { max-width: 600px; margin: 32px auto; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 24px rgba(15,41,107,.12); }

  /* ── Header ── */
  .header { background: #0f2942; padding: 24px 32px; }
  .header-inner { display: table; width: 100%; }
  .header-logo-wrap { display: table-cell; vertical-align: middle; width: 56px; }
  .header-logo-img { width: 48px; height: 48px; object-fit: contain; display: block; }
  .header-logo-placeholder {
    width: 48px; height: 48px; border-radius: 10px;
    background: rgba(255,255,255,.15);
    display: flex; align-items: center; justify-content: center;
    font-size: 20px; font-weight: 800; color: #fff; letter-spacing: -1px;
  }
  .header-text-wrap { display: table-cell; vertical-align: middle; padding-left: 14px; }
  .header-brand { font-size: 22px; font-weight: 800; color: #fff; letter-spacing: -.5px; line-height: 1.1; }
  .header-brand span { color: #38bdf8; }
  .header-subtitle { font-size: 11px; color: #94afc8; margin-top: 3px; text-transform: uppercase; letter-spacing: .1em; }

  /* ── Body ── */
  .body { padding: 32px 36px; }
  .greeting { font-size: 14px; color: #334155; margin-bottom: 22px; line-height: 1.7; }

  /* ── Invoice Table ── */
  .invoice-card {
    border: 1px solid #e2e8f0; border-radius: 10px;
    overflow: hidden; margin: 22px 0;
  }
  .invoice-card-title {
    background: #f8fafc; padding: 10px 16px;
    font-size: 10px; font-weight: 700; color: #94a3b8;
    text-transform: uppercase; letter-spacing: .1em;
    border-bottom: 1px solid #e2e8f0;
  }
  .invoice-table { width: 100%; border-collapse: collapse; }
  .invoice-table td {
    padding: 11px 16px;
    font-size: 13px;
    vertical-align: middle;
    border-bottom: 1px solid #f1f5f9;
  }
  .invoice-table tr:last-child td { border-bottom: none; }
  .invoice-table .td-label {
    width: 50%; color: #64748b; font-weight: 500;
    background: #fafbfc;
  }
  .invoice-table .td-value {
    width: 50%; color: #0f172a; font-weight: 600;
    text-align: right;
  }
  .due-badge {
    display: inline-block; background: #fee2e2; color: #991b1b;
    border-radius: 99px; padding: 3px 12px; font-size: 12px; font-weight: 700;
  }
  .amount-value { font-size: 20px; font-weight: 800; color: #0f2942; font-family: 'Courier New', monospace; }
  .paid-value   { color: #16a34a; font-weight: 700; }
  .remain-value { color: #dc2626; font-weight: 800; font-size: 16px; }

  /* ── Alert ── */
  .alert-box {
    border-left: 4px solid #f97316; border-radius: 8px;
    padding: 14px 18px; margin: 20px 0;
    font-size: 13px; line-height: 1.6;
    background: #fff7ed; color: #7c2d12;
  }

  /* ── Signature ── */
  .collector-sig { margin-top: 24px; padding-top: 18px; border-top: 1px solid #e2e8f0; }
  .collector-name { font-size: 14px; font-weight: 700; color: #0f2942; }
  .collector-role { font-size: 11px; color: #94a3b8; margin-top: 2px; }

  /* ── Footer ── */
  .footer { background: #f8fafc; border-top: 1px solid #e2e8f0; padding: 18px 36px; }
  .footer-text { font-size: 11px; color: #94a3b8; line-height: 1.7; }
</style>
</head>
<body>
<div class="wrapper">

  {{-- ── Header with Logo ── --}}
  <div class="header">
    <div class="header-inner">
      <div class="header-logo-wrap">
        {{-- Try to show the actual logo; fallback to text initials --}}
        @if(file_exists(public_path('images/logo_dkj.png')))
          <img src="{{ public_path('images/logo_dkj.png') }}" alt="DKJ Logo" class="header-logo-img">
        @elseif(file_exists(public_path('logo.png')))
          <img src="{{ public_path('logo.png') }}" alt="DKJ Logo" class="header-logo-img">
        @else
          <div class="header-logo-placeholder">DKJ</div>
        @endif
      </div>
      <div class="header-text-wrap">
        <div class="header-brand">DKJ <span>Finance</span></div>
        <div class="header-subtitle">PT. Dunia Kimia Jaya · AR Collection</div>
      </div>
    </div>
  </div>

  {{-- ── Body ── --}}
  <div class="body">
    <div class="greeting">
      @if($invoice->pic_name)
        Yth. <strong>{{ $invoice->pic_name }}</strong>,<br>
      @else
        Yth. Tim Finance / Accounting,<br>
      @endif
      <br>
      Dengan hormat, kami dari <strong>PT. Dunia Kimia Jaya</strong> ingin menyampaikan pengingat bahwa invoice berikut akan segera jatuh tempo:
    </div>

    {{-- ── Invoice Details Table (2 col × 6 row) ── --}}
    <div class="invoice-card">
      <div class="invoice-card-title">Detail Invoice</div>
      <table class="invoice-table">
        <tr>
          <td class="td-label">Nama Perusahaan</td>
          <td class="td-value">{{ $invoice->customer_name }}</td>
        </tr>
        <tr>
          <td class="td-label">Nomor Invoice</td>
          <td class="td-value">#{{ $invoice->invoice_id }}</td>
        </tr>
        <tr>
          <td class="td-label">Tanggal Jatuh Tempo</td>
          <td class="td-value">
            <span class="due-badge">
              {{ \Carbon\Carbon::parse($invoice->due_date)->locale('id')->isoFormat('D MMMM YYYY') }}
            </span>
          </td>
        </tr>
        <tr>
          <td class="td-label">Total Tagihan</td>
          <td class="td-value">
            <span class="amount-value">Rp {{ number_format($invoice->total_ar, 0, ',', '.') }}</span>
          </td>
        </tr>
        <tr>
          <td class="td-label">Sudah Dibayar</td>
          <td class="td-value">
            @if($invoice->ar_actual > 0)
              <span class="paid-value">Rp {{ number_format($invoice->ar_actual, 0, ',', '.') }}</span>
            @else
              <span style="color:#94a3b8">—</span>
            @endif
          </td>
        </tr>
        <tr>
          <td class="td-label">Sisa Tagihan</td>
          <td class="td-value">
            @php $sisa = max(0, $invoice->total_ar - $invoice->ar_actual); @endphp
            @if($sisa > 0)
              <span class="remain-value">Rp {{ number_format($sisa, 0, ',', '.') }}</span>
            @else
              <span style="color:#16a34a;font-weight:700">✓ Lunas</span>
            @endif
          </td>
        </tr>
      </table>
    </div>

    {{-- ── Alert box ── --}}
    @php
      $daysLeft = now()->diffInDays(\Carbon\Carbon::parse($invoice->due_date), false);
    @endphp

    @if($daysLeft < 0)
    <div class="alert-box" style="background:#fee2e2;border-left-color:#dc2626;color:#7f1d1d">
      ⚠️ <strong>Invoice ini telah melewati tanggal jatuh tempo</strong> ({{ abs((int)$daysLeft) }} hari yang lalu).
      Mohon segera melakukan pembayaran untuk menghindari penalti keterlambatan.
    </div>
    @elseif($daysLeft <= 3)
    <div class="alert-box" style="background:#fff1f2;border-left-color:#e11d48;color:#881337">
      🔴 <strong>Perhatian!</strong> Invoice akan jatuh tempo dalam <strong>{{ (int)$daysLeft }} hari</strong>.
      Harap segera memproses pembayaran.
    </div>
    @elseif($daysLeft <= 7)
    <div class="alert-box">
      🟠 Invoice akan jatuh tempo dalam <strong>{{ (int)$daysLeft }} hari</strong>.
      Mohon segera melakukan persiapan pembayaran.
    </div>
    @else
    <div class="alert-box" style="background:#f0fdf4;border-left-color:#16a34a;color:#14532d">
      📅 Invoice akan jatuh tempo pada tanggal <strong>{{ \Carbon\Carbon::parse($invoice->due_date)->locale('id')->isoFormat('D MMMM YYYY') }}</strong>.
      Harap pastikan pembayaran dilakukan tepat waktu.
    </div>
    @endif

    <p style="font-size:13px;color:#475569;line-height:1.7;margin-top:16px">
      Apabila pembayaran telah dilakukan, mohon abaikan email ini dan konfirmasikan kepada tim kami.
      Untuk informasi lebih lanjut, silakan hubungi collector kami.
    </p>

    <div class="collector-sig">
      <div class="collector-name">{{ $collectorName }}</div>
      <div class="collector-role">AR Collector · PT. Dunia Kimia Jaya</div>
    </div>
  </div>

  <div class="footer">
    <p class="footer-text">
      Email ini dikirim secara otomatis dari sistem AR Dashboard PT. Dunia Kimia Jaya.<br>
      Jika Anda memiliki pertanyaan, silakan balas email ini atau hubungi tim kami langsung.<br>
      © {{ date('Y') }} PT. Dunia Kimia Jaya · Sistem Manajemen Piutang
    </p>
  </div>

</div>
</body>
</html>