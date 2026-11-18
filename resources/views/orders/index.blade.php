@extends('layouts.app')

@section('title', 'Pesanan - Midad Al-Ilmi')
@section('header', 'Manajemen Pesanan (Order Entry)')
@section('subheader', 'Daftar pesanan pelanggan beserta status pembayaran dan progres produksi')

@section('header-actions')
<a href="{{ route('orders.create') }}" class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700">
    + Buat Pesanan Baru
</a>
@endsection

@section('content')
<div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4">
    <form method="GET" action="{{ route('orders.index') }}" class="flex flex-wrap items-center gap-2.5">
        <input type="text" name="q" value="{{ $search }}" placeholder="Cari No. Order / Pelanggan / WA..."
               class="flex-1 min-w-[200px] rounded-lg border border-slate-300 px-3.5 py-2 text-sm">
        <select name="payment_status" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
            <option value="">Semua Status Bayar</option>
            <option value="unpaid" @selected($paymentStatus === 'unpaid')>Belum Bayar</option>
            <option value="dp" @selected($paymentStatus === 'dp')>DP</option>
            <option value="paid" @selected($paymentStatus === 'paid')>Lunas</option>
        </select>
        <select name="order_status" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
            <option value="">Semua Status Produksi</option>
            <option value="antrean" @selected($orderStatus === 'antrean')>Antrean Produksi</option>
            <option value="diproduksi" @selected($orderStatus === 'diproduksi')>Sedang Diproduksi</option>
            <option value="siap_diambil" @selected($orderStatus === 'siap_diambil')>Siap Diambil</option>
            <option value="closed" @selected($orderStatus === 'closed')>Sudah Diambil (Closed)</option>
        </select>
        <button type="submit" class="px-4 py-2 rounded-lg bg-slate-900 text-white text-sm font-medium">Filter</button>
        @if($search || $paymentStatus || $orderStatus)
            <a href="{{ route('orders.index') }}" class="px-3 py-2 rounded-lg border border-slate-300 text-sm text-slate-600">Reset</a>
        @endif
    </form>
</div>

<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-semibold text-slate-500 uppercase">
                <tr>
                    <th class="px-5 py-3">No. Order</th>
                    <th class="px-5 py-3">Pelanggan</th>
                    <th class="px-5 py-3">Detail Cetak</th>
                    <th class="px-5 py-3 text-right">Total Tagihan</th>
                    <th class="px-5 py-3">Pembayaran</th>
                    <th class="px-5 py-3">Status Produksi</th>
                    <th class="px-5 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($orders as $order)
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-3">
                            <div class="font-semibold text-slate-900">{{ $order->order_number }}</div>
                            <div class="text-xs text-slate-400">{{ $order->created_at->format('d/m/Y H:i') }}</div>
                        </td>
                        <td class="px-5 py-3">
                            <div class="font-medium text-slate-900">{{ $order->customer->name ?? '-' }}</div>
                            <div class="text-xs text-slate-500">{{ $order->customer->phone_whatsapp ?? '' }}</div>
                        </td>
                        <td class="px-5 py-3 text-slate-600">
                            @foreach($order->details as $d)
                                <div class="text-xs">{{ $d->product_name }} ({{ number_format($d->qty, 0, ',', '.') }} {{ $d->material->unit ?? '' }})</div>
                            @endforeach
                        </td>
                        <td class="px-5 py-3 text-right">
                            <div class="font-bold text-slate-900">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</div>
                            @if($order->remaining_balance > 0)
                                <div class="text-xs text-rose-600">Sisa: Rp {{ number_format($order->remaining_balance, 0, ',', '.') }}</div>
                            @endif
                        </td>
                        <td class="px-5 py-3">
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold
                                {{ $order->payment_status === 'paid' ? 'bg-emerald-100 text-emerald-700' : ($order->payment_status === 'dp' ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-700') }}">
                                {{ $order->paymentStatusLabel() }}
                            </span>
                        </td>
                        <td class="px-5 py-3">
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold
                                {{ $order->order_status === 'diproduksi' ? 'bg-indigo-100 text-indigo-700' : ($order->order_status === 'siap_diambil' ? 'bg-emerald-100 text-emerald-700' : ($order->order_status === 'closed' ? 'bg-slate-200 text-slate-700' : 'bg-sky-100 text-sky-700')) }}">
                                {{ $order->orderStatusLabel() }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-right space-x-2 whitespace-nowrap">
                            <a href="{{ route('orders.show', $order) }}" class="text-indigo-600 hover:underline font-semibold">Kelola</a>
                            <a href="{{ route('orders.invoice', $order) }}" target="_blank" class="text-slate-600 hover:underline">Nota PDF</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-5 py-8 text-center text-slate-400">Belum ada pesanan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-5 py-3 border-t border-slate-200">{{ $orders->links() }}</div>
</div>
@endsection

