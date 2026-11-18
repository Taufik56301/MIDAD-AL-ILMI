@extends('layouts.app')

@section('title', 'Detail Pesanan '.$order->order_number)
@section('header', 'Pesanan '.$order->order_number)
@section('subheader', 'Pelanggan: '.($order->customer->name ?? '-').' • Diinput oleh: '.($order->user->name ?? '-'))

@section('header-actions')
<div class="flex flex-wrap items-center gap-2">
    @if(auth()->user()->hasRole('admin', 'owner'))
        <a href="{{ route('orders.invoice', $order) }}" target="_blank" class="px-3.5 py-2 rounded-lg bg-slate-900 text-white text-xs font-semibold hover:bg-slate-800">
            Cetak Nota PDF
        </a>
        @if($order->customer)
            @php
                $waMessage = "Assalamu'alaikum {$order->customer->name}, berikut informasi pesanan {$order->order_number} di Percetakan Midad Al-Ilmi.\nTotal: Rp ".number_format($order->total_amount, 0, ',', '.')."\nStatus Pembayaran: {$order->paymentStatusLabel()}\nStatus Produksi: {$order->orderStatusLabel()}\nSisa Tagihan: Rp ".number_format($order->remaining_balance, 0, ',', '.');
            @endphp
            <a href="{{ $order->customer->whatsappUrl($waMessage) }}" target="_blank" class="px-3.5 py-2 rounded-lg bg-emerald-600 text-white text-xs font-semibold hover:bg-emerald-700">
                Kirim Info WA
            </a>
        @endif
    @endif
