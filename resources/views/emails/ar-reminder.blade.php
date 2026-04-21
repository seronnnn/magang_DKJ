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
  .header  { background: #0f2942; padding: 32px 36px; }
  .header-logo { font-size: 22px; font-weight: 800; color: #fff; letter-spacing: -.5px; }
  .header-logo span { color: #38bdf8; }
  .header-subtitle { font-size: 12px; color: #94afc8; margin-top: 4px; text-transform: uppercase; letter-spacing: .1em; }
  .body { padding: 36px; }
  .greeting { font-size: 15px; color: #334155; margin-bottom: 18px; line-height: 1.6; }
  .invoice-card {
    background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px;
    padding: 24px; margin: 24px 0;
  }
  .invoice-card-title { font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: .1em; margin-bottom: 14px; }
  .invoice-row { display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid #e2e8f0; }
  .invoice-row:last-child { border-bottom: none; }
  .invoice-row .label { font-size: 12px; color: #64748b; }
  .invoice-row .value { font-size: 13px; font-weight: 600; color: #0f172a; text-align: right; }
  .due-badge {
    display: inline-block; background: #fee2e2; color: #991b1b;
    border-radius: 99px; padding: 4px 14px; font-size: 12px; font-weight: 700;
  }
  .amount { font-size: 22px; font-weight: 800; color: #0f2942; font-family: monospace; }
  .alert-box {
    background: #fff7ed; border: 1px solid #fed7aa; border-left: 4px solid #f97316;
    border-radius: 8px; padding: 14px 18px; margin: 20px 0;
    font-size: 13px; color: #7c2d12; line-height: 1.6;
  }
  .cta { margin: 28px 0 0; text-align: center; }
  .footer { background: #f8fafc; border-top: 1px solid #e2e8f0; padding: 20px 36px; }
  .footer-text { font-size: 11px; color: #94a3b8; line-height: 1.7; }
  .collector-sig { margin-top: 24px; padding-top: 18px; border-top: 1px solid #e2e8f0; }
  .collector-name { font-size: 14px; font-weight: 700; color: #0f2942; }
  .collector-role { font-size: 11px; color: #94a3b8; margin-top: 2px; }
</style>
</head>
<body>
<div class="wrapper">

  <div class="header">
    <div class="header-logo">DKJ <span>Finance</span></div>
    <div class="header-subtitle">PT. Dunia Kimia Jaya · AR Collection</div>
  </div>

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

    <div class="invoice-card">
      <div class="invoice-card-title">Detail Invoice</div>

      <div class="invoice-row">
        <span class="label">Nama Perusahaan</span>
        <span class="value">{{ $invoice->customer_name }}</span>
      </div>
      <div class="invoice-row">
        <span class="label">Nomor Invoice</span>
        <span class="value">#{{ $invoice->invoice_id }}</span>
      </div>
      <div class="invoice-row">
        <span class="label">Tanggal Jatuh Tempo</span>
        <span class="value">
          <span class="due-badge">
            {{ \Carbon\Carbon::parse($invoice->due_date)->locale('id')->isoFormat('D MMMM YYYY') }}
          </span>
        </span>
      </div>
      <div class="invoice-row">
        <span class="label">Total Tagihan</span>
        <span class="value">
          <div class="amount">Rp {{ number_format($invoice->total_ar, 0, ',', '.') }}</div>
        </span>
      </div>
      @if($invoice->ar_actual > 0)
      <div class="invoice-row">
        <span class="label">Sudah Dibayar</span>
        <span class="value" style="color:#16a34a">Rp {{ number_format($invoice->ar_actual, 0, ',', '.') }}</span>
      </div>
      <div class="invoice-row">
        <span class="label">Sisa Tagihan</span>
        <span class="value" style="color:#dc2626;font-size:16px">
          Rp {{ number_format(max(0, $invoice->total_ar - $invoice->ar_actual), 0, ',', '.') }}
        </span>
      </div>
      @endif
    </div>

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