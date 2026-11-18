@extends('layouts.app')

@section('title', 'Kasir & Pembayaran - Midad Al-Ilmi')
@section('header', 'Kasir & Manajemen Pembayaran')
@section('subheader', 'Tahap 3 & Tahap 5: Pencatatan DP, pelunasan, dan penerbitan Nota Digital PDF')

@section('content')
<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-200">
        <h2 class="text-base font-bold text-slate-900">Daftar Tagihan Belum Lunas (Unpaid & DP)</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-semibold text-slate-500 uppercase">
                <tr>
                    <th class="px-5 py-3">No. Order</th>
                    <th class="px-5 py-3">Pelanggan</th>
                    <th class="px-5 py-3 text-right">Total Tagihan</th>
                    <th class="px-5 py-3 text-right">Terbayar</th>
                    <th class="px-5 py-3 text-right">Sisa Tagihan</th>
                    <th class="px-5 py-3">Status</th>
                    <th class="px-5 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($unpaidOrders as $order)
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-3 font-semibold text-slate-900">{{ $order->order_number }}</td>
                        <td class="px-5 py-3">{{ $order->customer->name ?? '-' }}</td>
                        <td class="px-5 py-3 text-right">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</td>
                        <td class="px-5 py-3 text-right text-emerald-700">Rp {{ number_format($order->total_paid, 0, ',', '.') }}</td>
                        <td class="px-5 py-3 text-right font-bold text-rose-600">Rp {{ number_format($order->remaining_balance, 0, ',', '.') }}</td>
                        <td class="px-5 py-3">
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $order->payment_status === 'dp' ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-700' }}">
                                {{ $order->paymentStatusLabel() }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-right space-x-2">
                            <a href="{{ route('orders.show', $order) }}" class="px-3 py-1 rounded-lg bg-indigo-600 text-white text-xs font-semibold hover:bg-indigo-700">Bayar / Detail</a>
                            <a href="{{ route('orders.invoice', $order) }}" target="_blank" class="text-xs font-semibold text-slate-600 hover:underline">Nota PDF</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-5 py-6 text-center text-slate-400">Semua pesanan sudah lunas.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-200">
        <h2 class="text-base font-bold text-slate-900">Riwayat Transaksi Kas Masuk</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-semibold text-slate-500 uppercase">
                <tr>
                    <th class="px-5 py-3">Waktu Bayar</th>
                    <th class="px-5 py-3">No. Order</th>
                    <th class="px-5 py-3">Pelanggan</th>
                    <th class="px-5 py-3">Jenis</th>
                    <th class="px-5 py-3">Metode</th>
                    <th class="px-5 py-3 text-right">Nominal</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($payments as $pay)
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-3 text-slate-600">{{ $pay->date_paid->format('d/m/Y H:i') }}</td>
                        <td class="px-5 py-3 font-semibold">
                            <a href="{{ route('orders.show', $pay->order) }}" class="text-indigo-600 hover:underline">{{ $pay->order->order_number ?? '-' }}</a>
                        </td>
                        <td class="px-5 py-3">{{ $pay->order->customer->name ?? '-' }}</td>
                        <td class="px-5 py-3">
                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $pay->payment_type === 'lunas' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-800' }}">
                                {{ strtoupper($pay->payment_type) }}
                            </span>
                        </td>
                        <td class="px-5 py-3 uppercase text-xs font-medium text-slate-600">{{ $pay->payment_method }}</td>
                        <td class="px-5 py-3 text-right font-bold text-slate-900">Rp {{ number_format($pay->amount_paid, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-5 py-6 text-center text-slate-400">Belum ada pembayaran tercatat.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-5 py-3 border-t border-slate-200">{{ $payments->links() }}</div>
</div>
@endsection