</div>
@endsection

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
    {{-- Kolom Kiri: Spesifikasi & Progres --}}
    <div class="lg:col-span-2 space-y-5">
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
            <div class="flex flex-wrap items-center justify-between gap-3 pb-4 border-b border-slate-200">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="px-3 py-1 rounded-full text-xs font-bold
                        {{ $order->payment_status === 'paid' ? 'bg-emerald-100 text-emerald-700' : ($order->payment_status === 'dp' ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-700') }}">
                        Bayar: {{ $order->paymentStatusLabel() }}
                    </span>
                    <span class="px-3 py-1 rounded-full text-xs font-bold
                        {{ $order->order_status === 'diproduksi' ? 'bg-indigo-100 text-indigo-700' : ($order->order_status === 'siap_diambil' ? 'bg-emerald-100 text-emerald-700' : ($order->order_status === 'closed' ? 'bg-slate-200 text-slate-700' : 'bg-sky-100 text-sky-700')) }}">
                        Produksi: {{ $order->orderStatusLabel() }}
                    </span>
                    @if($order->stock_deducted_at)
                        <span class="px-3 py-1 rounded-full bg-teal-50 text-teal-700 border border-teal-200 text-xs font-semibold">
                            Stok Bahan Terpotong ({{ $order->stock_deducted_at->format('d/m H:i') }})
                        </span>
                    @endif
                </div>

                @if($order->file_design_link || $order->design_file_path)
                    <a href="{{ route('orders.design', $order) }}" target="_blank" class="px-3.5 py-1.5 rounded-lg bg-indigo-50 text-indigo-700 border border-indigo-200 text-xs font-semibold hover:bg-indigo-100">
                        Buka / Unduh File Desain
                    </a>
                @endif
            </div>

            <h3 class="text-sm font-bold text-slate-900 mt-4 mb-2">Rincian Item Cetak</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold text-slate-500 uppercase">
                        <tr>
                            <th class="px-3 py-2">Produk</th>
                            <th class="px-3 py-2">Bahan Baku</th>
                            <th class="px-3 py-2">Ukuran & Finishing</th>
                            <th class="px-3 py-2 text-right">Qty</th>
                            @if(auth()->user()->hasRole('admin', 'owner'))
                                <th class="px-3 py-2 text-right">Subtotal</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($order->details as $detail)
                            <tr>
                                <td class="px-3 py-2.5 font-semibold text-slate-900">{{ $detail->product_name }}</td>
                                <td class="px-3 py-2.5 text-slate-600">{{ $detail->material->name ?? '-' }}</td>
                                <td class="px-3 py-2.5 text-xs text-slate-600">
                                    <div>Ukuran: {{ $detail->size ?: '-' }}</div>
                                    <div>Finishing: {{ $detail->finishing_notes ?: '-' }}</div>
                                </td>
                                <td class="px-3 py-2.5 text-right font-semibold">{{ number_format($detail->qty, 0, ',', '.') }} {{ $detail->material->unit ?? '' }}</td>
                                @if(auth()->user()->hasRole('admin', 'owner'))
                                    <td class="px-3 py-2.5 text-right font-semibold">Rp {{ number_format($detail->sub_total, 0, ',', '.') }}</td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($order->notes)
                <div class="mt-4 rounded-lg bg-slate-50 border border-slate-200 p-3 text-xs text-slate-700">
                    <strong>Catatan Pesanan:</strong> {{ $order->notes }}
                </div>
            @endif
        </div>

        {{-- Kontrol Perubahan Status Produksi --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
            <h3 class="text-sm font-bold text-slate-900 mb-3">Tahapan Status Operasional</h3>
            <div class="flex flex-wrap gap-2.5">
                @if($order->order_status === 'antrean')
                    <form action="{{ route('orders.status', $order) }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="order_status" value="diproduksi">
                        <button type="submit" @disabled(!$order->isProductionEligible())
                                class="px-4 py-2 rounded-lg text-sm font-semibold text-white {{ $order->isProductionEligible() ? 'bg-indigo-600 hover:bg-indigo-700' : 'bg-slate-300 cursor-not-allowed' }}">
                            Mulai Produksi (Potong Stok Otomatis)
                        </button>
                    </form>
                @endif

                @if($order->order_status === 'diproduksi')
                    <form action="{{ route('orders.status', $order) }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="order_status" value="siap_diambil">
                        <button type="submit" class="px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700">
                            Tandai Selesai / Siap Diambil
                        </button>
                    </form>
                @endif

                @if(auth()->user()->hasRole('admin', 'owner') && $order->order_status !== 'closed')
                    <form action="{{ route('orders.status', $order) }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="order_status" value="closed">
                        <button type="submit" class="px-4 py-2 rounded-lg bg-slate-900 text-white text-sm font-semibold hover:bg-slate-800">
                            Sudah Diambil Pelanggan (Closed)
                        </button>
                    </form>
                @endif
            </div>
            @if(!$order->isProductionEligible())
                <p class="mt-2 text-xs text-amber-700">Catatan: Pesanan harus menerima minimal pembayaran DP sebelum dapat diproses di mesin cetak.</p>
            @endif
        </div>
    </div>

    {{-- Kolom Kanan: Ringkasan Keuangan & Kasir Split Payment --}}
    @if(auth()->user()->hasRole('admin', 'owner'))
        <div class="space-y-5">
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 space-y-3">
                <h3 class="text-sm font-bold text-slate-900">Ringkasan Tagihan</h3>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-slate-500">Total Tagihan</span>
                        <span class="font-bold text-slate-900">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Sudah Dibayar</span>
                        <span class="font-semibold text-emerald-700">Rp {{ number_format($order->total_paid, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between border-t border-slate-200 pt-2">
                        <span class="font-semibold text-slate-700">Sisa Tagihan</span>
                        <span class="font-bold {{ $order->remaining_balance > 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                            Rp {{ number_format($order->remaining_balance, 0, ',', '.') }}
                        </span>
                    </div>
                </div>

                @if($order->remaining_balance > 0)
                    <form action="{{ route('payments.store', $order) }}" method="POST" class="border-t border-slate-200 pt-4 space-y-3">
                        @csrf
                        <div class="text-xs font-bold uppercase text-slate-500">Input Pembayaran (DP / Pelunasan)</div>
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">Nominal Bayar (Rp)</label>
                            <input type="number" step="100" min="1" max="{{ $order->remaining_balance }}" name="amount_paid"
                                   value="{{ old('amount_paid', $order->remaining_balance) }}" required
                                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">Metode Pembayaran</label>
                            <select name="payment_method" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                <option value="tunai">Tunai</option>
                                <option value="transfer">Transfer Bank / QRIS</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">Catatan (Opsional)</label>
                            <input type="text" name="notes" placeholder="Mis. DP 50% / Pelunasan" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        </div>
                        <button type="submit" class="w-full py-2.5 rounded-lg bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700">
                            Catat Pembayaran
                        </button>
                    </form>
                @endif
            </div>

            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
                <h3 class="text-sm font-bold text-slate-900 mb-3">Riwayat Pembayaran</h3>
                <div class="space-y-2.5 text-xs">
                    @forelse($order->payments as $pay)
                        <div class="rounded-lg bg-slate-50 border border-slate-200 p-3 flex items-center justify-between">
                            <div>
                                <div class="font-bold text-slate-800">{{ strtoupper($pay->payment_type) }} ({{ ucfirst($pay->payment_method) }})</div>
                                <div class="text-slate-500">{{ $pay->date_paid->format('d/m/Y H:i') }}</div>
                                @if($pay->notes)
                                    <div class="text-slate-600 mt-0.5">{{ $pay->notes }}</div>
                                @endif
                            </div>
                            <div class="font-bold text-emerald-700">Rp {{ number_format($pay->amount_paid, 0, ',', '.') }}</div>
                        </div>
                    @empty
                        <div class="text-slate-400">Belum ada pembayaran masuk.</div>
                    @endforelse
                </div>
            </div>
        </div>
    @endif
</div>
@endsection

