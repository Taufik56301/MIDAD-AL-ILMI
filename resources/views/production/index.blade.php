@extends('layouts.app')

@section('title', 'Antrean Produksi - Midad Al-Ilmi')
@section('header', 'Antrean & Operasional Produksi')
@section('subheader', 'Tahap 4: Menampilkan pesanan berstatus DP/Lunas. Perubahan ke Sedang Diproduksi otomatis memotong stok bahan baku.')

@section('content')
<div class="flex flex-wrap items-center gap-2">
    <a href="{{ route('production.index', ['status' => 'active']) }}" class="px-3.5 py-1.5 rounded-lg text-sm font-medium {{ $statusFilter === 'active' ? 'bg-slate-900 text-white' : 'bg-white border border-slate-300 text-slate-700' }}">
        Antrean Aktif ({{ $counts['antrean'] + $counts['diproduksi'] + $counts['siap_diambil'] }})
    </a>
    <a href="{{ route('production.index', ['status' => 'antrean']) }}" class="px-3.5 py-1.5 rounded-lg text-sm font-medium {{ $statusFilter === 'antrean' ? 'bg-sky-600 text-white' : 'bg-white border border-slate-300 text-slate-700' }}">
        Menunggu Cetak ({{ $counts['antrean'] }})
    </a>
    <a href="{{ route('production.index', ['status' => 'diproduksi']) }}" class="px-3.5 py-1.5 rounded-lg text-sm font-medium {{ $statusFilter === 'diproduksi' ? 'bg-indigo-600 text-white' : 'bg-white border border-slate-300 text-slate-700' }}">
        Sedang Diproduksi ({{ $counts['diproduksi'] }})
    </a>
    <a href="{{ route('production.index', ['status' => 'siap_diambil']) }}" class="px-3.5 py-1.5 rounded-lg text-sm font-medium {{ $statusFilter === 'siap_diambil' ? 'bg-emerald-600 text-white' : 'bg-white border border-slate-300 text-slate-700' }}">
        Selesai / Siap Diambil ({{ $counts['siap_diambil'] }})
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
    @forelse($orders as $order)
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 flex flex-col justify-between space-y-4">
            <div class="space-y-3">
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <div>
                        <div class="text-base font-bold text-slate-900">{{ $order->order_number }}</div>
                        <div class="text-xs text-slate-500">Pelanggan: {{ $order->customer->name ?? '-' }} • {{ $order->created_at->format('d/m/Y H:i') }}</div>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $order->payment_status === 'paid' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-800' }}">
                            {{ $order->paymentStatusLabel() }}
                        </span>
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold
                            {{ $order->order_status === 'diproduksi' ? 'bg-indigo-100 text-indigo-700' : ($order->order_status === 'siap_diambil' ? 'bg-emerald-100 text-emerald-700' : 'bg-sky-100 text-sky-700') }}">
                            {{ $order->orderStatusLabel() }}
                        </span>
                    </div>
                </div>

                <div class="divide-y divide-slate-100 rounded-lg border border-slate-200 bg-slate-50/70">
                    @foreach($order->details as $d)
                        <div class="p-3 text-xs space-y-1">
                            <div class="flex items-center justify-between font-bold text-slate-900 text-sm">
                                <span>{{ $d->product_name }}</span>
                                <span class="text-indigo-700">{{ number_format($d->qty, 0, ',', '.') }} {{ $d->material->unit ?? '' }}</span>
                            </div>
                            <div class="text-slate-600">Bahan: <strong>{{ $d->material->name ?? '-' }}</strong> | Ukuran: {{ $d->size ?: '-' }}</div>
                            @if($d->finishing_notes)
                                <div class="text-amber-800 font-medium">Finishing: {{ $d->finishing_notes }}</div>
                            @endif
                        </div>
                    @endforeach
                </div>

                @if($order->notes)
                    <div class="text-xs text-slate-600 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
                        <strong>Catatan:</strong> {{ $order->notes }}
                    </div>
                @endif
            </div>

            <div class="flex flex-wrap items-center justify-between gap-2 border-t border-slate-200 pt-3">
                <div class="flex items-center gap-2">
                    @if($order->file_design_link || $order->design_file_path)
                        <a href="{{ route('orders.design', $order) }}" target="_blank" class="px-3 py-1.5 rounded-lg bg-indigo-50 text-indigo-700 border border-indigo-200 text-xs font-semibold hover:bg-indigo-100">
                            Unduh / Buka Desain
                        </a>
                    @else
                        <span class="text-xs text-slate-400">Tanpa file desain</span>
                    @endif
                    <a href="{{ route('orders.show', $order) }}" class="text-xs font-medium text-slate-600 hover:underline">Detail</a>
                </div>

                <div class="flex items-center gap-2">
                    @if($order->order_status === 'antrean')
                        <form action="{{ route('orders.status', $order) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="order_status" value="diproduksi">
                            <button type="submit" class="px-3.5 py-1.5 rounded-lg bg-indigo-600 text-white text-xs font-semibold hover:bg-indigo-700">
                                Mulai Produksi
                            </button>
                        </form>
                    @elseif($order->order_status === 'diproduksi')
                        <form action="{{ route('orders.status', $order) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="order_status" value="siap_diambil">
                            <button type="submit" class="px-3.5 py-1.5 rounded-lg bg-emerald-600 text-white text-xs font-semibold hover:bg-emerald-700">
                                Selesai / Siap Diambil
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 p-10 text-center text-slate-400">
            Tidak ada antrean produksi pada filter ini.
        </div>
    @endforelse
</div>

<div>{{ $orders->links() }}</div>
@endsection

