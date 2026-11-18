<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Nota Digital {{ $order->order_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1e293b; line-height: 1.45; }
        .header { border-bottom: 2px solid #0f172a; padding-bottom: 10px; margin-bottom: 16px; }
        .brand { font-size: 18px; font-weight: bold; color: #0f172a; }
        .sub { font-size: 11px; color: #475569; }
        .meta-table { width: 100%; margin-bottom: 16px; }
        .meta-table td { vertical-align: top; padding: 3px 0; }
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        .items-table th { background: #f1f5f9; border: 1px solid #cbd5e1; padding: 7px 8px; text-align: left; font-size: 11px; }
        .items-table td { border: 1px solid #cbd5e1; padding: 7px 8px; }
        .text-right { text-align: right; }
        .summary { width: 45%; margin-left: auto; border-collapse: collapse; }
        .summary td { padding: 5px 8px; border-bottom: 1px solid #e2e8f0; }
        .badge { display: inline-block; padding: 3px 8px; border-radius: 4px; font-size: 10px; font-weight: bold; background: #e2e8f0; }
        .footer { margin-top: 28px; font-size: 10px; color: #64748b; text-align: center; border-top: 1px dashed #cbd5e1; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="brand">PERCETAKAN MIDAD AL-ILMI</div>
        <div class="sub">Nota Digital / Invoice Resmi Pesanan Cetak</div>
    </div>

    <table class="meta-table">
        <tr>
            <td style="width: 55%;">
                <strong>Pelanggan:</strong> {{ $order->customer->name ?? '-' }}<br>
                <strong>No. WhatsApp:</strong> {{ $order->customer->phone_whatsapp ?? '-' }}<br>
                @if($order->customer?->address)
                    <strong>Alamat:</strong> {{ $order->customer->address }}
                @endif
            </td>
            <td style="width: 45%;" class="text-right">
                <strong>No. Order:</strong> {{ $order->order_number }}<br>
                <strong>Tanggal:</strong> {{ $order->created_at->format('d/m/Y H:i') }} WIB<br>
                <strong>Status Bayar:</strong> <span class="badge">{{ strtoupper($order->paymentStatusLabel()) }}</span><br>
                <strong>Status Produksi:</strong> {{ $order->orderStatusLabel() }}
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th>No</th>
                <th>Produk & Spesifikasi</th>
                <th>Bahan</th>
                <th class="text-right">Qty</th>
                <th class="text-right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->details as $i => $d)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>
                        <strong>{{ $d->product_name }}</strong><br>
                        <small>Ukuran: {{ $d->size ?: '-' }} | Finishing: {{ $d->finishing_notes ?: '-' }}</small>
                    </td>
                    <td>{{ $d->material->name ?? '-' }}</td>
                    <td class="text-right">{{ number_format($d->qty, 0, ',', '.') }} {{ $d->material->unit ?? '' }}</td>
                    <td class="text-right">Rp {{ number_format($d->sub_total, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="summary">
        <tr>
            <td><strong>Total Tagihan</strong></td>
            <td class="text-right"><strong>Rp {{ number_format($order->total_amount, 0, ',', '.') }}</strong></td>
        </tr>
        <tr>
            <td>Sudah Dibayar</td>
            <td class="text-right">Rp {{ number_format($order->total_paid, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td><strong>Sisa Tagihan</strong></td>
            <td class="text-right"><strong>Rp {{ number_format($order->remaining_balance, 0, ',', '.') }}</strong></td>
        </tr>
    </table>

    @if($order->payments->isNotEmpty())
        <div style="margin-top: 18px;">
            <strong>Riwayat Pembayaran:</strong>
            <ul style="margin: 6px 0 0 18px; padding: 0; font-size: 11px;">
                @foreach($order->payments as $p)
                    <li>{{ $p->date_paid->format('d/m/Y H:i') }} — {{ strtoupper($p->payment_type) }} ({{ ucfirst($p->payment_method) }}): Rp {{ number_format($p->amount_paid, 0, ',', '.') }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="footer">
        Terima kasih telah mempercayakan kebutuhan cetak Anda kepada Percetakan Midad Al-Ilmi.<br>
        Admin Pencatat: {{ $order->user->name ?? '-' }}
    </div>
</body>
</html>

